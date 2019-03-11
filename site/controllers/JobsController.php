<?php

namespace Thrust\Controllers;

use Thrust\Models\Api\JobModel;
use Thrust\Models\EntAgent;
use Thrust\Models\EntLeads;
use Thrust\Models\EntOrganization;
use Thrust\Models\EntStripeSubscription;
use Thrust\Models\EntUsers;
use Thrust\Models\EntAlertReview;

use Thrust\OpSwat\FileHash as OpSwatHash;
use Thrust\Stripe\Api as Stripe;

use Thrust\Helpers\BillingHelper;
use Thrust\Helpers\DeviceHelper;
use Thrust\Helpers\MailHelper;

/**
 * Handles various API endpoints
 */
class JobsController extends ControllerBase
{
    protected $config;
    protected $model;
    protected $mail;
    protected $stripe;
    protected $agentFiles;

    protected $email_template = 'job-alert';

    public function initialize()
    {
        // api authentication
        if ($this->request->getHeader('internalApiSecret') != $this->config->internalApiSecret->key) {
            $this->response->setStatusCode(503, 'Service Unavailable')->send();
            exit;
        }

        $this->config = \Phalcon\Di::getDefault()->get('config');
        $this->model = new JobModel();
        $this->mail = new MailHelper();
        $this->stripe = new Stripe();
    }

    /**
     * send weekly summary emails
     */
    public function weeklysummaryAction()
    {
        $template_data = []; // container for template specific data

        $email_count = 0;

        $helper = new DeviceHelper();

        $users = $this->model->getUDIDsForUsers();

        $threatIndicators = $this->model->getPastWeekThreatIndicators();

        if (isset($threatIndicators['indicator_count'])) {
            $threats_added = number_format($threatIndicators['indicator_count']);
            $threats_percentage = $threatIndicators['total'] ? round($threatIndicators['positives'] / $threatIndicators['total'] * 100) : 0;
        } else {
            $threats_added = 0;
            $threats_percentage = 0;
        }

        $this->logger->info('[JOB] Starting Weekly Summary Email Job...');

        foreach ($users as $user) {
            $this->logger->info('[JOB] Calculating weekly summary data for user ID ' . $user['userId']);

            $udids = $user['UDIDs'];

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

            $user_connected_devices = $this->model->getConnectedDevices($udids);

            // check users with less than 100mb protected data
            if ($dataTotal < 104857600) {
                if ($user_connected_devices) {
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

            $this->logger->info('[JOB] Weekly data total bytes for user ID ' . $user['userId'] . ': ' . $dataTotal . ' and selected template `' . $emailTemplate . '`');

            //Format data protected
            if ($dataTotal < 1000000000 ) {
                //Show in MB
                $data_protected['value'] = number_format($dataTotal / 1000 / 1000, 1, '.', ',');
                $data_protected['unit'] = 'MB';
            } elseif ($dataTotal < 100000000000) {
                //Show in GB
                $data_protected['value'] = number_format($dataTotal / 1000 / 1000 / 1000, 1, '.', ',');
                $data_protected['unit'] = 'GB';
            } else {
                //Show in TB
                $data_protected['value'] = number_format($dataTotal / 1000 / 1000 / 1000 / 1000, 1, '.', ',');
                $data_protected['unit'] = 'TB';
            }


            // data to be used for the email
            $data = array_merge($template_data, [
                'firstName'         => ucfirst($user['firstName']),
                'range'             => $range,
                'data_protected'    => $data_protected,
                'percentage'        => round($percentage),
                'traffic_blocked'   => number_format($pastweek_blocks),
                'blocked_history'   => $blocked_history,
                'threats_added'     => $threats_added,
                'threats_percentage'=> $threats_percentage,
            ]);

            // echo $this->getDI()->getMail()->getTemplate($emailTemplate, $data);
            // $this->mail->sendMail($user['userId'], $emailTemplate, $data);
            $this->getDI()->getMail()->send($user['email'], 'Your Weekly Todyl Protection Summary', $emailTemplate, $data);
            // $this->getDI()->getMail()->send('devemail@todyl.com', 'Your Weekly Todyl Protection Summary - ' . $user['userId'], $emailTemplate, $data);

            // sleep to meet per second send limit
            // usleep($this->getDI()->get('config')->awsSes->interval);

            $this->logger->info('[JOB] Sent weekly summary data to user ID ' . $user['userId'] . ', email: ' . $user['email']);

            $email_count ++;
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
     * compare DB counts with stripe device counts and send email if doesn't match
     */
    public function checkSubscriptionAction()
    {
        $billing = new BillingHelper();

        $result = [];

        $this->logger->info('[JOB] Running `checkSubscription` task...');

        //--- step 1 : check for device count v. deviceProtection

        $this->logger->info('[JOB] Step 1. Checking `deviceProtection` plans...');

        $organizations = $this->model->getOrgDeviceCount();

        foreach ($organizations as $organization) {
            $subscription = $billing->getSubscriptionForType($organization['stripe_customer_id'], 'device');

            if ($subscription == 'customer_not_found') {
                $this->logger->info('[JOB] Stripe customer not found for org ID ' . $organization['pk_id']);
                $result[] = 'Stripe customer not found for org ID ' . $organization['pk_id'];
            } else if (! $subscription) {
                $this->logger->info('[JOB] Subscription not found for org ID ' . $organization['pk_id']);
                $result[] = 'Subscription not found for org ID ' . $organization['pk_id'];
            } else {
                $plan_quantity = $subscription->items->data[0]->quantity;

                if ($organization['device_count'] != $plan_quantity) {
                    $this->logger->info('[JOB] Subscription plan doesn\'t match for org ID ' . $organization['pk_id'] . ' - Subscription Quantity : ' . $plan_quantity . ', DB device count : ' . $organization['device_count']);
                    $result[] = 'Subscription plan doesn\'t match for org ID ' . $organization['pk_id'] . ' - Subscription Quantity : ' . $plan_quantity . ', DB device count : ' . $organization['device_count'];
                }
            }
        }

        //--- steps will be added for additional services

        if (count($result)) {
            $alertHtml = '<p>';

            $alertHtml .= implode('</p><p>', $result);

            $alertHtml .= '</p>';

            $emailData = [
                'job_name'      => 'checkSubscription',
                'alert_html'    => $alertHtml,
            ];

            $this->mail->sendCustomMail('devops@todyl.com', '[JOB ALERT] checkSubscription', $this->email_template, $emailData);

            $this->logger->info('[JOB] `checkSubscription` task finished. ' . count($result) . ' issues detected. Admin notified.');
        } else {
            $this->logger->info('[JOB] OK - `checkSubscription` task finished. No issues detected.');
        }

        exit;
    }

    /**
     * sync after new changes
     */
    public function syncAction()
    {
        $this->logger->info('[JOB] Running sync task...');

        $billing = new BillingHelper();
        $device = new DeviceHelper();

        //--- step 1 : check for device count v. deviceProtection

        $this->logger->info('[JOB] Step 1. Syncing `deviceProtection` plans...');

        $organizations = $this->model->getOrgDeviceCount();

        $device_plan = $this->settings['device'];

        foreach ($organizations as $organization) {
            $this->logger->info('[JOB] Checking org ID ' . $organization['pk_id']);

            $subscription = $billing->getSubscriptionForType($organization['stripe_customer_id'], 'device');

            if ($subscription == 'customer_not_found') {
                $this->logger->info('[JOB] Stripe customer not found for org ID ' . $organization['pk_id']);

            } else if (! $subscription) {
                $plans = [
                    [
                        'plan'      =>  $device_plan,
                        'quantity'  =>  $organization['device_count'],
                    ],
                ];

                $new_subscription = $this->stripe->subscribeCustomer($organization['stripe_customer_id'], $plans);

                if ($new_subscription) {
                    $this->logger->info('[JOB] Subscription not found for org ID ' . $organization['pk_id'] . ', so created one for ' . $organization['device_count'] . ' devices.');
                } else {
                    $this->logger->info('[JOB] Subscription not found for org ID ' . $organization['pk_id'] . ', but there was an error while creating the subscription : ' . ($this->session->get('stripe_error') ? $this->session->get('stripe_error') : ''));
                    $this->session->remove('stripe_error');
                }
            } else {
                $plan_quantity = $subscription->items->data[0]->quantity;

                if ($organization['device_count'] != $plan_quantity) {
                    $this->logger->info('[JOB] Subscription plan didn\'t match for org ID ' . $organization['pk_id'] . ' - Subscription Quantity : ' . $plan_quantity . ', DB device count : ' . $organization['device_count']);

                    $data = [
                        'items' => [
                            [
                                'id'        => $subscription->items->data[0]->id,
                                'quantity'  => $organization['device_count'],
                            ],
                        ],
                    ];

                    $new_subscription = $this->stripe->updateSubscription($subscription->id, $data);

                    if ($new_subscription) {
                        $this->logger->info('[JOB] Updated subscription...');
                    } else {
                        $this->logger->info('[JOB] Failed to update subscription : ' . ($this->session->get('stripe_error') ? $this->session->get('stripe_error') : ''));
                        $this->session->remove('stripe_error');
                    }
                } else {
                    $this->logger->info('[JOB] Subscription plan matches for org ID ' . $organization['pk_id'] . ' - Device count : ' . $organization['device_count']);
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

    /**
     * send sales report email to admins
     * * sent every 5 mins
     */
    public function salesreportAction()
    {
        $reportHtml = '';

        $new_leads = $this->model->getNewSalesLeads();

        // check new leads

        if (count($new_leads['leads'])) {
            $reportHtml .= '<h3>New Leads</h3>';

            foreach ($new_leads['leads'] as $lead) {
                $reportHtml .= '<p>' . $lead['email'] . '</p>';
            }
        }

        // check new users

        if (count($new_leads['users'])) {
            $reportHtml .= '<h3>New Users</h3>';

            foreach ($new_leads['users'] as $user) {
                $reportHtml .= '<p>' . $user['email'] . '</p>';
            }
        }

        //Only send if there are new leads
        if (! $reportHtml) {
            exit;
        } else {

            // send report email
            $emailData = [
                'job_name'      => 'Sales Report',
                'alert_html'    => $reportHtml,
            ];

            $this->mail->sendCustomMail('sales@todyl.com', '[JOB REPORT] Sales Report', $this->email_template, $emailData);

            exit;
        }
    }

    /**
     * Send alert review email
     */
    public function alertReviewAction()
    {
        //Grab the unprocessed alerts
        $reviewAlerts = $this->model->getNewReviewAlerts();

        if ($reviewAlerts) {

            $reportHtml = '<h1>New Alerts For Review</h1><table>';

            foreach ($reviewAlerts as $reviewAlert) {
                $reportHtml .= '<tr>';
                $reportHtml .= '<td>' . $reviewAlert['email'] . '</td>';
                $reportHtml .= '<td>' . $reviewAlert['user_device_name'] . '</td>';
                $reportHtml .= '<td>' . $reviewAlert['datetime_updated'] . '</td>';
                $reportHtml .= '<td>' . $reviewAlert['short_alert_summary'] . '</td>';
                $reportHtml .= '<td>' . $reviewAlert['raw'] . '</td>';
                $reportHtml .= '<tr>';
            }

            $reportHtml .= '</table>';



            $clearAlerts = $this->model->setClearAlerts();

            $emailData = [
                'job_name'      => 'New Alert for Review',
                'alert_html'    => $reportHtml,
            ];

            $this->mail->sendCustomMail('secops@todyl.com', '[JOB REPORT] New Alert for Review', $this->email_template, $emailData);

        }
        exit;
    }

    /**
     * migrate customer subscription plans
     */
    public function migratePlanAction()
    {
        $this->logger->info('[JOB] Running migrate plan task...');

        $subscriptions = $this->stripe->getSubscriptions([
            'plan'  => 'introDeviceProtection',
            'limit' => 100,
        ]);

        $this->logger->info('[JOB] Found ' . count($subscriptions->data) . ' subscriptions with intro plan');

        foreach ($subscriptions->data as $subscription) {
            $this->logger->info('[JOB] Working on subscription ID ' . $subscription->id . ' with customer ID ' . $subscription->customer);

            $data = [
                'plan'  => 'deviceProtection',
                'quantity'  => $subscription->quantity,
            ];

            if ($subscription->discount) {
                $data['coupon'] = $subscription->discount->coupon->id;
            }

            $new_subscription = $this->stripe->updateSubscription($subscription->id, $data);

            if ($new_subscription) {
                $this->logger->info('[JOB] Updated plan...');
            } else {
                $this->logger->error('[JOB] Failed to update plan : ' . ($this->session->get('stripe_error') ? $this->session->get('stripe_error') : ''));
                $this->session->remove('stripe_error');
            }
        }

        $this->logger->info('[JOB] Finished migrate plan task...');

        exit;
    }

    /**
     * Process file hash matches and alerting
     */
    public function processSuspiciousFileHashesAction()
    {
        //Start performance tracking
        $time_start = microtime(true);

        //Get the new suspicious files without a previous scan
        $suspiciousFiles = $this->model->getNewSuspiciousFileHashes();

        //Skip to processing alerts if no new suspicious files
        if (!empty($suspiciousFiles)) {
            //Assemble for OpSwat
            $OpSwatArray = array();
            foreach ($suspiciousFiles as $suspiciousFile) {
                array_push($OpSwatArray, $suspiciousFile['sha256']);
            }

            $OpSwatArray = json_encode(array("hash" => $OpSwatArray));

            //Query OpSwat for the file scan data
            $OpSwat = new OpSwatHash();
            $OpSwatResult = $OpSwat->getHashData($OpSwatArray);
            if ($OpSwatResult === false) {
                $this->response->setStatusCode(500, "Error");
                $this->response->send();
                exit;
            }

            //Persist the results
            $OpSwatResult = json_decode($OpSwatResult);
            foreach ($OpSwatResult as $result) {
                if (isset($result->scan_details)) {
                    $this->model->persistOpswatResults($result);
                } // This is a work around for the OpSwat but where the API is returning a bad response - Ticket 82301
            }
        }

        //Create alerts
        $this->model->createFileScanAlerts();

        //End and log performance tracking
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        $this->logger->info('[JOB][processNewSuspiciousFileHashes][performance] Execution time: '. $time);

        exit;
    }

    public function migrateOldSubscriptionsAction()
    {
        $this->logger->info('[JOB] Running migration task...' . "\n");

        $organizations = EntOrganization::find([
            'cache'      => false,
        ])->toArray();

        foreach ($organizations as $organization) {
            $this->logger->info('[JOB] Processing org ID ' . $organization['pk_id']);

            if (! $organization['stripe_customer_id']) {
                $this->logger->info('[JOB] No stripe customer ID for org ID ' . $organization['pk_id'] . "\n");
                continue;
            }

            $customer = $this->stripe->getCustomer($organization['stripe_customer_id']);

            if ($customer && $customer->subscriptions) {
                $subscription = $customer->subscriptions->data[0];

                $exists = EntStripeSubscription::count([
                    'conditions' => 'subscriber_id = ?1 AND subscribe_level = ?2 AND stripe_customer_id = ?3 AND stripe_subscription_id = ?4 AND plan_key = ?5 AND plan_name = ?6',
                    'bind'       => [
                        1 => $organization['pk_id'],
                        2 => 'organization',
                        3 => $organization['stripe_customer_id'],
                        4 => $subscription->id,
                        5 => 'device',
                        6 => 'deviceProtection',
                    ],
                    'cache'      => false,
                ]);

                if ($exists) {
                    $this->logger->info('[JOB] Org ID ' . $organization['pk_id'] . ' has already been processed.' . "\n");
                    continue;
                }

                $stripeSubscription = new EntStripeSubscription();
                $stripeSubscriptionData = [
                    'subscriber_id'         => $organization['pk_id'],
                    'subscribe_level'       => 'organization',
                    'stripe_customer_id'    => $organization['stripe_customer_id'],
                    'stripe_subscription_id'=> $subscription->id,
                    'plan_key'              => 'device',
                    'plan_name'             => 'deviceProtection',
                    'created_by'            => 'thrust',
                    'updated_by'            => 'thrust',
                ];

                if ($subscription->discount) {
                    $stripeSubscriptionData['coupon'] = $subscription->discount->coupon->id;
                }

                if ($stripeSubscription->create($stripeSubscriptionData)) {
                    $this->logger->info('[JOB] Successfully registered subscription for org ID ' . $organization['pk_id'] . "\n");
                } else {
                    $this->logger->info('[JOB] An error occurred while registering subscription for org ID ' . $organization['pk_id'] . "\n");
                }
            } else {
                $this->logger->info('[JOB] Couldn\'t fetch stripe customer info for org ID ' . $organization['pk_id'] . ', skipping...' . "\n");
            }
        }

        $this->logger->info('[JOB] Finished migration of the organizations' . "\n");
        exit;
    }

    /**
     * Check customer cards for expiration status (notify if it's expiring in 1 month/week)
     */
    public function checkCustomerCardsAction()
    {
        $billing = new BillingHelper();

        $count = 0;

        $customers = [];

        $this->logger->info('[JOB] Running `checkCustomerCards` task...');

        $organizations = $this->model->getActiveOrgsWithAdmin();

        foreach ($organizations as $organization) {
            $customer = $this->stripe->getCustomer($organization['stripe_customer_id']);

            foreach ($customer->sources->data as $payment_method) {
                if ($payment_method['object'] === 'card') {
                    $cardExpireTime = strtotime($payment_method['exp_year'] . '-' . $payment_method['exp_month'] . '-01' . ' 00:00:00');

                    $cardExpireDate = date('Y-m-d', strtotime('+1 month', $cardExpireTime));

                    $dateDayLater = date('Y-m-d', strtotime('+1 day'));
                    $dateWeekLater = date('Y-m-d', strtotime('+1 week'));
                    $dateMonthLater = date('Y-m-d', strtotime('+1 month'));

                    if ($cardExpireDate == $dateDayLater || $cardExpireDate == $dateWeekLater || $cardExpireDate == $dateMonthLater) {
                        $upcomingInvoice = $this->stripe->getUpcomingInvoice($organization['stripe_customer_id']);
                        $organization['next_invoice_date'] = $upcomingInvoice->date;

                        $customers[] = $organization;
                        $count ++;
                    }
                }
            }

        }

        foreach ($customers as $customer) {
            $emailData = [
                'template'          => 'email3',
                'headerText'        => 'Please Update Your Billing Information',
                'firstName'         => $customer['admin_first_name'],
                'nextInvoiceDate'   => date('M j', $customer['next_invoice_date']),
            ];

            $this->logger->info('[COMMON] Card Expiration Email to ' . $customer['admin_email'] . ', data : ' . print_r($emailData, true));
            // $this->mail->sendMail($customer['admin_id'], 'card-expiration', $emailData);
            // echo $this->getDI()->getMail()->getTemplate('card-expiration', $emailData);
        }

        $this->logger->info('[JOB] `checkCustomerCards` task finished. ' . ($count ? 'Notified ' . $count . ' customer(s).' : 'Didn\'t find any cards which are about to expire.'));

        exit;
    }

}
