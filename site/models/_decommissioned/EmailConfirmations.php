<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * EmailConfirmations
 * Stores the reset password codes and their evolution.
 */
class EmailConfirmations extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $usersId;

    public $code;

    /**
     * @var int
     */
    public $createdAt;

    /**
     * @var int
     */
    public $modifiedAt;

    public $confirmed;

    /**
     * Before create the user assign a password.
     */
    public function beforeValidationOnCreate()
    {
        // Timestamp the confirmation
        $this->createdAt = time();

        // Generate a random confirmation code
        $this->code = preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(openssl_random_pseudo_bytes(24)));

        // Set status to non-confirmed
        $this->confirmed = 'N';
    }

    /**
     * Sets the timestamp before update the confirmation.
     */
    public function beforeValidationOnUpdate()
    {
        // Timestamp the confirmation
        $this->modifiedAt = time();
    }

    /**
     * Send a confirmation e-mail to the user after create the account.
     */
    public function afterCreate()
    {
        $this->getDI()
            ->getMail()
            ->send(array(
            $this->user->email => $this->user->name
        ), 'Please confirm your email', 'confirmation', array(
            'confirmUrl' => '/confirm/' . $this->code . '/' . $this->user->email
        ));
    }

    public function initialize()
    {
        $this->belongsTo('usersId', __NAMESPACE__ . '\Users', 'id', array(
            'alias' => 'user'
        ));
    }
}
