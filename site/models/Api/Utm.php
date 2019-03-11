<?php

namespace Thrust\Models\Api;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

/**
 * Utm
 * This model handles the interaction with the hardware products
 * This model uses the ApiBaseController error and response handling
 */

class Utm extends \Thrust\Models\ModelBase
{

	public $UDID;
	private $db;
	private $config;

	public function initialize()
    {
		$this->db = \Phalcon\Di::getDefault()->get('oltp-write'); //TODO: Clean this up and move to models
		$this->config = \Phalcon\Di::getDefault()->get('config');
    }

    public function getGrains($UDID)
	{
		$sql = "
			SELECT * 
			FROM ent_utm
			INNER JOIN ent_utm_settings ON ent_utm_settings.ent_utm_id = ent_utm.pk_id
			WHERE ent_utm.UDID = '$UDID'
		";
		$result = $this->db->fetchOne($sql);
		print_r($result); exit;
	}
}