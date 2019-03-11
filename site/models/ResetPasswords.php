<?php

namespace Thrust\Models;

use Thrust\Helpers\MailHelper;

/**
 * ResetPasswords
 * Stores the reset password codes and their evolution.
 */
class ResetPasswords extends ModelBase
{
    /**
     * @var int
     */
    public $pk_id;

    /**
     * @var int
     */
    public $userId;

    /**
     * @var string
     */
    public $code;

    /**
     * @var int
     */
    public $createdAt;

    /**
     * @var int
     */
    public $modifiedAt;

    /**
     * @var string
     */
    public $is_reset;

    /**
     * Before create the user assign a password.
     */
    public function beforeValidationOnCreate()
    {
        // Timestamp the confirmaton
        $this->createdAt = time();

        // Generate a random confirmation code
        $this->code = preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(openssl_random_pseudo_bytes(24)));

        // Set status to non-confirmed
        $this->is_reset = 'N';
    }

    /**
     * Sets the timestamp before update the confirmation.
     */
    public function beforeValidationOnUpdate()
    {
        // Timestamp the confirmaton
        $this->modifiedAt = time();
    }

    /**
     * Send an e-mail to users allowing him/her to reset his/her password.
     */
    public function afterCreate()
    {
        $this->getDI()
            ->getMail()
            ->send([
                $this->user->email => $this->user->namespace
            ],
            'Reset your password',
            'reset-password',
            [
                'resetUrl' => 'reset-password/' . $this->code . '/' . urlencode($this->user->email),
            ]
        );
    }

    public function initialize()
    {
        //Setup the DB connections
        $this->setReadConnectionService('oltp-read');
        $this->setWriteConnectionService('oltp-write');

        $this->belongsTo('userId', __NAMESPACE__ . '\EntUsers', 'pk_id', array(
            'alias' => 'user'
        ));
    }
}
