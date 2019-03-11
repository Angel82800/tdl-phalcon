<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Validator\Uniqueness;

/**
 * Thrust\Models\AttrRegions
 */
class AttrRegions extends ModelBase
{
    public $pk_id;
    public $name;
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
        
        $this->hasOne(
            'pk_id',
            __NAMESPACE__ . '\EntOrganization',
            'fk_attr_regions_id',
            [
                'alias' => 'map'
            ]
        );
    }
}
