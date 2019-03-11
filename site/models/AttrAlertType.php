<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\AttrAlertType
 */
class AttrAlertType extends ModelBase
{
    public $pk_id;
    public $platform_name;
    public $description;    
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
        
        $this->hasMany(
            'pk_id',
            __NAMESPACE__ . '\EntAlert',
            'fk_attr_alert_type_id',
        );
    }
}
