<?php

namespace Api\Models;

use Phalcon\Mvc\Model;

class Test extends Model
{

    public function initialize()
    {
    	//Populate properties
    	//$this->logger = $this->di->getShared('logger');

        //Setup the DB connections
        //$this->db = \Phalcon\Di::getDefault()->get('oltp-read');
    }

    public static function test ()
    {
    	echo "BOOM"; exit;

    	//$sql = "SELECT 1";
    	//$result = $this->db->execute($sql);
    	//echo $result; 
    }
}
