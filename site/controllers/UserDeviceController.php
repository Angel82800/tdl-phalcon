<?php

namespace Thrust\Controllers;

use Thrust\Models\Api\Agent;
use Thrust\Models\DashboardStatistics;
use Thrust\Models\EntAgent;
use Thrust\Models\EntUsers;
use Thrust\Models\EntOrganization;

use Thrust\Stripe\Api as Stripe;

use Thrust\Helpers\AccountHelper;
use Thrust\Helpers\BillingHelper;
use Thrust\Helpers\DeviceHelper;
use Thrust\Helpers\FtuHelper;
use Thrust\Helpers\PricingHelper;

/**
 * Thrust\Controllers\UserdeviceController.
 */
class UserdeviceController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateBefore('private');
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
        $helper = new AccountHelper();
        $stripe = new Stripe();

        $org_admin = $helper->getOrgOwner($organization->pk_id);

        $load_user_id = isset($this->request->getQuery()['user']) ? $this->request->getQuery()['user'] : false;
        $load_user = false;

        //--- get user info ---
        $user_info = $stats->getOrgDevicesPerUser($organization->pk_id);

        $stripeCustomer = $stripe->getCustomer($organization->stripe_customer_id);

        $this->persistent->current_stripe_plan = $stripeCustomer->subscriptions->data[0];

        $total_devices = 0;
        foreach ($user_info as $info) {
            $total_devices += $info['device_count'];
        }

        if ($org_admin->pk_id == $user_id) {
            if (count($user_info) > 1 || ! $total_devices) {
                // org has more than one user or has no devices - show user view
                $default_type = 'user';
            } else {
                $default_type = 'device';
            }
        } else if ($load_user_id) {
            // a normal user trying to view other user page

            $this->logger->error('[USER & DEVICE] User ID ' . $user->pk_id . ' tried to view devices data for user GUID ' . $load_user_id);
            throw new \Exception('Page not found');
        } else {
            $default_type = 'device';
        }

        $type = isset($this->request->getQuery()['type']) ? $this->request->getQuery()['type'] : $default_type;

        if ($load_user_id) {
            $type = 'device';

            $load_user = EntUsers::findFirst([
                'conditions' => 'GUID = ?1',
                'bind'       => [
                    1 => $load_user_id,
                ],
                'cache'      => 60,
            ]);

            // first check if current admin has permission to manage this user

            if ($user->role->name != 'admin' || $user->organization->pk_id != $load_user->organization->pk_id) {
                // permission error
                $this->logger->info('[USER & DEVICE] User ' . $user->pk_id . ' tried to access devices view of user ' . $load_user->pk_id);
                throw new \Exception('Invalid request');
            }
        }

        if ($type == 'device') {
            //--- get device count ---
            $device_count = $stats->deviceCount($user_id);

            $loading_message = 'Loading your device list...';

            $title = 'Devices <span class="text-light-grey">' . $device_count . '</span>';
        } else {
            //--- get user info ---
            // $user_info = $stats->getOrgDevicesPerUser($organization->pk_id);

            $total_device_count = 0;
            foreach ($user_info as $user) {
                $total_device_count += $user['device_count'];
            }

            $loading_message = 'Loading your user list...';

            $title = count($user_info) . (count($user_info) == 1 ? ' User' : ' Users') . ' with ';
            $title .= $total_device_count . ($total_device_count == 1 ? ' Device' : ' Devices');
        }

        $data = [
            'loading_message'   => $loading_message,
            'type'              => $type,
            'title'             => $title,
            'load_user'         => $load_user,
        ];

        $this->view->setVars($data);
    }

    public function summaryAction()
    {
        $order_details = $this->persistent->order_details;
        unset($this->persistent->order_details);

        if (! $order_details) {
            return $this->response->redirect('user-device');
        }

        $this->view->setVars($order_details);
    }

    /**
     * global ajax handler
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

            $content = [];
            $type = $this->request->getPost('type');

            $stats = new DashboardStatistics();
            $helper = new AccountHelper();

            $stripe = new Stripe();

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

            if ($identity['role'] == 'user') {
                throw new \Exception('Normal user ' . $user_id . ' tried to access global management action.');
            }

            if (empty($content)) {
                if ($type == 'getSummary') {
                    $action = $this->request->getPost('action');

                    $current_subscription = $this->persistent->current_stripe_plan;
                    // $current_subscription->plan->amount
                    // $current_subscription->plan->interval
                    // $current_subscription->quantity

                    $current_price = $current_subscription->items->data[0]->plan->amount;
                    $interval = $current_subscription->items->data[0]->plan->interval == 'month' ? 'm' : 'y';
                    $current_quantity = $current_subscription->items->data[0]->quantity;

                    // $introductory = strpos(strtolower($current_subscription->items->data[0]->plan->name), 'introductory') !== false;
                    // $oldPricingHelper = new PricingHelper($current_quantity, $introductory);

                    // $tier_price = $oldPricingHelper->getPricing('original') / $current_quantity;

                    $device_change = 0;

                    if ($action == 'remove') {
                        $entry = $this->request->getPost('entry');

                        if ($entry['type'] == 'device') {
                            $device_change = -1;
                        } else if ($entry['type'] == 'user') {
                            $remove_user = EntUsers::findFirst([
                                'conditions' => 'GUID = ?1',
                                'bind'       => [
                                    1 => $entry['id'],
                                ],
                                'cache'      => 60,
                            ]);

                            $device_change = 0 - $remove_user->getDeviceCount();
                        }

                        $content['change'] = $device_change . ($device_change == -1 ? ' Device' : ' Devices') . ' @ $' . number_format($current_price * $device_change / 100, 2) . ' p/' . $interval;

                    } else if ($action == 'remove_slots') {
                        $entry = $this->request->getPost('entry');
                        $device_change = 0 - $entry['type'];

                        $content['change'] = $device_change . ($device_change == -1 ? ' Device' : ' Devices') . ' @ -$' . number_format($current_price * $device_change / -100, 2) . ' p/' . $interval;
                    }

                    $final_device_count = $current_quantity + $device_change;

                    $introductory = strpos(strtolower($current_subscription->plan->name), 'introductory') !== false;

                    // $this->logger->info('[TEST] ' . $current_subscription->plan->name . ' : ' . $current_subscription->plan->amount . ' : ' . $final_device_count);

                    $pricingHelper = new PricingHelper($final_device_count, $introductory);
                    if ($this->persistent->current_stripe_plan->discount) {
                        $pricingHelper->setCoupon($this->persistent->current_stripe_plan->discount->coupon);

                        $content['coupon'] = $pricingHelper->getCouponDescription();
                        $content['discount'] = '- $' . number_format($pricingHelper->getPricing('discounted'), 2) . ' p/m';
                    }

                    $coupon_id = isset($current_subscription->discount) ? $current_subscription->discount->coupon : false;
                    $subscription_id = $current_subscription->id;

                    $subscription_items = [
                        [
                            'id'        => $current_subscription->items->data[0]->id,
                            'quantity'  => $final_device_count,
                        ],
                    ];

                    // calculate tier pricing based on upcoming invoice - to get price after coupon applied
                    $upcoming_invoice = $stripe->getUpcomingInvoice($user->organization->stripe_customer_id, $coupon_id, $subscription_id,  $subscription_items);
                    if ($upcoming_invoice) {
                        $final_amount = $upcoming_invoice->amount_due;
                    } else {
                        $this->logger->error('[USER & DEVICE] Error while fetching summary : ' . $this->session->get('stripe_error'));
                        $this->session->remove('stripe_error');
                        $final_amount = 0;
                    }

                    // $newPricingHelper = new PricingHelper($final_device_count, $introductory);
                    // if ($this->persistent->current_stripe_plan->discount) {
                    //     $newPricingHelper->setCoupon($this->persistent->current_stripe_plan->discount->coupon);
                    // }

                    $content['current'] = $current_quantity . ($current_quantity > 1 ? ' Devices' : ' Device') . ' @ $' . number_format($current_price * $current_quantity / 100, 2) . ' p/' . $interval;

                    // $content['total'] = '$' . number_format($tier_price * ($final_device_count), 2) . ' p/' . $interval;
                    $content['total'] = '$' . number_format($final_amount / 100, 2) . ' p/' . $interval;

                    $content['next_cycle'] = date('F jS', $current_subscription->current_period_end);

                    $content['status'] = 'success';

                } else {
                    // action not found
                    $response->setStatusCode(400, 'Bad Request');
                }
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

    /**
     * manage devices ajax handler
     */
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

            $content = [];
            $type = $this->request->getPost('type');

            $stats = new DashboardStatistics();
            $billing = new BillingHelper();
            $helper = new DeviceHelper();
            $stripe = new Stripe();

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

            $device_id = $this->request->getPost('device_id');

            $device = false;
            if ($device_id) {
                $device = EntAgent::findFirst([
                    'conditions' => 'pk_id = ?1',
                    'bind'       => [
                        1 => $device_id,
                    ],
                    'cache'      => 60,
                ]);

                if (! $device) {
                    $content = [
                        'status'    => 'fail',
                        'error'     => 'Device not found',
                    ];
                }
            }

            if (empty($content)) {
                if ($type == 'list') {
                    // get current devices

                    $is_own = true; // whether the device list is for current user's
                    $invitations = $user_slots = [];

                    $load_user_id = $this->request->getPost('user_id');

                    if ($identity['role'] == 'user') {
                        // load devices for user only
                        $device_status = $stats->deviceStatus($user->GUID);
                    } else if (! $load_user_id) {
                        // load devices for organization
                        $options = [
                            'type'      => 'organization',
                        ];
                        $device_status = $stats->deviceStatus($organization->pk_id, $options);

                        // pending invitations
                        $invitations = $stats->pendingInvitations($organization->pk_id);

                        // other users' available slots
                        $org_status = $stats->getOrgDevicesPerUser($organization->pk_id, 'fetchAll', true);

                        foreach ($org_status as $org_user) {
                            if ($org_user['user_id'] != $user->GUID && $org_user['is_active']) {
                                $user_slots[] = $org_user;
                            }
                        }
                    } else {
                        // load devices for specific user

                        // first check if current admin has permission to manage this user

                        $load_user = EntUsers::findFirst([
                            'conditions' => 'GUID = ?1',
                            'bind'       => [
                                1 => $load_user_id,
                            ],
                            'cache'      => 60,
                        ]);

                        if ($user->role->name != 'admin' || $user->organization->pk_id != $load_user->organization->pk_id) {
                            // permission error
                            $this->logger->info('[USER & DEVICE] User ' . $user->pk_id . ' tried to access devices view of user ' . $load_user->pk_id);
                            throw new \Exception('Invalid request');
                        }

                        $is_own = ($user->pk_id == $load_user->pk_id);

                        $device_status = $stats->deviceStatus($load_user_id);
                    }

                    foreach ($device_status as $key => $status) {
                        if (! $status['datetime_disconnected'] && $status['datetime_connected']) {
                            $tooltip = 'Todyl Defender is currently running on this device';
                        } else {
                            $tooltip = 'Todyl Defender is not currently running on this device';
                        }

                        $device_status[$key]['tooltip'] = $tooltip;

                        // unset unnecessary fields
                        unset($device_status[$key]['UDID']);

                        if ($status['user_id'] == $user_id) {
                            $device_status[$key]['is_own_device'] = 1;
                        } else {
                            $device_status[$key]['is_own_device'] = 0;
                        }
                    }

                    $current_device_count = EntAgent::count([
                        'conditions' => 'fk_ent_users_id = ?1 AND is_active = 1 AND is_deleted = 0 AND pin_used = 1',
                        'bind'       => [
                            1 => $load_user_id ? $load_user->pk_id : $user->pk_id,
                        ],
                        'cache'      => false,
                    ]);

                    // available device slots
                    if ($is_own) {
                        // $this->logger->info('[USER & DEVICE] Is Admin - Total : ' . $user->getDeviceCount() . ', Used : ' . $current_device_count);
                        $available_slots = $user->getDeviceCount() - $current_device_count;
                    } else {
                        // $this->logger->info('[USER & DEVICE] Is User - Total : ' . $load_user->getDeviceCount() . ', Used : ' . $current_device_count);
                        $available_slots = $load_user->getDeviceCount() - $current_device_count;
                    }

                    $result_user = $is_own ? $user : $load_user;

                    $content = [
                        'status'            => 'success',
                        'user_id'           => $result_user->GUID,
                        'user_email'        => $result_user->email,
                        'devices'           => $device_status,
                        'invitations'       => $invitations,
                        'available_slots'   => $available_slots,
                        'user_slots'        => $user_slots,
                        'is_own'            => $is_own,
                    ];

                } else if ($type == 'rename') {
                    try {
                        $old_device_name = $device->user_device_name;

                        $new_device_name = $this->request->getPost('device_name');
                        $device->user_device_name = $new_device_name;

                        if ($device->update()) {
                            $stats->deviceStatus($user_id, [], 'flush');

                            $this->logger->info('[USER & DEVICE] Successfully renamed device ID ' . $device->pk_id . ' from `' . $old_device_name . '` to `' . $new_device_name . '`');

                            $content = [
                                'status'    => 'success',
                                'message'   => $old_device_name . ' has been renamed to ' . $new_device_name . '.',
                            ];
                        } else {
                            $this->logger->error('[USER & DEVICE] Error while renaming device ID ' . $device->pk_id . ' : ' . implode("\n", $device->getMessages()));

                            $content = [
                                'status'    => 'fail',
                                'message'   => 'There was an error while renaming the device.',
                            ];
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('[USER & DEVICE] Device Rename Exception: ' . $e->getMessage() . ' with trace: ' . (string) $e->getTraceAsString());

                        $content = [
                            'status'    => 'fail',
                            'message'   => 'An error occurred while renaming ' . $device->user_device_name,
                        ];
                    }

                } else if ($type == 'install') {
                    $install_user_id = $this->request->getPost('user_id');
                    $direct_install = $this->request->getPost('direct_install');

                    if (! $install_user_id) {
                        $install_user_id = $user->GUID;
                        $install_user = $user;
                    } else {
                        $install_user = EntUsers::findFirst([
                            'conditions' => 'GUID = ?1',
                            'bind'       => [
                                1 => $install_user_id,
                            ],
                            'cache'      => 60,
                        ]);
                    }

                    try {
                        if ($direct_install == '1') {
                            $GUID = $helper->getMagicLink($user_id);

                            if ($GUID) {
                                $content = [
                                    'status'    => 'success',
                                    'GUID'      => $GUID,
                                ];
                            } else {
                                $content = [
                                    'status'    => 'fail',
                                    'message'   => 'There was an error while processing your request.',
                                ];
                            }
                        } else {
                            // send instruction
                            if ($helper->getPin($install_user_id, true)) {
                                $content = [
                                    'status'    => 'success',
                                    'message'   => 'Installations instructions were sent to ' . $install_user->email,
                                ];
                            } else {
                                $content = [
                                    'status'    => 'fail',
                                    'message'   => 'There was an error while processing your request.',
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('[USER & DEVICE] Device Installation Exception: ' . $e->getMessage() . ' with trace: ' . (string) $e->getTraceAsString());

                        $content = [
                            'status'    => 'fail',
                            'message'   => 'An error occurred while reinstalling ' . $device->user_device_name,
                        ];
                    }

                } else if ($type == 'reinstall') {
                    try {
                        $device->is_active = 0;

                        if ($device->update()) {
                            $GUID = $helper->getMagicLink($user_id, true);

                            if ($GUID) {
                                $stats->deviceStatus($user_id, [], 'flush');

                                $content = [
                                    'status'    => 'success',
                                    'GUID'      => $GUID,
                                ];
                            } else {
                                $content = [
                                    'status'    => 'fail',
                                    'message'   => 'There was an error while reinstalling the device.',
                                ];
                            }
                        } else {
                            $content = [
                                'status'    => 'fail',
                                'message'   => 'There was an error while reinstalling the device.',
                            ];
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('[USER & DEVICE] Device Reinstall Exception: ' . $e->getMessage() . ' with trace: ' . (string) $e->getTraceAsString());

                        $content = [
                            'status'    => 'fail',
                            'message'   => 'An error occurred while reinstalling ' . $device->user_device_name,
                        ];
                    }

                } else if ($type == 'remove') {
                    try {
                        // update stripe subscription first
                        $update_result = $billing->updateSubscriptionQuantity($organization, 'device', '-1');

                        if ($update_result['status'] == 'success') {
                            $new_subscription = $update_result['subscription'];

                            // stripe update success - update DB
                            $device->is_active = 0;
                            $device->is_deleted = 1;

                            if ($device->update()) {
                                $stats->deviceStatus($user_id, [], 'flush');

                                $current_subscription = $this->persistent->current_stripe_plan->items->data[0];

                                // $tier_price = $current_subscription->plan->amount;
                                $interval = $current_subscription->plan->interval == 'month' ? 'm' : 'y';
                                $current_quantity = $current_subscription->quantity ? $current_subscription->quantity : 0;
                                $current_price = $current_subscription->plan->amount;

                                $coupon_id = isset($this->persistent->current_stripe_plan->discount) ? $this->persistent->current_stripe_plan->discount->coupon : false;
                                $subscription_id = $this->persistent->current_stripe_plan->id;

                                $subscription_items = [
                                    [
                                        'id'        => $current_subscription->id,
                                        'quantity'  => $current_quantity - 1,
                                    ],
                                ];

                                // calculate tier pricing based on upcoming invoice - to get price after coupon applied
                                $upcoming_invoice = $stripe->getUpcomingInvoice($user->organization->stripe_customer_id, $coupon_id, $subscription_id,  $subscription_items);
                                $final_amount = $upcoming_invoice->amount_due;

                                // $introductory = strpos(strtolower($current_subscription->plan->name), 'introductory') !== false;
                                // $oldPricingHelper = new PricingHelper($current_quantity, $introductory);
                                // $tier_price = $oldPricingHelper->getPricing('original') / $current_quantity;

                                $order_label = 'Removed: Todyl Protection for 1 Device';

                                $order_amount = '-$' . number_format($current_price / 100, 2) . ' p/' . $interval;

                                if ($device->user_device_name) {
                                    $device_name = $device->user_device_name;
                                    $device_name .= '&nbsp;&nbsp;<span class="text-mid-grey">' . $device->user->email . '</span>';
                                } else {
                                    $device_name = $device->user->email;
                                }

                                // $newPricingHelper = new PricingHelper($current_quantity - 1, $introductory);
                                // if ($this->persistent->current_stripe_plan->discount) {
                                //     $newPricingHelper->setCoupon($this->persistent->current_stripe_plan->discount->coupon);
                                // }

                                $order_details = [
                                    'summary_title' => 'You\'ve successfully removed 1 device. Your subscription has been updated.',

                                    'change_title'  => 'Devices Removed',
                                    'change_label'  => $device_name,
                                    'change_value'  => '1 Device',

                                    'order_label'   => $order_label,
                                    'order_amount'  => $order_amount,
                                    'total'         => '$' . number_format($final_amount / 100, 2),
                                ];

                                $this->persistent->order_details = $order_details;

                                $this->persistent->current_stripe_plan = $new_subscription;

                                // send subscription update email

                                $email_data = [
                                    'left_header'       => 'Previous',
                                    'right_header'      => 'Update',
                                    'left_value'        => $current_quantity . ($current_quantity == 1 ? ' Device' : ' Devices') . ' Protected',
                                    'right_value'       => ($current_quantity - 1) . ($current_quantity == 2 ? ' Device' : ' Devices') . ' Protected',
                                ];

                                $this->mail->sendMail($user->pk_id, 'subscription-update', $email_data);

                                // if ($user->pk_id != $device->user->pk_id) {
                                //     // send see my devices email

                                //     $admin_name = ($user->firstName || $user->lastName) ? $user->firstName . ' ' . $user->lastName : $user->email;

                                //     $email_data = [
                                //         'admin_name'        => $admin_name,
                                //         'magic_link'        => 'user-device',
                                //     ];

                                //     $this->mail->sendMail($user->pk_id, 'add-device', $email_data);
                                // }

                                $this->logger->info('[USER & DEVICE] Removed one device for user ID ' . $user->pk_id);

                                $content = [
                                    'status'    => 'success',
                                    'message'   => $device->user_device_name . ' has been removed successfully. Your changes may take up to 30 seconds to reflect.',
                                ];
                            } else {
                                $this->logger->error('[USER & DEVICE] An error occured while updating the subscription');

                                $content = [
                                    'status'    => 'fail',
                                    'message'   => 'There was an error while removing the device.',
                                ];
                            }

                        } else {
                            $this->logger->error('[USER & DEVICE] An error occured while updating the DB');

                            $content = [
                                'status'    => 'fail',
                                'message'   => 'There was an error while removing the device.',
                            ];
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('[USER & DEVICE] Device Removal Exception: ' . $e->getMessage() . ' with trace: ' . (string) $e->getTraceAsString());

                        $content = [
                            'status'    => 'fail',
                            'message'   => 'An error occurred while removing ' . $device->user_device_name,
                        ];
                    }
                } else {
                    // action not found
                    $response->setStatusCode(400, 'Bad Request');
                }
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

    /**
     * manage users ajax handler
     */
    public function manageUserAction()
    {
        $this->view->disable();
        $content = [
            'message' => 'Oops! Something went wrong. Please try again later.',
        ];

        $response = new \Phalcon\Http\Response();
        $response->setStatusCode(400, 'Bad Request');

        if ($this->request->isPost() && $this->request->isAjax()) {
            $response->setStatusCode(200);

            $content = [];
            $type = $this->request->getPost('type');

            $stats = new DashboardStatistics();
            $billing = new BillingHelper();
            $helper = new AccountHelper();
            $deviceHelper = new DeviceHelper();
            $stripe = new Stripe();

            $identity = $this->auth->getIdentity();
            $admin_id = $identity['id'];

            $admin = EntUsers::findFirst([
                'conditions' => 'pk_id = ?1',
                'bind'       => [
                    1 => $admin_id,
                ],
                'cache'      => 60,
            ]);

            $organization = $admin->getOrganization([ 'cache' => 60 ]);

            if ($identity['role'] == 'user') {
                throw new \Exception('Normal user ' . $admin_id . ' tried to access user management action.');
            }

            $user_id = $this->request->getPost('user_id');

            $user = false;
            if ($user_id) {
                $user = EntUsers::findFirst([
                    'conditions' => 'GUID = ?1',
                    'bind'       => [
                        1 => $user_id,
                    ],
                    'cache'      => 60,
                ]);

                if (! $user) {
                    $content = [
                        'status'    => 'fail',
                        'error'     => 'User not found',
                    ];
                }
            }

            if (empty($content)) {
                if ($type == 'list') {
                    // get current users
                    $user_info = $stats->getOrgDevicesPerUser($organization->pk_id);

                    $users = [];
                    foreach ($user_info as $user) {
                        if ($user['user_name'] == ' ') {
                            // user doesn't have name
                            $user['initials'] = strtoupper(substr($user['user_email'], 0, 1));
                        } else {
                            // user has name
                            $user['initials'] = strtoupper(substr($user['firstName'], 0, 1) . substr($user['lastName'], 0, 1));
                        }

                        if ($user['user_id'] == $admin->GUID) {
                            $user['is_own'] = 1;
                        } else {
                            $user['is_own'] = 0;
                        }

                        $users[] = $user;
                    }

                    $content = [
                        'status'            => 'success',
                        'users'             => $users,
                    ];

                } else if ($type == 'remove') {
                    try {
                        $userDeviceCount = $user->getDeviceCount();

                        // update stripe subscription first
                        $update_result = $billing->updateSubscriptionQuantity($organization, 'device', '-' . $userDeviceCount);

                        if ($update_result['status'] == 'success') {
                            $new_subscription = $update_result['subscription'];

                            // save current active status (to prevent emailing invited users)
                            $was_active = $user->is_active;

                            // stripe update success - update DB
                            $user->is_invited = 0;
                            $user->is_active = 0;
                            $user->is_deleted = 1;

                            if ($user->update()) {
                                // remove all devices of user
                                foreach ($user->agents as $user_agent) {
                                    $user_agent->is_active = 0;
                                    $user_agent->is_deleted = 1;
                                    $user_agent->save();
                                }

                                $stats->deviceStatus($admin_id, [], 'flush');

                                $current_subscription = $this->persistent->current_stripe_plan->items->data[0];

                                $current_price = $current_subscription->plan->amount;
                                $interval = $current_subscription->plan->interval == 'month' ? 'm' : 'y';
                                $current_quantity = $current_subscription->quantity ? $current_subscription->quantity : 0;

                                $coupon_id = isset($this->persistent->current_stripe_plan->discount) ? $this->persistent->current_stripe_plan->discount->coupon : false;
                                $subscription_id = $this->persistent->current_stripe_plan->id;

                                $target_quantity = $current_quantity - $userDeviceCount;

                                $subscription_items = [
                                    [
                                        'id'        => $current_subscription->id,
                                        'quantity'  => $target_quantity,
                                    ],
                                ];

                                // calculate tier pricing based on upcoming invoice - to get price after coupon applied
                                $upcoming_invoice = $stripe->getUpcomingInvoice($admin->organization->stripe_customer_id, $coupon_id, $subscription_id,  $subscription_items);
                                $final_amount = $upcoming_invoice->amount_due;

                                $order_label = 'Removed: Todyl Protection for ' . $userDeviceCount;
                                $order_label .= ($userDeviceCount == 1) ? ' Device' : ' Devices';

                                $order_amount = '-$' . number_format($current_price * $userDeviceCount / 100, 2) . ' p/' . $interval;

                                if ($user->firstName || $user->lastName) {
                                    $user_name = $user->firstName . ' ' . $user->lastName;
                                    $user_name .= '<span class="text-mid-grey">' . $user->email . '</span>';
                                } else {
                                    $user_name = $user->email;
                                }

                                $order_details = [
                                    'summary_title' => 'You\'ve successfully removed 1 user. Your subscription has been updated.',

                                    'change_title'  => 'Users Removed',
                                    'change_label'  => $user_name,
                                    'change_value'  => $userDeviceCount . (($userDeviceCount == 1) ? ' Device' : ' Devices'),

                                    'order_label'   => $order_label,
                                    'order_amount'  => $order_amount,
                                    'total'         => '$' . number_format($final_amount / 100, 2),
                                ];

                                $this->persistent->order_details = $order_details;

                                $this->persistent->current_stripe_plan = $new_subscription;

                                $this->logger->info('[USER & DEVICE] User ID ' . $user->pk_id . ' has been removed by admin ID ' . $admin_id);

                                // if ($was_active) {
                                    // send suspension email to user
                                    // $admin_name = ($admin->firstName || $admin->lastName) ? $admin->firstName . ' ' . $admin->lastName : $admin->email;

                                    $emailData = [
                                        'admin_name' => $admin->getName(),
                                    ];

                                    $this->mail->sendMail($user->pk_id, 'suspension-user', $emailData);
                                // }

                                // send subscription update email

                                $email_data = [
                                    'left_header'       => 'Previous',
                                    'right_header'      => 'Update',
                                    'left_value'        => $current_quantity . ($current_quantity == 1 ? ' Device' : ' Devices') . ' Protected',
                                    'right_value'       => $target_quantity . ($target_quantity == 1 ? ' Device' : ' Devices') . ' Protected',
                                ];

                                $this->mail->sendMail($admin->pk_id, 'subscription-update', $email_data);

                                $content = [
                                    'status'    => 'success',
                                    'message'   => $user->email . ' has been removed successfully. Your changes may take up to 30 seconds to reflect.',
                                ];
                            } else {
                                $this->logger->error('[USER & DEVICE] Error while removing user ID ' . $user->pk_id . ' from DB.');

                                $content = [
                                    'status'    => 'fail',
                                    'message'   => 'There was an error while removing the device.',
                                ];
                            }

                        } else {
                            $this->logger->error('[USER & DEVICE] Error while removing user ID ' . $user->pk_id . ' from Stripe subscription.');

                            $content = [
                                'status'    => 'fail',
                                'message'   => 'There was an error while removing the device.',
                            ];
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('[USER & DEVICE] User Removal Exception: ' . $e->getMessage() . ' with trace: ' . (string) $e->getTraceAsString());

                        $content = [
                            'status'    => 'fail',
                            'message'   => 'An error occurred while removing ' . $user->email,
                        ];
                    }

                } else if ($type == 'remove_slots') {
                    $remove_slot_cnt = $this->request->getPost('slot_cnt');

                    $this->logger->info('[USER & DEVICE] Start removing ' . $remove_slot_cnt . ' slot(s) for user ID ' . $user->pk_id);

                    try {
                        // update stripe subscription first
                        $update_result = $billing->updateSubscriptionQuantity($organization, 'device', '-' . $remove_slot_cnt);

                        if ($update_result['status'] == 'success') {
                            $new_subscription = $update_result['subscription'];

                            $current_subscription = $this->persistent->current_stripe_plan->items->data[0];

                            $current_price = $current_subscription->plan->amount;
                            $interval = $current_subscription->plan->interval == 'month' ? 'm' : 'y';
                            $current_quantity = $current_subscription->quantity ? $current_subscription->quantity : 0;

                            $coupon_id = isset($this->persistent->current_stripe_plan->discount) ? $this->persistent->current_stripe_plan->discount->coupon : false;
                            $subscription_id = $this->persistent->current_stripe_plan->id;

                            // get user's current quantity instead of org's
                            $current_quantity = $user->getDeviceCount();

                            $target_quantity = $current_quantity - $remove_slot_cnt;

                            // save current active status (to prevent emailing invited users)
                            $was_active = $user->is_active;

                            $this->logger->info('[USER & DEVICE] Updating user device count from ' . $current_quantity . ' to ' . $target_quantity . ' for user ID ' . $user->pk_id);

                            // stripe update success - update DB
                            $device_update_result = $deviceHelper->updateDeviceCount($user->GUID, $target_quantity, false, true);

                            if (is_array($device_update_result)) {
                                $device_changes[] = [
                                    'label'     => $device_update_result['user_email'],
                                    'value'     => ($device_update_result['added_devices'] ? $device_update_result['added_devices'] : 0) . ($device_update_result['added_devices'] == 1 ? ' Device' : ' Devices'),
                                ];

                                $stats->deviceStatus($admin_id, [], 'flush');

                                $subscription_items = [
                                    [
                                        'id'        => $current_subscription->id,
                                        'quantity'  => $target_quantity,
                                    ],
                                ];

                                // calculate tier pricing based on upcoming invoice - to get price after coupon applied
                                $upcoming_invoice = $stripe->getUpcomingInvoice($admin->organization->stripe_customer_id, $coupon_id, $subscription_id,  $subscription_items);
                                $final_amount = $upcoming_invoice->amount_due;

                                $order_label = 'Removed: Todyl Protection for ' . $remove_slot_cnt;
                                $order_label .= ($remove_slot_cnt == 1) ? ' Device' : ' Devices';

                                $order_amount = '-$' . number_format($current_price * $remove_slot_cnt / 100, 2) . ' p/' . $interval;

                                if ($user->firstName || $user->lastName) {
                                    $user_name = $user->firstName . ' ' . $user->lastName;
                                    $user_name .= ' <span class="text-mid-grey">' . $user->email . '</span>';
                                } else {
                                    $user_name = $user->email;
                                }

                                $order_details = [
                                    'summary_title' => 'You\'ve successfully removed ' . (($remove_slot_cnt == 1) ? ' device' : ' devices') . ' for ' . $user->email,

                                    'change_title'  => 'Devices Removed',
                                    'change_label'  => $user_name,
                                    'change_value'  => $remove_slot_cnt . (($remove_slot_cnt == 1) ? ' Device' : ' Devices'),

                                    'order_label'   => $order_label,
                                    'order_amount'  => $order_amount,
                                    'total'         => '$' . number_format($final_amount / 100, 2),
                                ];

                                $this->persistent->order_details = $order_details;

                                $this->persistent->current_stripe_plan = $new_subscription;

                                $this->logger->info('[USER & DEVICE] ' . $remove_slot_cnt . ' device slots have been removed from User ID ' . $user->pk_id . ' by admin ID ' . $admin_id);

                                // send device count updated email to user
                                $emailData = [
                                    'admin_name' => $admin->getName(),
                                ];

                                if ($user->pk_id != $admin->pk_id) {
                                    $this->mail->sendMail($user->pk_id, 'device-changed', $emailData);
                                }

                                // send subscription update email

                                $email_data = [
                                    'left_header'       => 'Previous',
                                    'right_header'      => 'Update',
                                    'left_value'        => $current_quantity . ($current_quantity == 1 ? ' Device' : ' Devices') . ' Protected',
                                    'right_value'       => $target_quantity . ($target_quantity == 1 ? ' Device' : ' Devices') . ' Protected',
                                ];

                                $this->mail->sendMail($admin->pk_id, 'subscription-update', $email_data);

                                $content = [
                                    'status'    => 'success',
                                    'message'   => $user->email . ' has been removed successfully. Your changes may take up to 30 seconds to reflect.',
                                ];

                            } else {
                                // device slots removal fail

                                $this->logger->error('[USER & DEVICE] Error while removing slots for user ID ' . $user->pk_id . ' from DB.');

                                $content = [
                                    'status'    => 'fail',
                                    'message'   => 'There was an error while removing the slots.',
                                ];
                            }

                        } else {
                            $this->logger->error('[USER & DEVICE] Error while removing user ID ' . $user->pk_id . ' from Stripe subscription.');

                            $content = [
                                'status'    => 'fail',
                                'message'   => 'There was an error while removing the device.',
                            ];
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('[USER & DEVICE] User Removal Exception: ' . $e->getMessage() . ' with trace: ' . (string) $e->getTraceAsString());

                        $content = [
                            'status'    => 'fail',
                            'message'   => 'An error occurred while removing ' . $user->email,
                        ];
                    }
                } else {
                    // action not found
                    $response->setStatusCode(400, 'Bad Request');
                }
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }


    public function ftuAction()
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

            $identity = $this->auth->getIdentity();
            $user_id = $identity['id'];

            if ($type == 'pass') {
                $helper = new FtuHelper();

                $add_ftu = $helper->addFtuHistory($user_id, 'userdevice', 'page_info');

                if ($add_ftu['status'] == 'success') {
                    $content = [
                        'status'            => 'success',
                    ];
                } else {
                    $content = [
                        'status'            => 'fail',
                        'error'             => 'There was an error while processing your action.',
                        // 'error'             => implode('<br />', $add_ftu['error']),
                    ];
                }
            } else {
                // action not found
                $response->setStatusCode(400, 'Bad Request');
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

}
