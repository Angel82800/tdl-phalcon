<?php

namespace Thrust\Models;

/**
 * Thrust\Models\EntSettings
 */
class EntSettings extends ModelBase
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
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $key;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $value;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $createdy_by;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $updated_by;

    /**
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    public $datetime_created;

    /**
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    public $datetime_updated;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=true)
     */
    public $is_active;

    /**
     *
     * @var integer
     * @Column(type="integer", length=4, nullable=true)
     */
    public $is_deleted;

    public function initialize()
    {
        // Setup the DB connections
        $this->setReadConnectionService('oltp-read');
        $this->setWriteConnectionService('oltp-write');
    }

}
