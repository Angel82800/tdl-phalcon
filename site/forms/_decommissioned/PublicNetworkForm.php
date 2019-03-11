<?php

namespace Thrust\Forms;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Submit;
use Phalcon\Forms\Element\Text;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;

class PublicNetworkForm extends Form
{
    public function initialize()
    {
        // Public Network Name
        $publicNetworkName = new Text('public-network-name', array(
            'disabled' => true
        ));

        $publicNetworkName->setLabel('Wireless Network Name');

        $publicNetworkName->addValidators(array(
            new PresenceOf(array(
                'message' => 'Wireless network name required'
            ))
        ));

        $this->add($publicNetworkName);

        // Public Network Password
        $publicNetworkPassword = new Password('public-network-password', array(
            'disabled'    => true,
            'placeholder' => '************',
        ));

        $publicNetworkPassword->addValidator(new PresenceOf(array(
            'message' => 'A non-empty public network password is required'
        )));

        $publicNetworkPassword->addValidator(new StringLength(array(
            'min'            => 8,
            'messageMinimum' => 'Network password must be at least 8 characters long'
        )));

        $publicNetworkPassword->setLabel('Password');

        $publicNetworkPassword->clear();

        $this->add($publicNetworkPassword);

        $this->add(new Submit('submit', array(
            'class' => 'submit hidden-button medium button hollow secondary',
            'value' => 'Save Change'
        )));

        $this->add(new Hidden('formType', array(
            'value' => 'publicNetworkForm'
        )));
    }
}
