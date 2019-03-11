<?php

namespace Thrust\Controllers;

use Thrust\Models\DashboardStatistics;
use Thrust\Models\EntAlert;
use Thrust\Models\EntUsers;

use Thrust\Helpers\FtuHelper;

/**
 * Thrust\Controllers\ActivityController.
 */
class ActivityController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateBefore('private');
    }

    public function indexAction()
    {
        $identity = $this->auth->getIdentity();
        $user_id = $identity['id'];

        $data = [
            'loading_message' => 'Retrieving your recent activity...',
        ];

        $this->view->setVars($data);
    }

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

            $identity = $this->auth->getIdentity();
            $user_id = $identity['id'];

            $user = EntUsers::findFirst([
                'conditions' => 'pk_id = ?1',
                'bind'       => [
                    1 => $user_id,
                ],
                'cache'      => 60,
            ]);

            $stats = new DashboardStatistics();

            if ($type == 'stats') {
                $connected_text = ($stats->connectedDevices($user_id) ? $stats->connectedDevices($user_id) : 0) . ' of ' . $user->getDeviceCount();

                $blocked_threats = is_array($stats->totalUserAlerts($user_id, 30)) && isset($stats->totalUserAlerts($user_id, 30)['block']) ? $stats->totalUserAlerts($user_id, 30)['block'] : 0;

                $content = [
                    'status'            => 'success',
                    'hours_protected'   => $stats->getPastMonthOnlineTime($user_id) . ' Hrs',
                    'blocked_threats'   => $blocked_threats,
                    'connected_text'    => $connected_text,
                ];
            } else if ($type == 'list') {
                $period = $this->request->getPost('period');

                $organization = $user->getOrganization([ 'cache' => 60 ]);

                // filters for activity list
                $filters = [
                    'interval'  => $period,
                ];

                $activities = $stats->activityList($user_id, $filters);

                $activity_list = '';
                foreach ($activities as $activity) {
                    $activity_list .= '<tr>';
                        $activity_list .= '<td width="15%">' . date('F j g:i A', strtotime($activity['datetime_created'])) . '</td>';
                        $activity_list .= '<td width="10%">' . ucfirst($activity['priority']) . '</td>';
                        $activity_list .= '<td width="15%">' . $activity['location'] . '</td>';
                        $activity_list .= '<td width="15%">' . $activity['short_alert_summary'] . '</td>';
                        $activity_list .= '<td width="30%" data-tooltip data-allow-html="true" title="' . $activity['description'] . '">' . (strlen($activity['description']) > 50 ? substr($activity['description'], 0, 50) . '...' : $activity['description']) . '</td>';
                        $activity_list .= '<td width="15%">' . ucfirst($activity['result']) . '</td>';
                    $activity_list .= '</tr>';
                }

                $content = [
                    'status'            => 'success',
                    'activity_list'     => $activity_list,
                ];
            } else if ($type == 'pass_ftu') {
                $helper = new FtuHelper();

                $add_ftu = $helper->addFtuHistory($user_id, 'activity', 'todyl_info');

                if ($add_ftu['status'] == 'success') {
                    $content = [
                        'status'            => 'success',
                    ];
                } else {
                    $content = [
                        'status'            => 'fail',
                        'error'             => 'There was an error while processing your action.',
                        // 'error'             => implode('<br />', $add_ftu['error']),
                    ];
                }
            } else {
                // action not found
                $response->setStatusCode(400, 'Bad Request');
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

}
