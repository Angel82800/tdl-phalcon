<?php

namespace Thrust\Stripe;

use Phalcon\Mvc\User\Component;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use Stripe;

/**
 * Thrust\Stripe\Api
 * Handles making calls to Stripe API.
 */
class Api extends Component
{
  private $secretKey;

  private $publishKey;

  public function __construct()
  {
    $config = $this->getDi()->get('config');

    $this->secretKey = $config->stripe->secretKey;
    $this->publishKey = $config->stripe->publishKey;

    \Stripe\Stripe::setApiKey($this->secretKey);
  }

  /**
   * Create Stripe Customer
   * @param  [string]   $token   [token retrieved from Stripe elements]
   * @param  [string]   $email   [customer email]
   * @param  [string]   $name    [name to be included in description]
   * @return [Customer]          [Stripe Customer object]
   */
  public function createCustomer($token, $email, $name)
  {
    try {
      return \Stripe\Customer::create(array(
        'email'        => $email,
        'description'  => 'Customer for ' . $name,
        'source'       => $token,
      ));
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Update Stripe Customer
   * @param  string $cusId   [stripe customer id to update]
   * @param  string $token   [card source token to assign]
   * @param  string $email   [customer email]
   * @param  string $orgName [thrust organization name to be used for description]
   * @return mixed           [updated customer]
   */
  public function updateCustomer($cusId, $token, $email, $orgName)
  {
    try {
      $customer = \Stripe\Customer::retrieve($cusId);

      $customer->email = $email;
      $customer->description = 'Customer for ' . $orgName;
      $customer->source = $token;

      return $customer->save();
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Delete Stripe Customer
   * @param  string $cusId [stripe customer id to delete]
   * @return mixed         [deleted customer?]
   */
  public function deleteCustomer($cusId)
  {
    try {
      $cu = \Stripe\Customer::retrieve($cusId);

      return $cu->delete();
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Retrieve Stripe Customer
   * @param  string $cusId [stripe customer id to retrieve]
   * @return mixed         [retrieved customer info]
   */
  public function getCustomer($cusId)
  {
    try {
      $customer = \Stripe\Customer::retrieve($cusId);

      return $customer;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * List Customers By Options
   * @param  mixed $options [options to query with]
   * @return mixed          [customer list]
   */
  public function listCustomers($options)
  {
    try {
      $customers = \Stripe\Customer::all($options);

      return $customers;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * get all stripe plans
   * @param  string $plan_id - plan identifier
   * @return array<object>   - array of stripe plan objects
   */
  public function listPlans()
  {
    try {
      $plans = \Stripe\Plan::all();

      return $plans;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * get stripe plan object
   * @param  string $plan_id - plan identifier
   * @return object          - stripe plan object
   */
  public function getPlan($plan_id)
  {
    try {
      $plan = \Stripe\Plan::retrieve($plan_id);

      return $plan;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Create Stripe Plan
   * @param  mixed $plan_data [plan data to create]
   * @return mixed            [description]
   */
  public function createPlan($plan_data)
  {
    try {
      $plan = \Stripe\Plan::create(array(
        'amount'    => $plan_data['amount'],
        'interval'  => $plan_data['interval'],
        'name'      => $plan_data['name'],
              'currency'  => 'usd', // default currency
              'id'        => $plan_data['id'])
    );

      return $plan;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Create Stripe Plan with Passed Items and Subscribe Customer to it
   * @param  string  $cus_id            [stripe customer id]
   * @param  mixed   $items             [items to create plan from]
   * @param  string  $coupon            [coupon to apply]
   * @param  integer $trial_period_days [trial period days to apply]
   * @return mixed                      [created subscription data]
   */
  public function subscribeCustomer($cus_id, $items, $coupon = false, $trial_period_days = false)
  {
    try {
      $subscription_data = [
        'customer' => $cus_id,
        'items' => $items,
      ];

      if ($coupon) {
        // first validate coupon
        $coupon = strtoupper(trim($coupon));

        try {
          $couponObj = \Stripe\Coupon::retrieve($coupon);
          $subscription_data['coupon'] = $couponObj->id;
        } catch (\Stripe\Error\InvalidRequest $e) {
          // coupon doesn't exist
        }
      }

      if ($trial_period_days) {
        $subscription_data['trial_period_days'] = $trial_period_days;
      }

      $subscription = \Stripe\Subscription::create($subscription_data);

      return $subscription;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Retrieve Subscription Info
   * @param  string $subscription_id [subscription id to retrieve]
   * @return mixed                   [retrieved subscription info]
   */
  public function getSubscription($subscription_id)
  {
    try {
      $subscription = \Stripe\Subscription::retrieve($subscription_id);

      return $subscription;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Update Subscription Info
   * @param  string $subscription_id - subscription id to update
   * @param  array  $update_data     - data to update
   * @return mixed                   - updated subscription info
   */
  public function updateSubscription($subscription_id, $update_data)
  {
    try {
      $subscription = \Stripe\Subscription::retrieve($subscription_id);

      foreach ($update_data as $key => $value) {
        $subscription->{$key} = $value;
      }

          // if (! isset($subscription->prorate)) $subscription->prorate = false;

      return $subscription->save();
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Cancel All Subscriptions from Customer - this is not reversible if applied with period_end: false
   * @param  string   $customer_id    [stripe customer id to revoke subscription]
   * @param  mixed    $period_end     [whether to cancel at the end of billing cycle or right away]
   * @return mixed                    [cancelled subscription?]
   */
  public function cancelSubscription($customer_id, $period_end = false)
  {
    try {
      $customer = \Stripe\Customer::retrieve($customer_id);

      foreach ($customer->subscriptions->data as $subscription) {
        $subscription = \Stripe\Subscription::retrieve($subscription->id);

        $subscription->cancel([ 'at_period_end' => $period_end ]);
      }

      return true;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * get stripe subscriptions
   * @param  []     $options - fetch criteria
   * @return []              - fetch result
   */
  public function getSubscriptions($options)
  {
    try {
      $subscriptions = \Stripe\Subscription::all($options);

      return $subscriptions;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Get Invoice List based on query
   * @param  mixed $query     - query to fetch invoice by
   * @return mixed            - retrieved invoice list
   */
  public function getInvoices($query)
  {
    try {
      $invoice_list = \Stripe\Invoice::all($query);

      return $invoice_list;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Get Upcoming Invoice for Subscription - used for proration
   * @param  string $customer_id     [stripe customer id]
   * @param  string $coupon_id       [coupon id to apply to customer]
   * @param  string $subscription_id [subscription id to retrieve invoice from]
   * @param  mixed  $items           [new items to be applied]
   * @param  date   $proration_date  [proration date - used for syncing]
   * @return mixed                   [retrieved invoice]
   */
  public function getUpcomingInvoice($customer_id, $coupon_id = false, $subscription_id = false, $items = false, $proration_date = false)
  {
    try {
      $query_data = [
        'customer' => $customer_id,
      ];

      if ($coupon_id) {
        $query_data['coupon'] = $coupon_id;
      }

      if ($subscription_id) {
        $query_data['subscription'] = $subscription_id;
      }

      if ($subscription_id) {
        $query_data['subscription_items'] = $items;
      }

      if ($proration_date) {
        $query_data['subscription_prorate'] = true;
        $query_data['subscription_proration_date'] = $proration_date;
      }

      $invoice = \Stripe\Invoice::upcoming($query_data);

      return $invoice;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Create Invoice
   * @param  mixed $invoice_data          [invoice data to create invoice from]
   * @return mixed                        [created invoice]
   */
  public function createInvoice($invoice_data)
  {
    try {
      $invoice = \Stripe\Invoice::create($invoice_data);

      return $invoice;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Get Charge List based on query
   * @param  mixed $query     - query to fetch charge by
   * @return mixed            - retrieved charge list
   */
  public function getCharges($query)
  {
    try {
      $charge_list = \Stripe\Charge::all($query);

      return $charge_list;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Charge Customer
   * @param  string  $customer_id [customer id to charge amount from]
   * @param  integer $amount      [amount in cents]
   * @return mixed                [charge object response]
   */
  public function createCharge($customer_id, $amount)
  {
    try {
      $charge = \Stripe\Charge::create([
        'amount'    => $amount,
        'currency'  => 'usd',
        'customer'  => $customer_id,
      ]);

      return $charge;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Create a refund
   * @param  string $charge_id - charge to create refund on
   * @param  float  $amount    - amount to refund
   * @return mixed             - refund object
   */
  public function createRefund($charge_id, $amount)
  {
    try {
      $refund = \Stripe\Refund::create([
        'charge'    => $charge_id,
        'amount'    => $amount,
      ]);

      return $refund;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Apply Coupon to *Subscription*
   * @param  string $subscription_id - subscription to apply coupon to
   * @param  string $coupon_id       - coupon to apply
   * @return mixed                   - subscription data
   */
  public function applySubscriptionCoupon($subscription_id, $coupon_id)
  {
    try {
      $subscription = \Stripe\Subscription::retrieve($subscription_id);

      $subscription->coupon = $coupon_id;

      $subscription->save();

      return $subscription;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Apply Coupon to *Customer*
   * @param  string $customer_id [customer id to apply coupon to]
   * @param  string $coupon_id   [coupon id to apply]
   * @return mixed               [customer data]
   */
  public function applyCoupon($customer_id, $coupon_id)
  {
    try {
      $customer = \Stripe\Customer::retrieve($customer_id);

      $customer->coupon = $coupon_id;

      $customer->save();

      return $customer;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Remove Coupon from Customer
   * @param  string $customer_id [stripe customer id to remove coupon from]
   * @return mixed               [customer data]
   */
  public function removeCoupon($customer_id)
  {
    try {
      $customer = \Stripe\Customer::retrieve($customer_id);

      $customer->coupon = null;

      $customer->save();

      return $customer;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Retrieve Coupon Info
   * @param  string $promo_code [coupon id to retrieve]
   * @return mixed              [coupon info]
   */
  public function getCoupon($promo_code)
  {
    try {
      $promo_code = strtoupper(trim($promo_code));
      $coupon = \Stripe\Coupon::retrieve($promo_code);

      return $coupon;
    } catch(\Stripe\Error\Card $e) {
      $this->handleError('Card Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\InvalidRequest $e) {
      $this->handleError('Invalid Request', $e->getMessage());
      return false;
    } catch (\Stripe\Error\Authentication $e) {
      $this->handleError('Authentication Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error\ApiConnection $e) {
      $this->handleError('API Connection Error', $e->getMessage());
      return false;
    } catch (\Stripe\Error $e) {
      $this->handleError('Generic Error', $e->getMessage());
      return false;
    }
  }

  /**
   * Log Stripe Errors and set session error message
   * @param  string $type      [message type]
   * @param  string $error_msg [message content]
   * @return
   */
  protected function handleError($type, $error_msg)
  {
    $this->logger->error('Stripe Error : ' . $type . ' - ' . $error_msg);

    $error = $type;
    if ($error_msg == 'Your card was declined.') {
      $error = 'Your credit card has been declined, please contact your credit card provider and try again.';
    }

    $this->session->set('stripe_error', $error);
  }
}
