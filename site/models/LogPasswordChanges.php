<?php

namespace Thrust\Models;

/**
 * LogPasswordChanges
 * This model registers password reset attempts made through the account management and forgot password pages.
 */
class LogPasswordChanges extends ModelBase
{
    /**
     * @var int
     */
    public $pk_id;

    /**
     * @var int
     */
    public $fk_ent_users_id;

    /**
     * @var int
     */
    public $fk_attr_password_change_type_id;

    /**
     * @var string
     */
    public $ip_address;

    /**
     * @var string
     */
    public $ip_geolocation;

    /**
     * @var string
     */
    public $user_agent;


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

    public function initialize()
    {
        //Setup the DB connections
        $this->setReadConnectionService('oltp-read');
        $this->setWriteConnectionService('oltp-write');

        $this->hasOne(
            'fk_ent_users_id',
            __NAMESPACE__ . '\EntUsers',
            'pk_id',
            [
                "alias" => "user",
            ]
        );

        $this->hasOne(
            'fk_attr_password_change_type_id',
            __NAMESPACE__ . '\AttrPasswordChangeType',
            'pk_id',
            [
                "alias" => "passwordchangetype",
            ]
        );
    }
}
