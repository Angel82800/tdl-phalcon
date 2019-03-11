<?php

namespace Thrust\Controllers;

/**
 * Handles various errors within the application.
 */
class ErrorController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateBefore('error');
    }

    public function indexAction()
    {
        $this->response->setStatusCode(500, 'Internal Server Error');
    }

    public function route404Action()
    {
    }
}
