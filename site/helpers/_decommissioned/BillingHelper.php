<?php
/**
 * Helper library for handling stripe billings
 * this helper won't deal with thrust data
 */

namespace Thrust\Helpers;

use Thrust\Stripe\Api as Stripe;

class BillingHelper
{
    protected $config;
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
         * - Start - Suspend Stripe Customer (with coupon)
         **

        // retrieve stripe customer details
        $customer = $this->stripe->getCustomer($organization->stripe_customer_id);

        if (! $customer) {
            $stripeError = $this->session->get('stripe_error');
            $this->session->remove('stripe_error');

            return $stripeError;
        }

        // retrieve stripe subscription details
        $subscription = $this->stripe->getSubscription($customer->subscriptions->data[0]->id);

        if (! $subscription) {
            $stripeError = $this->session->get('stripe_error');
            $this->session->remove('stripe_error');

            return $stripeError;
        }

        // check if a coupon was already applied to the subscription
        $coupon = false;
        if ($subscription->discount && $subscription->discount->coupon) {
            $coupon = $subscription->discount->coupon->id;
        }

        // apply a 100% discount coupon to subscription
        $suspension_coupon = $this->config->stripe->suspension_coupon;

        if (! $this->stripe->applySubscriptionCoupon($subscription->id, $suspension_coupon)) {
            $stripeError = $this->session->get('stripe_error');
            $this->session->remove('stripe_error');

            return $stripeError;
        }

        // update organization
        if ($coupon && $coupon != $suspension_coupon) {
            $organization->coupon_before_deactivation = $coupon;
        } else {
            $organization->coupon_before_deactivation = null;
        }

        $organization->is_active = false;
        $organization->datetime_updated = date('Y-m-d H:i:s', $suspend_date);

        if ($organization->update()) {
            return true;
        } else {
            return $organization->getMessages();
        }
         *
         * - End - Suspend Stripe Customer (with coupon)
        **/

        /**
         * - Start - Suspend Stripe Customer (with subscription cancel)
         *
         * This will just set the suspend_on field for the organization to the end date of current month
         * The actual stripe subscription cancellation will be handled by a daily job
         *
         **/

        // $subscription = $this->stripe->cancelSubscription($organization->stripe_customer_id);

        // retrieve stripe customer details
        $customer = $this->stripe->getCustomer($organization->stripe_customer_id);

        if (! $customer) {
            $stripeError = $this->session->get('stripe_error');
            $this->session->remove('stripe_error');

            return $stripeError;
        }

        // retrieve stripe subscription details
        $subscription = $this->stripe->getSubscription($customer->subscriptions->data[0]->id);

        // calculate suspend date (current month in billing cycle)
        $suspend_date = strtotime(date('Y-m-') . date('d', $subscription->current_period_start));
        if ($suspend_date < time()) {
            // it's a date from previous month
            $suspend_date = strtotime('+1 month', $suspend_date);
        }

        // get last invoice for subscription
        $invoices = $this->stripe->getInvoices([
            'customer'      => $organization->stripe_customer_id,
            'subscription'  => $subscription->id,
            'limit'         => 1,
        ]);
        if (! $invoices) {
            $stripeError = $this->session->get('stripe_error');
            $this->session->remove('stripe_error');

            return $stripeError;
        }

        $invoice = $invoices->data[0];

        $cost = 0;
        foreach ($invoice->lines->data as $line) {
            if ($line->plan->interval == 'month') {
                // monthly plan - there's no need to prorate
            } else if ($line->plan->interval == 'year') {
                // annual plan - calculate proration after current month
                if ($line->period->start <= $suspend_date && $suspend_date <= $line->period->end) {
                    if ($line->amount > 0) {
                        $cost += $line->amount * ($line->period->end - $suspend_date) / ($line->period->end - $line->period->start);
                    }
                }
            }
        }

        if ($cost) {
            // refund proration amount to customer

            $refund = $this->stripe->createRefund($invoice->charge, $cost);

            if (! $refund) {
                $stripeError = $this->session->get('stripe_error');
                $this->session->remove('stripe_error');

                return $stripeError;
            }

            $this->logger->info('[BILLING] Refunding $' . number_format($cost / 100, 2) . ' to organization ' . $organization->pk_id);
        } else {
            // there's nothing to refund
            $this->logger->info('[BILLING] Suspending orgId ' . $organization->pk_id . ' but there\'s nothing to refund.');
        }

        // cancel subscription
        $cancel_result = $this->stripe->cancelSubscription($organization->stripe_customer_id, $suspend_date);

        if (! $cancel_result) {
            $stripeError = $this->session->get('stripe_error');
            $this->session->remove('stripe_error');

            return $stripeError;
        }

        $this->logger->info('[BILLING] Cancelled subscription for customer ' . $organization->stripe_customer_id);

        // update organization table
        $organization->suspend_date = date('Y-m-d H:i:s', $suspend_date);
        $organization->execute_time = null;
        $organization->reactivated = null;
        $organization->is_active = false;

        if ($organization->update()) {
            return true;
        } else {
            return $organization->getMessages();
        }

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

        // retrieve stripe subscription details
        $subscription = $this->stripe->getSubscription($customer->subscriptions->data[0]->id);

        if (! $subscription) {
            $stripeError = $this->session->get('stripe_error');
            $this->session->remove('stripe_error');

            return $stripeError;
        }

        $new_coupon = null;

        // update organization
        if ($organization->coupon_before_deactivation) {
            $new_coupon = $organization->coupon_before_deactivation;

            $organization->coupon_before_deactivation = null;
        }

        if (! $this->stripe->applySubscriptionCoupon($subscription->id, $new_coupon)) {
            $stripeError = $this->session->get('stripe_error');
            $this->session->remove('stripe_error');

            return $stripeError;
        }

        // reactivate organization
        $organization->is_active = 1;

        if ($organization->update()) {
            return true;
        } else {
            return $organization->getMessages();
        }
    }

    public function updateSubscriptionQuantity($organization, $plan, $quantity)
    {
        $subscription = $this->getSubscriptionForType($organization, $plan);

        $old_quantity = $subscription->items->data[0]->quantity;

        $operation = substr($quantity, 0, 1);

        if ($operation == '-') {
            $target_quantity = $subscription->items->data[0]->quantity - substr($quantity, 1);
        } else if ($operation == '+') {
            $target_quantity = $subscription->items->data[0]->quantity + substr($quantity, 1);
        } else {
            $target_quantity = $quantity;
        }

        // change customer stripe plans - for now just change the plan quantity
        $data = [
            'items' => [
                [
                    'id'        => $subscription->items->data[0]->id,
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
            $this->logger->info('[BILLING] Updated subscription and user structure of org ' . $organization->pk_id . ' from ' . $old_quantity . ' to ' . $target_quantity);

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
     * @param  EntOrganization  $organization - organization to fetch data for
     * @param  String           $plan         - plan identifier to map
     * @return \Stripe\Subscription           - subscription object for type
     */
    protected function getSubscriptionForType($organization, $plan)
    {
        $stripe_plan_names = $this->config->stripe_plan_names;

        $plan_name = $stripe_plan_names[$plan];

        $customer = $this->stripe->getCustomer($organization->stripe_customer_id);

        $subscriptions = $customer->subscriptions->data;

        foreach ($subscriptions as $subscription) {
            if ($plan_name == $subscription->items->data[0]->plan->id) {
                return $subscription;
            }
        }

        return false;
    }

}
