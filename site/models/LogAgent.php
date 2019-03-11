<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * LogAgent
 * This model ingests log entries from client software.
 */
class LogAgent extends ModelBase
{
    /**
     * @var int
     */
    public $pk_id;

    /**
     * @var int
     */
    public $fk_ent_agent_UDID;
	
	/**
     * @var string
     */
    public $client_ip;

    /**
     * @var string
     */
    public $message;

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
        
		$this->belongsTo('fk_ent_agent_UDID', "EntAgent", 'UDID');
    }
}
