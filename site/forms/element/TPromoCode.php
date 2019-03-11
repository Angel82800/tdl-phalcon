<?php

namespace Thrust\Forms\Element;

use Phalcon\Forms\Element;
use Thrust\Helpers\PricingHelper;

class TPromoCode extends Element
{
    /**
     * TPromoCode constructor.
     * @param string $name
     * @param array $attributes
     */
    public function __construct($name, $attributes = null)
    {
		parent::__construct($name, $attributes);
    }

    /**
     * render TPromoCode html
     * @param  array $attributes
     * @return mixed
     */
    public function render($attributes = null)
    {
        $html = '<div id="promo_section">';
        // $html .= '<pre>' . print_r($attributes, true) . '</pre>';

        $required = '';
        if (isset($attributes['required']) && $attributes['required']) {
            $required = ' required';
        }

        if (isset($attributes['coupon'])) {
            // coupon is already applied

            $pricingHelper = new PricingHelper(1);
            $pricingHelper->setCoupon($attributes['coupon']);

            $html .= '<div class="small-8 columns form-group"><input type="text" name="promo_code" id="promo_code" value="' . $pricingHelper->getCouponCode() . '" readonly' . $required . ' /><i class="bar"></i></div>';
            $html .= '<div class="small-4 columns">';
                $html .= '<button class="button remove-promo" id="remove_promo">Remove</button>';
                $html .= '<button class="button" id="apply_promo" style="display: none">Apply</button>';
                // add 'btn-link' to make transparent
            $html .= '</div>';

            $html .= '</div>';

            $html .= '<div id="promo_description" class="success medium-12 columns">' . $pricingHelper->getCouponDescription() . '</div>';
        } else {
            // coupon is not applied

            $promo_code = '';
            // check for saved beta coupon
            if (isset($attributes['promo_code'])) {
                $promo_code = $attributes['promo_code'];
            }

            $html .= '<div class="small-8 medium-9 columns form-group"><input type="text" name="promo_code" value="' . $promo_code . '" id="promo_code" placeholder=" "' . $required . ' /><label class="control-label">Enter Promo Code</label><i class="bar"></i></div>';
            $html .= '<div class="small-4 medium-3 columns end">';
                $html .= '<button class="button remove-promo" id="remove_promo" style="display: none">Remove</button>';
                $html .= '<button class="button" id="apply_promo" disabled="true">Apply</button>';
                // add 'btn-link' to make transparent
            $html .= '</div>';

            $html .= '</div>';

            $html .= '<div id="promo_description" class="medium-12 columns"></div>';
        }

        return $html;
    }
}
