<?php

namespace Thrust\Helpers;

use Phalcon\Http\Request;

use Thrust\Models\AttrPasswordChangeType;
use Thrust\Models\AttrPreferences;
use Thrust\Models\AttrRoles;
use Thrust\Models\DashboardStatistics;
use Thrust\Models\EntLeads;
use Thrust\Models\EntUsers;
use Thrust\Models\LogPasswordChanges;
use Thrust\Models\MapUsersPreferences;

use Thrust\Models\Api\Agent;
use Thrust\Helpers\DeviceHelper;
use Thrust\Helpers\MailHelper;

class AccountHelper
{
    protected $request;
    protected $di;
    protected $logger;
    protected $device;
    protected $mail;
    protected $defender;

    function __construct()
    {
        $this->request = new Request;
        $this->di = \Phalcon\DI::getDefault();
        $this->logger = \Phalcon\Di::getDefault()->getShared('logger');

        $this->defender = new Agent();
        $this->device = new DeviceHelper();
        $this->mail = new MailHelper();

        $this->defender->initialize();
    }

    /**
     * Create ent_users
     * @param  array   $user_info - user info to insert/update
     * @param  mixed   $email     - email data (if to send)
     * @param  boolean $existing  - whether to remove existing email
     * @return mixed
     */
    public function createUser($user_info, $email = false, $existing = false)
    {
        $stats = new DashboardStatistics();

        // check required user info
        if (
            ! isset($user_info['orgId']) ||
            ! isset($user_info['email']) ||
            (! isset($user_info['password']) && ! isset($user_info['is_invite']))
        ) {
            return false;
        }

        if (isset($user_info['role'])) {
            $role_name = $user_info['role'];
        } else {
            // default user role as 'user'
            $role_name = 'user';
        }

        if (isset($user_info['is_active'])) {
            $is_active = $user_info['is_active'];
        } else {
            // default user status is 'inactive'
            $is_active = 0;
        }

        $is_professional = isset($user_info['is_professional']) ? $user_info['is_professional'] : 0;

        $role = AttrRoles::findFirst([
            'conditions' => 'name = ?1 AND is_active = ?2',
            'bind'       => [
                1 => $role_name,
                2 => 1,
            ],
        ]);

        // generate unique GUID to identify users
        $GUID = preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(openssl_random_pseudo_bytes(24)));

        $userData = [
            'firstName'                 => isset($user_info['firstName']) ? $user_info['firstName'] : '',
            'lastName'                  => isset($user_info['lastName']) ? $user_info['lastName'] : '',
            'email'                     => $user_info['email'],
            'fk_ent_organization_id'    => $user_info['orgId'],
            'fk_attr_roles_id'          => $role->pk_id,
            'GUID'                      => $GUID,
            'is_professional'           => $is_professional,
            'is_active'                 => $is_active,
            'created_by'                => 'thrust',
            'updated_by'                => 'thrust',
        ];

        $invite_GUID = false;

        if (isset($user_info['password'])) {
            // generate password

            $userData['password'] = $this->di->getSecurity()->hash($user_info['password']);

        } else if (isset($user_info['is_invite'])) {
            // inviting a new user - no password but a password reset link

            if (! isset($user_info['userId'])) {
                // first check if there's a duplicate
                $already_invited = EntUsers::count([
                    'conditions' => 'email = ?1 AND is_invited = 1 AND is_deleted = 0',
                    'bind'       => [
                        1 => $user_info['email'],
                    ],
                    'cache'      => false,
                ]);

                if ($already_invited) {
                    return [
                        'status'    => 'fail',
                        'error'     => 'The user is already invited.',
                    ];
                }
            }

            $userData['is_invited'] = 1;
            $userData['token_time'] = date('Y-m-d H:i:s');
            $userData['token_type'] = 'user_invite';

        } else {
            return [
                'status'    => 'fail',
                'error'     => 'No password!',
            ];
        }

        if (isset($user_info['userId'])) {
            // update user

            if ($existing) {
                // readd user - reset user flags
                $this->logger->info('[ACCOUNT] Readding user ' . $user_info['userId']);

                $userData['is_invited'] = 1;
                $userData['is_active'] = 0;
                $userData['is_deleted'] = 0;
            }

            $user = EntUsers::findFirst([
                'conditions' => 'pk_id = ?1',
                'bind'       => [
                    1 => $user_info['userId'],
                ],
                'cache'      => false,
            ]);

            if (! $user) {
                // user has been removed from db somehow - create again

                $user = new EntUsers();
                if ($user->create($userData) === false) {
                    return [
                        'status'    => 'fail',
                        'error'     => implode('<br />', $user->getMessages()),
                    ];
                }
            } else {
                if ($user->update($userData) === false) {
                    return [
                        'status'    => 'fail',
                        'error'     => implode('<br />', $user->getMessages()),
                    ];
                }
            }

            $this->logger->info('[ACCOUNT] Updated user ' . $user->pk_id);
        } else {
            // create user

            $user = new EntUsers();
            if ($user->create($userData) === false) {
                return [
                    'status'    => 'fail',
                    'error'     => implode('<br />', $user->getMessages()),
                ];
            }

            $this->logger->info('[ACCOUNT] Created a new user ' . $user->pk_id);
        }

        // generate pins for user
        if (isset($user_info['max_devices'])) {
            $this->device->generatePins($user->pk_id, $user_info['max_devices']);
        }

        if ($email) {
            if (isset($user_info['is_invite'])) {
                // generate pins for newly invited users
                // $device_count = isset($user_info['max_devices']) ? $user_info['max_devices'] : 1;

                // $pins = $this->device->generatePins($user->pk_id, $device_count - 1);
                // for ($i = 0; $i < $device_count - 1; $i ++) {
                //     $this->defender->createNewAgent($user->pk_id, true);
                // }

                // generate magic device download link and include in email
                $download_GUID = $this->device->getMagicLink($user->pk_id);

                if (! $download_GUID) {
                    $stats->getOrgDevicesPerUser($user->organization->pk_id, 'flush');

                    return [
                        'status'    => 'fail',
                        'error'     => 'User has been created but there was an error while sending email.',
                    ];
                }

                $email['data']['magic_link'] = 'dnld/' . $download_GUID;
            }

            // send email
            $this->mail->sendMail($user->pk_id, $email['template'], $email['data']);
        }

        return [
            'status'    => 'success',
            'user'      => $user,
        ];
    }

    public function triggerForgotPassword($email)
    {
        $user = EntUsers::findFirst([
            'conditions' => 'email = ?1 AND is_active = 1 AND is_deleted = 0',
            'bind'       => [
                1 => $email,
            ],
            'cache'      => false,
        ]);

        // if (! $user || ! $user->organization->is_active) {
        if (! $user) {
            return [
                'result'        => 'not_found',
            ];
        } else {
            // log password reset request
            $passwordchangetype = AttrPasswordChangeType::findFirstByName('forgotPasswordClicked');

            $log = new LogPasswordChanges();
            $log->assign([
                'fk_ent_users_id' => $user->pk_id,
                'fk_attr_password_change_type_id' => $passwordchangetype->pk_id,
                'ip_address' => $this->request->getClientAddress(),
                'ip_geolocation' => $this->di->get('auth')->getGeoIp(),
                'user_agent' => $this->request->getUserAgent(),
                'created_by' => 'thrust',
                'updated_by' => 'thrust',
            ]);

            if ($log->save()) {
                $user->token_type = 'password_reset';
                $user->token_time = date('Y-m-d H:i:s');

                // Generate a random confirmation code
                $user->token_GUID = preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(openssl_random_pseudo_bytes(24)));

                if ($user->update()) {
                    // send email
                    $this->mail->sendMail($user->pk_id, 'reset-password', [ 'resetUrl' => 'reset-password/' . $user->token_GUID ]);

                    return [
                        'result'        => 'success',
                    ];
                } else {
                    $messages = [];
                    foreach ($user->getMessages() as $message) {
                        $messages[] = $message;
                    }

                    $this->logger->error('[ACCOUNT HELPER] Error while updating user token values: ' . implode("\n", $messages));

                    return [
                        'result'        => 'error',
                        'error'         => implode('<br />', $messages),
                    ];
                }
            } else {
                $messages = [];
                foreach ($log->getMessages() as $message) {
                    $messages[] = $message;
                }

                return [
                    'result'        => 'error',
                    'error'         => implode('<br />', $messages),
                ];
            }
        }
    }

    public function resetUserPassword($email, $is_invite = false)
    {
        $condition = $is_invite ? 'is_invited = 1' : 'is_active = 1';

        $user = EntUsers::findFirst([
            'conditions' => 'email = ?1 AND ' . $condition . ' AND is_deleted = 0',
            'bind'       => [
                1 => $email,
            ],
            'cache'      => false,
        ]);

        // if (! $user || ! $user->organization->is_active) {
        if (! $user) {
            return [
                'result'        => 'not_found',
            ];
        } else {
            if (! $is_invite) {
                // log password reset request
                $passwordchangetype = AttrPasswordChangeType::findFirstByName('forgotPasswordChanged');

                $log = new LogPasswordChanges();
                $log->assign([
                    'fk_ent_users_id' => $user->pk_id,
                    'fk_attr_password_change_type_id' => $passwordchangetype->pk_id,
                    'ip_address' => $this->request->getClientAddress(),
                    'ip_geolocation' => $this->di->get('auth')->getGeoIp(),
                    'user_agent' => $this->request->getUserAgent(),
                    'created_by' => 'thrust',
                    'updated_by' => 'thrust',
                ]);

                $log_result = $log->save();
            } else {
                $log_result = true;

                $user->is_invited = 0;
                $user->is_active = 1;
            }

            if ($log_result) {
                $user->token_GUID = null;
                $user->token_time = null;
                $user->token_type = null;

                $user->password = $this->di->getSecurity()->hash($this->request->getPost('todyl_password'));

                if ($user->update()) {
                    if (! $is_invite) {
                        $this->mail->sendMail($user->pk_id, 'password-changed', []);
                    }

                    $this->logger->info('[ACCOUNT] Reset password for user ID ' . $user->pk_id);

                    return [
                        'result'        => 'success',
                        'user'          => $user,
                    ];
                } else {
                    $messages = [];
                    foreach ($user->getMessages() as $message) {
                        $messages[] = $message;
                    }

                    return [
                        'result'        => 'error',
                        'error'         => implode('<br />', $messages),
                    ];
                }
            } else {
                $messages = [];
                foreach ($log->getMessages() as $message) {
                    $messages[] = $message;
                }

                return [
                    'result'        => 'error',
                    'error'         => implode('<br />', $messages),
                ];
            }
        }
    }

    /**
     * actual trigger for user email verification
     */
    public function triggerVerifyEmail($GUID)
    {
        $user = EntUsers::findFirst([
            'conditions' => 'GUID = ?1',
            'bind'       => [
                1 => $GUID,
            ],
            'cache'      => 60,
        ]);

        if (! $user) {
            // user not found
            $this->logger->error('[ACCOUNT] User with GUID ' . $GUID . ' not found.');

            return [
                'result'    => 'not_found',
            ];
        }

        $user->token_type = 'verify_email';
        $user->token_time = date('Y-m-d H:i:s');

        // Generate a random confirmation code
        $user->token_GUID = preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(openssl_random_pseudo_bytes(24)));

        if ($user->update()) {
            // send email
            $this->mail->sendMail($user->pk_id, 'email-verification', [ 'magic_link' => 'verify/' . $user->token_GUID ]);

            $this->logger->info('[ACCOUNT] Mail verification email sent to user ID ' . $user->pk_id);

            return [
                'result'        => 'success',
            ];
        } else {
            $this->logger->error('[COMMON] Error while updating user for email verification for user ID ' . $user->pk_id . ' : ' . implode('<br />', $user->getMessages()));

            return [
                'result'        => 'error',
                'error'         => implode('<br />', $user->getMessages()),
            ];
        }
    }

    public function getUserPreference($user_id, $preference_name)
    {
        $preference = AttrPreferences::findFirst([
            'conditions'    => 'LOWER(name) = ?1',
            'bind'          => [
                1           => strtolower($preference_name),
            ],
        ]);

        if (! $preference) return false;

        $user_preference = MapUsersPreferences::findFirst([
            'conditions'    => 'fk_ent_users_id = ?1 AND fk_attr_preferences_id = ?2',
            'bind'          => [
                1           => $user_id,
                2           => $preference->pk_id,
            ],
            'cache'         => false,
        ]);

        if ($user_preference) {
            return $user_preference->value;
        } else {
            return $preference->default_value;
        }
    }

    public function setUserPreference($user_id, $preference_name, $preference_val)
    {
        $preference = AttrPreferences::findFirst([
            'conditions'    => 'LOWER(name) = ?1',
            'bind'          => [
                1           => strtolower($preference_name),
            ],
        ]);

        if (! $preference) return false;

        $user_preference = MapUsersPreferences::findFirst([
            'conditions'    => 'fk_ent_users_id = ?1 AND fk_attr_preferences_id = ?2',
            'bind'          => [
                1           => $user_id,
                2           => $preference->pk_id,
            ],
            'cache'         => false,
        ]);

        if ($user_preference) {
            // preference exists - update

            if ($user_preference->value == $preference_val) {
                return true;
            }

            $user_preference->value = $preference_val;
            $user_preference->updated_by = 'thrust';

            $user_preference->update();
        } else {
            // preference not set - create
            $user_preference = new MapUsersPreferences();

            $user_preference->fk_ent_users_id = $user_id;
            $user_preference->fk_attr_preferences_id = $preference->pk_id;
            $user_preference->value = $preference_val;
            $user_preference->created_by = 'thrust';
            $user_preference->updated_by = 'thrust';

            $user_preference->create();
        }

        return true;
    }

    /**
     * Get organization owner info
     * @param  integer $org_id - organization id
     * @return object          - owner info
     */
    public function getOrgOwner($org_id)
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
