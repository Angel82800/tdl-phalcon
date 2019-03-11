<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Permissions
 * Stores the permissions by profile.
 */
class Permissions extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $profilesId;

    /**
     * @var string
     */
    public $resource;

    /**
     * @var string
     */
    public $action;

    public function initialize()
    {
        $this->belongsTo('profilesId', 'Thrust\Models\Profiles', 'id', array(
            'alias' => 'profile'
        ));
    }
}
