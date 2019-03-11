<?php

namespace Thrust\Controllers;

use Thrust\Forms\ContactsForm;
use Thrust\Models\LogSignupSubmittedInfo;

/**
 * Handles submissions of email signup form.
 */
class EmailsignupController extends ControllerBase
{
    public function indexAction()
    {
        $form = new ContactsForm();
        $this->view->disable();
        $message = 'Oops! Something went wrong. Please try again later.';

        $response = new \Phalcon\Http\Response();
        $response->setStatusCode(400, 'Bad Request');

        if ($this->request->isPost() && $this->request->isAjax()) {
            $params = array(
                'name'                 => $this->request->getPost('name', 'striptags', 'Email Footer'),
                'email'                => $this->request->getPost('email'),
                'businessSize'         => $this->request->getPost('businessSize', null, 'default'),
                'g-recaptcha-response' => $this->request->getPost('g-recaptcha-response'),
                'csrf'                 => $this->request->getPost('csrf')
            );

            if ($form->isValid($params) != false) {
                $contact = new LogSignupSubmittedInfo();

                $contact->assign($params);

                if ($contact->save()) {
                    // TODO: Resolve this issue. This code isn't run as the response/status is set
                    // by the the afterSave() method of $contact which is a network call to AWS SES
                    $message = "Thanks - We\'ll let you know when Todyl Protection&#8482;is available!";
                    $response->setStatusCode(200);
                } else {
                    $message = $contact->getMessages()[0]->getMessage();
                }
            } else {
                $message = $form->getMessages()[0]->getMessage();
            }
        }

        $response->setContent($message);
        $response->send();
        exit;
    }
}
