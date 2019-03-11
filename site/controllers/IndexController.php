<?php

namespace Thrust\Controllers;

use Thrust\Models\DashboardStatistics;

/**
 * Display the default index page.
 */
class IndexController extends ControllerBase
{
	protected $db;

    public function initialize()
    {
        // $this->view->setTemplateBefore('public');
    }

    public function indexAction()
    {
        $this->view->setTemplateBefore('public');

		$stats = new DashboardStatistics();

        // Latest Info
        $latest_threat = $stats->latestThreat();

        // Threat Count
		$threat = $stats->indicatorCount();

    	// Blocks
        $blocks = $stats->totalBlockedThreats();

        // Traffic
        $traffic = $stats->lifetimeData();

        // [threats] => 28765213 [blocks] => 684 [traffic] => 2.3658

        $data = [
            'threats'      =>  number_format($threat, 0, '', ''),
            'blocks'       =>  number_format($blocks, 0, '', ''),
            'traffic'	   =>  $traffic,
            'name'         =>  $latest_threat['name'],
            'percentage'   =>  $latest_threat['percentage'],
            'dateDetected' =>  $latest_threat['dateDetected'],
        ];

        $this->view->setVars($data);
    }

    /**
     * Privacy policy
     */
    public function privacyAction()
    {
        $this->view->setLayout('session');
    }

    /**
     * Terms & Conditions
     */
    public function termsAction()
    {
        $this->view->setLayout('session');
    }

    /**
     * Customer Agreement
     */
    public function customeragreementAction()
    {
        $this->view->setLayout('session');
    }

    /**
     * Beta T&C
     */
    public function betatermsAction()
    {
         $this->view->setLayout('session');
    }

    /**
     * thank you page
     */
    public function thankyouAction()
    {
        if ($this->session->get('thankyou_billing_data')) {
            $data = $this->session->get('thankyou_billing_data');
            // $this->session->remove('thankyou_billing_data');

            $data = array_merge($data, [
                'from' => 'billing',
            ]);

            $this->view->setVars($data);
            $this->view->setLayout('private');
        } else if ($this->session->get('registration_time')) {
            $data = [
                'from' => 'registration',
            ];

            $this->view->setVars($data);
            $this->view->setLayout('session');
        } else {
            // if this is not redirected from signup, throw an error
            throw new \Exception('Page not found');
        }
    }

}
