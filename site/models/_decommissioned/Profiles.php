<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\Profiles
 * All the profile levels in the application. Used in conjenction with ACL lists.
 */
class Profiles extends Model
{
    /**
     * ID.
     *
     * @var int
     */
    public $id;

    /**
     * Name.
     *
     * @var string
     */
    public $name;

    /**
     * Define relationships to Users and Permissions.
     */
    public function initialize()
    {
        $this->hasMany('id', __NAMESPACE__ . '\EntUsers', 'profilesId', array(
            'alias'      => 'users',
            'foreignKey' => array(
                'message' => 'Profile cannot be deleted because it\'s used on Users'
            )
        ));

        $this->hasMany('id', __NAMESPACE__ . '\Permissions', 'profilesId', array(
            'alias' => 'permissions'
        ));
    }
}
