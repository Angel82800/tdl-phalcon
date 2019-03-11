<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * RememberTokens
 * Stores the remember me tokens.
 */
class RememberTokens extends ModelBase
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $usersId;

    /**
     * @var string
     */
    public $token;

    /**
     * @var string
     */
    public $userAgent;

    /**
     * @var int
     */
    public $createdAt;

    /**
     * Before create the user assign a password.
     */
    public function beforeValidationOnCreate()
    {
        // Timestamp the confirmaton
        $this->createdAt = time();
    }

    public function initialize()
    {
        //Setup the DB connections
        $this->setReadConnectionService('oltp-read');
        $this->setWriteConnectionService('oltp-write');

        $this->belongsTo('usersId', __NAMESPACE__ . '\Users', 'id', array(
            'alias' => 'user'
        ));
    }
}
