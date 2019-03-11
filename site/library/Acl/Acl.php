<?php

namespace Thrust\Acl;

use Phalcon\Mvc\User\Component;

use Phalcon\Acl\Adapter\Memory as AclList;
use Phalcon\Acl\Role as AclRole;
use Phalcon\Acl\Resource as AclResource;

/**
 * Thrust\Acl\Acl
 */
class Acl extends Component
{
    /**
     * The ACL Object.
     *
     * @var \Phalcon\Acl\Adapter\Memory
     */
    protected $acl;

    /**
     * Define the controllers that are considered "public".
     * These controllers don't require authentication.
     *
     * @var []
     */
    protected $publicControllers = [
        'about',
        'common',
        'details',
        'download',
        'error',
        'index',
        'landing',        
        'managed',        
        'partners',
        'preview',
        'pricing',        
        'registration',
        'session',

        // API controllers
        'agent',
        'apibase',
        'jobs',
        'lb',
        'logstash',
        'msp',
        'utm',
    ];

    /**
     * Define the roles
     *
     * - role name
     * - - role level (the higher the more privileged)
     *
     * @var []
     */
    protected $roles = [
        'user'      => 0,
        'admin'     => 1,
        'provider'  => 2,
    ];

    /**
     * Define the resources to be assigned to roles
     * [Activity based authorization] - only define activities to be restricted
     *
     * @var []
     * - controller
     * - - action
     * - - - type (actions types used in ajax handlers)
     * - - - - level to define the minimum role level to access this resource
     */
    protected $resources = [
        'account' => [
            'manage' => [
                'billing' => 1,
                'suspend' => 1,
                'reactivate' => 1,
            ],
        ],
        'user-device' => [
            'manageUser' => 1,
        ],
        'service' => [
            'index' => 1,
            'device' => 1,
        ],
    ];

    /**
     * Checks if a controller is private or not.
     *
     * @param string $controllerName
     *
     * @return bool
     */
    public function isPrivate($controllerName)
    {
        $controllerName = strtolower($controllerName);

        return ! in_array($controllerName, $this->publicControllers);
    }

    /**
     * Checks if the current profile is allowed to access a resource.
     *
     * @param string $role          - User role to check access
     * @param string $controller    - Controller
     * @param string $action        - Action
     * @param string $type          - Type in ajax handler actions
     *
     * @return bool
     */
    public function isAllowed($role, $controller, $action, $type = false)
    {
        // first check if the requested action is among the restricted list
        if (
            ! isset($this->resources[$controller]) ||
            ! isset($this->resources[$controller][$action]) ||
            ($type && ! isset($this->resources[$controller][$action][$type]))
        ) {
            return true;
        }

        if ($type) {
            // check for individual action types

            return $this->getAcl()->isAllowed(
                $role,
                $controller,
                $action,
                [
                    'allowed' => ($this->resources[$controller][$action][$type] <= $this->roles[$role]),
                ]
            );
        } else {
            // only check for action

            return $this->getAcl()->isAllowed(
                $role,
                $controller,
                $action
            );
        }
    }

    /**
     * Returns the role list
     *
     * @param  boolean $include_level - whether to include the role level in the result
     * @return []
     */
    public function getRoles($include_level = false)
    {
        if ($include_level) {
            return $this->roles;
        } else {
            $roles = $this->roles;
            arsort($roles); // sort by role level descending order
            return array_keys($roles);
        }
    }

    /**
     * Returns the ACL list.
     *
     * @return Phalcon\Acl\Adapter\Memory
     */
    public function getAcl()
    {
        // Check if the ACL is already created
        if (is_object($this->acl)) {
            return $this->acl;
        }

        $this->acl = $this->buildAclList();

        return $this->acl;
    }

    /**
     * Build ACL List
     *
     * @return \Phalcon\Acl\Adapter\Memory
     */
    public function buildAclList()
    {
        $acl = new AclList();

        $acl->setDefaultAction(\Phalcon\Acl::DENY);

        foreach ($this->roles as $role_name => $role_level) {
            $acl->addRole(new AclRole($role_name));
        }

        foreach ($this->resources as $controller => $actions) {
            // add actions to ACL list
            $acl->addResource(new AclResource($controller), array_keys($actions));

            // allow resources to roles
            foreach ($actions as $action_name => $action) {
                foreach ($this->roles as $role_name => $role_level) {
                    if (is_array($action)) {
                        /**
                         * Allow action types to role
                         * check by role levels for cascaded privileges
                         */
                        $acl->allow(
                            $role_name,
                            $controller,
                            $action_name,
                            function($allowed) {
                                return $allowed;
                            }
                        );
                    } else {
                        // only assign actions
                        if ($action <= $role_level) {
                            $acl->allow($role_name, $controller, $action_name);
                        }
                    }
                }
            }
        }

        return $acl;
    }
}
