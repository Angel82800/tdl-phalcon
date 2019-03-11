<?php

namespace Thrust\Forms;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Check;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Submit;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\Identical;

class ContactsForm extends Form
{
    public function initialize($entity = null, $options = null)
    {
        $name = new Text(
           'name',
           array(
               'maxlength'   => 30,
               'placeholder' => 'Your Name',
           )
       );
        $name->addValidators(array(
            new PresenceOf(array(
                'message' => 'Please Enter Your Name',
            ))
        ));
        $this->add($name);

        // Email
        $email = new Text(
            'email',
            array(
                'placeholder' => 'Your Email',
                )
            );
        $email->addValidators(array(
            new PresenceOf(array(
                'message' => 'Please Enter a Valid Email Address'
            )),
            new Email(array(
                'message' => 'Please Enter a Valid Email Address'

            ))
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

         // Google Recaptcha v2
        $recaptcha = new Check('recaptcha');
        $recaptcha->addValidator(new \Thrust\Validators\RecaptchaValidator([
            'message' => 'Please confirm that you are human'
        ]));
        $this->add($recaptcha);

        // Sign Up
        $this->add(new Submit('Submit', array(
            'class' => 'button large active button-border'
        )));
    }
    /**
     * Prints messages for a specific element.
     */
    public function messages($name)
    {
        if ($this->hasMessagesFor($name)) {
            foreach ($this->getMessagesFor($name) as $message) {
                $this->flash->error($message);
            }
        }
    }
}
