<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Validator\Uniqueness;

/**
 * Thrust\Models\EntDevice
 */
class EntDevice extends Model
{
    public $pk_id;
    public $fk_map_organization_locations_id;
    public $fk_attr_device_models_id;
    public $unique_device_id;
    public $hostname;
    public $datetime_created;
    public $created_by;
    public $datetime_updated;
    public $updated_by;
    public $is_active;
    public $is_deleted;

    public function initialize()
    {
        $this->belongsTo(
            'fk_map_organization_locations_id',
            __NAMESPACE__ . '\MapOrganizationLocations',
            'pk_id',
            [
                'alias' => 'map'
            ]
        );

        $this->hasOne(
            'fk_attr_device_models_id',
            __NAMESPACE__ . '\AttrDeviceModels',
            'pk_id',
            [
                'alias' => 'model'
            ]
        );
    }
}
