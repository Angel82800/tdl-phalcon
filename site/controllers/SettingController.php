<?php

namespace Thrust\Controllers;

use Thrust\Stripe\Api as Stripe;

use Thrust\Models\EntSettings;

/**
 * Thrust\Controllers\SettingController.
 */
class SettingController extends ControllerBase
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

        $settings = EntSettings::find([
            'is_active' => true,
            'cache'     => false,
        ]);

        $settingArr = [];
        foreach ($settings as $setting) {
            $settingArr[$setting->key] = $setting->value;
        }

        // get all stripe plans
        $stripe = new Stripe();
        $plans = $stripe->listPlans()->data;

        // echo '<pre>'; print_r($plans); echo '</pre>'; exit;

        $data = [
            'settings'  => $settingArr,
            'plans'     => $plans,
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

            $data = $this->request->getPost();

            $settings = EntSettings::findFirst([
                'conditions' => 'key = ?1',
                'bind'       => [
                    1 => $data['key'],
                ],
                'cache'      => false,
            ]);

            if (! $settings) {
                // create new
                $settings = new EntSettings();

                $settingsData = [
                    'key'       => $data['key'],
                    'value'     => $data['value'],
                    'created_by'=> 'thrust',
                    'updated_by'=> 'thrust',
                ];

                if ($settings->create($settingsData) === false) {
                    $error = implode('<br />', $settings->getMessages());
                } else {
                    $this->logger->info('[SETTINGS] Successfully created setting - ' . $data['key'] . ' : ' . $data['value']);
                }
            } else {
                // update
                $settings->value = $data['value'];

                if ($settings->update() === false) {
                    $error = implode('<br />', $settings->getMessages());
                } else {
                    $this->logger->info('[SETTINGS] Successfully updated setting - ' . $data['key'] . ' : ' . $data['value']);
                }
            }

            if (isset($error)) {
                $content = [
                    'status'    => 'fail',
                    'error'     => $error,
                ];
            } else {
                $content = [
                    'status'    => 'success',
                    'key'       => $settings->key,
                    'value'     => $settings->value,
                    'message'   => 'Settings updated successfully',
                ];
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

}
