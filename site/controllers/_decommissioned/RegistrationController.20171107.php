<?php

namespace Thrust\Controllers;

use Phalcon\Http\Response;

use Thrust\Models\AttrRoles;
use Thrust\Models\EntLeads;
use Thrust\Models\EntUsers;
use Thrust\Models\EntOrganization;

use Thrust\Auth\Exception as AuthException;
use Thrust\Stripe\Api as Stripe;

use Thrust\Helpers\AccountHelper;
use Thrust\Helpers\AgreementHelper;
use Thrust\Helpers\PricingHelper;
use Thrust\Helpers\SignupHelper;

/**
 * Controller used handle user signup.
 */
class RegistrationController extends ControllerBase
{
    public function initialize()
    {
        // $this->view->setTemplateBefore('session');
        $this->view->setTemplateBefore('public');

        if (is_array($this->auth->getIdentity())) {
            // user is logged in
            $this->response->redirect('/');
        }
    }

	/**
	 * user signup main handler
	 */
	public function indexAction()
	{
        $config = $this->getDi()->get('config');

        $stripe_pk = $config->stripe->publishKey;
        $google_pk = $config->recaptcha->publicKey;
        $google_invisible_pk = $config->recaptcha->invisible_publicKey;

        $helper = new SignupHelper();
        $accountHelper = new AccountHelper();

        $sidebar_content = $helper->getSidebarContent();
        $signupMessage = null;
        $signupErrors = [];

        $step = $this->dispatcher->getParam('step') ? $this->dispatcher->getParam('step') : 1;
        $helper->setStep($step);

        $signup_data = @$this->session->get('signup_data');

        $signup_data['passed_steps'] = isset($signup_data['passed_steps']) ? $signup_data['passed_steps'] : [ 0 ];
        if ($step > max($signup_data['passed_steps']) + 1) {
            // trying to skip steps?

            $response = new Response();
            $response->redirect('signup/' . (max($signup_data['passed_steps']) + 1));

            return $response;
        }

        if ($this->request->isPost()) {
            // validate post data
            $validation = $helper->getValidation();
            $messages = $validation->validate($this->request->getPost());

            if (! count($messages)) {
                if ($step < $helper->getStepcount()) {
                    // We're on our way to final submit

                    foreach ($this->request->getPost() as $key => $value) {
                        $signup_data[$key] = $value;
                    }

                    // store passed steps also
                    $signup_data['passed_steps'][] = $step;
                    $signup_data['passed_steps'] = array_unique($signup_data['passed_steps'], SORT_NUMERIC);

                    if ($step == 1) {
                        // create/update user and organization

                        $lead = $this->handleLead($signup_data);

                        if (isset($lead['signupMessage'])) {
                            $signupMessage = $lead['signupMessage'];
                        } else {
                            $signup_data['leadId'] = $lead['lead']->pk_id;

                            // pre-populate name on card
                            // if (! isset($signup_data['name_on_card'])) {
                            //     $signup_data['name_on_card'] = $signup_data['todyl_first_name'] . ' ' . $signup_data['todyl_last_name'];
                            // }
                        }

                    } else if ($step == 2) {
                        // customer packages selection

                        $lead = $this->handleLead($signup_data);

                        if (isset($lead['signupMessage'])) {
                            $signupMessage = $lead['signupMessage'];
                        }

                        // check if we're saving previous pre-pay option
                        if (! $this->request->getPost('prepay')) unset($signup_data['prepay']);
                    }

                    // if an error message is set, don't proceed
                    if (! $signupMessage) {
                        $this->session->set('signup_data', $signup_data);

                        // go to next step
                        $steps = array_keys($sidebar_content);
                        $next_step = $steps[array_search($step, $steps) + 1];

                        $response = new Response();
                        $response->redirect('signup/' . $next_step);

                        return $response;
                    }
                } else {
                    // Final submit!
                    $signup_data = @$this->session->get('signup_data');

                    foreach ($this->request->getPost() as $key => $value) {
                        $signup_data[$key] = $value;
                    }

                    $agreement = new AgreementHelper();
                    $stripe = new Stripe();

                    if ($this->request->getPost('card_token')) {
                        // new card token was submitted

                        $cardToken = $signup_data['card_token'];

                        $firstName = $signup_data['todyl_first_name'];
                        $lastName = $signup_data['todyl_last_name'];

                        $nameOnCard = $firstName . ' ' . $lastName;

                        // create new stripe customer
                        $customer = $stripe->createCustomer($cardToken, $signup_data['todyl_email'], $nameOnCard);

                        if (! $customer) {
                            $signupMessage = 'Error while creating customer: ' . $this->session->get('stripe_error');
                            $this->session->remove('stripe_error');
                        } else {
                            $signup_data['stripe_customer_id'] = $customer->id;
                            $signup_data['card_brand'] = $customer->sources->data[0]->brand;
                            $signup_data['card_last4'] = $customer->sources->data[0]->last4;
                            $signup_data['payment_info'] = $signup_data['card_brand'] . ' Ending in ' . $signup_data['card_last4'];
                        }
                    } else {
                        if (! isset($signup_data['payment_info'])) {
                            // no new token, no existing payment information
                            throw new \Exception('No card has been entered');
                        }
                    }

                    $accounts = $this->handleAccounts($signup_data);

                    if (isset($accounts['signupMessage'])) {
                        $signupMessage = $accounts['signupMessage'];
                    } else {
                        $signup_data['userId'] = $accounts['user']->pk_id;
                        $signup_data['orgId'] = $accounts['organization']->pk_id;
                    }

                    if (! $signupMessage) {
                        $organization = EntOrganization::findFirst([
                            'conditions' => 'pk_id = ?1',
                            'bind'       => [
                                1 => $signup_data['orgId'],
                            ],
                            'cache'      => false,
                        ]);

                        $organization->stripe_customer_id = $signup_data['stripe_customer_id'];

                        if ($organization->update() === false) {
                            $signupMessage = implode('<br />', $organization->getMessages());
                        } else {
                            // subscribe customer to plans

                            $pricingHelper = new PricingHelper($signup_data['num_devices']);

                            $pricingHelper->setPrepay(isset($signup_data['prepay']));
                            $pricingHelper->setSupportLevel($signup_data['package']);
                            $pricingHelper->setDiscount(0.5);

                            if (isset($signup_data['coupon'])) {
                                $pricingHelper->setCoupon($signup_data['coupon']);
                            }

                            // $tier_plan = $pricingHelper->getTierPlan(isset($signup_data['prepay']) ? 'annual' : 'monthly');
                            $device_plan = $pricingHelper->getPlan('device');
                            // $support_plan = $pricingHelper->getSupportPlan();

                            $plans = [
                                [
                                    'plan'      =>  $device_plan,
                                    'quantity'  =>  $signup_data['num_devices'],
                                ],
                            ];

                            $coupon = isset($signup_data['coupon']) ? $signup_data['coupon']->id : false;
                            // $coupon = 'INTRODUCTORY';

                            // subscribe customer with coupon and 15 trial days
                            $subscription = $stripe->subscribeCustomer($signup_data['stripe_customer_id'], $plans, $coupon, 15);

                            $this->logger->info('[REGISTRATION] Subscribed user ' . $signup_data['userId'] . ' to stripe plan.');

                            if ($subscription) {
                                // activate user, and update a few more values

                                $user = EntUsers::findFirst([
                                    'conditions' => 'pk_id = ?1',
                                    'bind'       => [
                                        1 => $signup_data['userId'],
                                    ],
                                    'cache'      => false,
                                ]);

                                $user->firstName = $signup_data['todyl_first_name'];
                                $user->lastName = $signup_data['todyl_last_name'];
                                $user->is_active = 1;

                                $organization = $user->getOrganization([ 'cache' => false ]);
                                $organization->is_active = 1;

                                if ($user->update() === false || $organization->update() === false) {
                                    $signupMessage = implode('<br />', array_merge($user->getMessages(), $organization->getMessages()));
                                } else {
                                    // remove signup session
                                    session_unset();
                                    session_regenerate_id(true);

                                    // values to be used in summary email
                                    $signup_data['total_charge'] = number_format($pricingHelper->getPricing('final'), 2);

                                    // send confirmation email
                                    $this->mail->sendMail($user->pk_id, 'confirmation', $signup_data);

                                    $accountHelper->triggerVerifyEmail($user->GUID);

                                    // login the user
                                    $this->auth->check(array(
                                        'email'    => $signup_data['todyl_email'],
                                        'password' => $signup_data['todyl_password'],
                                    ));

                                    // save current time - for 10 minute check
                                    $this->session->set('registration_time', time());

                                    // save agreement acceptance

                                    $agreement->addAgreement($user->pk_id, 'customer');

                                    // go to thankyou page
                                    $response = new Response();
                                    $response->redirect('/thankyou');

                                    // go to dashboard
                                    // $response = new Response();
                                    // $response->redirect('/dashboard');

                                    return $response;
                                }
                            } else {
                                $signupMessage = 'Error while subscribing customer: ' . $this->session->get('stripe_error');
                                $this->session->remove('stripe_error');
                            }
                        }
                    }

                    // we've come this far - means there was an error
                    $this->session->set('signup_data', $signup_data);
                }
            } else {
                foreach ($messages as $message) {
                    $signupErrors[$message->getField()][] = $message->getMessage();
                }
            }

        } else {
            $helper->setStep($step);
        }

        // set helper properties
        $helper->setSavedData($signup_data);
        $helper->setErrors($signupErrors);

        $this->view->setVars(
            array(
                'current_step'          => $step,
                'stripe_pk'             => $stripe_pk,
                'google_pk'             => $google_pk,
                'google_invisible_pk'   => $google_invisible_pk,
                'steps'                 => $sidebar_content,
                'helper'                => $helper,
                'saved_data'            => $signup_data,
                'signupMessage'         => $signupMessage,
                'signupErrors'          => $signupErrors,
            )
        );
    }

    // public function manageAction()
    // {
    //     $this->view->disable();
    //     $content = [
    //         'message' => 'Oops! Something went wrong. Please try again later.',
    //     ];

    //     $response = new \Phalcon\Http\Response();
    //     $response->setStatusCode(400, 'Bad Request');

    //     if ($this->request->isPost() && $this->request->isAjax()) {
    //         $response->setStatusCode(200);

    //         $type = $this->request->getPost('type');
    //         $manage_data = $this->request->getPost();
    //         if ($type) $content = [];

    //         $accountHelper = new AccountHelper();

    //         $identity = $this->auth->getIdentity();
    //         $user_id = $identity['id'];

    //         $user = EntUsers::findFirst([
    //             'conditions' => 'pk_id = ?1',
    //             'bind'       => [
    //                 1 => $user_id,
    //             ],
    //             'cache'      => false,
    //         ]);

    //         $organization = $user->getOrganization([ 'cache' => false ]);

    //         if ($type == 'validateEmail') {
    //             $email = $manage_data['email'];

    //             $email_exists = EntUsers::count([
    //                 'conditions' => 'email = ?1 AND ((is_invited = 1 AND is_deleted = 0) OR is_active = 1)',
    //                 'bind'       => [
    //                     1 => $email,
    //                 ],
    //                 'cache'      => false,
    //             ]);

    //             if ($email_exists) {
    //                 $content = [
    //                     'exists'    => 'yes',
    //                 ];
    //             } else {
    //                 $content = [
    //                     'exists'    => 'no',
    //                 ];
    //             }

    //             $content['status'] = 'success';

    //         } else {
    //             // no type found
    //             $content = [
    //                 'status'    => 'fail',
    //                 'message'   => 'Sorry, no action has been found.',
    //             ];
    //         }
    //     }

    //     $response->setContent(json_encode($content));
    //     $response->send();
    //     exit;
    // }

    protected function handleLead($signup_data)
    {
        $leadData = [
            'email'         => $signup_data['todyl_email'],
            'num_devices'   => isset($signup_data['num_devices']) ? $signup_data['num_devices'] : null,
            'created_by'    => 'thrust',
            'updated_by'    => 'thrust',
        ];

        if (isset($signup_data['leadId']) && $signup_data['leadId']) {
            // lead is already created - update it

            $lead = EntLeads::findFirst([
                'conditions' => 'pk_id = ?1',
                'bind'       => [
                    1 => $signup_data['leadId'],
                ],
                'cache'      => false,
            ]);

            if ($lead->update($leadData) === false) {
                $this->logger->error('[REGISTRATION] Error while updating lead : ' . implode(', ', $lead->getMessages()));

                return [
                    'signupMessage' => implode('<br />', $lead->getMessages()),
                ];
            }

            $this->logger->info('[REGISTRATION] Successfully updated lead ID ' . $lead->pk_id);
        } else {
            // create new lead

            $lead = new EntLeads();
            if ($lead->create($leadData) === false) {
                $this->logger->error('[REGISTRATION] Error while creating lead : ' . implode(', ', $lead->getMessages()));

                return [
                    'signupMessage' => implode('<br />', $lead->getMessages()),
                ];
            }

            $this->logger->info('[REGISTRATION] Successfully created lead ID ' . $lead->pk_id);
        }

        return [
            'lead'          => $lead,
        ];
    }

    /**
     * create/update organization and user according to the submitted data
     * @param  [array] $signup_data [submitted data]
     */
    protected function handleAccounts($signup_data)
    {
        $helper = new AccountHelper();

        //-- organization --

        $orgData = [
            'name'      => isset($signup_data['todyl_business_name']) ? $signup_data['todyl_business_name'] : '',
            'created_by'=> 'thrust',
            'updated_by'=> 'thrust',
            'is_active' => 0,
        ];

        if (isset($signup_data['orgId']) && $signup_data['orgId']) {
            // organization is already created - update it

            $organization = EntOrganization::findFirst([
                'conditions' => 'pk_id = ?1',
                'bind'       => [
                    1 => $signup_data['orgId'],
                ],
                'cache'      => false,
            ]);

            if ($organization->update($orgData) === false) {
                return [
                    'signupMessage' => implode('<br />', $organization->getMessages()),
                ];
            }
        } else {
            // create new organization

            $organization = new EntOrganization();
            if ($organization->create($orgData) === false) {
                return [
                    'signupMessage' => implode('<br />', $organization->getMessages()),
                ];
            }
        }

        // set lead registered flag
        $lead = EntLeads::findFirst([
            'conditions' => 'pk_id = ?1',
            'bind'       => [
                1 => $signup_data['leadId'],
            ],
            'cache'      => false,
        ]);

        $lead->is_registered = 1;
        if ($lead->update() === false) {
            return [
                'signupMessage' => implode('<br />', $lead->getMessages()),
            ];
        }

        //-- user --

        $userData = [
            'orgId'         => $organization->pk_id,
            'firstName'     => isset($signup_data['todyl_first_name']) ? $signup_data['todyl_first_name'] : '',
            'lastName'      => isset($signup_data['todyl_last_name']) ? $signup_data['todyl_last_name'] : '',
            'email'         => $signup_data['todyl_email'],
            'password'      => $signup_data['todyl_password'],
            'max_devices'   => isset($signup_data['num_devices']) ? $signup_data['num_devices'] : 0,
            'role'          => 'admin',
        ];

        if (isset($signup_data['userId']) && $signup_data['userId']) {
            // update user

            $userData['userId'] = $signup_data['userId'];
        }

        $user_result = $helper->createUser($userData);

        if ($user_result['status'] != 'success') {
            return [
                'signupMessage' => isset($user_result['error']) ? $user_result['error'] : 'There was an error while creating user.',
            ];
        }

        return [
            'user'          => $user_result['user'],
            'organization'  => $organization,
        ];
    }
}
