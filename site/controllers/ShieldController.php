<?php

namespace Thrust\Controllers;

use Thrust\Models\EntUtmSettings;

/**
 * Thrust\Controllers\ShieldController.
 */
class ShieldController extends ControllerBase
{
    public function initialize()
    {
        $this->db = \Phalcon\Di::getDefault()->get('oltp-read');

        $this->view->setTemplateBefore('private');

        $identity = $this->auth->getIdentity();
        // check if admin
        // DEMO
        if ($identity['orgId'] != 1 && $identity['email'] != 'demo@todyl.com') {
            throw new \Exception('User ID ' . $identity['id'] . ' tried to enter shield page.');
        }
    }

    public function indexAction()
    {
        $identity = $this->auth->getIdentity();

        $utm_settings = EntUtmSettings::findFirst([
            'cache'      => 60,
        ]);

        $data = [
            'settings'  => $utm_settings,
        ];

        $this->view->setVars($data);
    }

    /**
     * ajax handler
     */
    public function manageAction()
    {
        $this->view->disable();
        $content = [
            'message' => 'Oops! Something went wrong. Please try again later.',
        ];

        $response = new \Phalcon\Http\Response();
        $response->setStatusCode(400, 'Bad Request');

        if ($this->request->isPost() && $this->request->isAjax()) {
            $response->setStatusCode(200);

            $type = $this->request->getPost('type');
            $data = $this->request->getPost();

            if ($type == 'network') {
                $utm_settings = EntUtmSettings::findFirst([
                    'cache'      => false,
                ]);

                $name = $data['name'];
                $password = $data['password'];

                $utm_settings->secure_wireless_name = $name;
                $utm_settings->secure_wireless_password = $password;
                $utm_settings->update();

                $content = [
                    'status'    => 'success',
                    'name'      => $name,
                    'password'  => $password,
                    'message'   => 'Network settings updated successfully',
                ];
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

}
