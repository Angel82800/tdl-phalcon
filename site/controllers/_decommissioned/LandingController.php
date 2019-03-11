<?php

namespace Thrust\Controllers;

use Thrust\Forms\ContactsForm;

class LandingController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateBefore('public');
        $this->view->setVar('logged_in', is_array($this->auth->getIdentity()));
    }

    public function indexAction()
    {
        $form = new ContactsForm();
        $bigHeadline = 'Now Accepting Beta Testers';
        $leadSentence = 'Get enterprise-grade security from Todyl and get the peace of mind knowing that your business is safe.';
        $bullet1 = 'Sign up for a chance to become a Todyl beta tester - absolutely free. You will be notified via email if you are selected to participate in the test.';
        $bullet2 = 'We will send out a limited number of invitations based on several criteria.';
        $bullet3 = 'The beta test will include Todyl Shield&trade; and Todyl Support&trade;';
        $depositText = '&nbsp;';

        $plan = $this->request->get('plan');
        if ($plan == '72113') {
            $bigHeadline = 'Now Accepting Beta Testers';
            $leadSentence = 'Get enterprise-grade security from Todyl and get the peace of mind knowing that your business is safe.';
            $bullet1 = 'Sign up for a chance to become a Todyl beta tester - absolutely free. You will be notified via email if you are selected to participate in the test.';
            $bullet2 = 'We will send out a limited number of invitations based on several criteria.';
            $bullet3 = 'The beta test will include Todyl Shield&trade; and Todyl Support&trade;';
            $depositText = '&nbsp;';
        } elseif ($plan == '37157') {
            $bigHeadline = '3 Months Free';
            $leadSentence = 'Get enterprise-grade security from Todyl and get the peace of mind knowing that your business is safe.';
            $bullet1 = '$199.99 per month after beta trial if you decide to join';
            $bullet2 = 'Save on subscription costs when signing up for annual service';
            $bullet3 = 'Includes Todyl Shield&trade; and Todyl Support&trade;';
            $depositText = 'A $350 deposit is required for Todyl Shield&trade; hardware.<br>This deposit is waived with annual sign up.';
        } elseif ($plan == '90291') {
            $bigHeadline = '3 Months Free';
            $leadSentence = 'Get enterprise-grade security from Todyl and get the peace of mind knowing that your business is safe.';
            $bullet1 = 'If you decide to keep the service after the beta test, you will be charged $299.99 per month.';
            $bullet2 = 'Save on subscription costs when signing up for annual service';
            $bullet3 = 'Includes Todyl Shield&trade; and Todyl Support&trade;';
            $depositText = 'A $350 deposit is required for Todyl Shield&trade; hardware.<br>This deposit is waived with annual sign up.';
        }

        $this->view->setVars(
            array(
                'form'         => $form,
                'bigHeadline'  => $bigHeadline,
                'leadSentence' => $leadSentence,
                'bullet1'      => $bullet1,
                'bullet2'      => $bullet2,
                'bullet3'      => $bullet3,
                'depositText'  => $depositText
            )
        );
    }
}
