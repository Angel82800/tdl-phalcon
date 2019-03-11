<?php

namespace Thrust\Helpers;

use Phalcon\Http\Request;

use Thrust\Models\Api\Agent;
use Thrust\Models\EntAgent;
use Thrust\Models\EntUsers;
use Thrust\Models\AttrRoles;

use Thrust\Helpers\AccountHelper;
use Thrust\Helpers\MailHelper;

class DeviceHelper
{
    protected $request;
    protected $di;
    protected $logger;
    protected $defender;
    protected $mail;
    protected $session;

    function __construct()
    {
        $this->request = new Request;
        $this->di = \Phalcon\DI::getDefault();
        $this->logger = \Phalcon\Di::getDefault()->getShared('logger');
        $this->session = \Phalcon\Di::getDefault()->getShared('session');

        $this->defender = new Agent();
        $this->mail = new MailHelper();

        $this->defender->initialize();
    }

    public function getPin($user_id, $force_email = 0)
    {
        $user = EntUsers::findFirst([
            'conditions' => 'GUID = ?1',
            'bind'       => [
                1 => $user_id,
            ],
            'cache'      => false,
        ]);

        $organization = $user->getOrganization([ 'cache' => false ]);

        // check for is_active status - security check
        if (! $user->is_active || ! $user->organization->is_active) {
            $this->logger->info('[DEVICE] Trying to generate pin for inactive user: ' . $user->pk_id . ', org: ' . $organization->pk_id);

            return false;
        }

        $device_limit = $user->getDeviceCount();

        $device_count = EntAgent::count([
            'conditions' => 'fk_ent_users_id = ?1 AND is_active = 1 AND is_deleted = 0 AND pin_used = 1',
            'bind'       => [
                1 => $user->pk_id,
            ],
            'cache'      => false,
        ]);

        // pin generate start

        $current_pin = $this->defender->unregisteredAgentPin($user->pk_id);

        $send_email = false;

        if ($current_pin === false) {
            // no unused pins

            $this->logger->info('[DEVICE] Device Limit Reached for user: ' . $user->pk_id . ', org: ' . $organization->pk_id);

            return false;
            // if ($device_count < $device_limit) {
            //     // generate a new pin

            //     $pin = $this->generatePins($user_id, 1)[0];

            //     $info = [
            //         'pin' => $pin
            //     ];

            //     $send_email = true;

            //     $result_pin = $pin;
            // } else {
            //     // device limit reached

            //     $this->logger->info('[DEVICE] Device Limit Reached for user: ' . $user->pk_id . ', org: ' . $organization->pk_id);

            //     return false;
            // }
        } else {
            // used pin exists

            if ($force_email) {
                // send email for existing pin

                $send_email = true;
            }

            $result_pin = $current_pin;
        }

        if ($send_email && $force_email !== false) {
            // generate magic link

            $pin = EntAgent::findFirst([
                'conditions' => 'install_pin = ?1 AND pin_used = 0 AND is_active = 1 AND is_deleted = 0',
                'bind'       => [
                    1 => $result_pin,
                ],
                'cache'      => false,
            ]);

            $pin->download_time = date('Y-m-d H:i:s');
            $pin->download_GUID = preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(openssl_random_pseudo_bytes(24)));

            if ($pin->update()) {
                $info = [
                    'GUID'      => $pin->download_GUID,
                ];

                if (! $device_count) {
                    // new user
                    $info['is_ftu'] = true;
                }

                // send email
                $this->mail->sendMail($user->pk_id, 'pin', $info);
            } else {
                $messages = [];
                foreach ($pin->getMessages() as $message) {
                    $messages[] = $message;
                }

                $this->logger->error('[DEVICE] Error while generating magic link :' . implode("\n", $messages));
                return false;
            }
        }

        return $result_pin;
    }

    public function getMagicLink($user_id, $create_new = false)
    {
        $pin = false;
        if ($create_new === true) {
            $current_pin = $this->generatePins($user_id, 1)[0];
        } else if ($create_new === false) {
            $current_pin = $this->defender->unregisteredAgentPin($user_id);
        } else {
            // agent id passed
            $pin = EntAgent::findFirst([
                'conditions' => 'pk_id = ?1',
                'bind'       => [
                    1 => $create_new,
                ],
                'cache'      => false,
            ]);

            $current_pin = $pin ? $pin->install_pin : false;
        }

        if ($current_pin === false) {
            // no unused pins

            $this->logger->error('[DEVICE] Error while generating magic link : No unused pins');

            return false;
        } else {
            // unused pin exists

            $pin = EntAgent::findFirst([
                'conditions' => 'install_pin = ?1 AND pin_used = 0',
                'bind'       => [
                    1 => $current_pin,
                ],
                'cache'      => false,
            ]);

            $pin->download_time = date('Y-m-d H:i:s');
            $pin->download_GUID = preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(openssl_random_pseudo_bytes(24)));

            if ($pin->update()) {
                return $pin->download_GUID;
            } else {
                $messages = [];
                foreach ($user->getMessages() as $message) {
                    $messages[] = $message;
                }

                $this->logger->error('[DEVICE] Error while generating magic link :' . implode("\n", $messages));

                return false;
            }
        }
    }

    public function checkMagicLink($code)
    {
        $agent = EntAgent::findFirst([
            'conditions' => 'download_GUID = ?1 AND pin_used = 0',
            'bind'       => [
                1 => $code,
            ],
            'cache'      => false,
        ]);

        if ($agent) {
            // check 24 hour validation

            if (strtotime($agent->download_time) <= time() && strtotime($agent->download_time) > (time() - 24 * 3600)) {
                $this->logger->info('[DEVICE] Magic link `' . $code . '` found. User invited: ' . $agent->user->is_invited);

                if ($agent->user->is_invited) {
                    // an invited user
                    return 'INV-' . $agent->install_pin;
                } else {
                    return $agent->install_pin;
                }

            } else {
                $this->logger->error('[DEVICE] Magic link has expired for user ID ' . $agent->fk_ent_users_id);

                return 'expired';
            }

        } else {
            $this->logger->error('[DEVICE] Magic link not found, CODE: ' . $code);

            return 'not_found';
        }
    }

    /**
     * update device count for user and create pins
     * @param  integer $user_id      - user id to update
     * @param  integer $device_count - target device count
     * @param  boolean $send_email   - whether to send email to user
     * @param  boolean $return_obj   - whether to return update result
     */
    public function updateDeviceCount($user_id, $device_count, $send_email = false, $return_obj = false)
    {
        $user = EntUsers::findFirst([
            'conditions' => 'GUID = ?1',
            'bind'       => [
                1 => $user_id,
            ],
            'cache'      => false,
        ]);

        if (! $user) {
            $this->logger->info('[DEVICE] Tried to update device count for user ' . $user_id . ', but entry doesn\'t exist.');

            return 'No user found';
        }

        $user_device_count = $user->getDeviceCount();

        // update device count

        if ($device_count < $user_device_count) {
            // reduce device slots
            $to_remove_devices = EntAgent::find([
                'conditions' => 'fk_ent_users_id = ?1 AND is_active = 1 AND is_deleted = 0 AND pin_used = 0',
                'bind'       => [
                    1 => $user->pk_id,
                ],
                'limit'      => $user_device_count - $device_count,
                'cache'      => false,
            ]);

            foreach ($to_remove_devices as $device_slot) {
                $device_slot->is_active = 0;
                $device_slot->is_deleted = 1;
                $device_slot->update();
            }

            if (! $device_count && $user->role->name == 'user') {
                // mark user deleted if device count is 0
                $user->is_active = 0;
                $user->is_deleted = 1;

                if ($user->update() === false) {
                    return implode('<br />', $user->getMessages());
                }

            }

            $added_devices = $device_count - $user_device_count;

        } else if ($device_count > $user_device_count) {
            // add more devices
            $added_devices = $device_count - $user_device_count;

            // create pins for additional devices
            $this->generatePins($user->pk_id, $added_devices);
            // for ($i = 0; $i < $added_devices; $i ++) {
            //     $this->defender->createNewAgent($user_id, true);
            // }

            // $user->max_devices = $device_count;
        } else {
            return true;
        }

        $this->logger->info('[DEVICE] Updated device count for user ' . $user->pk_id . ' to ' . $device_count);

        if ($send_email) {
            $admin = $this->getOrgOwner($user->organization->pk_id);

            if (! $admin) {
                $this->logger->info('[DEVICE] No admin found for org ID ' . $user->organization->pk_id);
            } else {
                // $admin_name = ($admin->firstName || $admin->lastName) ? $admin->firstName . ' ' . $admin->lastName : $admin->email;

                $mail_data = [
                    'admin_name'    => $admin->getName(),
                    'magic_link'    => 'user-device',
                ];

                // send email
                $this->mail->sendMail($user->pk_id, 'add-device', $mail_data);
            }
        }

        $this->logger->info('[DEVICE] Successfully modified device count for user ID ' . $user->pk_id . ', device changes: ' . $added_devices);

        if ($return_obj) {
            return [
                'user_email'    => $user->email,
                'added_devices' => $added_devices,
            ];
        } else {
            return true;
        }
    }

    public function generatePins($user_id, $generate_count)
    {
        $generated_pins = [];

        try {
            for ($i = 0; $i < $generate_count; $i ++) {
                $generated_pins[] = $this->defender->createNewAgent($user_id, true);
            }
        } catch (\Exception $e) {
            $this->logger->error('[DEVICE] Error while creating pins for User ID ' . $user_id . "\n" . $e->getMessage());
        }

        $this->logger->info('[DEVICE] Generated ' . $generate_count . ' pins for User ID ' . $user_id);

        // save generated pins in session (for dev)
        $current_generated_pins = $this->session->get('generated_pins') ? $this->session->get('generated_pins') : [];
        $current_generated_pins = array_merge($current_generated_pins, $generated_pins);
        $this->session->set('generated_pins', $current_generated_pins);

        return $generated_pins;
    }

    /**
     * Get organization owner info
     * @param  integer $org_id - organization id
     * @return object          - owner info
     */
    private function getOrgOwner($org_id)
    {
        $role = AttrRoles::findFirst([
            'conditions' => 'name = ?1 AND is_active = ?2',
            'bind'       => [
                1 => 'admin',
                2 => 1,
            ],
        ]);

        $admin = EntUsers::findFirst([
            'conditions' => 'fk_ent_organization_id = ?1 AND fk_attr_roles_id = ?2 AND is_active = 1 AND is_deleted = 0',
            'bind'       => [
                1 => $org_id,
                2 => $role->pk_id,
            ],
        ]);

        if ($admin) {
            return $admin;
        } else {
            return false;
        }
    }

}
