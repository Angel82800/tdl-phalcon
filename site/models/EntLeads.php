<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Message;
use Phalcon\Validation;

use Thrust\Models\EntUsers;

/**
 * Thrust\Models\EntLeads
 */
class EntLeads extends ModelBase
{

    /**
     *
     * @var integer
     * @Primary
     * @Identity
     * @Column(type="integer", length=20, nullable=false)
     */
    public $pk_id;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $email;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $password;

    /**
     *
     * @var integer
     * @Column(type="integer", length=20, nullable=true)
     */
    public $num_devices;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $is_registered;

    /**
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    public $datetime_created;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $created_by;

    /**
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    public $datetime_updated;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $updated_by;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=true)
     */
    public $is_active;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=true)
     */
    public $is_deleted;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        // Setup the DB connections
        $this->setReadConnectionService('oltp-read');
        $this->setWriteConnectionService('oltp-write');
    }

    /**
     * Ensure email is not already registered in EntUsers
     */
    public function validation()
    {
        $count = EntUsers::count([
            'email = ?0',
            'bind' => [ $this->email ],
            'cache' => false,
        ]);

        if ($count) {
            $this->appendMessage(new Message('It appears that this email is already registered. Please try to <a href="/session/login">log in</a> or <a href="/session/forgotPassword">reset your password</a>.'));

            return false;
        }

        return $this->validationHasFailed() != true;
    }

}
