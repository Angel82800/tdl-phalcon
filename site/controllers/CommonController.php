<?php

namespace Thrust\Controllers;

use Phalcon\Http\Response;

use Thrust\Models\DashboardStatistics;
use Thrust\Models\EntUsers;

use Thrust\Stripe\Api as Stripe;

use Thrust\Helpers\AccountHelper;
use Thrust\Helpers\BillingHelper;
use Thrust\Helpers\DeviceHelper;

/**
 * Thrust\Controllers\CommonController.
 */
class CommonController extends ControllerBase
{
    protected $accountHelper;

    public function initialize()
    {
        $this->view->setTemplateBefore('private');

        $this->accountHelper = new AccountHelper();
        $this->deviceHelper = new DeviceHelper();
    }

    public function emailVerifyNoticeAction()
    {
        $message = $this->dispatcher->getParam('message');

        $this->view->setVar('message', $message);
    }

    /**
     * trigger user email verification
     */
    public function verifyUserEmailAction()
    {
        $this->view->disable();
        $content = [
            'message' => 'Oops! Something went wrong. Please try again later.',
        ];

        $response = new \Phalcon\Http\Response();
        $response->setStatusCode(400, 'Bad Request');

        if ($this->request->isPost() && $this->request->isAjax()) {
            $response->setStatusCode(200);

            $identity = $this->auth->getIdentity();
            $GUID = $identity['GUID'];

            $triggerVerifyResult = $this->accountHelper->triggerVerifyEmail($identity['GUID']);

            if ($triggerVerifyResult['result'] == 'success') {
                $content = [
                    'status'    => 'success',
                    'message'   => 'Verification email sent successfully.',
                ];
            } else {
                $content = [
                    'status'    => 'fail',
                    'message'   => 'An error occurred while processing your request.',
                ];
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

    /**
     * handle user email verification from magic link
     */
    public function confirmEmailVerificationAction()
    {
        $data = [];

        $code = $this->dispatcher->getParam('code');

        if ($code) {
            $user = EntUsers::findFirst([
                'conditions' => 'token_GUID = ?1 AND token_type = ?2 AND is_active = 1 AND is_deleted = 0',
                'bind'       => [
                    1 => $code,
                    2 => 'verify_email',
                ],
                'cache'      => false,
            ]);

            if ($user && $user->organization->is_active) {
                // check one hour validation & if the link was already used
                if ($user->email_verified) {
                    // user email is already verified
                    $data['status'] = 'duplicate';
                } else if (strtotime($user->token_time) < time() && strtotime($user->token_time) > (time() - 3600) && $user->token_type == 'verify_email') {
                    // all tests passed! - set email verified flag

                    $user->email_verified = 1;
                    $user->token_GUID = null;
                    $user->token_time = null;
                    $user->token_type = null;

                    if ($user->update()) {
                        $this->logger->info('[COMMON] Email verification successful for user ID ' . $user->pk_id);

                        $identity = $this->session->get('auth-identity');

                        if ($identity) {
                            // user is logged in - update session
                            $identity['email_verified'] = 1;
                            $this->session->set('auth-identity', $identity);
                            $data['status'] = 'verified';
                        } else {
                            // user is not logged in - redirect to login page with message
                            $this->flashSession->success('<h4>Your email has been successfully verified.</h4><p>Please log in to continue to your dashboard.</p>');
                            return $this->response->redirect('/session/login');
                        }
                    } else {
                        $this->logger->error('[COMMON] Error while verifying email for user ID ' . $user->pk_id . ' : ' . implode('<br />', $user->getMessages()));

                        throw new \Exception('Error while verifying user email');
                    }
                } else {
                    // 1 hour expired
                    $this->flashSession->error('Sorry, the link is not valid anymore. Please submit a new request.');
                    return $this->response->redirect('/dashboard');
                }
            } else {
                $this->logger->error('Email verification page redirecting to dashboard.' . "\n" . 'Reason: No user found with GUID ' . $code);
                return $this->response->redirect('/dashboard');
            }
        } else {
            return $this->response->redirect('/dashboard');
        }

        $this->view->setVars($data);
    }

    /**
     * send installation instructions to user - used for mobile redirections
     */
    public function sendInstructionsAction()
    {
        $identity = $this->auth->getIdentity();

        if ($this->deviceHelper->getPin($identity['GUID'], true)) {
            $this->logger->info('[COMMON] Device installation instructions sent to user ID ' . $identity['id']);

            $data = [
                'result'    => 'Instructions have been sent successfully.',
                'paragraph' => 'An email for a link to install Todyl Defender on your device has been sent.',
            ];
        } else {
            $this->logger->error('[COMMON] Failed to send device installation instructions to user ID ' . $identity['id']);

            $data = [
                'result'    => 'There was an error while sending instructions.',
                'paragraph' => 'Sorry for the inconvenience.',
            ];
        }

        $this->view->setLayout('');

        $this->view->setVars($data);
    }

    /**
     * send notification email for stripe failed payments webhook
     */
    public function stripeWebhookAction()
    {
        $response = new Response();

        if (! isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
            $response->setStatusCode(400);
            $response->send();
            exit;
        }

        // Retrieve the request's body and parse it as JSON
        $input = @file_get_contents('php://input');

        $this->logger->info('[COMMON] Incoming Stripe webhook : ' . print_r($input, true));

        // You can find your endpoint's secret in your webhook settings
        $endpoint_secret = $this->config->stripe->webhookSecret;

        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $input, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            $this->logger->info('[COMMON] Invalid Payload');

            $response->setStatusCode(400);
            $response->send();
            exit;
        } catch(\Stripe\Error\SignatureVerification $e) {
            // Invalid signature
            $this->logger->info('[COMMON] Invalid Signature: ' . $endpoint_secret);

            $response->setStatusCode(400);
            $response->send();
            exit;
        }

        $this->logger->info('[COMMON] Webhook verified : ' . print_r($event, true));

        // handle failed payments
        if (isset($event) && $event->type == 'invoice.payment_failed') {
            $user = EntUsers::query()
                ->columns([
                    'Thrust\Models\EntUsers.pk_id',
                    'Thrust\Models\EntUsers.email',
                    'Thrust\Models\EntUsers.firstName',
                ])
                ->leftJoin('Thrust\Models\EntOrganization', 'Thrust\Models\EntOrganization.pk_id = Thrust\Models\EntUsers.fk_ent_organization_id')
                ->where('Thrust\Models\EntOrganization.stripe_customer_id=\'' . $event->data->object->customer . '\'')
                ->execute()
                ->toArray()
            ;
            $user = $user[0];

            $this->logger->info('[COMMON] User for failed payment : ' . print_r($user, true));

            // Sending your customers the amount in pennies is weird, so convert to dollars
            $amount = sprintf('$%0.2f', $event->data->object->amount_due / 100.0);

            $emailData = [
                'template'          => 'email3',
                'headerText'        => 'Please Update Your Billing Information',
                'firstName'         => $user['firstName'],
                'amount_due'        => $amount,
            ];

            $this->logger->info('[COMMON] Failed Payments Email to ' . $user['email'] . ', data : ' . print_r($emailData, true));
            // $this->mail->sendMail($user['pk_id'], 'failed-payment', $emailData);
        }

        $response->setStatusCode(200);
        $response->send();
        exit;

    }

}
