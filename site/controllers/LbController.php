<?php

namespace Thrust\Controllers;

/**
 * Health Checks for Load Balancers
 */
class LbController extends ControllerBase
{

    private $db;

    public function initialize()
    {
        //DB Connections
        $this->db = \Phalcon\Di::getDefault()->get('oltp-write');

        //Authentication
        if ($this->request->getQuery()['secret'] != "1078734dd204b66bfd5d04e10a6df890") {
            $this->response->setStatusCode(503, 'Service Unavailable')->send();
            exit;
        }
    }

    //Need to update this in the future to cover more DBs and resources - IE memcache, etc.
    public function indexAction()
    {
        $this->view->disable();
        $sql = "select 1 as Result";
        $dbTest = $this->db->fetchOne($sql)['Result'];
        if ($dbTest == 1) {
            $this->response->setStatusCode(200, 'OK')->send();
        } else {
            $this->response->setStatusCode(500, 'Error')->send();
        }
    }
}
