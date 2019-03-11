<?php

namespace Thrust\Controllers;

use Thrust\Models\Api\JobModel;
use Thrust\Models\EntAgent;
use Thrust\Models\EntOrganization;
use Thrust\Models\EntUsers;

use Thrust\Stripe\Api as Stripe;

use Thrust\Helpers\BillingHelper;
use Thrust\Helpers\DeviceHelper;
use Thrust\Helpers\MailHelper;

/**
 * Handles various API endpoints
 */
class JobsController extends ControllerBase
{
    protected $model;
    protected $mail;

    public function initialize()
    {
        // api authentication
        if ($this->request->getHeader('internalApiSecret') != $this->config->internalApiSecret->key) {
            $this->response->setStatusCode(503, 'Service Unavailable')->send();
            exit;
        }

        $this->model = new JobModel();
        $this->mail = new MailHelper();
    }

    /**
     * send weekly summary emails
     */
    public function weeklysummaryAction()
    {
        $template_data = []; // container for template specific data

        $email_count = 0;

        $helper = new DeviceHelper();

        $organizations = $this->model->getUDIDsForOrg();

        $threatIndicators = $this->model->getPastWeekThreatIndicators();

        if (isset($threatIndicators['indicator_count'])) {
            $threats_added = number_format($threatIndicators['indicator_count']);
            $threats_percentage = $threatIndicators['total'] ? round($threatIndicators['positives'] / $threatIndicators['total'] * 100) : 0;
        } else {
            $threats_added = 0;
            $threats_percentage = 0;
        }

        $this->logger->info('[JOB] Starting Weekly Summary Email Job...');

        // $organizations = array_slice($organizations, 0, 1);

        foreach ($organizations as $organization) {
            $this->logger->info('Sending weekly summary email to organization ID ' . $organization['orgId']);

            $udids = $organization['UDIDs'];

            $pastweek_alerts = $this->model->getPastWeekAlerts($udids);

            $pastweek_blocks = isset($pastweek_alerts['block']) ? $pastweek_alerts['block'] : 0;
            $past_avg_blocks = $this->model->getPastAvgBlocks($udids) * 7;

            $dataTotal = $this->model->getPastWeekDataTotal($udids);
            $pastDataTotal = $this->model->getPast2WeeksDataTotal($udids);

            if ($pastDataTotal) {
                $percentage = ($dataTotal - $pastDataTotal) / $pastDataTotal * 100;
            } else {
                $percentage = $dataTotal ? 100 : 0;
            }

            // get last week range string
            $before = new \DateTime('7 days ago');
            $now = new \DateTime('1 days ago');

            $range = $before->format('M j') . ' - ' . $now->format('M j');

            if ($pastweek_blocks > $past_avg_blocks * 1.1) {
                $blocked_history = '<b>This is higher than usual</b> based on your history.';
            } else if ($pastweek_blocks < $past_avg_blocks * 0.9) {
                $blocked_history = '<b>This is less than usual</b> based on your history.';
            } else {
                $blocked_history = '<b>This is within the normal range</b> based on your history.';
            }

            $org_connected_devices = $this->model->getConnectedDevices($udids);

            // send email to all users in organization
            foreach ($organization['users'] as $user) {
                // check users with less than 100mb protected data
                if ($dataTotal < 0.1) {
                    if ($org_connected_devices) {
                        // user has connected devices
                        $emailTemplate = 'weekly-summary-nodata';
                    } else {
                        // user doesn't have any connected devices
                        $emailTemplate = 'weekly-summary-nodevice';

                        $template_data = [
                            'GUID' => $helper->getMagicLink($user['userId']),
                        ];
                    }
                } else {
                    $emailTemplate = 'weekly-summary';
                }

                // data to be used for the email
                $data = array_merge($template_data, [
                    'firstName'         => ucfirst($user['firstName']),
                    'range'             => $range,
                    'data_protected'    => $dataTotal,
                    'percentage'        => round($percentage),
                    'traffic_blocked'   => number_format($pastweek_blocks),
                    'blocked_history'   => $blocked_history,
                    'threats_added'     => $threats_added,
                    'threats_percentage'=> $threats_percentage,
                ]);

                // echo $this->getDI()->getMail()->getTemplate($emailTemplate, $data);
                $this->mail->sendMail($user['userId'], $emailTemplate, $data);
                // $this->getDI()->getMail()->send($user['email'], 'Your Weekly Todyl Protection Summary', $emailTemplate, $data);
                // $this->getDI()->getMail()->send('devemail@todyl.com', 'Your Weekly Todyl Protection Summary - ' . $organization['orgId'], $emailTemplate, $data);

                // sleep to meet per second send limit
                // usleep($this->getDI()->get('config')->awsSes->interval);

                $email_count ++;
            }
        }

        $this->logger->info('[JOB] Successfully sent weekly summary emails to ' . $email_count . ' users');

        exit;
    }

    /**
     * send registration pin email to users who haven't used
     * the generated pin until 10 mins after registration
     */
    public function nodeviceftuAction()
    {
        // save emailed users to remove duplicates
        $sent_users = [];

        $email_count_user = 0;
        $email_count_org = 0;

        $helper = new DeviceHelper();

        // send email to users who haven't used the pin 10 mins after registration

        $users = $this->model->getUsersWithDeviceString();

        $this->logger->info('[JOB] Starting Pin Notification Email Job');

        foreach ($users as $user) {
            // check if user has only one unused pin, and no used pins
            if ($user['device_string'] == '0:1') {
                $time_elapsed = time() - strtotime($user['device_created']);

                // check if 10 mins have passed since registration
                if (20 * 60 >= $time_elapsed && $time_elapsed >= 10 * 60) {
                    if ($helper->getPin($user['GUID'], true)) {
                        $sent_users[] = $user['pk_id'];

                        $email_count_user ++;
                    }
                }
            }
        }

        // send email to org admins who haven't generated the pin after registration

        $org_admins = $this->model->getOrgswithNoDevices();

        foreach ($org_admins as $org_admin) {
            if ($org_admin['device_count'] == 0) {
                $time_elapsed = time() - strtotime($org_admin['datetime_created']);

                // check if 10 mins have passed since registration
                if (20 * 60 >= $time_elapsed && $time_elapsed >= 10 * 60) {
                    if (in_array($org_admin['admin_id'], $sent_users)) {
                        // email was already sent to this user in previous process

                        $this->logger->info('[JOB] Device activation email has already been sent to user ID ' . $org_admin['admin_id']);
                    } else {
                        if ($helper->getPin($org_admin['admin_GUID'], true)) {
                            $sent_users[] = $org_admin['admin_id'];

                            $email_count_org ++;
                        }
                    }
                }
            }
        }

        $this->logger->info('[JOB] Successfully sent pin notification emails to ' . $email_count_user . ' users and ' . $email_count_org . ' organization admins. User IDs: ' . implode(', ', $sent_users));

        exit;
    }

    /**
     * Daily job to handle pending suspensions
     * processes organizations, users and agents
     */
    public function handleSuspensionAction()
    {
        $this->logger->info('[JOB] Running suspension task...');

        $suspension_query = 'suspend_date <= CURDATE() AND execute_time is null AND (reactivated is null OR reactivated = 0)';

        //--- process organizations

        $organizations = EntOrganization::find([
            'conditions' => $suspension_query,
            'cache'      => false,
        ]);

        foreach ($organizations as $organization) {
            $organization->execute_time = date('Y-m-d H:i:s');
            $organization->is_active = 0;

            $organization->save();

            $this->logger->info('[JOB] Suspended org ID ' . $organization->pk_id);
        }

        //--- process users

        $users = EntUsers::find([
            'conditions' => $suspension_query,
            'cache'      => false,
        ]);

        foreach ($users as $user) {
            $user->execute_time = date('Y-m-d H:i:s');
            $user->is_active = 0;

            $user->save();

            $this->logger->info('[JOB] Suspended user ID ' . $user->pk_id);
        }

        //--- process devices

        $agents = EntAgent::find([
            'conditions' => $suspension_query,
            'cache'      => false,
        ]);

        foreach ($agents as $agent) {
            $agent->execute_time = date('Y-m-d H:i:s');
            $agent->is_active = 0;

            $agent->save();

            $this->logger->info('[JOB] Suspended device ID ' . $agent->pk_id);
        }

        $this->logger->info('[JOB] Suspension task finished.');

        exit;
    }

    /**
     * sync after new changes
     * * 1 - generate pins for all available slots
     * * 2 - update stripe subscriptions for mismatches
     */
    public function syncAction()
    {
        $this->logger->info('[JOB] Running sync task...');

        $billing = new BillingHelper();
        $device = new DeviceHelper();
        $stripe = new Stripe();

        //--- generate pins

        $this->logger->info('[JOB] 1. Running pin generation task...');

        $users = $this->model->getUserDeviceCount();

        foreach ($users as $user) {
            $diff_count = $user['max_devices'] - $user['device_count'];

            // generate devices for the diff count
            if ($diff_count) {
                $this->logger->info('[JOB] Found ' . $diff_count . ' differences for user id ' . $user['user_id']);
                $device->generatePins($user['user_id'], $diff_count);
            }
        }

        //--- update stripe subscriptions

        $this->logger->info('[JOB] 2. Running stripe sync task...');

        $organizations = $this->model->getOrgDeviceCount();

        foreach ($organizations as $organization) {
            if (! $organization['stripe_customer_id']) {
                $this->logger->info('[JOB] Skipping org ID ' . $organization['org_id'] . ' as there\'s no stripe customer id set...');
                continue;
            }

            $stripeCustomer = $stripe->getCustomer($organization['stripe_customer_id']);

            $subscription_quantity = $stripeCustomer->subscriptions->data[0]->items->data[0]->quantity;

            if ($subscription_quantity != $organization['device_count']) {
                // update stripe subscription

                $data = [
                    'items' => [
                        [
                            'id'        => $stripeCustomer->subscriptions->data[0]->items->data[0]->id,
                            'quantity'  => $organization['device_count'],
                        ],
                    ],
                ];

                $new_subscription = $stripe->updateSubscription($stripeCustomer->subscriptions->data[0]->id, $data);

                if (! $new_subscription) {
                    $this->logger->info('[JOB] Error while updating stripe subscription for org ID ' . $organization['org_id'] . ' : ' . $this->session->get('stripe_error'));

                    $this->session->remove('stripe_error');
                } else {
                    $this->logger->info('[JOB] Updated subscription of org ID ' . $organization['org_id'] . ' from ' . $subscription_quantity . ' to ' . $organization['device_count']);
                }
            }
        }

        $this->logger->info('[JOB] Sync task finished.');

        exit;
    }

    /**
     * clean up everything to match new structure
     * *
     * * 1 - generate GUIDs for existing users
     */
    public function cleanAction()
    {
        $this->logger->info('[JOB] Running clean task...');

        // loop through all undeleted users
        $users = EntUsers::find([
            'conditions' => 'GUID IS NULL AND is_deleted = 0',
            'cache'      => false,
        ]);

        foreach ($users as $user) {
            $user->GUID = preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(openssl_random_pseudo_bytes(24)));

            if ($user->update()) {
                $this->logger->info('[JOB] Added GUID for User ID: ' . $user->pk_id);
            } else {
                $this->logger->info('[JOB] Error while adding GUID for User ID: ' . $user->pk_id . ', Error: ' . implode('<br />', $user->getMessages()));
            }
        }

        $this->logger->info('[JOB] Clean task finished.');

        exit;
    }
}
