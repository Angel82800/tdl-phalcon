<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\LogIncidentComments
 */
class LogIncidentComments extends ModelBase
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
    public $fk_ent_incident_id;

    /**
     *
     * @var integer
     * @Column(type="integer", length=20, nullable=false)
     */
    public $fk_ent_users_id;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $comment;

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

        $this->belongsTo('fk_ent_incident_id', __NAMESPACE__ . '\EntIncident', 'pk_id', ['alias' => 'incident']);
        $this->belongsTo('fk_ent_users_id', __NAMESPACE__ . '\EntUsers', 'pk_id', ['alias' => 'user']);
    }

}
