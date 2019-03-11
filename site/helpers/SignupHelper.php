<?php

namespace Thrust\Helpers;

use Thrust\Forms\BaseForm;

use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Numeric;
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

class SignupHelper
{
    private $step_cnt;          // total number of steps
    private $current_step;

    private $sidebar_content;   // sidebar steps content
    private $step_pages;        // page template names for steps
    private $step_fields;

    private $validation;
    private $form;              // actual form (BaseForm type)

    private $saved_data;        // sign up data passed from controller
    private $errors;            // sign up errors

    /**
     * constructor.
     */
    function __construct()
    {
        $this->current_step = 1;

        // initialize step content to show on sidebar
        $this->sidebar_content = [
            1 => [
                'title' => 'Create Your Account',
                'description' => '',
            ],
            2 => [
                'title' => 'About You',
                'description' => '',
            ],
        ];

        $this->step_pages = [
            1           => 'account',
            2           => 'aboutyou',
        ];

        // initialize fields for each step
        $this->step_fields = [
            1 => [
                // Account Step
                'caption_try15days' => [
                    'type' => 'Label',
                    'label' => '<p class="group text-partner-blue">Enter your information below to start your free 15-day trial. Setup only takes a few moments.  </p>',
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
                        'autocomplete' => 'off',
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
                    'label' => 'Create a Password',
                    'attributes' => [
                        'autocomplete' => 'new-password',
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
                'recaptcha' => [
                    'type' => 'Label',
                    'label' => '<div id="signup-recaptcha" class="g-recaptcha" data-checked="no"></div><span id="recaptcha_error" class="form-error"></span>',
                ],
                'spacer_1' => [
                    'type' => 'Spacer',
                    'height' => '15px',
                ],
                'Next' => [
                    'type' => 'Submit',
                    'attributes' => [
                        'class'     => 'button',
                        'onClick'   => 'gtag_report_signUpLead();',
                    ],
                ],
            ],
            2 => [
                // About You Step
                'caption_aboutyou' => [
                    'type' => 'Label',
                    'label' => '<h2 class="text-light text-partner-blue mb-1">About You</h2>',
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
                'todyl_business_name' => [
                    'type' => 'Text',
                    'label' => 'Name of your Business (optional)',
                    'attributes' => [
                        'maxlength' => 100,
                        'required'  => false,
                    ],
                ],
                'is_professional' => [
                    'type' => 'TCheckboxGroup',
                    'options' => [
                        'yes' => [
                            'label' => 'Are you an <span data-tooltip data-allow-html="true" title="This will tailor your Todyl experience to better fit your needs.">IT Professional</span>?',
                            'value' => 'yes',
                        ],
                    ],
                    'attributes' => [
                        'type'   => 'checkbox',
                    ],
                ],
                'Finish' => [
                    'type' => 'Submit',
                    'attributes' => [
                        'class'     => 'button disabled',
                    ],
                ],
            ],
        ];

        $this->step_cnt = count($this->step_fields);
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
     * get page template name for step
     * @param  int  $step [current step]
     * @return string     [step template name]
     */
    public function getStepPage($step)
    {
        return $this->step_pages[$step];
    }

    /**
     * store sign up data in property
     * @param array $saved_data [signup data in session]
     */
    public function setSavedData($saved_data)
    {
        $this->saved_data = $saved_data;
    }

    /**
     * store errors in property
     * @param array $errors [errors passed from controller]
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;
    }

    /**
     * set form for step
     * @param $step
     */
    public function setStep($step)
    {
        $this->current_step = $step;

        if ($step == 'splash') return;

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

//        if ($step < $this->step_cnt) {
//            // we're on our way - show Next button
//            $button_label = 'Next';
//        } else {
//            // final step - show Complete button
//            $button_label = 'Complete Your Order';
//        }
//        $this->form->add(new Submit($button_label, array(
//            'class' => 'button',
//        )));

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
     */
    public function renderSteps()
    {
        if ($this->current_step == 'splash') return;

        $html = '<ol class="reg-list">';

        $passed_steps = isset($this->saved_data['passed_steps']) && !empty($this->saved_data['passed_steps']) ? max($this->saved_data['passed_steps']) : 0;

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
     * @return string
     */
    public function renderFields()
    {
        // if ($this->current_step == 'splash') {
        //     return $this->_renderSplash();
        // }

        $html = "<div class='stage_" . $this->current_step . " stages'>";

        $fields = $this->step_fields[$this->current_step];
        foreach ($fields as $field_id => $field) {
            $html .= $this->renderField($field_id);
        }

        $html .= "</div>";

        return $html;
    }

    /**
     * render form field
     * @param $field_id
     * @return string
     */
    public function renderField($field_id)
    {
        $field = $this->step_fields[$this->current_step][$field_id];

        $html = '';
        if ($field['type'] == 'Label') {
            // label field
            $html .= $this->_addLabel($field);
        } else if ($field['type'] == 'Spacer') {
            $html .= '<div style="clear: both; height: ' . $field['height'] . '"></div>';
        } else if ($field['type'] == 'Separator') {
            $html .= '<div class="columns" style="float: none"><div style="clear: both; border-bottom: 1px solid ' . $field['color'] . '; padding-top: ' . $field['margin'] . '; margin-bottom: ' . $field['margin'] . '"></div></div>';
        } else {
            $html .= $this->_addField($field_id, $field);
        }

        return $html;
    }

    /**
     * render individual form field
     * @param [string] $field_id   [field id to render]
     * @param [array]  $field      [actual field information]
     */
    protected function _addField($field_id, $field)
    {
        $attrs = [ 'required' => true, 'placeholder' => ' ' ];

        $attrs['class'] = 'validate';
        // if (in_array($field['type'], array( 'Text', 'Numeric', 'Select', 'Password' ))) {
        //     $attrs['class'] = 'inputMaterial validate';
        // } else {
        //     $attrs['class'] = 'validate';
        // }

        if (isset($field['attributes'])) {
            $attrs = array_merge($attrs, $field['attributes']);
        }

        $gridClass = 'medium-12 small-12';
        if (isset($field['grid'])) {
            $gridClass = $field['grid'];
        }

        if (isset($this->saved_data[$field_id]) && !isset($field['protected']) && (!isset($field['populate']) || $field['populate'])) {
            $attrs['value'] = $this->saved_data[$field_id];
        }

        if (isset($field['link_fields'])) {
            if ($field['link_fields'] === true) {
                // pass all saved data to the element
                $attrs['saved_data'] = $this->saved_data;
            } else {
                // pass only selected data
                foreach ($field['link_fields'] as $to_link_field => $link_field) {
                    if (isset($this->saved_data[$link_field])) {
                        $attrs[$to_link_field] = $this->saved_data[$link_field];
                    }
                }
            }
        }

        // remove required if false
        if (isset($field['attributes']['required']) && $field['attributes']['required'] === false) {
            unset($attrs['required']);
        }

        if (! isset($this->errors[$field_id])) {
            $field = $this->form->renderDecorated($field_id, $attrs, $gridClass);
        } else {
            $field = $this->form->renderDecoratedErrors($field_id, $this->errors[$field_id], $attrs, $gridClass);
        }

        return $field;
    }

    /**
     * render Label type fields - this is for caption fields
     * @param [type] $field      [description]
     */
    protected function _addLabel($field)
    {
        $hidden_field = false;
        if (isset($field['condition'])) {
            // condition `not` handler
            if (substr($field['condition'], 0, 1) == '!') {
                $field['condition'] = substr($field['condition'], 1);

                if (isset($this->saved_data[$field['condition']]) && $this->saved_data[$field['condition']]) {
                    $hidden_field = true;
                }
            } else {
                if (!isset($this->saved_data[$field['condition']]) || !$this->saved_data[$field['condition']]) {
                    return '';
                }
            }
        }

        if (isset($field['link_fields'])) {
            foreach ($field['link_fields'] as $link_field) {
                if (isset($this->saved_data[$link_field])) {
                    // first search for the field to link among the previous stored data
                    $field['label'] = str_replace('@' . $link_field, $this->saved_data[$link_field], $field['label']);
                } else {
                    // the link field not found among previous data
                    $data = 'N/A';
                    // switch ($link_field) {
                    //     case 'payment_info':
                    //         $data = $this->saved_data['card_brand'] . ' Ending in ' . $this->saved_data['card_last4'];
                    //         break;
                    // }
                    $field['label'] = str_replace('@' . $link_field, $data, $field['label']);
                }
            }
        }

        $gridClass = 'medium-12 small-12';
        if (isset($field['grid'])) {
            $gridClass = $field['grid'];
        }

        if ($hidden_field) {
            $gridClass .= ' hide';
        }

        $label = '<div class="step_label columns ' . $gridClass . '">' . $field['label'] . '</div>';
        return $label;
    }

    /**
     * deprecated
     * render splash page html
     */
    protected function _renderSplash()
    {
        $fields = $this->step_fields[$this->current_step];

        $splash = '<div class="splash" next="' . $fields['next'] . '" delay="' . $fields['delay'] . '">';
        $splash .= '<div class="splash_content">';
            $splash .= '<img src="' . $fields['image'] . '" />';
            $splash .= '<p>' . $fields['caption'] . '</p>';
        $splash .= '</div>';
        $splash .= '</div>';

        return $splash;
    }

    protected function _calculateInterval($type, $count)
    {
        if ($count == 1) {
            $interval = $type == 'month' ? 'Monthly' : 'Annual';
        } else {
            $interval = "Every $count {$type}s";
        }

        return $interval;
    }
}
