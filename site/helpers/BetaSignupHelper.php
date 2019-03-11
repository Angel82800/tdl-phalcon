<?php

namespace Thrust\Helpers;

use Thrust\Forms\BaseForm;

use Thrust\Forms\Element\TCheckboxGroup;
use Thrust\Forms\Element\TPromoCode;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Submit;
use Phalcon\Forms\Element\Check;
use Phalcon\Forms\Element\Select;

use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\Identical;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\Regex;
use Phalcon\Validation\Validator\CreditCard;

class BetaSignupHelper
{
    private $step_cnt; // total number of steps
    private $current_step;

    private $sidebar_content;
    private $step_fields;

    private $validation;
    private $form; // actual form type BaseForm

    /**
     * constructor.
     */
    function __construct($industries)
    {
        $this->current_step = 1;

        // initialize step content to show on sidebar
        $this->sidebar_content = [
            1 => [
                'title' => 'Your Information',
                'description' => '',
            ],
            2 => [
                'title' => 'Get Started',
                'description' => '',
            ],
        ];

        // initialize fields for each step
        $this->step_fields = [
            1 => [
                'caption_verify' => [
                    'type' => 'Label',
                    'label' => '<h5>Verify Your Beta Code</h5>',
                ],
                'beta_code' => [
                    'type' => 'TPromoCode',
                    'link_fields' => [
                        'promo_code' => 'promo_code',
                    ],
                    'grid' => 'medium-12 small-12',
                    'attributes' => [
                        'required' => true,
                    ],
                ],

                'separator_1' => [
                    'type' => 'Separator',
                    'color' => '#8a8a8a',
                    'margin' => '20px',
                ],

                'caption_business' => [
                    'type' => 'Label',
                    'label' => '<h5>About Your Business</h5>',
                ],
                'primary_use' => [
                    'type' => 'Select',
                    'options' => [
                        'personal'  => 'Personal or Home',
                        'business'  => 'Business',
                    ],
                    'label' => 'Primary Use',
                    'validators' => [
                        'PresenceOf' => [
                            'message' => 'This field is required',
                        ],
                    ],
                ],
                'todyl_company_name' => [
                    'type' => 'Text',
                    'label' => 'Company Name',
                    'attributes' => [
                        'maxlength' => 100,
                        // 'abide' => [
                        //     'pattern'   => '(.){5,}',
                        //     'error'     => 'Company Name is too short. Minimum 5 characters',
                        // ],
                    ],
                    'validators' => [
                        'PresenceOf' => [
                            'message' => 'Please enter a company name',
                        ],
                    ],
                ],
                'todyl_office_zip' => [
                    'type' => 'Text',
                    'label' => 'Main Office Zip',
                    'attributes' => [
                        'maxlength' => 10,
                        'abide' => [
                            'pattern'   => '^(\d{5}$)|(^\d{5}-\d{4})$',
                            'error'     => 'Please enter a valid zip code',
                        ],
                    ],
                    'validators' => [
                        'PresenceOf' => [
                            'message' => 'The office zip is required',
                        ],
                        'Regex' => [
                            'pattern' => '/^(\d{5}$)|(^\d{5}-\d{4})$/',
                            'message' => 'Please enter a valid zip code',
                        ],
                    ],
                ],
                'business_type' => [
                    'type' => 'Select',
                    'options' => $industries,
                    'attributes' => [
                        'useEmpty' => true,
                        'emptyText' => 'Please Select One...',
                    ],
                    'label' => 'Type of Business',
                    'validators' => [
                        'PresenceOf' => [
                            'message' => 'This field is required',
                        ],
                    ],
                ],

                'separator_2' => [
                    'type' => 'Separator',
                    'color' => '#8a8a8a',
                    'margin' => '20px',
                ],

                'caption_account' => [
                    'type' => 'Label',
                    'label' => '<h5>About You</h5>',
                ],
                'todyl_first_name' => [
                    'type' => 'Text',
                    'label' => 'Your First Name',
                    'attributes' => [
                        'maxlength' => 50,
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
                ],
                'todyl_last_name' => [
                    'type' => 'Text',
                    'label' => 'Your Last Name',
                    'attributes' => [
                        'maxlength' => 50,
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
                ],
                'todyl_email' => [
                    'type' => 'Text',
                    'label' => 'Your Email Address',
                    'attributes' => [
                        'maxlength' => 100,
                        'abide' => [
                            'pattern'   => 'email',
                            'error'     => 'Please enter a valid email address',
                        ],
                    ],
                    'validators' => [
                        'PresenceOf' => [
                            'message' => 'Please enter a valid email address',
                        ],
                        'Email' => [
                            'message' => 'Please enter a valid email address',
                        ],
                    ],
                ],
                'todyl_password' => [
                    'type' => 'Password',
                    'label' => 'Create Your Password',
                    'attributes' => [
                        'autocomplete' => 'off',
                    ],
                    'validators' => [
                        'PresenceOf' => [
                            'message' => 'Please enter password you\'ll use',
                        ],
                        'StringLength' => [
                            'min'            => 7,
                            'messageMinimum' => 'Password is too short. Minimum 7 characters',
                        ],
                        'Regex' => [
                            'pattern' => '/^(?=.*\d)(?=.*[!?^&*$])(?!.*\s).*$/',
                            'message' => 'At least 7 Characters in Length, Include a Number and a Symbol - !?&* or $',
                        ],
                    ],
                ],
                'todyl_phone_no' => [
                    'type' => 'Text',
                    'label' => 'Your Cellular Number ###-###-####',
                    'attributes' => [
                        'abide' => [
                            'pattern'   => '^(\([0-9]{3}\) |[0-9]{3}-)[0-9]{3}-[0-9]{4}$',
                            'error'     => 'Please enter a valid phone number',
                        ],
                        'maxlength' => 20,
                    ],
                    'validators' => [
                        'PresenceOf' => [
                            'message' => 'Please enter a valid phone number',
                        ],
                    ],
                ],

                'terms' => [
                    'type' => 'TCheckboxGroup',
                    'options' => [
                        'yes' => [
                            'label' => 'By clicking this checkbox you agree to the <a target="_blank" href="/terms/beta">Todyl Terms of Service</a> and <a target="_blank" href="/privacy">Privacy Policy</a>.',
                            'value' => 'yes',
                        ],
                    ],
                    'attributes' => [
                        'type' => 'checkbox',
                    ],
                    'grid' => 'medium-12 small-12 highlight',
                    'validators' => [
                        'Identical' => [
                            'value' => 'yes',
                            'message' => 'Please accept the terms and conditions',
                        ],
                    ],
                ],

                'user_platform' => [
                    'type' => 'Hidden',
                ],

                'recaptcha' => [
                    'type' => 'Label',
                    'label' => '<div id="signup-recaptcha" class="g-recaptcha" data-checked="no"></div><span id="recaptcha_error" class="form-error"></span>',
                ],
                'spacer_1' => [
                    'type' => 'Spacer',
                    'height' => '15px',
                ],
                'Submit' => [
                    'type' => 'Submit',
                    'attributes' => [
                        'class'     => 'button',
                    ],
                ],
            ],
        ];

        $this->step_cnt = count($this->step_fields) - 1;
    }

    /**
     * get total steps
     * @return int
     */
    public function getStepcount()
    {
        return $this->step_cnt;
    }

    /**
     * get steps content to display on sidebar
     * @return mixed
     */
    public function getSidebarContent()
    {
        return $this->sidebar_content;
    }

    /**
     * set form for step
     * @param $step
     */
    public function setStep($step)
    {
        $this->current_step = $step;

        $this->form = new BaseForm();
        $this->validation = new Validation();

        $fields = $this->step_fields[$step];

        foreach ($fields as $field_id => $field) {
            if (in_array($field['type'], ['Label', 'Spacer', 'Separator'])) {
                // the caption field - don't add it to the form
                continue;
            }

            $type = "Phalcon\\Forms\\Element\\" . $field['type'];
            if (!class_exists($type)) {
                $type = "Thrust\\Forms\\Element\\" . $field['type'];
            }
            if (isset($field['options'])) {
                $element = new $type($field_id, $field['options']);
            } else {
                $element = new $type($field_id);
            }

            if (isset($field['label'])) {
                $element->setLabel($field['label']);
            }

            if (isset($field['validators'])) {
                foreach ($field['validators'] as $validator_type => $validator) {
                    $validator_type_full = "Phalcon\\Validation\\Validator\\" . $validator_type;
                    $this->validation->add($field_id, new $validator_type_full($validator));
                }
            }

            $this->form->add($element);
        }

        // CSRF - this will be added to every form
        $csrf = new Hidden('csrf');

        $csrf->addValidator(new Identical(array(
            'value'   => $this->form->security->getSessionToken(),
            'message' => 'CSRF validation failed',
        )));

        $this->form->add($csrf);

        return true;
    }

    /**
     * get form
     * @return mixed
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * get validation for current form
     * @return mixed
     */
    public function getValidation()
    {
        return $this->validation;
    }

    /**
     * render steps html
     * @param $saved_data
     */
    public function renderSteps($saved_data)
    {
        $html = '<ol class="reg-list">';

        $passed_steps = isset($saved_data['passed_steps']) && !empty($saved_data['passed_steps']) ? max($saved_data['passed_steps']) : 0;

        foreach ($this->sidebar_content as $step_no => $step) {
            if ($step_no == 'splash') continue;

            $html .= '<li class="step_' . $step_no .
                ($this->current_step == $step_no ? ' active' : '') .
                ($step_no <= $passed_steps ? ' passed' : '') . '">';

            $inner_html = $step['title'];
            $inner_html .= '<p>' . $step['description'] . '</p>';

            $step_link = 'javascript:void(0);';
            if ($step_no <= $passed_steps && $this->current_step != $step_no) {
                $step_link = '/signup/' . $step_no;
            }

            $html .= '<a href="' . $step_link . '">';
            $html .= $inner_html;
            $html .= '</a>';

            $html .= '</li>';
        }

        $html .= "</ol>";

        return $html;
    }

    /**
     * render form html for current step
     * @param $saved_data
     * @param $errors
     * @return string
     */
    public function renderFields($saved_data, $errors)
    {
        $html = "<div class='stage_" . $this->current_step . " stages'>";

        $fields = $this->step_fields[$this->current_step];
        foreach ($fields as $field_id => $field) {
            if ($field['type'] == 'Label') {
                // label field
                $html .= $this->_addLabel($saved_data, $field);
            } else if ($field['type'] == 'Spacer') {
                $html .= '<div style="clear: both; height: ' . $field['height'] . '"></div>';
            } else if ($field['type'] == 'Separator') {
                $html .= '<div class="columns"><div style="clear: both; border-bottom: 1px solid ' . $field['color'] . '; padding-top: ' . $field['margin'] . '; margin-bottom: ' . $field['margin'] . '"></div></div>';
            } else {
                $html .= $this->_addField($saved_data, $errors, $field_id, $field);
            }

        }

        $html .= "</div>";

        return $html;
    }

    /**
     * render individual form field
     * @param [array]  $saved_data [saved session data to populate in fields]
     * @param [array]  $errors     [array of errors returned from controller]
     * @param [string] $field_id   [field id to render]
     * @param [array]  $field      [actual field information]
     */
    protected function _addField($saved_data, $errors, $field_id, $field)
    {
        $attrs = [ 'required' => true, 'placeholder' => ' ' ];

        if (in_array($field['type'], array( 'Text', 'Numeric', 'Select', 'Password' ))) {
            $attrs['class'] = 'inputMaterial validate';
        } else {
            $attrs['class'] = 'validate';
        }

        if (isset($field['attributes'])) {
            $attrs = array_merge($attrs, $field['attributes']);
        }

        $gridClass = 'medium-12 small-12';
        if (isset($field['grid'])) {
            $gridClass = $field['grid'];
        }

        if (isset($saved_data[$field_id]) && !isset($field['protected'])) {
            $attrs['value'] = $saved_data[$field_id];
        }

        if (isset($field['link_fields'])) {
            foreach ($field['link_fields'] as $to_link_field => $link_field) {
                if (isset($saved_data[$link_field])) {
                    $attrs[$to_link_field] = $saved_data[$link_field];
                }
            }
        }

        if (! isset($errors[$field_id])) {
            $field = $this->form->renderDecorated($field_id, $attrs, $gridClass);
        } else {
            $field = $this->form->renderDecoratedErrors($field_id, $errors[$field_id], $attrs, $gridClass);
        }

        return $field;
    }

    /**
     * render Label type fields - this is for caption fields
     * @param [type] $saved_data [description]
     * @param [type] $field      [description]
     */
    protected function _addLabel($saved_data, $field)
    {
        if (isset($field['condition'])) {
            if (!isset($saved_data[$field['condition']]) || !$saved_data[$field['condition']]) {
                return '';
            }
        }

        if (isset($field['link_fields'])) {
            foreach ($field['link_fields'] as $link_field) {
                if (isset($saved_data[$link_field])) {
                    // first search for the field to link among the previous stored data
                    $field['label'] = str_replace('@' . $link_field, $saved_data[$link_field], $field['label']);
                } else {
                    // the link field not found among previous data - compose it
                    $data = 'N/A';
                    switch ($link_field) {
                        case 'payment_info':
                            $data = $saved_data['card_brand'] . ' Ending in ' . $saved_data['card_last4'];
                            break;
                    }
                    $field['label'] = str_replace('@' . $link_field, $data, $field['label']);
                }
            }
        }

        $gridClass = 'medium-12 small-12';
        if (isset($field['grid'])) {
            $gridClass = $field['grid'];
        }

        $label = '<div class="step_label columns ' . $gridClass . '">' . $field['label'] . '</div>';
        return $label;
    }

}
