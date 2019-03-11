<?php

namespace Thrust\Forms;

use Thrust\Forms\BaseForm;

class BillingForm extends BaseForm
{
    function __construct()
    {
        parent::__construct();

        $this->setFields([
            'caption_promo' => [
                'type' => 'Label',
                'label' => '<h4>Add a Promo Code</h4>',
            ],
            'promo_section' => [
                'type' => 'TPromoCode',
                'link_fields' => [
                    'coupon' => 'coupon',
                ],
                'attributes' => [
                    'required' => false,
                ],
                'grid' => 'medium-12 small-12',
            ],
            'caption_payment' => [
                'type' => 'Label',
                'label' => '<h4>Payment</h4>',
            ],
            'todyl_first_name' => [
                'type' => 'Text',
                'label' => 'First name',
                'attributes' => [
                    'maxlength' => 100,
                    'abide' => [
                        'pattern'   => '^[a-zA-Z\s]+$',
                        'error'     => 'Please use only letters in this field',
                    ],
                ],
                'validators' => [
                    'PresenceOf' => [
                        'message' => 'Please enter your first name',
                    ],
                ],
                'grid' => 'medium-6 small-12',
            ],
            'todyl_last_name' => [
                'type' => 'Text',
                'label' => 'Last name',
                'attributes' => [
                    'maxlength' => 100,
                    'abide' => [
                        'pattern'   => '^[a-zA-Z\s]+$',
                        'error'     => 'Please use only letters in this field',
                    ],
                ],
                'validators' => [
                    'PresenceOf' => [
                        'message' => 'Please enter your last name',
                    ],
                ],
                'grid' => 'medium-6 small-12',
            ],
            'payment_info' => [
                'type' => 'Label',
                'condition' => 'payment_info',
                'link_fields' => [ 'payment_info' ],
                'label' => '<div class="medium-4 small-12 columns kill-padding" id="payment_info">Your Card :</div><div class="medium-8 small-12 columns">@payment_info<a class="f_right" id="change_card" href="javascript:void(0);">Change</a></div>',
            ],
            'card_element' => [
                'type' => 'Label',
                'condition' => '!payment_info', // this will hide the element if payment_info is set
                'label' => '<div id="card-element"></div><span id="card-errors" class="form-error"></span>',
            ],
            'payment_badges' => [
                'type' => 'Label',
                'label' => '<div class="clearfix position-relative"><img class="vertical-center" src="/img/registration/pci-badge.png" /><img class="float-right" src="/img/registration/powered-by-stripe.png" /></div>',
            ],
            'card_token' => [
                'type' => 'Hidden',
                'populate' => false,
            ],
            'terms' => [
                'type' => 'TCheckboxGroup',
                'options' => [
                    'yes' => [
                        'label' => 'By clicking this checkbox you agree to the <a target="_blank" href="/customeragreement">Todyl Customer Agreement</a>.',
                        'value' => 'yes',
                    ],
                ],
                'attributes' => [
                    'type' => 'checkbox',
                ],
                'validators' => [
                    'Identical' => [
                        'value' => 'yes',
                        'message' => 'Please review and accept the above',
                    ],
                ],
            ],
            'caption_summary' => [
                'type' => 'Label',
                'label' => '<h4>Summary</h4>',
            ],
            'summary' => [
                'type' => 'TSummary',
                'link_fields' => true,
            ],
            'user_platform' => [
                'type' => 'Hidden',
            ],
            'separator' => [
                'type' => 'Separator',
                'color' => '#8a8a8a',
                'margin' => '0',
            ],
            'separator_grey' => [
                'type' => 'Separator',
                'color' => '#BCC3C5',
                'margin' => '0',
            ],
            'spacer_sm' => [
                'type' => 'Spacer',
                'height' => '1em',
            ],
            'spacer' => [
                'type' => 'Spacer',
                'height' => '2em',
            ],
            'Complete Your Order' => [
                'type' => 'Submit',
                'attributes' => [
                    'class'     => 'button btn-wide',
                    'disabled'  => true,
                ],
            ],
        ]);

        // add fields to form
        $this->buildFields();
    }

}
