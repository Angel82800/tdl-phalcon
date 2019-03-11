<?php

namespace Thrust\Controllers;

use Thrust\Models\DashboardStatistics;
use Thrust\Models\EntUsers;

use Thrust\Stripe\Api as Stripe;

use Thrust\Forms\BillingForm;

use Thrust\Helpers\AccountHelper;
use Thrust\Helpers\AgreementHelper;
use Thrust\Helpers\BillingHelper;
use Thrust\Helpers\DeviceHelper;
use Thrust\Helpers\PricingHelper;

/**
 * Thrust\Controllers\ServiceController.
 */
class ServiceController extends ControllerBase
{
    protected $billingHelper;

    public function initialize()
    {
        $this->view->setTemplateBefore('private');

        $identity = $this->auth->getIdentity();

        // check if admin
        if ($identity['role'] == 'user') {
            throw new \Exception('User ID ' . $identity['id'] . ' tried to enter services page.');
        }

        $this->billingHelper = new BillingHelper();
    }

    public function indexAction()
    {
        $identity = $this->auth->getIdentity();
        $user_id = $identity['id'];

        $user = EntUsers::findFirst([
            'conditions' => 'pk_id = ?1',
            'bind'       => [
                1 => $user_id,
            ],
            'cache'      => 60,
        ]);

        $organization = $user->getOrganization([ 'cache' => 60 ]);

        $stats = new DashboardStatistics();
        $stripe = new Stripe();

        if (isset($this->persistent->order_details)) {
            // show successful order page

            $order_details = $this->persistent->order_details;
            unset($this->persistent->order_details);

            $order_details['env'] = $this->config->environment->env;

            $this->view->setVars($order_details);

            $this->view->pick('service/thankyou-billing');

        } else if (isset($this->persistent->account_updated)) {
            // show successful account update page

            unset($this->persistent->account_updated);

            $this->view->pick('service/thankyou-account');

        } else {
            // show management page

            $data = [
                'user'          => $user,
                'organization'  => $organization,
            ];

            $this->view->setVars($data);
        }
    }

    /**
     * billing action for first time users
     */
    public function billingAction()
    {
        $stats = new DashboardStatistics();
        $deviceHelper = new DeviceHelper();
        $form = new BillingForm();

        $config = $this->getDi()->get('config');
        $stripe_pk = $config->stripe->publishKey;

        $identity = $this->auth->getIdentity();
        $user_id = $identity['id'];

        // get user device count
        $device_count = $this->persistent->ftu_device_count;

        if ($this->session->get('is_ftu') && ! $device_count) {
            $this->response->redirect('/service/device');
        } else if (! $this->session->get('is_ftu')) {
            $device_count = $stats->userDevices($user_id)['total_count'];
        }

        // set device count in session
        $billing_data = @ $this->session->get('billing_data');
        if (! $billing_data) $billing_data = [];
        $billing_data['num_devices'] = $device_count;
        $this->session->set('billing_data', $billing_data);

        $user = EntUsers::findFirst([
            'conditions' => 'pk_id = ?1',
            'bind'       => [
                1 => $user_id,
            ],
            'cache'      => 60,
        ]);

        $organization = $user->getOrganization([ 'cache' => 60 ]);

        $data = [];
        $errorMessage = null;
        $billingErrors = [];

        // handle billing form submit
        if ($this->request->isPost()) {
            // validate post data
            $validation = $form->getValidation();
            $messages = $validation->validate($this->request->getPost());

            if (! count($messages)) {
                $agreement = new AgreementHelper();
                $stripe = new Stripe();

                $billing_data = @ $this->session->get('billing_data');
                if (! $billing_data) $billing_data = [];

                $billing_data = array_merge($billing_data, $this->request->getPost());

                if ($this->request->getPost('card_token')) {
                    // new card token was submitted

                    $cardToken = $billing_data['card_token'];

                    $firstName = $billing_data['todyl_first_name'];
                    $lastName = $billing_data['todyl_last_name'];

                    $nameOnCard = $firstName . ' ' . $lastName;

                    // create new stripe customer
                    $customer = $stripe->createCustomer($cardToken, $user->email, $nameOnCard);

                    if (! $customer) {
                        $errorMessage = 'Error while creating customer: ' . $this->session->get('stripe_error');
                        $this->session->remove('stripe_error');
                    } else {
                        $organization->stripe_customer_id = $customer->id;
                    }
                } else {
                    if (! $organization->stripe_customer_id) {
                        // no new token, no existing payment information
                        throw new \Exception('No card has been entered');
                    }
                }

                if ($organization->update() === false) {
                    $errorMessage = implode('<br />', $organization->getMessages());
                } else {
                    // subscribe customer to plans

                    $pricingHelper = new PricingHelper($device_count);

                    // $pricingHelper->setDiscount(0.5);

                    if (isset($billing_data['coupon'])) {
                        $pricingHelper->setCoupon($billing_data['coupon']);
                    }

                    // $tier_plan = $pricingHelper->getTierPlan(isset($billing_data['prepay']) ? 'annual' : 'monthly');
                    $device_plan = $pricingHelper->getPlan('device');
                    // $support_plan = $pricingHelper->getSupportPlan();

                    $plans = [
                        [
                            'plan'      =>  $device_plan,
                            'quantity'  =>  $device_count,
                        ],
                    ];

                    $coupon = isset($billing_data['coupon']) ? $billing_data['coupon']->id : false;
                    // $coupon = 'INTRODUCTORY';

                    // subscribe customer with coupon and 15 trial days
                    $subscription = $stripe->subscribeCustomer($organization->stripe_customer_id, $plans, $coupon, 15);

                    $this->logger->info('[SERVICE] Subscribed user ' . $user_id . ' to stripe plan ' . $device_plan);

                    if ($subscription) {
                        $this->logger->info('[SERVICE] Generating ' . $device_count . ' pins for user ID ' . $user_id);
                        $deviceHelper->generatePins($user_id, $device_count);

                        // save agreement acceptance
                        $agreement->addAgreement($user_id, 'customer');

                        unset($this->persistent->ftu_device_count);
                        $this->session->remove('billing_data');
                        $this->session->set('is_ftu', false);

                        $data['thankyou'] = true;
                        $data['final_price'] = '$' . number_format($subscription->plan->amount / 100, 2) . ' p/m';
                    } else {
                        $errorMessage = 'Error while subscribing customer: ' . $this->session->get('stripe_error');
                        $this->session->remove('stripe_error');
                    }
                }

            } else {
                foreach ($messages as $message) {
                    $billingErrors[$message->getField()][] = $message->getMessage();
                }
            }
        }

        // set form properties
        $form->setSavedData($billing_data);
        $form->setErrors($billingErrors);

        $trial_end = date('F dS', strtotime('+15 days'));

        $data = array_merge($data, [
            'stripe_pk'     => $stripe_pk,
            'user'          => $user,
            'organization'  => $organization,
            'device_count'  => $ftu_device_count,
            'trial_end'     => $trial_end,
            'form'          => $form,
            'errorMessage'  => $errorMessage,
        ]);

        $this->view->setVars($data);
    }

    public function deviceAction()
    {
        $identity = $this->auth->getIdentity();
        $user_id = $identity['id'];

        // do not proceed if email is not verified
        // if (! $identity['email_verified']) {
        //     return $this->dispatcher->forward(array(
        //         'controller' => 'common',
        //         'action'     => 'emailVerifyNotice',
        //         'params'     => [
        //             'message'=> 'In order to invite users, you must first verify your email address.',
        //         ],
        //     ));
        // }

        $user = EntUsers::findFirst([
            'conditions' => 'pk_id = ?1',
            'bind'       => [
                1 => $user_id,
            ],
            'cache'      => 60,
        ]);

        $organization = $user->getOrganization([ 'cache' => 60 ]);

        $stats = new DashboardStatistics();
        $stripe = new Stripe();

        //--- get device count ---
        $devices_per_user = $stats->getOrgDevicesPerUser($organization->pk_id);

        if ($organization->stripe_customer_id) {
            $stripeCustomer = $stripe->getCustomer($organization->stripe_customer_id);

            $this->persistent->current_stripe_plan = $stripeCustomer->subscriptions->data[0];
            // echo '<pre>'; print_r($stripeCustomer->subscriptions->data[0]); echo '</pre>'; exit;
            // echo '<pre>'; print_r($stripeCustomer->subscriptions->data[0]->items->data[0]); echo '</pre>'; exit;
        }

        // start device count from 1 if ftu
        if ($this->session->get('is_ftu')) {
            foreach ($devices_per_user as $key => $sub_user) {
                if ($sub_user['user_id'] == $identity['GUID']) {
                    // current user
                    if (! $sub_user['device_count']) {
                        $devices_per_user[$key]['device_count'] = 1;
                    }

                    break;
                }
            }
        }

        $data = [
            'loading_message'   => 'Processing your request...',
            'current_users'     => $devices_per_user,
            'email_verified'    => $identity['email_verified'],
        ];

        $this->view->setVars($data);
    }

    public function manageDeviceAction()
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
            $service_data = $this->request->getPost();
            if ($type) $content = [];

            $accountHelper = new AccountHelper();
            $deviceHelper = new DeviceHelper();
            $stats = new DashboardStatistics();

            $stripe = new Stripe();

            $identity = $this->auth->getIdentity();
            $user_id = $identity['id'];

            $user = EntUsers::findFirst([
                'conditions' => 'pk_id = ?1',
                'bind'       => [
                    1 => $user_id,
                ],
                'cache'      => false,
            ]);

            $organization = $user->getOrganization([ 'cache' => false ]);

            if ($type == 'getSummary') {
                if ($this->session->get('is_ftu')) {
                    $content['is_ftu'] = true;
                    $content['trial_end'] = date('F dS', strtotime('+15 days'));

                    $this->persistent->ftu_device_count = array_values($service_data['current_devices'])[0];
                } else {
                    $total_devices = 0;

                    foreach ($service_data['current_devices'] as $user_id => $device_count) {
                        $total_devices += $device_count;
                    }

                    if (isset($service_data['new_users'])) {
                        foreach ($service_data['new_users']['device'] as $new_device) {
                            $total_devices += $new_device;
                        }
                    }

                    $current_subscription = $this->persistent->current_stripe_plan->items->data[0];
                    // $current_subscription->plan->amount
                    // $current_subscription->plan->interval
                    // $current_subscription->quantity

                    $interval = $current_subscription->plan->interval == 'month' ? 'm' : 'y';
                    $current_quantity = $current_subscription->quantity;
                    $current_price = $current_subscription->plan->amount;

                    $introductory = strpos(strtolower($current_subscription->plan->name), 'introductory') !== false;

                    // $oldPricingHelper = new PricingHelper($current_quantity, $introductory);
                    // $newPricingHelper = new PricingHelper($total_devices, $introductory);
                    // if ($this->persistent->current_stripe_plan->discount) {
                    //     $newPricingHelper->setCoupon($this->persistent->current_stripe_plan->discount->coupon);

                    //     $content['coupon'] = $newPricingHelper->getCouponDescription();
                    //     $content['discount'] = '- $' . $newPricingHelper->getPricing('discounted') . ' p/m';
                    // }

                    // the pricing helper will be used just to calculate the coupon amount
                    $pricingHelper = new PricingHelper($total_devices, $introductory);
                    if ($this->persistent->current_stripe_plan->discount) {
                        $pricingHelper->setCoupon($this->persistent->current_stripe_plan->discount->coupon);

                        $content['coupon'] = $pricingHelper->getCouponDescription();
                        $content['discount'] = '- $' . $pricingHelper->getPricing('discounted') . ' p/m';
                    }

                    $coupon_id = isset($this->persistent->current_stripe_plan->discount) ? $this->persistent->current_stripe_plan->discount->coupon : false;
                    $subscription_id = $this->persistent->current_stripe_plan->id;

                    $subscription_items = [
                        [
                            'id'        => $current_subscription->id,
                            'quantity'  => $total_devices,
                        ],
                    ];

                    // calculate tier pricing based on upcoming invoice - to get price after coupon applied
                    $upcoming_invoice = $stripe->getUpcomingInvoice($user->organization->stripe_customer_id, $coupon_id, $subscription_id,  $subscription_items);
                    $final_amount = $upcoming_invoice->amount_due;

                    if ($total_devices == $current_quantity) {
                        // no subscription changes
                        $content['changed'] = false;

                        // check if there're device changes
                        $content['updated'] = false;
                        if (isset($service_data['new_users'])) {
                            $content['updated'] = true;
                        } else {
                            $devices_per_user = $stats->getOrgDevicesPerUser($organization->pk_id);

                            foreach ($devices_per_user as $device_per_user) {
                                if ($service_data['current_devices'][$device_per_user['user_id']] != $device_per_user['device_count']) {
                                    // changes in user device count
                                    $content['updated'] = true;
                                    break;
                                }
                            }
                        }
                    } else {
                        $content['changed'] = true;

                        $device_change = abs($total_devices - $current_quantity);
                        if ($total_devices > $current_quantity) {
                            $content['change'] = '+' . $device_change . ' @ $' . number_format($current_price * $device_change / 100, 2) . ' p/' . $interval;
                        } else if ($total_devices < $current_quantity) {
                            $content['change'] = '-' . $device_change . ' @ $' . number_format($current_price * $device_change / 100, 2) . ' p/' . $interval;
                        }

                        $content['current'] = $current_quantity . ' ' . ($current_quantity == 1 ? 'Device' : 'Devices') . ' @ $' . number_format($current_price * $current_subscription->quantity / 100, 2) . ' p/' . $interval;

                        $content['total'] = '$' . number_format($final_amount / 100, 2) . ' p/' . $interval;
                    }

                }

                $content['status'] = 'success';

            } else if ($type == 'validateEmail') {
                $email = $service_data['email'];

                $existing_user = EntUsers::findFirst([
                    'conditions' => 'email = ?1',
                    'bind'       => [
                        1 => $email,
                    ],
                    'cache'      => false,
                ]);

                if ($existing_user) {
                    if (($existing_user->is_deleted = 1 || ($existing_user->is_active = 0 && $existing_user->is_invited = 0)) && $existing_user->fk_ent_organization_id == $organization->pk_id) {
                        // user is from the same org - allow to readd
                        $content = [
                            'result'    => 'deleted',
                        ];
                    } else {
                        $content = [
                            'result'    => 'exists',
                        ];
                    }
                } else {
                    $content = [
                        'result'    => 'available',
                    ];
                }

                $content['status'] = 'success';

            } else if ($type == 'processOrder') {
                //--- update users & devices and stripe plan ---

                $total_devices = 0;
                $errors = [];

                // first validate device count input

                $is_valid_request = true;

                foreach ($service_data['current_devices'] as $user_id => $device_count) {
                    if ($device_count < 0 || $device_count > 99) {
                        $is_valid_request = false;
                        break;
                    }
                }

                if ($is_valid_request && isset($service_data['new_users'])) {
                    foreach ($service_data['new_users']['device'] as $device_count) {
                        if ($device_count < 0 || $device_count > 99) {
                            $is_valid_request = false;
                            break;
                        }
                    }
                }

                if (! $is_valid_request) {
                    // invalid request

                    $this->logger->info('[SERVICE] Admin ID ' . $user_id . ' tried to process invalid device count');

                    $content = [
                        'status'    => 'fail',
                        'message'   => 'Invalid request',
                    ];
                } else {
                    // update device count for current users

                    $device_changes = [];

                    foreach ($service_data['current_devices'] as $user_id => $device_count) {
                        $device_update_result = $deviceHelper->updateDeviceCount($user_id, $device_count, true, true);

                        if (is_array($device_update_result)) {
                            $total_devices += $device_count;

                            $device_changes[] = [
                                'label'     => $device_update_result['user_email'],
                                'value'     => ($device_update_result['added_devices'] ? $device_update_result['added_devices'] : 0) . ($device_update_result['added_devices'] == 1 ? ' Device' : ' Devices'),
                            ];
                        } else if ($device_update_result !== true) {
                            $errors[] = $device_update_result;

                            // We shouldn't be exiting here - may create a security hole
                            // $response->setContent(json_encode($content));
                            // $response->send();
                            // exit;
                        } else {
                            // same
                            $total_devices += $device_count;
                        }
                    }

                    // add new users

                    $new_users = []; // new user array for showing on thank you page

                    if (isset($service_data['new_users'])) {
                        // get inactive user list of the organization
                        $inactive_users = EntUsers::find([
                            'conditions' => 'fk_ent_organization_id = ?1 AND ((is_active = 0 AND is_invited = 0) OR is_deleted = 1)',
                            'bind'       => [
                                1 => $organization->pk_id,
                            ],
                            'cache'      => false,
                        ]);

                        $inactive_user_list = [];
                        foreach ($inactive_users as $inactive_user) {
                            $inactive_user_list[$inactive_user->pk_id] = $inactive_user->email;
                        }

                        foreach ($service_data['new_users']['email'] as $key => $email) {
                            // $resetpw_GUID = preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(openssl_random_pseudo_bytes(24)));

                            $userData = [
                                'orgId'         => $organization->pk_id,
                                'email'         => $email,
                                'max_devices'   => $service_data['new_users']['device'][$key],
                                'is_invite'     => true,
                                // 'resetpw_GUID'  => $resetpw_GUID,
                            ];

                            $admin_name = ($user->firstName || $user->lastName) ? $user->firstName . ' ' . $user->lastName : $user->email;

                            $email_data = [
                                'template'      => 'user-invitation',
                                'data'          => [
                                    'admin_name'    => $admin_name,
                                    // 'GUID'          => $resetpw_GUID,
                                ],
                            ];

                            $existing = false;
                            $inactive_user_id = array_search($email, $inactive_user_list);
                            if ($inactive_user_id !== false) {
                                // user was previously in organization - readd
                                $existing = true;
                                $userData['userId'] = $inactive_user_id;
                            }

                            $user_create_result = $accountHelper->createUser($userData, $email_data, $existing);

                            if ($user_create_result['status'] != 'success') {
                                $errors[] = $user_create_result['error'];

                                // $content = [
                                //     'status'    => 'fail',
                                //     'message'   => $user_create_result['error'],
                                // ];

                                // $response->setContent(json_encode($content));
                                // $response->send();
                                // exit;
                            } else {
                                $new_users[] = [
                                    'label'     => $email,
                                    'value'     => $userData['max_devices'] . ($userData['max_devices'] == 1 ? ' Device' : ' Devices'),
                                ];

                                $total_devices += $service_data['new_users']['device'][$key];
                            }
                        }
                    }

                    $current_subscription = $this->persistent->current_stripe_plan->items->data[0];
                    $current_quantity = $current_subscription->quantity;

                    $stats->getOrgDevicesPerUser($organization->pk_id, 'flush');

                    if ($total_devices == $current_quantity) {
                        //--- just updates - no stripe change ---

                        $this->persistent->account_updated = true;

                        $this->logger->info('[SERVICE] Updated only user structure of org ' . $organization->pk_id);

                        $content = [
                            'status'    => 'success',
                        ];
                    } else {
                        //--- stripe changes ---

                        $update_result = $this->billingHelper->updateSubscriptionQuantity($organization, 'device', $total_devices);

                        if ($update_result['status'] != 'success') {
                            $this->logger->error('[SERVICE] Error while updating subscription : ' . $this->session->get('stripe_error'));
                            $this->session->remove('stripe_error');

                            $content = [
                                'status'    => 'fail',
                                'message'   => 'Sorry, there was an error while updating your subscription',
                            ];

                        } else {
                            $this->logger->info('[SERVICE] Updated subscription item quantity of org ' . $organization->pk_id . ' to ' . $total_devices);

                            $order_items = [];

                            $current_subscription = $this->persistent->current_stripe_plan->items->data[0];

                            // $tier_price = $current_subscription->plan->amount;
                            $interval = $current_subscription->plan->interval == 'month' ? 'm' : 'y';
                            $current_price = $current_subscription->plan->amount;

                            $introductory = strpos(strtolower($current_subscription->plan->name), 'introductory') !== false;

                            // the pricing helper will be used just to calculate the coupon amount
                            $pricingHelper = new PricingHelper($total_devices, $introductory);
                            if ($this->persistent->current_stripe_plan->discount) {
                                $pricingHelper->setCoupon($this->persistent->current_stripe_plan->discount->coupon);

                                $content['coupon'] = $pricingHelper->getCouponDescription();
                                $content['discount'] = '- $' . $pricingHelper->getPricing('discounted') . ' p/m';
                            }

                            $coupon_id = isset($this->persistent->current_stripe_plan->discount) ? $this->persistent->current_stripe_plan->discount->coupon : false;
                            $subscription_id = $this->persistent->current_stripe_plan->id;

                            $subscription_items = [
                                [
                                    'id'        => $current_subscription->id,
                                    'quantity'  => $total_devices,
                                ],
                            ];

                            // calculate tier pricing based on upcoming invoice - to get price after coupon applied
                            $upcoming_invoice = $stripe->getUpcomingInvoice($user->organization->stripe_customer_id, $coupon_id, $subscription_id,  $subscription_items);
                            $final_amount = $upcoming_invoice->amount_due;

                            // $oldPricingHelper = new PricingHelper($current_quantity, $introductory);
                            // $newPricingHelper = new PricingHelper($total_devices, $introductory);
                            // if ($this->persistent->current_stripe_plan->discount) {
                            //     $newPricingHelper->setCoupon($this->persistent->current_stripe_plan->discount->coupon);
                            // }

                            // $tier_price = $current_quantity ? $oldPricingHelper->getPricing('original') / $current_quantity : $oldPricingHelper->getPricing('original');

                            $device_change = abs($total_devices - $current_quantity);

                            $change_label = ($total_devices > $current_quantity) ? 'Added' : 'Removed';
                            $change_label .= ': Todyl Protection for ' . $device_change;
                            $change_label .= ($device_change > 1) ? ' Devices' : ' Device';

                            $change_amount = ($total_devices > $current_quantity) ? '' : '-';
                            $change_amount .= '$' . number_format($current_price * $device_change / 100, 2) . ' p/' . $interval;

                            $generated_pins = $this->session->get('generated_pins') ? $this->session->get('generated_pins') : [];
                            $this->session->remove('generated_pins');

                            if (! empty($device_changes)) {
                                $order_items[] = [
                                    'title' => 'Device Changes',
                                    'items' => $device_changes,
                                ];
                            }

                            if (! empty($new_users)) {
                                $order_items[] = [
                                    'title' => 'Invited Users',
                                    'items' => $new_users,
                                ];
                            }

                            $order_details = [
                                'change_label'  => $change_label,
                                'change_amount' => $change_amount,
                                'total'         => '$' . number_format($final_amount / 100, 2),
                                'pins'          => implode(', ', $generated_pins),

                                'order_items'   => $order_items,
                            ];

                            if ($this->persistent->current_stripe_plan->discount) {
                                $order_details['coupon'] = $pricingHelper->getCouponDescription();
                                $order_details['discount'] = '- $' . $pricingHelper->getPricing('discounted') . ' p/m';
                            }

                            $this->persistent->order_details = $order_details;

                            $this->persistent->current_stripe_plan = $update_result['subscription'];

                            // send subscription update email

                            $email_data = [
                                'left_header'       => 'Previous',
                                'right_header'      => 'Update',
                                'left_value'        => $current_quantity . ($current_quantity == 1 ? ' Device' : ' Devices') . ' Protected',
                                'right_value'       => $total_devices . ($total_devices == 1 ? ' Device' : ' Devices') . ' Protected',
                            ];

                            $this->mail->sendMail($user->pk_id, 'subscription-update', $email_data);

                            $content = [
                                'status'    => 'success',
                            ];
                        }
                    }

                    if (count($errors)) {
                        $this->flashSession->error('There were some errors while processing your request.<br />' . implode('<br />', $errors));
                    }

                }

            } else if ($type == 'resendInvite') {
                $invited_id = $service_data['user_id'];

                $invited_user = EntUsers::findFirst([
                    'conditions' => 'GUID = ?1',
                    'bind'       => [
                        1 => $invited_id,
                    ],
                    'cache'      => false,
                ]);

                $userData = [
                    'userId'        => $invited_user->pk_id,
                    'orgId'         => $organization->pk_id,
                    'email'         => $invited_user->email,
                    'is_invite'     => true,
                ];

                $admin_name = ($user->firstName || $user->lastName) ? $user->firstName . ' ' . $user->lastName : $user->email;

                $email = [
                    'template'      => 'user-invitation',
                    'data'          => [
                        'admin_name'    => $admin_name,
                    ],
                ];

                $resend_result = $accountHelper->createUser($userData, $email);

                if ($resend_result['status'] == 'success') {
                    $content = [
                        'status'    => 'success',
                    ];
                } else {
                    $content = [
                        'status'    => 'fail',
                        'message'   => $user_create_result['error'],
                    ];
                }

            } else if ($type == 'cancelInvite') {
                $invited_id = $service_data['user_id'];

                $invited_user = EntUsers::findFirst([
                    'conditions' => 'GUID = ?1 AND is_invited = 1',
                    'bind'       => [
                        1 => $invited_id,
                    ],
                    'cache'      => false,
                ]);

                if (! $invited_user) {
                    $content = [
                        'status'    => 'fail',
                        'message'   => 'Sorry, we couldn\'t find the user.',
                    ];
                } else {
                    $invited_user->is_invited = 0;

                    if ($invited_user->update()) {
                        $content = [
                            'status'    => 'success',
                        ];
                    } else {
                        $content = [
                            'status'    => 'fail',
                            'message'   => $user_create_result['error'],
                        ];
                    }

                }

            } else {
                // no type found
                $content = [
                    'status'    => 'fail',
                    'message'   => 'Sorry, no action has been found.',
                ];
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

}
