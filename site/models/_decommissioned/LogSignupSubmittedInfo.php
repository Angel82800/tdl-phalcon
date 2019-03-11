<?php

namespace Thrust\Models;

use Phalcon\Mvc\Models;

class LogSignupSubmittedInfo extends Models
{
    public $pk_id;
    public $name;
    public $email;
    public $business_size;
    public $datetime_created;
    public $created_by;
    public $datetime_updated;
    public $updated_by;

    public function initialize()
    {

    }
}