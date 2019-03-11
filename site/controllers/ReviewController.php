<?php

namespace Thrust\Controllers;

use Thrust\Models\DashboardStatistics;
use Thrust\Models\AttrRoles;
use Thrust\Models\EntAlert;
use Thrust\Models\EntUsers;

use Thrust\Models\AttrIncidentClassification;
use Thrust\Models\AttrIncidentState;
use Thrust\Models\EntIncident;
use Thrust\Models\LogIncidentComments;

/**
 * Thrust\Controllers\ReviewController.
 */
class ReviewController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateBefore('private');

        $identity = $this->auth->getIdentity();
        // check if admin
        if ($identity['orgId'] != 1) {
            throw new \Exception('User ID ' . $identity['id'] . ' tried to enter incidents review page.');
        }
    }

    public function indexAction()
    {
        $data = [
            'loading_message' => 'Retrieving alerts list...',
        ];

        $this->view->setVars($data);
    }

    public function viewIncidentAction()
    {
        $identity = $this->auth->getIdentity();
        $user_id = $identity['id'];

        $incident_id = $this->dispatcher->getParam('incident');

        $incident = EntIncident::findFirst([
            'conditions' => 'pk_id = ?1 AND is_active = 1 AND is_deleted = 0',
            'bind'       => [
                1        => $incident_id,
            ],
            'cache'      => 30,
        ]);

        if (! $incident) {
            $this->flashSession->error('No incident found');
            return $this->response->redirect('review');
        }

        $role = AttrRoles::findFirst([
            'conditions' => 'name = ?1 AND is_active = 1 AND is_deleted = 0',
            'bind'       => [
                1 => 'admin',
            ],
        ]);

        $assignees = EntUsers::find([
            'conditions'    => 'fk_attr_roles_id = ?1 AND is_active = 1 AND is_deleted = 0',
            'bind'          => [
                1 => $role->pk_id,
            ],
            'cache'         => 60,
        ]);

        $classifications = AttrIncidentClassification::find([
            'conditions'    => 'is_active = 1 AND is_deleted = 0',
        ]);

        $states = AttrIncidentState::find([
            'conditions'    => 'is_active = 1 AND is_deleted = 0',
        ]);

        $comments = LogIncidentComments::find([
            'conditions'    => 'fk_ent_incident_id = ?1 AND is_active = 1 AND is_deleted = 0',
            'bind'          => [
                1 => $incident_id,
            ],
            'cache'         => false,
        ]);

        $data = [
            'incident'          => $incident,
            'raw_alert'         => $incident->alert->raw,
            'assignees'         => $assignees,
            'classifications'   => $classifications,
            'states'            => $states,
            'comments'          => $comments,
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

            if ($type == 'list') {
                $show_resolved = $this->request->getPost('show_resolved');

                $incidents = $stats->incidentList([ 'show_resolved' => $show_resolved ]);

                $incident_list = '';
                foreach ($incidents as $incident) {
                    $incident_list .= '<tr data-link="/review/incident/' . $incident['incident_id'] . '">';
                        $incident_list .= '<td>' . date('F j g:i A', strtotime($incident['datetime_created'])) . '</td>';
                        $incident_list .= '<td>' . $incident['email'] . '</td>';
                        $incident_list .= '<td>' . $incident['short_alert_summary'] . '</td>';
                        $incident_list .= '<td>' . ucfirst($incident['action_taken']) . '</td>';
                        $incident_list .= '<td>Misc</td>';
                        $incident_list .= '<td><i class="i-right"></i></td>';
                    $incident_list .= '</tr>';
                }

                $content = [
                    'status'            => 'success',
                    'incident_list'     => $incident_list,
                    'incident_count'    => count($incidents),
                ];
            } else if ($type == 'saveIncident') {
                $incident_data = $this->request->getPost();

                $this->logger->info('[REVIEW] Start saving incident instructions for ID ' . $incident_data['incident_id']);

                // get incident to edit
                $incident = EntIncident::findFirst([
                    'conditions' => 'pk_id = ?1 AND is_active = 1 AND is_deleted = 0',
                    'bind'       => [
                        1 => $incident_data['incident_id'],
                    ],
                    'cache'      => 30,
                ]);

                if (! $incident) {
                    $content = [
                        'status'    => 'fail',
                        'message'   => 'Incident not found',
                    ];
                } else {
                    $incident->assigned_to = $incident_data['assign_to'];
                    $incident->fk_attr_incident_classification_id = $incident_data['classification'];
                    $incident->fk_attr_incident_state_id = $incident_data['mark_as'];
                    if ($incident_data['instructions']) {
                        $incident->user_instructions = $incident_data['instructions'];
                    }

                    if ($incident->update() === false) {
                        $content = [
                            'status'    => 'fail',
                            'message'   => 'An error occurred while updating incident',
                        ];
                    } else {
                        $this->flashSession->success('Incident Instructions saved successfully.');

                        $this->logger->info('[REVIEW] Successfully saved incident instructions for ID ' . $incident_data['incident_id']);

                        $content = [
                            'status'    => 'success',
                        ];
                    }
                }
            } else if ($type == 'saveComment') {
                $incident_data = $this->request->getPost();

                $this->logger->info('[REVIEW] Start saving incident comment for ID ' . $incident_data['incident_id']);

                $commentData = [
                    'fk_ent_incident_id'    => $incident_data['incident_id'],
                    'fk_ent_users_id'       => $user_id,
                    'comment'               => $incident_data['comment'],
                    'created_by'            => 'thrust',
                    'updated_by'            => 'thrust',
                ];

                $comment = new LogIncidentComments();
                if ($comment->create($commentData) === false) {
                    $this->logger->error('[REVIEW] Error while commenting on incident ID ' . $incident_data['incident_id'] . ': ' . implode('<br />', $comment->getMessages()));

                    $content = [
                        'status'    => 'fail',
                        'message'   => 'An error occurred while commenting on incident',
                    ];
                } else {
                    $this->flashSession->success('Incident comment saved successfully.');

                    $this->logger->info('[REVIEW] Successfully saved incident comment for ID ' . $incident_data['incident_id']);

                    $content = [
                        'status'    => 'success',
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
