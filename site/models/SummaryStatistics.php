<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\SummaryStatistics
 */
class SummaryStatistics extends ModelBase
{

    //Not declaring the variables as there are far too many columns

    public function initialize()
    {
        //Set the table name
        $this->setSource('SummaryStatistics');

        //Setup the DB connections
        $this->setReadConnectionService('ioc-read');
        $this->setWriteConnectionService('ioc-write');
        
    }
}
