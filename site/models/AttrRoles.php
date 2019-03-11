<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\AttrRoles
 */
class AttrRoles extends ModelBase
{
    public $pk_id;
    public $name;
    public $description;
    public $datetime_created;
    public $created_by;
    public $datetime_updated;
    public $updated_by;
    public $is_active;
    public $is_deleted;

    /**
     * Define relationships to Accounts.
     */
    public function initialize()
    {
        //Setup the DB connections
        $this->setReadConnectionService('oltp-read');
        $this->setWriteConnectionService('oltp-write');

        $this->hasMany('pk_id', "EntUsers", 'fk_attr_roles_id');
    }
}
