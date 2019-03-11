<?php

namespace Thrust\Forms;

use Thrust\Forms\BaseForm;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Submit;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Identical;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\Regex;
use Phalcon\Validation\Validator\Email;

class InviteUserForm extends BaseForm
{
    public function initialize()
    {
        $password = new Password('todyl_password', array(
            'placeholder' => ' '
        ));

        $password->addValidators(array(
            new PresenceOf(array(
                'message' => 'Please enter password you\'ll use',
            )),
            new StringLength(array(
                'min'     => 7,
                'message' => 'Password is too short. Minimum 7 characters',
            )),
            new Regex(array(
                'pattern' => '/^(?=.*\d)(?=.*[!?^&*$])(?!.*\s).*$/',
                'message' => 'At least 7 Characters in Length, Include a Number and a Symbol - !?&* or $',
            )),
        ));

        $password->setLabel('Create a Password');

        $this->add($password);

        // -- hidden fields for password reset

        // User Email
        $email = new Hidden('todyl_email');

        $email->addValidators(array(
            new PresenceOf(array(
                'message' => 'Invalid form fields',
            )),
            new Email(array(
                'message' => 'Invalid email passed',
            )),
        ));

        $this->add($email);

        // CSRF
        $csrf = new Hidden('csrf');

        $csrf->addValidator(new Identical(array(
            'value'   => $this->security->getSessionToken(),
            'message' => 'CSRF validation failed'
        )));

        $csrf->clear();

        $this->add($csrf);

        $this->add(new Submit('submit', array(
            'class' => 'button btn-wide',
            'value' => 'Next'
        )));
    }
}
