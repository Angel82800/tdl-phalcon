<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\EntUserFilters
 * Blocked Sites for an Account.
 */
class EntUserFilters extends Model
{
    public $pk_id;

    public $fk_ent_device_id;

    public $fk_ent_users_id;

    public $fk_attr_filter_action_id;

    public $filter_hostname;

    public $datetime_created;

    public $created_by;

    public $datetime_updated;

    public $updated_by;

    public $is_active;

    public $is_deleted;

    public function initialize()
    {
        $this->belongsTo(
            'fk_ent_users_id',
            "Thrust\Models\EntUsers",
            'pk_id',
            [
                'alias' => 'user'
            ]
        );

        $this->hasOne(
            'fk_attr_filter_action_id',
            __NAMESPACE__ . '\AttrFilterAction',
            'pk_id',
            [
                'alias' => 'action'
            ]
        );
    }
}
