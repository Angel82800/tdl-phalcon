<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\EntAlert
 */
class EntAlert extends ModelBase
{
    public $pk_id;
    public $fk_ent_agent_UDID;
    public $fk_attr_alert_classification_id;
    public $fk_attr_alert_action_id;
    public $fk_attr_alert_type_id;
    public $hostname;
    public $timestamp;
    public $raw;
    public $short_alert_summary;
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
            'fk_ent_agent_UDID',
            __NAMESPACE__ . '\EntAgent',
            'UDID',
            [
                'alias' => 'agent'
            ]
        );

        $this->belongsTo(
            'fk_attr_alert_classification_id',
            __NAMESPACE__ . '\AttrAlertClassification',
            'pk_id',
            ['alias' => 'classification']
        );

        $this->belongsTo(
            'fk_attr_alert_action_id',
            __NAMESPACE__ . '\AttrAlertAction',
            'pk_id',
            ['alias' => 'action']
        );

        $this->belongsTo(
            'fk_attr_alert_type_id',
            __NAMESPACE__ . '\AttrAlertType',
            'pk_id'
        );

        $this->hasOne(
            'pk_id',
            __NAMESPACE__ . '\EntIps',
            'fk_ent_alert_id'
        );

        $this->hasOne(
            'pk_id',
            __NAMESPACE__ . '\EntIoc',
            'fk_ent_alert_id'
        );

        $this->hasOne(
            'pk_id',
            __NAMESPACE__ . '\EntAlertReview',
            'fk_ent_alert_id'
        );
    }
}
