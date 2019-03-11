<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;
use Thrust\TinyCert\Api as TinyCert;

/**
 * Subscriptions
 * This model handles the resigstration, creation, authentication, and decomissioning of agents.
 * This model uses the ApiBaseController error and response handling
 */

public function 