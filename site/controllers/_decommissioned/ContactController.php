<?php

namespace Thrust\Controllers;

use Thrust\Forms\ContactsForm;

/**
 * Display the contact us page.
 */
class ContactController extends ControllerBase
{
    public function initialize()
    {
        $this->tag->setTitle('Todyl | Our Business is Protecting Yours');
        $this->view->setVar('logged_in', is_array($this->auth->getIdentity()));
        $this->view->setTemplateBefore('session');
    }

    public function indexAction()
    {
        $form = new ContactsForm();

        $this->view->setVars(
            array(
                'form' => $form
            )
        );
    }
}
