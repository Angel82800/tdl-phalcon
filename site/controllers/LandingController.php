<?php

namespace Thrust\Controllers;

/**
 * Display the default index page.
 */
class LandingController extends ControllerBase
{
	protected $db;

    public function initialize()
    {
        $this->view->setTemplateBefore('public');        
		$this->db = \Phalcon\Di::getDefault()->get('oltp-read');
    }

    public function indexAction()
    {
        
    }

    public function realestateAction()
    {

    }

    public function legalAction()
    {

    }

    public function expertCallAction()
    {

    }

    public function financeAction()
    {

    }

    public function generalAction()
    {

    }    


}
