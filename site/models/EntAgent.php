<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\EntAgent
 */
class EntAgent extends ModelBase
{
    public $pk_id;
    public $fk_ent_users_id;
    public $user_device_name;
    public $install_pin;
    public $pin_used;
    public $device_serial;
    public $UDID;
    public $ca;
    public $cert;
    public $datetime_created;
    public $created_by;
    public $datetime_updated;
    public $updated_by;
    public $is_active;
    public $is_deleted;

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
                'alias' => 'user',
            ]
        );
        $this->hasMany(
            'UDID',
            __NAMESPACE__ . '\LogAgent',
            'fk_ent_agent_UDID'
        );
        $this->hasMany(
            'UDID',
            __NAMESPACE__ . '\LogAgentDataTransfer',
            'fk_ent_agent_UDID'
        );
        $this->hasMany(
            'UDID',
            __NAMESPACE__ . '\LogAgentConnections',
            'fk_ent_agent_UDID'
        );
       $this->hasMany(
            'UDID',
            __NAMESPACE__ . '\EntAlert',
            'fk_ent_agent_UDID'
        );
    }
}
