<?php

namespace Thrust\Controllers;

use Thrust\Models\Api\Agent;
use Thrust\Models\DashboardStatistics;
use Thrust\Models\EntAgent;
use Thrust\Models\EntUsers;
use Thrust\Models\EntOrganization;

use Thrust\Helpers\DeviceHelper;

/**
 * Thrust\Controllers\DeviceController.
 */
class DeviceController extends ControllerBase
{
    public function initialize()
    {
        $this->db = \Phalcon\Di::getDefault()->get('oltp-read');

        $this->view->setTemplateBefore('private');
    }

    public function indexAction()
    {
        $identity = $this->auth->getIdentity();
        $user_id = $identity['id'];

        $type = isset($this->request->getQuery()['type']) ? $this->request->getQuery()['type'] : 1;

        $stats = new DashboardStatistics();

        //--- your devices ---
        $device_status = $stats->deviceStatus($user_id, [ 'is_active' => $type ]);

        $connected_count = 0;
        foreach ($device_status as $key => $status) {
            // count connected devices
            if (! $status['datetime_disconnected'] && $status['datetime_connected']) {
                $connected_count ++;
            }

            $device_status[$key]['protected_data'] = $stats->formatBytes($status['protected_data'], 1);
        }

        // total device count
        $total_devices = count($device_status);

        $remaining_devices = $stats->userDevices($user_id)['unused_count'];

        if ($type) {
            $title = 'Todyl Defender <span class="text-light-grey">' . $connected_count . ' of ' . $total_devices . ' Connected</span>';
        } else {
            $title = 'Deactivated Devices <span class="text-light-grey">' . $total_devices . '</span>';
        }

        $data = [
            'title'             => $title,
            'devices'           => $device_status,
            'remaining_devices' => $remaining_devices,
            'type'              => $type == 1 ? 'active' : 'inactive',
        ];

        $this->view->setVars($data);
    }

    public function managementAction()
    {
        $identity = $this->auth->getIdentity();
        $user_id = $identity['id'];

        $user = EntUsers::findFirst([
            'conditions' => 'pk_id = ?1',
            'bind'       => [
                1 => $user_id,
            ],
            'cache'      => 60,
        ]);

        $type = $this->dispatcher->getParam('type');
        $device_id = $this->dispatcher->getParam('device_id');

        $stats = new DashboardStatistics();

        if ($type) { // management action on a device agent
            // check if user has permission on the device

            $device = false;
            if ($device_id) {
                $device = EntAgent::findFirst([
                    'conditions' => 'pk_id = ?1 AND fk_ent_users_id = ?2',
                    'bind'       => [
                        1 => $device_id,
                        2 => $user_id,
                    ],
                    'cache'      => false,
                ]);
            }

            if (! $device) {
                $this->flashSession->error('Sorry, we cannot find the device.');
                return $this->response->redirect('device');
            }

            // flush data
            $this->flushCache();

            $data = [
                'type'      => $type,
                'device'    => $device,
            ];
        } else { // new device
            // check if user has remaining devices available

            $remaining_devices = $stats->userDevices($user_id)['unused_count'];

            if (! $remaining_devices) {
                $this->flashSession->error('Sorry, you have already reached your device limit.');
                return $this->response->redirect('device');
            } else {
                // send activation email
                $helper = new DeviceHelper();

                $pin = $helper->getPin($user->GUID, true);

                if ($pin) {
                    // set layout back to default (after sending email)
                    $this->view->setLayout('');
                } else {
                    // device limit reached
                }
            }

            // flush data
            $this->flushCache();

            $data = [
                'type'      => 'new',
                'email'     => $identity['email'],
                'pin'       => $pin,
                'env'       => $this->config->environment->env,
            ];
        }

        $this->view->setVars($data);
    }

    public function pinAction()
    {
        $identity = $this->auth->getIdentity();
        $user_id = $identity['id'];

        $user = EntUsers::findFirst([
            'conditions' => 'pk_id = ?1',
            'bind'       => [
                1 => $user_id,
            ],
            'cache'      => 60,
        ]);

        // template variables init

        $os = isset($this->request->getQuery()['os']) ? $this->request->getQuery()['os'] : 'win';

        $info = [
            'os'        => $os,
            'pin'       => '',
            'limited'   => false,
        ];

        // generate pin

        $helper = new DeviceHelper();

        $pin = $helper->getPin($user->GUID);

        if ($pin) {
            // set layout back to default (after sending email)
            $this->view->setLayout('');

            $info['pin'] = $pin;
        } else {
            // device limit reached

            $info['limited'] = true;
        }

        $this->view->setVars($info);
    }

    /**
     * device management ajax handler
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
            $device_id = $this->request->getPost('device');

            $identity = $this->auth->getIdentity();

            // check if user has permission on the device
            $device = false;
            if ($device_id) {
                $device = EntAgent::findFirst([
                    'conditions' => 'pk_id = ?1 AND fk_ent_users_id = ?2',
                    'bind'       => [
                        1 => $device_id,
                        2 => $identity['id'],
                    ],
                    'cache'      => false,
                ]);
            }

            if (! $device) {
                $content = [
                    'status'    => 'fail',
                    'message'   => 'User not found',
                ];
            } else {
                if ($type == 'deactivate') {
                    try {
                        // deactivate device
                        $device->is_active = 0;
                        $device->update();

                        $this->flashSession->success('Protection for ' . $device->user_device_name . ' has been deactivated.');

                        // flush data
                        $this->flushCache();

                        $content = [
                            'status'    => 'success',
                        ];
                    } catch (\Exception $e) {
                        $this->logger->error('Device Update Exception: ' . $e->getMessage() . ' with trace: ' . (string) $e->getTraceAsString());

                        $content = [
                            'status'    => 'fail',
                            'message'   => 'An error occurred while deactivating ' . $device->user_device_name,
                        ];
                    }
                } else if ($type == 'reactivate') {
                    try {
                        // reactivate device

                        $agent = new Agent();

                        // check for device name duplicates
                        if ($agent->deviceNameTaken($this->db->escapeString($device->install_pin), $this->db->escapeString($device->user_device_name))) {
                            $content = [
                                'status'    => 'fail',
                                'message'   => 'The device name is already taken.',
                            ];
                        } else {
                            $device->is_active = 1;
                            $device->update();

                            $this->flashSession->success('Protection for ' . $device->user_device_name . ' has been reactivated.');

                            // flush data
                            $this->flushCache();

                            $content = [
                                'status'    => 'success',
                            ];
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('Device Update Exception: ' . $e->getMessage() . ' with trace: ' . (string) $e->getTraceAsString());

                        $content = [
                            'status'    => 'fail',
                            'message'   => 'An error occurred while reactivating ' . $device->user_device_name,
                        ];
                    }
                } else if ($type == 'rename') {
                    try {
                        // rename device

                        $new_device_name = $this->request->getPost('new_name');
                        $old_device_name = $device->user_device_name;
                        $device->user_device_name = $new_device_name;
                        $device->update();

                        $this->flashSession->success($old_device_name . ' has been renamed to ' . $new_device_name . '.');

                        // flush data
                        $this->flushCache();

                        $content = [
                            'status'    => 'success',
                        ];
                    } catch (\Exception $e) {
                        $this->logger->error('Device Update Exception: ' . $e->getMessage() . ' with trace: ' . (string) $e->getTraceAsString());

                        $content = [
                            'status'    => 'fail',
                            'message'   => 'An error occurred while forgetting ' . $device->user_device_name,
                        ];
                    }
                }
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

    protected function flushCache()
    {
        // flush cache keys for queries used

        $identity = $this->auth->getIdentity();
        $user_id = $identity['id'];

        $stats = new DashboardStatistics();

        $stats->deviceStatus($user_id, [], 'flush');
        $stats->deviceStatus($user_id, [ 'is_active' => 0 ], 'flush');
        $stats->userDevices($user_id, 'flush');
    }

}
