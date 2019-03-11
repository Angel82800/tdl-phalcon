<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Validator\Uniqueness;

/**
 * Todyl\Models\Contaact
 * Users who signed up to be contacted.
 */
class Contacts extends Model
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $email;
    /**
     * @var string
     */
    public $businessSize;

    /**
     * Send a confirmation e-mail to the user if the account is not active.
     */
    public function afterSave()
    {
        // Get first name if possible
        $nameParts = explode(' ', $this->name);
        $this->getDI()
            ->getMail()
            ->send(array(
            $this->email => $this->email
        ), 'Welcome To Todyl', 'general-submission', array());
    }

    /**
     * Validate that emails are unique across users.
     */
    public function validation()
    {
        $this->validate(new Uniqueness(array(
            'field'   => 'email',
            'message' => 'The email is already registered'
        )));

        return $this->validationHasFailed() != true;
    }
}
