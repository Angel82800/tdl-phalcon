<?php

namespace Thrust\Controllers;

use Thrust\Models\DashboardStatistics;

/**
 * Display the default index page.
 */
class PreviewController extends ControllerBase
{
	protected $db;

    public function initialize()
    {
        // $this->view->setTemplateBefore('public');
    }

    public function indexAction()
    {
        $this->view->setTemplateBefore('public');

    }
}
