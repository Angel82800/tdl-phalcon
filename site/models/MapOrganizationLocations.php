<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Validator\Uniqueness;

/**
 * Thrust\Models\MapOrganizationLocations
 */
class MapOrganizationLocations extends ModelBase
{
    public $pk_id;
    public $fk_attr_regions_id;
    public $fk_ent_organization_id;
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
            'fk_attr_regions_id',
            __NAMESPACE__ . '\AttrRegions',
            'pk_id',
            [
                'alias' => 'region'
            ]
        );

        $this->belongsTo(
            'fk_ent_organization_id',
            __NAMESPACE__ . '\EntOrganization',
            'pk_id',
            [
                'alias' => 'organization'
            ]
        );
    }
}
