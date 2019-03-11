<?php

namespace Thrust\Forms;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Submit;
use Phalcon\Forms\Element\Text;
use Phalcon\Validation\Validator\PresenceOf;

class BlockedSiteForm extends Form
{
    public function initialize()
    {
        // Private Network Name
        $blockedSite = new Text('blocked-site');

        $blockedSite->addValidators(array(
            new PresenceOf(array(
                'message' => 'Non-empty site URL required'
            ))
        ));

        $this->add($blockedSite);

        $this->add(new Submit('submit', array(
            'class' => 'submit hidden-button medium button hollow input-height',
            'value' => 'Block this Site'
        )));

        $this->add(new Hidden('formType', array(
            'value' => 'blockedSiteForm'
        )));
    }
}
