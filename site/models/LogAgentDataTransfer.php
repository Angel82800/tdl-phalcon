<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\LogAgentDataTransfer
 */
class LogAgentDataTransfer extends ModelBase
{
    public $pk_id;
    public $fk_ent_agent_UDID;
    public $fk_log_agent_connections_id;
    public $total_bytes_received;
    public $total_bytes_sent;
    public $incremental_bytes_received;
    public $incremental_bytes_sent;
    public $datetime_created;
    public $created_by;
    public $datetime_updated;
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
        $this->belongsTo(
            'fk_log_agent_connections_id',
            __NAMESPACE__ . '\LogAgentConnections',
            'pk_id'
        );
    }
}
