<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\EntIps
 */
class EntIps extends ModelBase
{
    public $pk_id;
    public $fk_ent_alert_id;
    public $gid;
    public $sid;
    public $alert;
    public $classification;
    public $protocol;
    public $srcIp;
    public $srcPort;
    public $dstIp;
    public $dstPort;
    public $action;
    public $dstCountryCode2;
    public $dstCountryCode3;
    public $dstCountryName;
    public $dstRegionName;
    public $dstCityName;
    public $dstPostalCode;
    public $dstLatitude;
    public $dstLongitude;
    public $dstDmaCode;
    public $dstAreaCode;
    public $dstTimezone;
    public $dstRealRegionName;
    public $srcCountryCode2;
    public $srcCountryCode3;
    public $srcCountryName;
    public $srcRegionName;
    public $srcCityName;
    public $srcPostalCode;
    public $srcLatitude;
    public $srcLongitude;
    public $srcDmaCode;
    public $srcAreaCode;
    public $srcTimezone;
    public $srcRealRegionName;
    public $datetime_created;
    public $created_by;
    public $datetime_updated;
    public $updated_by;
    public $is_active;
    public $is_deleted;

    public function initialize()
    {
        //Setup the DB connections
        $this->setReadConnectionService('oltp-read');
        $this->setWriteConnectionService('oltp-write');

        $this->belongsTo(
            'fk_ent_alert_id',
            __NAMESPACE__ . '\EntAlert',
            'pk_id'
        );
    }
}
