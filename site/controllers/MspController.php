<?php

namespace Thrust\Controllers;
use Thrust\Models\MspBackup;

class MspController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateBefore('public');
        $this->view->setVar('logged_in', is_array($this->auth->getIdentity()));
    }

    public function indexAction()
    {
    	//Wrap the object for exceptions
    	try {
    		$MspBackup = new MspBackup();
		} catch (Exception $e) {
            $dispatcher->forward(
                array(
                    'controller' => 'error',
                    'action' => 'index',
                )
            );
    	}

        var_dump($MspBackup->registerDevice(array('UDID-130')));
   		//var_dump($MspBackup->removeDevice(array('UDID-130')));

    	exit;
    }
}
