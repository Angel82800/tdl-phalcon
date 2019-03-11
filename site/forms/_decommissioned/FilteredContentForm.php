<?php

namespace Thrust\Forms;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Check;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Submit;

class FilteredContentForm extends Form
{
    public function initialize()
    {

        // adult
        $adult = new Check('adult', array(
            'value' => 'on',
            'class' => 'switch-input',
            'id'    => 'adult-switch'
        ));

        $adult->setLabel('Adult Content');

        $this->add($adult);

        // alcohol/drugs
        $alc = new Check('drugs', array(
            'value' => 'on',
            'class' => 'switch-input',
            'id'    => 'drugs-switch'
        ));

        $alc->setLabel('Alcohol/Drugs');

        $this->add($alc);

        // violence/agression
        $violence = new Check('violence', array(
            'value' => 'on',
            'class' => 'switch-input',
            'id'    => 'violence-switch'
        ));

        $violence->setLabel('Violence/Aggression');

        $this->add($violence);

        // gambling
        $gambling = new Check('gambling', array(
            'value' => 'on',
            'class' => 'switch-input',
            'id'    => 'gambling-switch'
        ));

        $gambling->setLabel('Gambling');

        $this->add($gambling);

        // ads
        $ads = new Check('ads', array(
            'value' => 'on',
            'class' => 'switch-input',
            'id'    => 'ads-switch'
        ));

        $ads->setLabel('Advertisements');

        $this->add($ads);

        $this->add(new Submit('submit', array(
            'class' => 'medium button hollow primary float-right',
            'value' => 'Submit'
        )));

        $this->add(new Hidden('formType', array(
            'value' => 'filteredContentForm'
        )));
    }
}
