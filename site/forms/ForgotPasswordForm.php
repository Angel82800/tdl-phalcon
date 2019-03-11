<?php

namespace Thrust\Forms;

use Thrust\Forms\BaseForm;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Submit;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\Identical;

class ForgotPasswordForm extends BaseForm
{
    public function initialize()
    {
        $email = new Text('todyl_email', array(
            'placeholder' => ' '
        ));

        $email->addValidators(array(
            new PresenceOf(array(
                'message' => 'The e-mail is required'
            )),
            new Email(array(
                'message' => 'The e-mail is not valid'
            ))
        ));

        $email->setLabel('Your Email Address');

        $this->add($email);

        // CSRF
        $csrf = new Hidden('csrf');

        $csrf->addValidator(new Identical(array(
            'value'   => $this->security->getSessionToken(),
            'message' => 'CSRF validation failed'
        )));

        $csrf->clear();

        $this->add($csrf);

        $this->add(new Submit('send', array(
            'class' => 'large button hollow secondary float-left',
            'value' => 'Confirm'
        )));
    }
}
