<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * LogAgent
 * This model ingests customer acceptance of various agreements.
 */
class LogAgreement extends ModelBase
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
    public $fk_ent_users_id;

    /**
     *
     * @var string
     * @Column(type="string", length=45, nullable=false)
     */
    public $agreement_name;

    /**
     *
     * @var string
     * @Column(type="string", length=15, nullable=false)
     */
    public $ip_address;

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
