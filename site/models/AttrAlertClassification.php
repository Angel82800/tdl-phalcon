<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\AttrAlertClassification
 */
class AttrAlertClassification extends ModelBase
{
    public $pk_id;
    public $class_type;
    public $description;
    public $user_description;
    public $priority;
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
            'fk_attr_alert_classification_id'
        );
    }
}
