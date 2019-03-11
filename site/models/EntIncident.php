<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\EntIncident
 */
class EntIncident extends ModelBase
{

    /**
     *
     * @var integer
     * @Primary
     * @Identity
     * @Column(type="integer", length=20, nullable=false)
     */
    public $pk_id;

    /**
     *
     * @var integer
     * @Column(type="integer", length=20, nullable=false)
     */
    public $fk_ent_alert_id;

    /**
     *
     * @var integer
     * @Column(type="integer", length=20, nullable=false)
     */
    public $fk_attr_incident_state_id;

    /**
     *
     * @var integer
     * @Column(type="integer", length=20, nullable=false)
     */
    public $fk_attr_incident_classification_id;

    /**
     *
     * @var integer
     * @Column(type="integer", length=20, nullable=true)
     */
    public $assigned_to;

    /**
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    public $user_instructions;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=true)
     */
    public $alert_sent;

    /**
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    public $datetime_created;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $created_by;

    /**
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    public $datetime_updated;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $updated_by;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=true)
     */
    public $is_active;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=true)
     */
    public $is_deleted;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        // Setup the DB connections
        $this->setReadConnectionService('oltp-read');
        $this->setWriteConnectionService('oltp-write');

        $this->hasMany('pk_id', __NAMESPACE__ . 'LogIncidentComments', 'fk_ent_incident_id', ['alias' => 'comments']);
        $this->belongsTo('fk_attr_incident_state_id', __NAMESPACE__ . '\AttrIncidentState', 'pk_id', ['alias' => 'state']);
        $this->belongsTo('fk_ent_alert_id', __NAMESPACE__ . '\EntAlert', 'pk_id', ['alias' => 'alert']);
        $this->belongsTo('fk_attr_incident_classification_id', __NAMESPACE__ . '\AttrIncidentClassification', 'pk_id', ['alias' => 'classification']);
        $this->belongsTo('assigned_to', __NAMESPACE__ . '\EntUsers', 'pk_id', ['alias' => 'user']);
    }

}
