<?php

namespace Thrust\Controllers;

use Thrust\Stripe\Api as Stripe;
use Thrust\Helpers\BillingHelper;
use Thrust\Helpers\PricingHelper;

use Thrust\Models\EntUsers;

class PricingController extends ControllerBase
{
    protected $billingHelper;

    public function initialize()
    {
        $this->view->setTemplateBefore('public');
        $this->billingHelper = new BillingHelper();
    }

    public function indexAction()
    {
        $plan = $this->billingHelper->getPlanInfo('device');

        $data = [
            'price'     => number_format($plan->amount / 100, 2),
            'trial_days'=> $plan->trial_period_days,
        ];

        $this->view->setVars($data);
    }

    public function applyCouponAction()
    {
        $this->view->disable();
        $content = [
            'message' => 'Oops! Something went wrong. Please try again later.',
        ];

        $response = new \Phalcon\Http\Response();
        $response->setStatusCode(400, 'Bad Request');

        $stripe = new Stripe();

        if ($this->request->isPost() && $this->request->isAjax()) {
            $response->setStatusCode(200);

            $promo_code = $this->request->getPost('promo_code');

            $suspension_coupon = \Phalcon\Di::getDefault()->get('config')->stripe->suspension_coupon;

            if (! trim($promo_code)) {
                $content = [
                    'status'  => 'fail',
                    'message' => 'Please enter your promo code.',
                ];
            } else if (strtolower($promo_code) == strtolower($suspension_coupon)) {
                // trying to use DEACTIVATED coupon
                $content = [
                    'status'  => 'fail',
                    'message' => 'The promo code is not valid.',
                ];
            } else {
                $coupon = $stripe->getCoupon($promo_code);
                if ($coupon) {
                    if ($coupon->valid) {
                        $billing_data = @ $this->session->get('billing_data');

                        if ($billing_data) {
                            // $tier = $billing_data['num_devices'];
                            $tier = 1;
                        } else {
                            $tier = 1;
                            $billing_data = [];
                        }

                        $billing_data['coupon'] = $coupon;
                        $this->session->set('billing_data', $billing_data);

                        $plan = $this->billingHelper->getPlanInfo('device');

                        $pricingHelper = new PricingHelper($tier);

                        $pricingHelper->setPrice($plan->amount * $billing_data['num_devices'] / 100);
                        $pricingHelper->setCoupon($coupon);

                        $final_price = $pricingHelper->getPricing('final');

                        $content = [
                            'status'  => 'success',
                            'message' => $pricingHelper->getCouponDescription(),
                            'price'   => '$' . number_format($final_price, 2),
                        ];

                        if (! $final_price) {
                            // price is zero after coupon application
                            $content['billing_date'] = $pricingHelper->getRecurringBillingDate();
                        }
                    } else {
                        $content = [
                            'status'  => 'fail',
                            'message' => 'Sorry, this code appears to be no longer valid. Please try another.',
                        ];
                    }
                } else {
                    $content = [
                        'status'  => 'fail',
                        'message' => 'Sorry, we could not find the promo code you entered. Please try another.',
                    ];
                }
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

    public function removeCouponAction()
    {
        $this->view->disable();
        $content = [
            'message' => 'Oops! Something went wrong. Please try again later.',
        ];

        $response = new \Phalcon\Http\Response();
        $response->setStatusCode(400, 'Bad Request');

        $stripe = new Stripe();

        if ($this->request->isPost() && $this->request->isAjax()) {
            $response->setStatusCode(200);

            $billing_data = @ $this->session->get('billing_data');

            if (isset($billing_data['coupon'])) {
                unset($billing_data['coupon']);

                $this->session->set('billing_data', $billing_data);

                $plan = $this->billingHelper->getPlanInfo('device');

                $pricingHelper = new PricingHelper(1);

                $pricingHelper->setPrice($plan->amount / 100);

                $content = [
                    'status'  => 'success',
                    'message' => 'Promo code has been removed successfully',
                    'price'   => '$' . number_format($pricingHelper->getPricing('final'), 2),
                ];
            } else {
                $content = [
                    'status'  => 'not_found',
                    'message' => 'There is no promo code applied',
                ];
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

    public function getPricingAction()
    {
        $this->view->disable();
        $content = [
            'message' => 'Oops! Something went wrong. Please try again later.',
        ];

        $response = new \Phalcon\Http\Response();
        $response->setStatusCode(400, 'Bad Request');

        if ($this->request->isPost() && $this->request->isAjax()) {
            $response->setStatusCode(200);

            $prepay = $this->request->getPost('prepay') == 'true' ? true : false;
            $tier_x = $this->request->getPost('devices');

            // $billing_data = @$this->session->get('billing_data');

            // new pricing logic based on number of devices
            // $tier_x = $billing_data['num_devices'];
            $pricingHelper = new PricingHelper($tier_x);

            // $pricingHelper->setSupportLevel($support_level);
            // $pricingHelper->setEarlyAccess($early_access);

            $pricingHelper->setPrepay($prepay);

            // limited time 50% discount
            $pricingHelper->setDiscount(0.5);

            // do not apply coupon for the packages step
            // if (isset($billing_data['coupon'])) {
            //     $pricingHelper->setCoupon($billing_data['coupon']);
            // }

            $support_plans = [
                'standard',
                'expedited',
                'dedicated',
            ];

            $content = [];

            // show text with discount in mind
            if ($prepay) {
                // annual
                foreach ($support_plans as $support_plan) {
                    $content[$support_plan] = '<strike>$' . number_format($pricingHelper->getPricing('tier', $support_plan), 2) . '/m</strike><br /><span class="text-orange"><span class="large_text">$' . number_format($pricingHelper->getPricing('final', $support_plan, true), 2) . '</span>/m</span>';
                }
            } else {
                // monthly
                foreach ($support_plans as $support_plan) {
                    $content[$support_plan] = '<strike>$' . number_format($pricingHelper->getPricing('tier', $support_plan), 2) . '/m</strike><br /><span class="text-blue"><span class="large_text">$' . number_format($pricingHelper->getPricing('final', $support_plan), 2) . '</span>/m</span>';
                }
            }

            // if ($prepay) {
            //     // annual
            //     foreach ($support_plans as $support_plan) {
            //         $content[$support_plan] = '<strike>$' . number_format($pricingHelper->getPricing('original', $support_plan, true), 2) . '</strike><br /><span class="text-orange"><span class="large_text">$' . number_format($pricingHelper->getPricing('final', $support_plan, true), 2) . '</span> /month</span>';
            //     }
            // } else {
            //     // monthly
            //     foreach ($support_plans as $support_plan) {
            //         $content[$support_plan] = '<span class="large_text">$' . number_format($pricingHelper->getPricing('final', $support_plan), 2) . '</span> /month';
            //     }
            // }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

    public function getSignupSummaryAction()
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

            $billing_data = @$this->session->get('billing_data');

            // new pricing logic based on number of devices
            $tier_x = $billing_data['num_devices'];
            $pricingHelper = new PricingHelper($tier_x);

            $pricingHelper->setSupportLevel($billing_data['package']);

            if (isset($billing_data['prepay'])) {
                $pricingHelper->setPrepay(1);
            }

            // limited time 50% discount
            $pricingHelper->setDiscount(0.5);

            if (isset($billing_data['coupon'])) {
                $pricingHelper->setCoupon($billing_data['coupon']);
                $content['promo'] = '-$' . number_format($pricingHelper->getPricing('discounted'), 2) . (isset($billing_data['prepay']) ? '/y' : '/m');
            }

            // discount price
            $content['discount'] = '-$' . number_format($pricingHelper->getPricing('discount'), 2) . (isset($billing_data['prepay']) ? '/y' : '/m');

            if (isset($billing_data['prepay'])) {
                $content['prepay'] = '-$' . number_format($pricingHelper->getPricing('prepay'), 2) . (isset($billing_data['prepay']) ? '/y' : '/m');
            }

            $final_price = $pricingHelper->getPricing('final');

            if (! $final_price) {
                // price is zero after coupon application
                $content['billing_date'] = $pricingHelper->getRecurringBillingDate();
            }

            $content['subtotal'] = '$' . number_format($pricingHelper->getPricing('original'), 2) . (isset($billing_data['prepay']) ? '/y' : '/m');
            $content['total'] = '$' . number_format($final_price, 2) . (isset($billing_data['prepay']) ? '/y' : '/m');
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

}
