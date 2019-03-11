<?php

namespace Thrust\Models;

use JsonMapper;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Uniqueness;

// for Phalcon 2.0
use Phalcon\Mvc\Model\Message;
// use Phalcon\Mvc\Model\Validator\Uniqueness;

use Thrust\Models\EntAgent;

/**
 * Thrust\Models\EntUsers
 * All the users registered in the application.
 */
class EntUsers extends ModelBase
{

    const HANZO_ENDPOINT = '/user';

    /**
     * @var int
     */
    public $pk_id;

    /**
     * @var int
     */
    public $fk_attr_roles_id;

    /**
     * @var int
     */
    public $ent_organization_pk_id;

    /**
     * @var string
     */
    public $firstName;

    /**
     * @var string
     */
    public $lastName;

    /**
     * @var string
     */
    public $primaryPhone;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $password;

    /**
     * @var bool
     */
    public $is_force_password_change;

    /**
     * @var bool
     */
    public $is_banned;

    /**
     * @var string
     */
    public $token_GUID;

    /**
     * @var datetime
     */
    public $token_time;

    /**
     * @var string
     */
    public $token_type;

    /**
     * @var bool
     */
    public $is_beta;

    /**
     * @var datetime
     */
    public $datetime_created;

    /**
     * @var string
     */
    public $created_by;

    /**
     * @var datetime
     */
    public $datetime_updated;

    /**
     * @var string
     */
    public $updated_by;

    /**
     * @var bool
     */
    public $is_active;

    /**
     * @var bool
     */
    public $is_deleted;

    /**
     * Before create the user assign a password.
     */
    public function beforeValidationOnCreate()
    {
        if (empty($this->password)) {

            // Generate a plain temporary password
            $tempPassword = preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(openssl_random_pseudo_bytes(12)));

            // The user must change its password in first login
            $this->mustChangePassword = 1;

            // Use this password as default
            $this->password = $this->getDI()
                ->getSecurity()
                ->hash($tempPassword);
        } else {
            // The user must not change its password in first login
            $this->mustChangePassword = 0;
        }

        // set default active status
        if (! isset($this->is_active)) {
            $this->is_active = 1;
        }

        $this->is_deleted = 0;

        // The account is not banned by default
        $this->is_banned = 0;
    }

    /**
     *  actions to take after user insert
     */
    public function afterSave()
    {

    }

    /**
     * Validate that emails are unique across users.
     */

    public function validation()
    {
        // if ($this->pk_id) {
        //     // update process
        //     $count = self::count([
        //         'pk_id != ?0 AND email = ?1 AND is_active = 1 AND is_deleted = 0',
        //         'bind' => [ $this->pk_id, $this->email ],
        //         'cache' => false,
        //     ]);
        // } else {
        //     // create process
        //     $count = self::count([
        //         'email = ?0 AND is_active = 1 AND is_deleted = 0',
        //         'bind' => [ $this->email ],
        //         'cache' => false,
        //     ]);
        // }

        // if ($count) {
        //     $this->appendMessage(new Message('It appears that this email is already registered. Please try to <a href="/session/login">sign in</a> or <a href="/session/forgotPassword">reset your password</a>.'));
        //     return false;
        // }

        // return $this->validationHasFailed() != true;

        $validator = new Validation();

        $validator->add(
            'email',
            new Uniqueness(
                [
                    'message' => 'The email is already registered',
                ]
            )
        );

        return $this->validate($validator);
    }

    public function getDeviceCount()
    {
        $device_count = EntAgent::count([
            'fk_ent_users_id = ?1 AND is_active = 1 AND is_deleted = 0',
            'bind'          => [
                1           => $this->pk_id,
            ],
            'cache'         => false,
        ]);

        return $device_count;
    }

    public function getName()
    {
        return ($this->firstName || $this->lastName) ? $this->firstName . ' ' . $this->lastName : $this->email;
    }

    public function initialize()
    {
        //Setup the DB connections
        $this->setReadConnectionService('oltp-read');
        $this->setWriteConnectionService('oltp-write');

        $this->hasMany(
            'pk_id',
            __NAMESPACE__ . '\LogLogins',
            'fk_ent_users_id',
            [
                'alias' => 'logins'
            ]
        );

        $this->hasMany(
            'pk_id',
            __NAMESPACE__ . '\LogPasswordChanges',
            'fk_ent_users_id'
        );

        $this->hasOne(
            'fk_attr_roles_id',
            __NAMESPACE__ . '\AttrRoles',
            'pk_id',
            [
                "alias" => "role",
            ]
        );

        $this->hasOne(
            'fk_ent_organization_id',
            __NAMESPACE__ . '\EntOrganization',
            'pk_id',
            [
                'alias'     => 'organization',
            ]
        );

        $this->hasMany(
            'pk_id',
            __NAMESPACE__ . '\EntAgent',
            'fk_ent_users_id',
            [
                'alias' => 'agents'
            ]
        );

        $this->hasMany(
            'pk_id',
            __NAMESPACE__ . '\MapUsersPreferences',
            'fk_ent_users_id',
            [
                'alias' => 'preferences'
            ]
        );
    }
}
