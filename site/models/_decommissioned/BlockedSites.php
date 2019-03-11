<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;
use Thrust\Saltstack\Api;

/**
 * Thrust\Models\BlockedSites
 * Blocked Sites for an Account.
 */
class BlockedSites extends Model
{
    public $id;

    public $accountId;

    public $blockedTime;

    public $blockedUrl;

    public $blockedBy;

    /**
     * Define relationships to Accounts.
     */
    public function initialize()
    {
        $this->belongsTo('accountId', "Thrust\Models\Users", 'id');
    }

    public function updateSalt($newUrl = null)
    {
        $saltApi = new Api();

        return $saltApi->updateBlacklist($this->blockedUrl, $newUrl);
    }

    // TODO use a beforeDelete function instead maybe?
}
