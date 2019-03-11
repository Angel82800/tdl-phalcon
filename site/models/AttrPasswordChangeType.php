<?php

namespace Thrust\Models;

/**
 * Thrust\Models\AttrPasswordChangeType
 */
class AttrPasswordChangeType extends ModelBase
{
    /**
     * @var int
     */
    public $pk_id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var datetime
     */
    public $datetime_created;

    /**
     * @var int
     */
    public $created_by;

    /**
     * @var datetime
     */
    public $datetime_updated;

    /**
     * @var int
     */
    public $updated_by;

    /**
     * @var boolean
     */
    public $is_active;

    /**
     * @var boolean
     */
    public $is_deleted;

    public function initialize()
    {
        // Setup the DB connections
        $this->setReadConnectionService('oltp-read');
        $this->setWriteConnectionService('oltp-write');

        $this->hasMany(
            'pk_id',
            __NAMESPACE__ . '\LogPasswordChanges',
            'fk_attr_password_change_type_id'
        );
    }
}
