<?php

namespace Thrust\Forms;

use Thrust\Forms\BaseForm;

class LoginForm extends BaseForm
{
    function __construct()
    {
        parent::__construct();

        $this->setFields([
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
                'label' => 'Your Password',
                'attributes' => [
                    'autocomplete' => 'new-password',
                    'class' => 'no_hint validate',
                ],
                'validators' => [
                    'PresenceOf' => [
                        'message' => 'Please enter password you\'ll use',
                    ],
                ],
            ],
            'Log In' => [
                'type' => 'Submit',
                'attributes' => [
                    'class'     => 'button',
                ],
            ],
        ]);

        // $remember = new Check('remember', array(
        //     'value' => 'yes'
        // ));
        // $remember->setLabel('Remember me');
        // $this->add($remember);

        // add fields to form
        $this->buildFields();
    }

}
