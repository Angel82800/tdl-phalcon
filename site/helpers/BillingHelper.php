<?php
/**
 * Helper library for handling stripe billings
 * this helper won't deal with thrust data
 */

namespace Thrust\Helpers;

use Thrust\Stripe\Api as Stripe;

use Thrust\Models\EntSettings;
use Thrust\Models\EntStripeSubscription;

class BillingHelper
{
    protected $config;
    protected $settings;
    protected $di;
    protected $logger;
    protected $stripe;
    protected $session;

    function __construct()
    {
        $this->config = \Phalcon\Di::getDefault()->get('config');
        $this->di = \Phalcon\DI::getDefault();
        $this->logger = \Phalcon\Di::getDefault()->getShared('logger');
        $this->session = \Phalcon\Di::getDefault()->getShared('session');

        // get settings
        $settings = EntSettings::find([
            'is_active' => true,
            'cache'     => 60,
        ]);

        $this->settings = [];
        foreach ($settings as $setting) {
            $this->settings[$setting->key] = $setting->value;
        }

        $this->stripe = new Stripe();
    }

    /**
     * Process organization suspension
     * @param  Object $organization - Thrust\Models\EntOrganization instance
     * @return mixed
     */
    public function suspendOrganization($organization)
    {
        /**
         * - Start - Suspend Stripe Customer (with subscription cancel)
         *
         **/

        // $subscription = $this->stripe->cancelSubscription($organization->stripe_customer_id);

        // retrieve stripe subscription details
        $subscription = $this->getSubscriptionForType($organization->id, 'device');

        // cancel subscription
        $cancel_result = $this->stripe->cancelSubscription($organization->stripe_customer_id);

        if (! $cancel_result) {
            $stripeError = $this->session->get('stripe_error');
            $this->session->remove('stripe_error');

            return $stripeError;
        }

        $this->logger->info('[BILLING] Cancelled subscription for org ID ' . $organization->pk_id);

        return true;
        // update organization table
        // $organization->suspend_date = date('Y-m-d H:i:s', $suspend_date);
        // $organization->execute_time = null;
        // $organization->reactivated = null;
        // $organization->is_active = false;

        // if ($organization->update()) {
        //     return true;
        // } else {
        //     return $organization->getMessages();
        // }

        /**
         * - End - Suspend Stripe Customer (with subscription cancel)
         *
         **/
    }

    public function reactivateCustomer($organization, $user, $account_data)
    {
        $token = $account_data['card_token'];

        if ($token) {
            // stripe card update

            $this->logger->info('[BILLING] Updating customer billing information');

            $customer = $this->stripe->updateCustomer($organization->stripe_customer_id, $token, $user->email, $organization->name);
        } else {
            $customer = $this->stripe->getCustomer($organization->stripe_customer_id);
        }

        if (! $customer) {
            $stripeError = $this->session->get('stripe_error');
            $this->session->remove('stripe_error');

            return $stripeError;
        }

        $device_plan = $this->settings['device_plan'];

        $plans = [
            [
                'plan'      =>  $device_plan,
                'quantity'  =>  1,
            ],
        ];

        $subscription = $this->stripe->subscribeCustomer($organization->stripe_customer_id, $plans, $account_data['promo_code']);

        if (! $subscription) {
            $stripeError = $this->session->get('stripe_error');
            $this->session->remove('stripe_error');

            return $stripeError;
        }

        $this->logger->info('[BILLING] Reactivated subscription for org ID ' . $organization->pk_id);

        return true;
    }

    public function updateSubscriptionQuantity($organization, $plan, $quantity)
    {
        $subscription = $this->getSubscriptionForType($organization, $plan);

        if (! $subscription) {
            // no subscription fetched
            $this->logger->info('[BILLING] Couldn\'t fetch subscription for org ID ' . $organization->pk_id . ' for `' . $plan . '` type');
            return false;
        }

        $old_quantity = $subscription->items->data[0]->quantity;

        $operation = substr($quantity, 0, 1);

        if ($operation == '-') {
            $target_quantity = $subscription->items->data[0]->quantity - substr($quantity, 1);
        } else if ($operation == '+') {
            $target_quantity = $subscription->items->data[0]->quantity + substr($quantity, 1);
        } else {
            $target_quantity = $quantity;
        }

        if ($target_quantity != intval($target_quantity)) {
            // fix non-integer error of stripe subscription update
            $this->logger->info('[BILLING] Target quantity for org ID ' . $organization->pk_id . ' in corrupt format : ' . $target_quantity);
            $target_quantity = intval($target_quantity);
        }

        $plan = $this->getPlanInfo($plan, 'id', $target_quantity);

        // change customer stripe plans - for now just change the plan quantity
        $data = [
            'items' => [
                [
                    'id'        => $subscription->items->data[0]->id,
                    'plan'      => $plan,
                    'quantity'  => $target_quantity,
                ],
            ],
        ];

        $new_subscription = $this->stripe->updateSubscription($subscription->id, $data);

        if (! $new_subscription) {
            $this->logger->error('[BILLING] There has been an error while updating subscription for org ' . $organization->pk_id);

            $result = [
                'status'    => 'fail',
                'message'   => $this->session->get('stripe_error'),
            ];

            $this->session->remove('stripe_error');

            return $result;
        } else {
            $this->logger->info('[BILLING] Updated stripe subscription of org ' . $organization->pk_id . ' from ' . $old_quantity . ' to ' . $target_quantity);

            $result = [
                'status'        => 'success',
                'subscription'  => $new_subscription,
            ];

            $this->session->remove('stripe_error');

            return $result;
        }
    }

    /**
     * get stripe subscription object for specified service type
     * @param  Object           $subscriber     subscriber (org, user) object
     * @param  String           $plan           plan identifier to map
     * @param  String           $subscribe_type subscriber type (organization, user)
     * @return \Stripe\Subscription             subscription object for type
     */
    public function getSubscriptionForType($subscriber, $plan, $subscribe_type = 'organization')
    {
        try {
            // if (isset($this->settings[$plan])) $plan_name = $this->settings[$plan];
            // else if (isset($this->settings[$plan . '_plan'])) $plan_name = $this->settings[$plan . '_plan'];
            // else return false;

            $stripeSubscription = EntStripeSubscription::findFirst([
                'conditions' => 'subscriber_id = ?1 AND subscribe_level = ?2 AND plan_key = ?3',
                'bind'       => [
                    1 => $subscriber->pk_id,
                    2 => $subscribe_type,
                    3 => $plan,
                ],
                'cache'      => 60,
            ]);

            if ($stripeSubscription) {
                $subscriptionId = $stripeSubscription->stripe_subscription_id;
                $subscription = $this->stripe->getSubscription($subscriptionId);
                if ($subscription) {
                    return $subscription;
                } else {
                    return false;
                }
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error('[BILLING] Error while getting subscription : ' . $e->getMessage());

            return false;
        }
    }

    /**
     * get stripe plan info
     * @param  string  $plan  - plan identifier (device_info, ...)
     * @param  string  $key   - detailed value identifier
     * @param  integer $count - service count to determine the tier
     * @return mixed
     */
    public function getPlanInfo($plan_key, $key = false, $count = 0)
    {
        try {
            /* determine the setting key */

            $setting_key = $plan_key;
            $tier = 1;

            if ($count) {
                // tiered plan's setting key is in "{key}_plan_{tier}" format
                $setting_key .= '_plan_';

                // tiered plan's threshold setting key is in "{key}_threshold_{tier}" format
                $tier_setting_prefix = $plan_key . '_threshold_';

                // determine the tier
                while ($this->settings[$tier_setting_prefix . $tier] && $count > $this->settings[$tier_setting_prefix . $tier]) {
                    $tier ++;
                }

                $setting_key .= $tier == 1 ? $tier : $tier - 1;
            }

            /* setting key determined */

            if (isset($this->settings[$setting_key])) $plan_name = $this->settings[$setting_key];
            else if (isset($this->settings[$setting_key . '_plan'])) $plan_name = $this->settings[$setting_key . '_plan'];
            else if (isset($this->settings[$plan_key])) $plan_name = $this->settings[$plan_key];
            else return false;

            $plans = $this->stripe->listPlans();

            foreach ($plans->data as $plan) {
                if ($plan_name == $plan->id) {
                    $targetPlan = $plan;
                    break;
                }
            }

            if (! isset($targetPlan)) return false;

            if ($key) {
                return property_exists($key, $targetPlan) ? $targetPlan->{$key} : 0;
            } else {
                return $targetPlan;
            }
        } catch (\Exception $e) {
            $this->logger->error('[BILLING] Error while getting plan details : ' . $e->getMessage());

            return false;
        }
    }

}
