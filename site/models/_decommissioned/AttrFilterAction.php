<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\AttrFiltercAction
 */
class AttrFilterAction extends Model
{
    public $pk_id;
    public $action_name;
    public $is_blocked;
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
            "Thrust\Models\EntUserFilters",
            'fk_attr_filter_action_id',
            [
                'alias' => 'filters'
            ]
        );
    }
}
