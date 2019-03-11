<?php

namespace Thrust\Forms;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Submit;
use Phalcon\Forms\Element\Text;
use Phalcon\Validation\Validator\PresenceOf;

class PrivateNetworkForm extends Form
{
    public function initialize()
    {
        // Private Network Name
        $privateNetworkName = new Text('private-network-name', array(
            'disabled' => true,
        ));

        $privateNetworkName->setLabel('Private Wireless Network Name');

        $privateNetworkName->addValidators(array(
            new PresenceOf(array(
                'message' => 'Private wireless network name required',
            )),
        ));

        $this->add($privateNetworkName);

        // Password
        $password = new Password('password', array(
            'placeholder' => 'Password',
            'class'       => 'hidden-password',
        ));

        $password->addValidator(new PresenceOf(array(
            'message' => 'The password is required',
        )));

        $password->clear();

        $password->setLabel('Your Password is Required to Make This Change');

        $this->add($password);

        $this->add(new Submit('submit', array(
            'class' => 'submit hidden-button medium button hollow secondary',
            'value' => 'Save Change',
        )));

        $this->add(new Hidden('formType', array(
            'value' => 'privateNetworkForm',
        )));
    }
}
