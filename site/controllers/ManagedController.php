<?php

namespace Thrust\Controllers;

/**
 * Display the default index page.
 */
class ManagedController extends ControllerBase
{

    public function initialize()
    {
        
    }

    public function indexAction()
    {
		$this->view->setTemplateBefore('public');
    }

    /**
     * Privacy policy
     */
    public function managedCallLandingAction()
    {
            
    }

}
