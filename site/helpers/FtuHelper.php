<?php

namespace Thrust\Helpers;

use Phalcon\Http\Request;

use Thrust\Models\LogFtu;

class FtuHelper
{
    protected $di;
    protected $logger;
    protected $session;

    function __construct()
    {
        $this->di = \Phalcon\DI::getDefault();
        $this->logger = \Phalcon\Di::getDefault()->getShared('logger');
        $this->session = \Phalcon\Di::getDefault()->getShared('session');
    }

    public function getFtuHistory($user_id)
    {
        $ftu_history = LogFtu::find([
            'conditions' => 'fk_ent_users_id = ?1 AND is_active = 1 AND is_deleted = 0',
            'bind'       => [
                1 => $user_id,
            ],
            'cache'      => false,
        ]);

        $ftu_actions = [];
        foreach ($ftu_history as $history) {
            if (! isset($ftu_actions[$history->controller])) {
                $ftu_actions[$history->controller] = [];
            }

            $ftu_actions[$history->controller][$history->ftu_action] = 1;
        }

        return $ftu_actions;
    }

    public function addFtuHistory($user_id, $controller, $ftu_action)
    {
        $ftuData = [
            'fk_ent_users_id'   => $user_id,
            'controller'        => $controller,
            'ftu_action'        => $ftu_action,
            'created_by'        => 'thrust',
            'updated_by'        => 'thrust',
        ];

        $logftu = new LogFtu();
        if ($logftu->create($ftuData) === false) {
            return [
                'status' => 'fail',
                'error' => $logftu->getMessages(),
            ];
        }

        $this->session->set('ftu', $this->getFtuHistory($user_id));

        return [
            'status' => 'success',
        ];
    }

}
