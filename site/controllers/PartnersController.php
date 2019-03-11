<?php

namespace Thrust\Controllers;

/**
 * Display the default index page.
 */
class PartnersController extends ControllerBase
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
    public function partnerCallLandingAction()
    {
            
    }

}
