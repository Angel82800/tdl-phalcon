<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\LogAgentConnections
 */
class LogAgentConnections extends ModelBase
{
    public $pk_id;
    public $fk_ent_agent_UDID;
    public $host;
    public $client_ip;
    public $UDID_IP;
    public $exit_type;
    public $datetime_connected;
    public $created_by;
    public $datetime_disconnected;
    public $updated_by;

    public function initialize()
    {
        //Setup the DB connections
        $this->setReadConnectionService('oltp-read');
        $this->setWriteConnectionService('oltp-write');
        
        $this->belongsTo(
            'fk_ent_agent_UDID',
            __NAMESPACE__ . '\EntAgent',
            'UDID'
        );
        $this->hasMany(
            'pk_id',
            __NAMESPACE__ . '\LogAgentDataTranser',
            'fk_log_agent_connections_id'
        );
    }
}
