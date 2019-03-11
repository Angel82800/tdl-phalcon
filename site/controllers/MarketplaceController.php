<?php

namespace Thrust\Controllers;

use Thrust\Models\EntUsers;

/**
 * Thrust\Controllers\MarketplaceController.
 */
class MarketplaceController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateBefore('private');

        $identity = $this->auth->getIdentity();
        // check if admin
        // DEMO
        if ($identity['orgId'] != 1 && $identity['email'] != 'demo@todyl.com') {
            throw new \Exception('User ID ' . $identity['id'] . ' tried to enter marketplace page.');
        }
    }

    public function indexAction()
    {
        $identity = $this->auth->getIdentity();
        $user_id = $identity['id'];

        $user = EntUsers::findFirst([
            'conditions' => 'pk_id = ?1',
            'bind'       => [
                1 => $user_id,
            ],
            'cache'      => 60,
        ]);

        $organization = $user->getOrganization([ 'cache' => 60 ]);

        $data = [
            'user'          => $user,
            'organization'  => $organization,
        ];

        $this->view->setVars($data);
    }

}
