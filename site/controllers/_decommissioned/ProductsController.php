<?php

namespace Thrust\Controllers;

class ProductsController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateBefore('public');
        $this->view->setVar('logged_in', is_array($this->auth->getIdentity()));
    }

    public function indexAction()
    {
        // Forward flow to the index action
        $this->dispatcher->forward(
            array(
                'controller' => 'products',
                'action'     => 'shield'
            )
        );
    }

    public function shieldAction()
    {
    }

    public function guidanceAction()
    {
        //TODO: Remove later. Redirect to contact page for now 
        $this->response->redirect('contact/index?redirect=true');
    }

    public function supportAction()
    {
        //TODO: Remove later. Redirect to contact page for now 
        $this->response->redirect('contact/index?redirect=true');
    }
}
