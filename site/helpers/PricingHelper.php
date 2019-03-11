<?php

namespace Thrust\Helpers;

use Thrust\Models\EntSettings;

class PricingHelper
{
    const TIER_PRICING = [ 124.99, 99.99, 89.99 ];
    const SUPPORT_PRICING = [ 0, 99.99, 499.99 ];
    const SUPPORT_LEVELS = [
        'standard'      => 1,
        'expedited'     => 2,
        'dedicated'     => 3,
    ];
    // const EARLY_ACCESS_PRICING = 24.99;

    protected $config;
    protected $settings;

    protected $X;
    protected $tier;
    protected $price;

    protected $support_level;
    protected $introductory;
    protected $prepay;
    protected $discount;
    // protected $early_access;

    protected $coupon;
    protected $free_months;

    /**
     * constructor
     * @param int     $X            - X for calculation of tier
     * @param boolean $introductory - introductory flag : 50% off
     */
    function __construct($X, $introductory = false)
    {
        $this->config = \Phalcon\Di::getDefault()->get('config');

        // get settings
        $settings = EntSettings::find([
            'is_active' => true,
            'cache'     => 60,
        ]);

        $this->settings = [];
        foreach ($settings as $setting) {
            $this->settings[$setting->key] = $setting->value;
        }

        $this->X = $X;
        $this->tier = $this->calculateTier($X);
        $this->price = false;

        $this->support_level = 1;
        $this->introductory = $introductory;
        $this->prepay = 0;
        $this->discount = 0;
        // $this->early_access = false;
        $this->coupon = false;
        $this->free_months = false;
    }

    protected function calculateTier($X)
    {
        // X is # of devices

        //*** multiplied by 1000 to remove tiers for now

        if ($X <= 6000) {
            $tier = 1;
        } else if ($X <= 18000) {
            $tier = 2;
        } else if ($X <= 50000) {
            $tier = 3;
        } else {
            $tier = 4;
        }

        return $tier;
    }

    public function setSupportLevel($support_level)
    {
        $this->support_level = self::SUPPORT_LEVELS[$support_level];
    }

    public function setPrepay($prepay)
    {
        $this->prepay = $prepay;
    }

    public function setDiscount($discount)
    {
        $this->discount = $discount;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    // public function setEarlyAccess($early_access)
    // {
    //     $this->early_access = $early_access;
    // }

    public function setCoupon($coupon)
    {
        $this->coupon = $coupon;
        if ($this->coupon->percent_off && $this->coupon->percent_off == '100') {
            if ($this->coupon->duration == 'once') {
                $this->free_months = 1;
            } else if ($this->coupon->duration == 'forever') {
                $this->free_months = 'forever';
            } else if ($this->coupon->duration == 'repeating') {
                $this->free_months = $this->coupon->duration_in_months;
            }
        }
    }

    public function getTier()
    {
        return $this->tier;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getPricing($type, $support_level = false, $monthly = false)
    {
        if ($this->price) {
            $monthly_price = $this->price;
        } else {
            $monthly_price = $this->tier < 4 ? self::TIER_PRICING[$this->tier - 1] * $this->X : 0;
            if ($this->introductory) $monthly_price = ceil($monthly_price) / 2;
        }

        // add support level pricing
        if ($support_level) {
            // manual support level setting
            $monthly_price += self::SUPPORT_PRICING[self::SUPPORT_LEVELS[$support_level] - 1];
        } else {
            $monthly_price += self::SUPPORT_PRICING[$this->support_level - 1];
        }

        $tier_price = $monthly_price;
        $discount_price = 0;
        if ($this->discount) {
            $discount_price = $monthly_price * $this->discount;
            $monthly_price -= $discount_price;
        }

        // use set prepay
        if ($this->prepay) {
            $original_price = $monthly_price * 12;
            $monthly_price *= 0.9;
            $annual_price = $monthly_price * 12;
        } else {
            $original_price = $monthly_price;
        }

        $discounted_price = $monthly_price;

        // if coupon is set, apply it
        if ($this->coupon) {
        /*
            "id": "1STMONTH",
            "duration": "once",
            "duration_in_months": null,
            "amount_off": null,
            "percent_off": 10,
        */
            $discounted_price = $this->coupon->amount_off ? ($monthly_price - $this->coupon->amount_off / 100) : ($monthly_price * (100 - $this->coupon->percent_off) / 100);

            if ($discounted_price < 0) $discounted_price = 0;
            $coupon_price = $monthly_price - $discounted_price;

            // calculate the annual price when coupon codes are applied for partial months only
            if ($this->coupon->duration == 'once') {
                $annual_price = $discounted_price + $monthly_price * 11;
            } else if ($this->coupon->duration == 'forever') {
                $annual_price = $discounted_price * 12;
                $monthly_price = $discounted_price;
            } else if ($this->coupon->duration == 'repeating') {
                $duration = ($this->coupon->duration_in_months < 12) ? $this->coupon->duration_in_months : 12;
                $annual_price = $discounted_price * $duration + $monthly_price * (12 - $duration);
                // if ($discounted_price) {
                //     $monthly_price = ($duration * $discounted_price + (12 - $duration) * $monthly_price) / 12;
                // }
            }
        }

        // use set prepay
        if ($this->prepay) {
            $coupon_price = $original_price * 0.9 - $annual_price;
            $discounted_price = $annual_price;
        }

        // show monthly price - packages step
        if ($monthly) {
            if ($this->prepay) {
                $original_price /= 12;
                $discounted_price /= 12;
            }
        }

        // discount
        $prepay_price = $original_price / 10;
        if ($this->discount) {
            $original_price = $this->prepay ? $tier_price * 12 : $tier_price;
            $discount_price = $original_price * $this->discount;
            $prepay_price = ($original_price - $discount_price) / 10;
        }

        switch ($type) {
            case 'tier':
                return $tier_price;
            case 'original':
                return $original_price;
            case 'final':
                return $discounted_price;
            case 'prepay':
                return $prepay_price;
            case 'discount':
                return $discount_price;
            case 'discounted':
                return $coupon_price;
        }
    }

    public function getSupportPlan()
    {
        return ($this->prepay ? 'annual' : 'monthly') . '_support_lvl_' . $this->support_level;
    }

    // public function getEarlyAccessPlan()
    // {
    //     $plan_name = false;

    //     if ($this->type == 'annual') {
    //         $plan_name = 'early_access_free';
    //     } else if ($this->early_access) {
    //         $plan_name = 'early_access';
    //     }

    //     return $plan_name;
    // }

    public function getCouponCode()
    {
        if (!$this->coupon) return false;

        return $this->coupon->id;
    }

    public function getCouponDescription()
    {
        if (!$this->coupon) return false;

        if ($this->free_months) {
            // free months
            if ($this->free_months == 1) {
                $coupon_description = 'Free first month';
            } else if ($this->free_months == 'forever') {
                $coupon_description = 'Ongoing free subscription';
            } else {
                $coupon_description = 'Free for the first ' . $this->free_months . ' months';
            }
        } else {
            $coupon_description = $this->coupon->amount_off ? 'Up to $' . number_format($this->coupon->amount_off / 100, 2) : $this->coupon->percent_off . '%';
            $coupon_description .= ' off';

            if ($this->coupon->duration == 'once') {
                $coupon_description .= ' first month';
            } else if ($this->coupon->duration == 'repeating') {
                $coupon_description .= ' monthly for ' . $this->coupon->duration_in_months . ' months';
            } else if ($this->coupon->duration == 'forever') {
                $coupon_description .= ' forever';
            }
        }

        return $coupon_description;
    }

    public function getRecurringBillingDate()
    {
        $billing_date = false;

        if ($this->coupon) {
            if ($this->free_months == 'forever' || $this->coupon->duration == 'forever') {
                $billing_date = false;
            } else {
                $coupon_months = 0;

                if ($this->coupon->duration == 'once') {
                    $coupon_months = 1;
                } else {
                    $coupon_months = $this->coupon->duration_in_months;
                }

                $billing_date = date('F d, Y', strtotime('+' . $coupon_months . ' months'));
            }
        }

        return $billing_date;
    }

    public function getFreeMonths()
    {
        return $this->free_months;
    }

    public function hasForever()
    {
        return $this->free_months == 'forever' || ($this->coupon && $this->coupon->duration == 'forever');
    }
}
