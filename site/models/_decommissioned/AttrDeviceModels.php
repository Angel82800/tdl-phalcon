<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

class AttrDeviceModels extends Model
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

    public function initialize()
    {
        $this->hasMany(
            'pk_id',
            __NAMESPACE__ . '\EntDevice',
            'fk_attr_device_models_id',
            [
                'alias' => 'device'
            ]
        );
    }
}
