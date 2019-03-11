<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * LogLogins
 * This model registers unsuccessfull logins registered and non-registered users have made.
 */
class LogLogins extends ModelBase
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
     * @var string
     */
    public $ip_geolocation;

    /**
     * @var string
     */
    public $ip_address;

    /**
     * @var string
     */
    public $user_agent;

    /**
     * @var bool
     */
    public $is_failed;

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

        $this->belongsTo(
            'fk_ent_users_id',
            __NAMESPACE__ . '\EntUsers',
            'pk_id',
            [
                'alias' => 'user'
            ]
        );
    }
}
