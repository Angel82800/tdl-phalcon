<?php

namespace Thrust\Controllers;

use Thrust\Models\AttrRoles;
use Thrust\Models\EntUsers;
use Thrust\Models\EntOrganization;
use Thrust\Models\AttrPasswordChangeType;
use Thrust\Models\LogPasswordChanges;

use Thrust\Stripe\Api as Stripe;

use Thrust\Forms\BillingForm;

use Thrust\Helpers\AccountHelper;
use Thrust\Helpers\DeviceHelper;
use Thrust\Helpers\BillingHelper;
use Thrust\Helpers\PricingHelper;

/**
 * manage user account
 */
class AccountController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateBefore('private');
    }

    /**
     * manage account settings main page
     */
    public function indexAction()
    {
        $identity = $this->auth->getIdentity();

        $user = EntUsers::findFirst([
            'conditions' => 'pk_id = ?1',
            'bind'       => [
                1 => $identity['id'],
            ],
            'cache'      => false,
        ]);

        $organization = $user->getOrganization([ 'cache' => false ]);

        $stripe = new Stripe();

        $stripeCustomer = $stripe->getCustomer($organization->stripe_customer_id);

        $upcomingInvoice = $stripe->getUpcomingInvoice($organization->stripe_customer_id);

        // $chargeHistory = $stripe->getCharges([
        $invoiceHistory = $stripe->getInvoices([
            'customer'  => $organization->stripe_customer_id,
            'limit'     => 100,
        ])->data;

        // echo '<pre>'; print_r($invoiceHistory); echo '</pre>'; exit;

        // filter first 12 paid invoices
        $pastInvoices = [];
        $count = 0;
        while (count($pastInvoices) < 12 && isset($invoiceHistory[$count])) {
            if ($invoiceHistory[$count]['paid']) {
                $pastInvoices[] = $invoiceHistory[$count ++];
            }
        }

        // echo '<pre>'; print_r($chargeHistory); echo '</pre>'; exit;

        $helper = new AccountHelper();

        $config = $this->getDi()->get('config');

        $stripe_pk = $config->stripe->publishKey;

        $org_owner = $helper->getOrgOwner($organization->pk_id);

        if (! $org_owner) {
            $this->logger->error('[ACCOUNT] Not able to find organization owner for org ' . $organization->pk_id);
            throw new \Exception('No Organization Owner');
        }

        if ($identity['is_active']) {
            // account is active

            $user_tiers = [
                'Standard Service',
                'Expedited Service',
                'Dedicated Service',
            ];
            // $tier_x = floor($user->max_devices / 2) ? floor($user->max_devices / 2) : 1;

            $tier_x = floor($user->getDeviceCount() / 2) ? floor($user->getDeviceCount() / 2) : 1;

            $ph = new PricingHelper($tier_x);
            $user_tier = $user_tiers[$ph->getTier() - 1];

            $billing_interval = $stripeCustomer->subscriptions->data[0]->items->data[0]->plan->interval == 'month' ? 'Monthly, on the ' . date('jS', $stripeCustomer->subscriptions->data[0]->current_period_end) : 'Annual, ' . date('F d', $stripeCustomer->subscriptions->data[0]->current_period_end);

            $promo_code = false;
            if ($stripeCustomer->subscriptions->data[0]->discount) {
                $ph->setCoupon($stripeCustomer->subscriptions->data[0]->discount->coupon);
                $promo_code = $ph->getCouponDescription();
                if ($stripeCustomer->subscriptions->data[0]->discount->end) {
                    $promo_code .= '<p class="subtext">Expires on ' . date('dS F', $stripeCustomer->subscriptions->data[0]->discount->end) . '</p>';
                }
            }

            $email_setting = $helper->getUserPreference($identity['id'], 'Email Setting');

            $this->view->setVars([
                'user'              => $user,
                'org_owner'         => $org_owner,
                'organization'      => $organization,
                'stripe_pk'         => $stripe_pk,
                'stripe_customer'   => $stripeCustomer,
                'upcoming_invoice'  => $upcomingInvoice,
                'invoice_history'   => $pastInvoices,
                'user_tier'         => $user_tier,
                'billing_interval'  => $billing_interval,
                'promo_code'        => $promo_code,
                'email_setting'     => $email_setting,
            ]);
        } else {
            // account is suspended

            $form = new BillingForm();

            $this->view->setVars([
                'form'              => $form,
                'user'              => $user,
                'org_owner'         => $org_owner,
                'organization'      => $organization,
                'stripe_pk'         => $stripe_pk,
                'stripe_customer'   => $stripeCustomer,
                'is_card_active'    => $stripeCustomer && isset($stripeCustomer->sources) && isset($stripeCustomer->sources->data) && ! empty($stripeCustomer->sources->data),
            ]);

            // show suspended account page
            $this->view->pick('account/inactive');
        }
    }

    public function inprogressAction()
    {
        if (! $this->session->get('suspension_in_progress')) {
            $this->response->redirect('/account');
        }
    }

    /**
     * account settings ajax handler
     */
    public function manageAction()
    {
        $this->view->disable();
        $content = [
            'message' => 'Oops! Something went wrong. Please try again later.',
        ];

        $response = new \Phalcon\Http\Response();
        $response->setStatusCode(400, 'Bad Request');

        if ($this->request->isPost() && $this->request->isAjax()) {
            $response->setStatusCode(200);

            $type = $this->request->getPost('type');
            $account_data = $this->request->getPost();

            $helper = new AccountHelper();
            $billing = new BillingHelper();
            $deviceHelper = new DeviceHelper();
            $stripe = new Stripe();

            $identity = $this->auth->getIdentity();
            $user = EntUsers::findFirst([
                'conditions' => 'pk_id = ?1',
                'bind'       => [
                    1 => $identity['id'],
                ],
                'cache'      => false,
            ]);

            $organization = $user->getOrganization([ 'cache' => false ]);

            if ($user) {
                if ($type == 'account') {

                    try {
                        // update organization title
                        $organization->name = $account_data['org_name'];
                        $organization->update();

                        // update user information
                        $user->firstName = $account_data['firstName'];
                        $user->lastName = $account_data['lastName'];
                        $user->email = $account_data['email'];
                        $user->primaryPhone = preg_replace('/[^0-9]/i', '', $account_data['primaryPhone']);
                        $user->update();

                        $this->logger->info('[ACCOUNT] Account info successfully updated for user ID ' . $user->pk_id);

                        $content = [
                            'status'    => 'success',
                            'message'   => 'Account updated successfully. Your changes may take up to 30 seconds to reflect.',
                        ];
                    } catch (\Exception $e) {
                        $this->logger->error('[ACCOUNT] Account Update Exception while updating user ID ' . $user->pk_id . ': ' . $e->getMessage() . ' with trace: ' . (string) $e->getTraceAsString());

                        $content = [
                            'status'    => 'fail',
                            'message'   => 'An error occurred while updating your account information',
                        ];
                    }

                } else if ($type == 'password') {

                    $current_password = $account_data['current_password'];
                    $new_password = $account_data['new_password'];

                    // check if current password is correct
                    if ($this->security->checkHash($current_password, $user->password)) {

                        // log password change
                        $passwordchangetype = AttrPasswordChangeType::findFirstByName('portalPasswordChange');

                        $log = new LogPasswordChanges();
                        $log->assign([
                            'fk_ent_users_id' => $user->pk_id,
                            'fk_attr_password_change_type_id' => $passwordchangetype->pk_id,
                            'ip_address' => $this->request->getClientAddress(),
                            'ip_geolocation' => $this->auth->getGeoIp(),
                            'user_agent' => $this->request->getUserAgent(),
                            'created_by' => 'thrust',
                            'updated_by' => 'thrust',
                        ]);

                        if (! $log->save()) {
                            $message = implode('<br />', $log->getMessages());
                        }

                        // generate password
                        $hashed_password = $this->getDI()->getSecurity()->hash($new_password);

                        $user->password = $hashed_password;
                        $user->update();

                        $this->mail->sendMail($user->pk_id, 'password-changed', []);

                        $content = [
                            'status'    => 'success',
                            'message'   => 'Password updated successfully. Your changes may take up to 30 seconds to reflect.',
                        ];
                    } else {
                        // current password doesn't match

                        if (! $this->session->has('password_not_match_count')) {
                            $password_not_match_count = 1;
                        } else {
                            $password_not_match_count = $this->session->get('password_not_match_count') + 1;
                        }
                        $this->session->set('password_not_match_count', $password_not_match_count);

                        if ($password_not_match_count >= 5) {
                            // log out user if wrong password is entered more than 5 times

                            $this->auth->remove();

                            $this->flashSession->error('Locked for security purposes, please use forgot my password to reset.');

                            // trigger forgot password email

                            $result = $helper->triggerForgotPassword($user->email);

                            $this->view->setLayout('');
                            $content = [
                                'status'    => 'security_fail',
                            ];
                        } else {
                            $content = [
                                'status'    => 'fail',
                                'message'   => 'Sorry, your current password doesn\'t match. Please try again.',
                            ];
                        }

                    }

                } else if ($type == 'billing') {

                    $name = $account_data['name_on_card'];
                    $token = $account_data['card_token'];

                    $customer = $stripe->updateCustomer($organization->stripe_customer_id, $token, $user->email, $organization->name);

                    if (!$customer) {
                        $content = [
                            'status'    => 'failure',
                            'message'   => $this->session->get('stripe_error'),
                        ];
                        $this->session->remove('stripe_error');
                    } else {
                        $content = [
                            'status'    => 'success',
                            'card_info' => $customer->sources->data[0]->brand . ' Ending in ' . $customer->sources->data[0]->last4,
                            'message'   => 'Your payment information was updated successfully. Your changes may take up to 30 seconds to show.',
                        ];
                    }

                } else if ($type == 'advanced_view') {

                    try {
                        // update user information
                        $user->is_professional = $account_data['value'] == 'true' ? 1 : 0;
                        $user->update();

                        $this->logger->info('[ACCOUNT] Account is_professional successfully updated for user ID ' . $user->pk_id);

                        $content = [
                            'status'    => 'success',
                            'message'   => 'Account updated successfully.',
                        ];
                    } catch (\Exception $e) {
                        $this->logger->error('[ACCOUNT] Account Update Exception while updating user ID ' . $user->pk_id . ': ' . $e->getMessage() . ' with trace: ' . (string) $e->getTraceAsString());

                        $content = [
                            'status'    => 'fail',
                            'message'   => 'An error occurred while updating your advanced view information',
                        ];
                    }

                } else if ($type == 'checkpw') {
                    $current_password = $account_data['current_pw'];

                    $role = AttrRoles::findFirst([
                        'conditions' => 'name = ?1 AND is_active = 1 AND is_deleted = 0',
                        'bind'       => [
                            1 => 'admin',
                        ],
                    ]);

                    if ($user->fk_attr_roles_id != $role->pk_id) {
                        // user is not an admin and can't reactivate the organization
                        $content = [
                            'status'    => 'fail',
                            'message'   => 'Sorry, only administrators can change account status.',
                        ];
                    } else {
                        // check if current password is correct
                        if ($this->security->checkHash($current_password, $user->password)) {
                            $this->session->set('suspension_in_progress', true);

                            $content = [
                                'status'        => 'success',
                                'redirectUrl'   => '/account/inprogress',
                            ];
                        } else {
                            // current password doesn't match
                            $content = [
                                'status'    => 'fail',
                                'message'   => 'Sorry, your current password doesn\'t match. Please try again.',
                            ];
                        }
                    }

                } else if ($type == 'suspend') {
                    if ($this->session->get('suspension_in_progress')) {
                        // handle stripe suspension
                        $suspend_result = $billing->suspendOrganization($organization);

                        if ($suspend_result === true) {
                            // suspend all users and agents
                            $org_users = EntUsers::find([
                                'conditions' => 'fk_ent_organization_id = ?1 AND is_active = 1 AND is_deleted = 0',
                                'bind'       => [
                                    1 => $organization->pk_id,
                                ],
                                'cache'      => false,
                            ]);

                            foreach ($org_users as $org_user) {
                                foreach ($org_user->agents as $device) {
                                    $device->is_active = 0;
                                    $device->save();
                                }

                                $emailTemplate = 'suspension-user';
                                $emailData = [];

                                // $admin_name = ($user->firstName || $user->lastName) ? $user->firstName . ' ' . $user->lastName : $user->email;

                                if ($org_user->role->name == 'admin') {
                                    // admin
                                    $emailTemplate = 'suspension-admin';
                                } else {
                                    $emailData['admin_name'] = $user->getName();
                                }

                                $this->mail->sendMail($org_user->pk_id, $emailTemplate, $emailData);
                            }

                            foreach ($organization->users as $org_user) {
                                if ($org_user->role->name != 'admin') {
                                    // only deactivate org users - admin user should be enabled for reactivation
                                    $org_user->is_active = 0;
                                    $org_user->is_invited = 0;
                                    $org_user->save();
                                }
                            }

                            $this->logger->info('[ACCOUNT] Deactivated all users (except admin) and agents of org ID ' . $organization->pk_id);

                            // deactivate organization
                            $organization->is_active = false;

                            if ($organization->update()) {
                                $this->logger->info('[ACCOUNT] Deactivated org ID ' . $organization->pk_id);

                                $this->auth->remove();

                                $this->flashSession->success('<h4>Your account has been suspended.</h4><p>Devices and users associated with this account are no longer protected. To reactivate your account, simply log in and follow the on screen instructions.</p>');

                                $content = [
                                    'status'    => 'success',
                                    'message'   => 'Your account has been suspended.',
                                ];
                            } else {
                                $this->logger->info('[ACCOUNT] Everything for org ID ' . $organization->pk_id . ' have been deactivated but not the org itself... : ' . implode('<br />', $organization->getMessages()));

                                $content = [
                                    'status'    => 'fail',
                                    'message'   => 'There was a problem deactivating your account. Please contact support@todyl.com.',
                                ];
                            }
                        } else {
                            $this->logger->error('[ACCOUNT] Error while reactivating subscription for org ID ' . $organization->pk_id . ' : ' . is_array($suspend_result) ? implode('<br />', $suspend_result) : $suspend_result);

                            $content = [
                                'status'    => 'fail',
                                'message'   => 'There was a problem cancelling your subscription. Please contact support@todyl.com.',
                            ];
                        }
                    } else {
                        $content = [
                            'status'    => 'fail',
                            'message'   => 'Invalid request',
                        ];
                    }

                } else if ($type == 'reactivate') {
                    $reactivate_result = $billing->reactivateCustomer($organization, $user, $account_data);

                    if ($reactivate_result === true) {
                        // send reactivation emails - right now only the org admin will receive the email as he's the only one active
                        $org_users = EntUsers::find([
                            'conditions' => 'fk_ent_organization_id = ?1 AND is_active = 1 AND is_deleted = 0',
                            'bind'       => [
                                1 => $organization->pk_id,
                            ],
                            'cache'      => false,
                        ]);

                        foreach ($org_users as $org_user) {
                            $emailTemplate = 'reactivation-user';

                            if ($org_user->role->name == 'admin') {
                                // admin
                                $emailTemplate = 'reactivation-admin';

                                $deviceHelper->updateDeviceCount($org_user->GUID, 1);
                            }

                            $identity['is_active'] = 1;
                            $this->session->set('auth-identity', $identity);

                            $this->mail->sendMail($org_user->pk_id, $emailTemplate, []);
                        }

                        // reactivate organization
                        $organization->is_active = 1;

                        if ($organization->update()) {
                            $this->flashSession->success('Your account has been reactivated.');

                            $content = [
                                'status'    => 'success',
                                'message'   => 'Your account has been reactivated.',
                            ];
                        } else {
                            $this->logger->error('[ACCOUNT] A new subscription org ID ' . $organization->pk_id . ' has been created but the org itself hasn\'t been reactivated... : ' . implode('<br />', $organization->getMessages()));

                            $content = [
                                'status'    => 'fail',
                                'message'   => 'There was a problem reactivating your account. Please contact support@todyl.com.',
                            ];
                        }
                    } else {
                        $this->logger->error('[ACCOUNT] Error while reactivating subscription for org ID ' . $organization->pk_id . ' : ' . $reactivate_result);

                        $content = [
                            'status'    => 'fail',
                            'message'   => 'There was an error while reactivating your subscription. Please contact site administrator.',
                        ];
                    }

                } else if ($type == 'email_settings') {
                    $email_setting = $account_data['setting'];

                    $helper->setUserPreference($user->pk_id, 'Email Setting', $email_setting);

                    if ($email_setting == 'critical') {
                        $frequency = 'Low';
                    } else {
                        $frequency = 'Weekly';
                    }

                    $content = [
                        'status'    => 'success',
                        'frequency' => $frequency,
                        'message'   => 'Your email settings have been successfully updated. Your changes may take up to 30 seconds to reflect.',
                    ];

                }
            } else {
                $content = [
                    'status'    => 'fail',
                    'message'   => 'User not found',
                ];
            }
        }

        // update auth identity object
        $this->auth->updateIdentity();

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

}
