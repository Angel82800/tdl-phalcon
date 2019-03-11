<?php

namespace Thrust\Models\Security;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\Security\Alerts
 * Security alerts for a user.
 */
class Alerts extends Model
{
    /**
     * ID.
     *
     * @var int
     */
    public $id;

    public $userId;

    public $alertTime;

    public $alertUrl;

    public $description;

    public $isRead;

    /**
     * Define relationships to Users.
     */
    public function initialize()
    {
        $this->belongsTo('userId', "Thrust\Models\Users", 'id');
    }

    /**
     * Stub method to mock DB results.
     *
     * @param mixed $params
     */
    public static function find($params = null)
    {
        $alert1 = new self();
        $alert1->id = 1;
        $alert1->userId = 123;
        $alert1->alertType = 'new_device';
        $alert1->alertUrl = 'http://todyl.com/dashboard/alerts';
        $alert1->alertTime = '01-27-2016 08:32:16';
        $alert1->description = "John's iPhone attempted to connect to your wireless network. Todyl Shield blocked this attempt and did not allow this device to connect.";
        $alert1->isRead = 0;

        $alert2 = new self();
        $alert2->id = 2;
        $alert2->userId = 123;
        $alert2->alertType = 'user_blocklist_add';
        $alert2->alertUrl = 'http://facebook.com';
        $alert2->alertTime = '01-25-2016 08:32:16';
        $alert2->description = '';
        $alert2->isRead = 0;

        $alert3 = new self();
        $alert3->id = 3;
        $alert3->userId = 123;
        $alert3->alertType = 'blocked_site';
        $alert3->alertUrl = 'http://badwebsite.com';
        $alert3->alertTime = '01-23-2016 08:32:16';
        $alert3->description = 'http://badwebsite.com has been known to house files that may harm your business such as maware, spyware or virues. Todyl has automatically placed this website on our watch list, which prevents all members from viewing this site content.';
        $alert3->isRead = 0;

        $result = [$alert1, $alert2, $alert3];

        return $result;
    }
}
