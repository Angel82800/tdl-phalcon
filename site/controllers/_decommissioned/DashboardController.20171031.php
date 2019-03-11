<?php

namespace Thrust\Controllers;

use Thrust\Models\DashboardStatistics;
use Thrust\Models\EntAgent;
use Thrust\Models\EntUsers;
use Thrust\Models\EntOrganization;
use Thrust\Models\EntThreatLevel;

use Thrust\Helpers\DeviceHelper;

/**
 * Thrust\Controllers\DashboardController.
 */
class DashboardController extends ControllerBase
{
    protected $deviceHelper;
    protected $stats;

    public function initialize()
    {
        $this->view->setTemplateBefore('private');

        $this->deviceHelper = new DeviceHelper();
        $this->stats = new DashboardStatistics();
    }

    public function indexAction()
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

        $device_count = $this->stats->userDevices($user_id);

        if (! $device_count['total_count']) {
            // no devices for user

            $this->view->pick('dashboard/nothing');
        } else if (! $device_count['used_count']) {
            // device not installed yet - show download section

            $os = isset($this->request->getQuery()['os']) ? $this->request->getQuery()['os'] : 'win';

            $info = [
                'os'        => $os,
                'pin'       => '',
                'limited'   => false,
                'is_beta'   => $identity['is_beta'],
            ];

            // generate pin - but don't send email at first
            $pin = $this->deviceHelper->getPin($user->GUID, false);

            if ($pin) {
                // set layout back to default (after sending email)
                $this->view->setLayout('');

                $info['pin'] = $pin;
            } else {
                // device limit reached

                $info['limited'] = true;
            }

            $this->view->setVars($info);

            // show download page
            $this->view->pick('dashboard/nodevices');
        } else {
            // show default dashboard page

            $threat_levels = EntThreatLevel::find([
                'cache'      => false,
            ]);

            $data = [
                'threat_levels' => $threat_levels,
            ];

            $this->view->setVars($data);
        }
    }

    public function getstatsAction()
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

        $organization = $user->getOrganization([ 'cache' => 60 ]);

        $this->view->disable();
        $content = [
            'message' => 'Oops! Something went wrong. Please try again later.',
        ];

        $response = new \Phalcon\Http\Response();
        $response->setStatusCode(400, 'Bad Request');

        // if (true) {
        if ($this->request->isPost() && $this->request->isAjax()) {
            $response->setStatusCode(200);

            //--- latest threat indicator ---
            $latestThreatInfo = $this->stats->latestThreat();

            // preparation for calculating last 7 days
            $now = new \DateTime('6 days ago');
            $interval = new \DateInterval('P1D'); // 1 Day interval
            $period = new \DatePeriod($now, $interval, 6); // 6 Days

            //--- chart data

            // potential threats blocked
            $blocked_threats = $this->stats->blockedThreats($user_id);

            $blocked_threats_data = [];
            $blocked_threats_total = 0;

            foreach ($blocked_threats as $blocked_threat) {
                $blocked_threats_data[$blocked_threat['blocked_date']] = $blocked_threat['block_count'];
                $blocked_threats_total += $blocked_threat['block_count'];
            }

            // threat indicators

            $threat_indicators = $this->stats->threatIndicators();

            $threat_indicators_data = [];
            $threat_indicators_total = 0;

            foreach ($threat_indicators as $threat_indicator) {
                $threat_indicators_data[$threat_indicator['added_date']] = $threat_indicator['indicator_count'];
                $threat_indicators_total += $threat_indicator['indicator_count'];
            }

            // fill in missing chart data
            foreach ($period as $day) {
                $date = $day->format('Y-m-d');

                if (! isset($blocked_threats_data[$date])) {
                    $blocked_threats_data[$date] = 0;
                }

                if (! isset($threat_indicators_data[$date])) {
                    $threat_indicators_data[$date] = 0;
                }
            }

            $blocked_threat_chart = [
                [
                    'label'                 => 'Potential Threats Blocked',
                    'data'                  => array_values($blocked_threats_data),
                    'fill'                  => true,
                    'pointRadius'           => 7,
                    'pointHoverRadius'      => 8,
                    'borderWidth'           => 2,
                    'pointBackgroundColor'  => 'rgb(255,255,255)',
                    'backgroundColor'       => 'rgba(43,128,187,0.2)',
                    'borderColor'           => 'rgb(43,128,187)',
                ]
            ];

            $threat_indicator_chart = [
                [
                    'label'                 => 'Threat Indicators Added',
                    'data'                  => array_values($threat_indicators_data),
                    'fill'                  => true,
                    'pointRadius'           => 6,
                    'pointHoverRadius'      => 7,
                    'borderWidth'           => 2,
                    'pointBackgroundColor'  => 'rgb(255,255,255)',
                    'backgroundColor'       => 'rgba(188,195,197,0.3)',
                    'borderColor'           => 'rgb(188,195,197)',
                ]
            ];

            //--- your devices ---
            $device_status = $this->stats->deviceStatus($user->GUID);

            // if ($identity['role'] == 'user') {
            //     // load devices for user only
            //     $device_status = $this->stats->deviceStatus($user_id);
            // } else {
            //     // load devices for organization
            //     $device_status = $this->stats->deviceStatus($organization->pk_id, [ 'type' => 'organization' ]);
            // }

            $status_devices = [];

            // count connected devices
            $status_count = array_count_values(array_map(function($item) {
                return (! $item['datetime_disconnected'] && $item['datetime_connected']) ? 'connected' : 'disconnected';
            }, $device_status));
            $connected_count = isset($status_count['connected']) ? $status_count['connected'] : 0;

            // total device count
            $total_devices = count($device_status);

            // leave only first 3 device status
            $device_status = array_slice($device_status, 0, 3);

            foreach ($device_status as $status) {
                $status_html = '<div class="row expanded icon-element pb-1">';
                if (! $status['datetime_disconnected'] && $status['datetime_connected']) {
                    // connected
                    $status_html .= '<div class="element"><i class="text-green text-4em pad-r-2 i-' . $status["device_type"] . '"></i></div>';
                } else {
                    // disconnected
                    $status_html .= '<div class="element"><i class="text-mid-grey text-4em pad-r-2 i-' . $status["device_type"] . '"></i></div>';
                }
                $status_html .= '<div class="element">';

                $status_html .= '<p class="header">' . $status['user_device_name'] . '</p>';

                if (! $status['datetime_disconnected'] && $status['datetime_connected']) {
                    $status_html .= '<p class="sub_header hide-for-small-only">Connected | ' . $this->stats->formatBytes($status['protected_data'], 1) . ' Data Protected All Time</p>';
                } else {
                    if ($status['datetime_connected']) {
                        $status_html .= '<p class="sub_header hide-for-small-only">Last Connected: ' . date('F j, Y', strtotime($status['datetime_disconnected'])) . '</p>';
                    } else {
                        $status_html .= '<p class="sub_header hide-for-small-only">Not Connected</p>';
                    }
                }
                $status_html .= '</div></div>';

                $status_devices[] = $status_html;
            }

            $device_count = $this->stats->userDevices($user_id);
            if (count($status_devices) < 3 && $device_count['total_count']) {
                $status_devices[] = '<div class="row expanded icon-element">
                        <div class="element">
                            <i class="text-light-grey text-4em pad-r-2 i-add-device"></i>
                        </div>
                        <div class="element">
                            <p class="header text-light-grey">Activate protection on more devices</p>
                        </div>
                    </div>';
            }

            //--- internet threat level

            $threat_state = $this->stats->threatState();

            $threat_level_obj = EntThreatLevel::findFirst([
                'conditions' => 'LOWER(title) = ?1',
                'bind'       => [
                    1        => $threat_state,
                ],
                'cache'      => false
            ]);

            $threat_level = [
                'title'         => $threat_level_obj->title,
                'description'   => $threat_level_obj->description,
                'image_path'    => $threat_level_obj->image_path,
            ];

            //--- bottom stats ---

            // Data Protected
            $traffic = $this->stats->lifetimeUserData($user_id);

            if ($traffic != '0B') {
                // Potential Threats Blocked
                $blocks = $this->stats->totalUserAlerts($user_id)['block'];

                // Potential Threats Blocked Across All Customers
                $blocks_all = $this->stats->totalBlockedThreats();

                $bottom_stats = [
                    'stat_blocks'         => number_format($blocks),
                    'stat_blocks_all'     => number_format($blocks_all),
                ];
            } else {
                // Data protected all time
                $traffic = $this->stats->lifetimeData();

                // Potential threats blocked all time
                $blocks_all = $this->stats->totalBlockedThreats();

                // Malicious files blocked all time
                $malicious_files = $this->stats->maliciousfilesAll();

                $bottom_stats = [
                    'stat_blocks_all'     => number_format($blocks_all),
                    'stat_malicious'    => number_format($malicious_files),
                ];
            }

            $content = [
                'block_data'                => $blocked_threat_chart,
                'blocked_threats_total'     => $blocked_threats_total,
                'indicator_data'            => $threat_indicator_chart,
                'threat_indicators_total'   => number_format($threat_indicators_total),
                'latestThreat'              => $latestThreatInfo,
                'connected_count'           => $connected_count,
                'total_devices'             => $total_devices,
                'device_status'             => implode('', $status_devices),
                'threat_level'              => $threat_level,
                'stat_traffic'              => $traffic,
            ];

            $content = array_merge($content, $bottom_stats);

            // echo '<pre>';
            // print_r($content);
            // echo '</pre>';
            // exit;
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

    public function alertsAction()
    {
        $identity = $this->auth->getIdentity();

        $this->view->setVar('user_email', $identity['email']);
    }

    public function settingsAction()
    {

    }

    /**
     * check if device agent is registered
     * @return bool [whether the device is registered]
     */
    public function checkDeviceAction()
    {
        $identity = $this->auth->getIdentity();
        $user_id = $identity['id'];

        $this->view->disable();
        $content = [
            'message' => 'Oops! Something went wrong. Please try again later.',
        ];

        $response = new \Phalcon\Http\Response();
        $response->setStatusCode(400, 'Bad Request');

        if ($this->request->isPost() && $this->request->isAjax()) {
            $response->setStatusCode(200);

            $pin = $this->request->getPost('pin');

            if ($pin) {
                $device = EntAgent::findFirst([
                    'conditions' => 'install_pin = ?1 AND pin_used = 1',
                    'bind'       => [
                        1 => $pin,
                    ],
                    'cache'      => false,
                ]);
            } else {
                $device = EntAgent::findFirst([
                    'conditions' => 'fk_ent_users_id = ?1 AND pin_used = 1',
                    'bind'       => [
                        1 => $user_id,
                    ],
                    'cache'      => false,
                ]);
            }

            // $phql = 'SELECT COUNT(*) AS device_count FROM `ent_users` u
            //             LEFT JOIN `ent_agent` a ON u.pk_id = a.fk_ent_users_id
            //             WHERE a.pin_used = 1 AND u.pk_id = :user_id';
            // $row = $this->modelsManager->executeQuery($phql, [ 'user_id' => $user_id ])->getFirst();

            if ($device) {
                // update ftu status for left navigation
                $this->session->set('is_ftu', 0);

                $this->logger->info('[DASHBOARD] Device check detected installed device ID ' . $device->pk_id);

                $content = [
                    'status'    => 'success',
                ];
            } else {
                // check for 10 minute after registration

                // if ($this->session->get('registration_time')) {
                //     $elapsed_minutes = (time() - $this->session->get('registration_time')) / 60;

                //     if ($elapsed_minutes >= 10) {
                //         // 10 mins have passed and user hasn't installed device yet - send email

                //         $this->session->remove('registration_time');

                //         \Phalcon\Di::getDefault()->getShared('logger')->info('[DASHBOARD] Sending device installation email to ' . $user_id . ' due to inactivity in 10 mins after registration.');

                //         $pin = $helper->getPin($user_id, true);
                //     }
                // }

                $content = [
                    'status'    => 'fail',
                ];
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

    /**
     * account settings ajax handler
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

            if ($type == 'threat_level') {
                $threat_level = $this->request->getPost('threat_level');
                $description = $this->request->getPost('description');

                $current_threat_level = EntThreatLevel::findFirst([
                    'conditions' => 'is_active = 1 AND is_deleted = 0',
                    'cache'      => false,
                ]);

                if (! $current_threat_level || $current_threat_level->pk_id != $threat_level) {
                    if ($current_threat_level) {
                        $current_threat_level->is_active = 0;
                        $current_threat_level->datetime_updated = date('Y-m-d H:i:s');
                        $current_threat_level->updated_by = 'thrust';
                        $current_threat_level->update();
                    }

                    $new_threat_level = EntThreatLevel::findFirst([
                        'conditions' => 'pk_id = ?1',
                        'bind'       => [
                            1        => $threat_level,
                        ],
                        'cache'      => false,
                    ]);

                    $new_threat_level->description = $description;
                    $new_threat_level->datetime_updated = date('Y-m-d H:i:s');
                    $new_threat_level->updated_by = 'thrust';
                    $new_threat_level->is_active = 1;

                    $new_threat_level->update();
                } else {
                    $current_threat_level->description = $description;

                    $current_threat_level->update();
                }

                // delete cache
                $cache = \Phalcon\Di::getDefault()->get('modelsCache');
                $cache->delete('internet_threat_level');

                $content = [
                    'status'    => 'success',
                    'message'   => 'The internet threat level has been successfully updated.',
                ];

            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

}
