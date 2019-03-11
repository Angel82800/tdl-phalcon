<?php

namespace Thrust\Controllers;

use Phalcon\Http\Response;
use Phalcon\Mvc\View;

use Thrust\Models\Api\Agent;
use Thrust\Models\EntAgent;

use Thrust\Helpers\DeviceHelper;

class DownloadController extends ControllerBase
{
    public function initialize()
    {
        if (! is_array($this->auth->getIdentity()) && $this->dispatcher->getActionName() !== 'magicDownload') {
            // user is not logged in
            $this->dispatcher->forward(
                array(
                    'controller' => 'index',
                    'action'     => 'index'
                )
            );
        } else {
            $this->view->setTemplateBefore('default'); // to disable the 'email sign up' popup
        }
    }

    public function indexAction()
    {
        // Forward flow to the landing page
        $this->dispatcher->forward(
            array(
                'controller' => 'index',
                'action'     => 'index'
            )
        );
    }

    /**
     * download executable
     */
    public function downloadAction()
    {
		$files = scandir($this->config->application->downloadDir);

		//Find a list of files by type
		$file_names = array_filter($files, function ($haystack) {
            $os = $this->request->getQuery()['os'];

			if ($os == "mac") { $search = ".dmg";
			} else if ($os == "win") { $search = ".exe"; }

			return(strpos($haystack, $search));
		});

		// find latest file
		$time = 0;
		foreach ($file_names as $key => $value) {
			$new_time = filemtime($this->config->application->downloadDir."/".$value);
			if ($new_time > $time) {
				$time = $new_time;
				$name = $value;
				$latest = $this->config->application->downloadDir."/".$value;
			}
		}

        // turn off compression on the server
        @apache_setenv('no-gzip', 1);
        @ini_set('zlib.output_compression', 'Off');

        // make sure the file exists
        if (is_file($latest)) {
            // set the headers, prevent caching
            header('Pragma: public');
            header('Expires: -1');
            header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
            header('Content-Disposition: attachment; filename="' . $value . '"');
            header('Content-Type: application/octet-stream');
            header('Pragma: public');
            header('Content-Length: ' . filesize($latest));
            readfile($latest);
            exit;
        } else {
            exit;
        }
    }

    /**
     * check if device agent is registered
     * @return bool [whether the device is registered]
     */
    public function checkDeviceAction()
    {
        $this->view->disable();
        $content = [
            'message' => 'Oops! Something went wrong. Please try again later.',
        ];

        $response = new \Phalcon\Http\Response();
        $response->setStatusCode(400, 'Bad Request');

        if ($this->request->isPost() && $this->request->isAjax()) {
            $response->setStatusCode(200);

            $pin = $this->request->getPost('pin');

            $device = EntAgent::findFirst([
                'conditions' => 'install_pin = ?1 AND pin_used = 1',
                'bind'       => [
                    1 => strtolower($pin),
                ],
                'cache'      => false,
            ]);

            if ($device) {
                $this->flashSession->success('Your device has been registered successfully.');

                $content = [
                    'status'    => 'success',
                ];
            } else {
                $content = [
                    'status'    => 'fail',
                ];
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

    public function magicDownloadAction()
    {
        $identity = $this->auth->getIdentity();

        $this->view->disableLevel(
            View::LEVEL_LAYOUT
        );
        $this->view->setTemplateBefore('session');

        $download_GUID = $this->dispatcher->getParam('code');

        $agent = EntAgent::findFirst([
            'conditions' => 'download_GUID = ?1 AND pin_used = 0',
            'bind'       => [
                1 => $download_GUID,
            ],
            'cache'      => false,
        ]);

        $helper = new DeviceHelper();

        $pin = $helper->checkMagicLink($download_GUID);

        if ($pin == 'expired') {
            // throw new \Exception('Magic link expired');

            $message = 'Sorry, this link has expired.';
            $url = '/user-device';

            if (! is_array($identity)) {
                $message .= ' Please log in to add a new device.';
                $url = '/session/login';
            }

            $this->flashSession->error($message);

            return $this->response->redirect($url);

            // $this->dispatcher->forward(
            //     array(
            //         'controller' => 'index',
            //         'action'     => 'index'
            //     )
            // );
        } else if ($pin == 'not_found') {
            // throw new \Exception('Magic link not valid');
            $message = 'Sorry, this link is not valid.';
            $url = '/user-device';

            if (! is_array($identity)) {
                $message .= ' Please log in to add a new device.';
                $url = '/session/login';
            }

            $this->flashSession->error($message);

            return $this->response->redirect($url);
        } else if (substr($pin, 0, 4) == 'INV-') {
            if (! is_array($identity) || $identity['id'] != $agent->fk_ent_users_id) {
                // purge session
                $this->auth->remove();

                $this->logger->info('[DOWNLOAD] Forwarding to user invitation');

                // download page for invited user
                $this->session->set('invite_download_GUID', $download_GUID);

                // $this->view->setLayout('');
                $this->view->disableLevel(
                    View::LEVEL_LAYOUT
                );

                return $this->dispatcher->forward([
                    'controller' => 'session',
                    'action'     => 'invite'
                ]);
            }

            $pin = substr($pin, 4);
        }

        // link is valid, continue

        // check current logged in user
        if (! $identity || ($identity && $identity['id'] != $agent->fk_ent_users_id)) {
            // user id doesn't match, purge session
            $this->logger->info('[DOWNLOAD] Purging session...');

            $this->auth->remove();

            $this->flashSession->clear();

            // log in user for the device
            $this->auth->authUserById($agent->fk_ent_users_id);
        }

        $os = isset($this->request->getQuery()['os']) ? $this->request->getQuery()['os'] : 'win';

        if (isset($this->request->getQuery()['dl'])) {
            // download software

            // update agent
            $agent->download_time = date('Y-m-d H:i:s');
            $agent->download_GUID = '';

            if ($agent->update()) {
                return $this->downloadAction();
            } else {
                throw new \Exception('Error while updating agent');
            }
        } else {
            $info = [
                'os'        => $os,
                'pin'       => $pin,
            ];

            $this->view->setVars($info);

        }
    }
}
