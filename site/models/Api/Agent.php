<?php

namespace Thrust\Models\Api;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;
use Thrust\TinyCert\Api as TinyCert;
use Thrust\Models\DashboardStatistics;
use Thrust\Models\EntThreatLevel;

/**
 * Agent
 * This model handles the resigstration, creation, authentication, and decomissioning of agents.
 * This model uses the ApiBaseController error and response handling
 */

class Agent extends Model
{

	public $userID;
	public $pin;
	public $UDID;
	private $db;
	private $config;
	private $cache;

	public function initialize()
    {
		$this->db = \Phalcon\Di::getDefault()->get('oltp-write'); //TODO: Clean this up and move to models
		$this->config = \Phalcon\Di::getDefault()->get('config');
		$this->cache = \Phalcon\Di::getDefault()->get('modelsCache');
    }

	public function getOvpnFile($UDID, $isMac = null)
	{
		#Making backwards compatible with V1.x Defenders
		if (isset($isMac))
		{
			//Validate isMac
			if ($isMac == "0" OR $isMac == "1") {
				$isMac = ($isMac == 0 ? false : true);
			} else {
				return array(400, "Bad Parameter");
			}

			//Set the correct key mapping
			if ($isMac == 1)
			{
				$os = 2;
			} else if ($isMac == 0) {
				$os = 1;
			}

			//Update the device accordingly
			$sql = "UPDATE ent_agent SET fk_attr_os_type_id = '$os' WHERE UDID = '$UDID'";
			$result = $this->db->execute($sql);
			if ($result != 1) {
				return array(500, "Error");
			}
		}

		//Fetch the crypto and orgID
		$sql = "SELECT 
					ent_agent.ca, 
				    ent_agent.cert, 
				    ent_agent.private_key, 
				    ent_agent.fk_attr_os_type_id,
					ent_organization.pk_id
				FROM ent_organization
					INNER JOIN ent_users ON ent_users.fk_ent_organization_id = ent_organization.pk_id
					INNER JOIN ent_agent ON ent_agent.fk_ent_users_id = ent_users.pk_id
				WHERE UDID = '$UDID'";
		$result = $this->db->fetchOne($sql);

		//Create OVPN File
		$OvpnFile = $this->createOvpnFile($result['ca'], $result['cert'], $result['private_key'], $result['fk_attr_os_type_id'], $result['pk_id']);
		if(strpos($OvpnFile, 'Error') === false && $result != "") {
			$OvpnFile = json_encode(array('ovpn' => $OvpnFile));
			return array(200, $OvpnFile);
		} else {
			return array(400, "Error or bad UDID");
		}
	}

	public function verifyUDID($UDID)
	{
		$sql = "
			SELECT count(*) as count
			FROM ent_organization
				INNER JOIN ent_users ON ent_organization.pk_id = ent_users.fk_ent_organization_id
				INNER JOIN ent_agent ON ent_agent.fk_ent_users_id = ent_users.pk_id
			WHERE 
				ent_agent.UDID = '$UDID'
			    AND ent_agent.is_active = 1
			    AND ent_agent.is_deleted = 0
			    AND ent_users.is_active = 1
			    AND ent_users.is_deleted = 0
			    AND ent_organization.is_active = 1
			    AND ent_organization.is_deleted = 0
		";
		$result = $this->db->fetchOne($sql)['count'];
		if ($result > 0) {
			return json_encode(array('status'=>'active'));
		} else {
			return json_encode(array('status'=>'inactive'));;
		}
	}

	//public function decomAgent($UDID)
	//{
    //	$sql = "UPDATE ent_agent SET is_active = 0 WHERE UDID = '$UDID'";
	//  return $this->db->execute($sql);
	//}

	public function downloadVersion($UDID, $file)
	{
		//Prevent directory traversal attack
		if( ($file == '') || (strpos($file, '../') !== false) ) {
			return array(400, "Invalid Request");
		}

		//Get the orgID
		$sql = "
			SELECT ent_organization.pk_id as pk_id
			FROM ent_organization
				INNER JOIN ent_users ON ent_organization.pk_id = ent_users.fk_ent_organization_id
				INNER JOIN ent_agent ON ent_agent.fk_ent_users_id = ent_users.pk_id
			WHERE 
				ent_agent.UDID = '$UDID'
		";
		$orgID = $this->db->fetchOne($sql)['pk_id'];
		
		//Todyl Alpha Release
		if ($orgID == 1) {
			$dir = $this->config->application->alphaDownloadDir;
		} else {
		//Mainline defender release
			$dir = $this->config->application->downloadDir;
		}

		//Check if exists
		if (! file_exists($dir . $file)) { return array(400, "Invalid Request"); }

		//Start file download
		$file_path  = $dir . $file;
		header('Pragma: public');
        header('Expires: -1');
        header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Type: application/octet-stream');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
		exit;
	}


	public function getVersion($UDID)
	{
		//Get the orgID
		$sql = "
			SELECT ent_organization.pk_id as pk_id
			FROM ent_organization
				INNER JOIN ent_users ON ent_organization.pk_id = ent_users.fk_ent_organization_id
				INNER JOIN ent_agent ON ent_agent.fk_ent_users_id = ent_users.pk_id
			WHERE 
				ent_agent.UDID = '$UDID'
		";
		$orgID = $this->db->fetchOne($sql)['pk_id'];
		
		//Todyl Alpha Release
		if ($orgID == 1) {
			$dir = $this->config->application->alphaDownloadDir;
		} else {
		//Mainline defender release
			$dir = $this->config->application->downloadDir;
		}

		//Set the files and extension
		$files = scandir($dir);
		$extensions = array("WIN" => ".exe", "MACv2" => ".dmg");

		//Find the newest version of each file for each extension
		foreach ($extensions as $key => $value) {
			$file_names = array_filter($files, function ($haystack) use ($value) {
				return(strpos($haystack, $value));
			});
			$latest = $this->findLatestByTime($file_names, $dir);
			$latest_array[$key]['md5'] = md5_file($dir . $latest);
			$latest_array[$key]['version'] = substr($latest, strpos($latest, "_") +1, -4);
			$latest_array[$key]['file'] = $latest;
		}

		//Response
		return array(200, json_encode($latest_array));
	}

	private function findLatestByTime($array, $dir) {
		$time = 0;
		foreach ($array as $key => $value) {
			$new_time = filemtime($dir.$value);
			if ($new_time > $time) {
				$time = $new_time;
				$latest = $value;
			}
		}
		return $latest;
	}

	public function registerAgent2($pin, $serial, $userDeviceName, $os)
	{
		//Sanitize input
		$pin = $this->db->escapeString($pin);
		$serial = $this->db->escapeString($serial);
		$userDeviceName = $this->db->escapeString($userDeviceName);

		//Check if PIN is valid
		$pin_check = $this->checkPIN($pin);
		if ($pin_check === false) {
			return array(400, json_encode(array('Message' => "PIN has already been used or is invalid", 'errorCode' => '1')));
		}

		//Check if userDeviceName is unique
		$deviceNameTaken = $this->deviceNameTaken($pin, $userDeviceName);
		if ($deviceNameTaken) {
			return array(400, json_encode(array('Message' => "This device name is already in use. Please try again.", 'errorCode' => '2')));
		}

		//Grab UDID
		$sql = "SELECT 
				    ent_agent.UDID,
					ent_organization.pk_id
				FROM ent_organization
					INNER JOIN ent_users ON ent_users.fk_ent_organization_id = ent_organization.pk_id
					INNER JOIN ent_agent ON ent_agent.fk_ent_users_id = ent_users.pk_id
				WHERE ent_agent.install_pin = $pin and pin_used = 0";
		$result = $this->db->fetchOne($sql);

		//Setup and store PKI from TinyCert, userDeviceName, and serial
		$setupAndStorePKI = $this->setupAndStorePKI($result['UDID'], $serial, $userDeviceName, $os);
		if($setupAndStorePKI === false) return array(500, json_encode(array('Message' => "Error", 'errorCode' => '0')));

		//Create OVPN File
		$OvpnFile = $this->createOvpnFile($setupAndStorePKI['ca'], $setupAndStorePKI['cert'], $setupAndStorePKI['pk'], $os, $result['pk_id']);

		//Form JSON
		$json = json_encode(array('ovpn' => $OvpnFile, 'UDID' => $result['UDID']));

		//Mark PIN used
		$sql = "UPDATE ent_agent SET pin_used = 1 WHERE install_pin = $pin";
		$result = $this->db->execute($sql);

		//Response
		if ($result !== false) {
			return array(200, $json);
		} else {
			return array(500, json_encode(array('Message' => "Error", 'errorCode' => '0')));
		}
	}

		public function reregisterAgent($pin, $serial, $userDeviceName, $os, $UDID)
	{
		//Sanitize input
		$pin = $this->db->escapeString($pin);
		$serial = $this->db->escapeString($serial);
		$userDeviceName = $this->db->escapeString($userDeviceName);
		$UDID = $this->db->escapeString($UDID);

		//Check if PIN is valid
		$pin_check = $this->checkPIN($pin);
		if ($pin_check === false) {
			return array(400, json_encode(array('Message' => "PIN has already been used or is invalid", 'errorCode' => '1')));
		}

		//Check if userDeviceName is unique
		$deviceNameTaken = $this->deviceNameTaken($pin, $userDeviceName, $UDID);
		if ($deviceNameTaken) {
			return array(400, json_encode(array('Message' => "This device name is already in use. Please try again.", 'errorCode' => '2')));
		}

		//Check if the Serials match - BYPASS DEV DUE TO REFRESH PROCESS
		if ($this->getDI()->get('config')->environment->env != "dev") {
		$sql = "SELECT device_serial from ent_agent WHERE UDID = $UDID";
			$old_serial = $this->db->fetchOne($sql)['device_serial'];
			if ($this->db->escapeString($old_serial) != $serial) {
				return array(400, json_encode(array('Message' => "This device could not be reactivated, serial check failed, please contact support@todyl.com", 'errorCode' => '3')));
			}
		}

		//Check if the old UDID is valid, if not valid assume moving cross account and register under new UDID/Pin to drop fingerprint
		$oldUdidExists = $this->oldUdidCheck($pin, $UDID);
		if ($oldUdidExists) {
			//Get the userID of the pin
			$sql = "		
				SELECT ent_users.pk_id 
				FROM ent_users
				INNER JOIN ent_agent on ent_agent.fk_ent_users_id = ent_users.pk_id
        		WHERE install_pin = $pin
        	";
        	$fk_ent_users_id = $this->db->fetchOne($sql)['pk_id'];

			//Swap the pins to migrate the security data, update the user as well
			$sql = "
				UPDATE ent_agent SET pin_used = 1, is_active = 0 WHERE install_pin = $pin;
				UPDATE ent_agent 
				SET 
					pin_used = 1, 
				    is_active = 1, 
				    is_deleted = 0, 
				    user_device_name = $userDeviceName, 
				    fk_attr_os_type_id = $os, 
				    device_serial = $serial,
				    fk_ent_users_id = $fk_ent_users_id
				WHERE UDID = $UDID;
			";
			$result = $this->db->execute($sql);

			//Fetch the crypto
			$sql = "SELECT 
					ent_agent.ca, 
				    ent_agent.cert, 
				    ent_agent.private_key, 
				    ent_agent.fk_attr_os_type_id,
					ent_organization.pk_id
				FROM ent_organization
					INNER JOIN ent_users ON ent_users.fk_ent_organization_id = ent_organization.pk_id
					INNER JOIN ent_agent ON ent_agent.fk_ent_users_id = ent_users.pk_id
				WHERE UDID = $UDID";
			$result = $this->db->fetchOne($sql);

			//Create OVPN File
			$OvpnFile = $this->createOvpnFile($result['ca'], $result['cert'], $result['private_key'], $result['fk_attr_os_type_id'], $result['pk_id']);

		} else {
			//Get the new UDID based on the PIN, since the old UDID is not valid anymore
			$sql = "SELECT 
					    ent_agent.UDID,
						ent_organization.pk_id
					FROM ent_organization
						INNER JOIN ent_users ON ent_users.fk_ent_organization_id = ent_organization.pk_id
						INNER JOIN ent_agent ON ent_agent.fk_ent_users_id = ent_users.pk_id
					WHERE ent_agent.install_pin = $pin";
			$result = $this->db->fetchOne($sql);
			$UDID = $result['UDID'];

			//Setup and store PKI from TinyCert, userDeviceName, and serial
			$setupAndStorePKI = $this->setupAndStorePKI($UDID, $serial, $userDeviceName, $os);
			if($setupAndStorePKI === false) return array(500, json_encode(array('Message' => "Error", 'errorCode' => '0')));

			//Create OVPN File
			$OvpnFile = $this->createOvpnFile($setupAndStorePKI['ca'], $setupAndStorePKI['cert'], $setupAndStorePKI['pk'], $os, $result['pk_id']);

			//Mark PIN used
			$sql = "UPDATE ent_agent SET pin_used = 1 WHERE install_pin = $pin";
			$result = $this->db->execute($sql);
		}

		//Form JSON
		//Remove the escapeString
		$UDID = str_replace("'", "", $UDID);
		$json = json_encode(array('ovpn' => $OvpnFile, 'UDID' => $UDID));

		//Response
		if ($result !== false) {
			return array(200, $json);
		} else {
			return array(500, json_encode(array('Message' => "Error", 'errorCode' => '0')));
		}
	}

	public function updateAgent($UDID, $os, $osEdition, $osBuild, $deviceType, $defenderVersion, $serial)
	{
		//Sanitize & validate input
		$UDID = $this->db->escapeString($UDID);
		$os = $this->db->escapeString($os);
		$osEdition = $this->db->escapeString($osEdition);
		$osBuild = $this->db->escapeString($osBuild);
		$deviceType = $this->db->escapeString($deviceType);
		$defenderVersion = $this->db->escapeString($defenderVersion);
		$serial = $this->db->escapeString($serial);

		//Insert Update Log
		$sql = "INSERT INTO log_agent_updates (
					fk_ent_agent_UDID,
					os_edition,
					os_build,
					defender_version,
					created_by,
					updated_by
				) VALUES (
					$UDID,
					$osEdition,
					$osBuild,
					$defenderVersion,
					'thrust',
					'thrust'
				);
				";
		//Update Agent Attributes
		if ($serial != "'null'") {
			$sql = $sql."
				UPDATE ent_agent SET
					fk_attr_os_type_id = $os,
					fk_attr_device_type_id = $deviceType,
					updated_by = 'thrust',
					device_serial = $serial,
					datetime_updated = now()
				WHERE UDID = $UDID;";
		} else {
			$sql = $sql."
				UPDATE ent_agent SET
					fk_attr_os_type_id = $os,
					fk_attr_device_type_id = $deviceType,
					updated_by = 'thrust',
					datetime_updated = now()
				WHERE UDID = $UDID;";
		}

		$result = $this->db->execute($sql);

		if ($result !== false) {
			return array(200, "OK");
		} else {
			return array(500, "Error");
		}
	}

	/**
	 * create a new pin for user
	 * @param  integer $userID     - user id to create pin for
	 * @param  boolean $create_new - whether to create a new pin regardless of current unused ones
	 * @return string 						 - generated pin no
	 */
	public function createNewAgent($userID, $create_new = false)
	{
		$unregisteredAgentPin = false;
		if (! $create_new) $unregisteredAgentPin = $this->unregisteredAgentPin($userID);

		if ($unregisteredAgentPin === false) {
			$newPin = $this->generatePin();
			$UDID = $this->generateUDID();
			$sql = "INSERT INTO ent_agent (
						fk_ent_users_id,
						install_pin,
						UDID,
						created_by,
						updated_by
					) values (
						$userID,
						'$newPin',
						'$UDID',
						'thrust',
						'thrust'
					)";
			$this->db->execute($sql);
			return $newPin;
		} else {
			return $unregisteredAgentPin;
		}
	}

	public function unregisteredAgentPin($userID)
	{
		$sql = "SELECT install_pin FROM ent_agent WHERE fk_ent_users_id = $userID AND pin_used = 0 AND is_active = 1 AND is_deleted = 0";
		$result = $this->db->fetchOne($sql);
		if ($result === false) {
			return false;
		} else {
			return $result['install_pin'];
		}
	}

	public function home($UDID, $request)
	{
		$response_array = array();

        $stats = new DashboardStatistics();

        $threat_state = $stats->threatState();

        $threat_level_obj = EntThreatLevel::findFirst([
            'conditions' => 'LOWER(title) = ?1',
            'bind'       => [
                1        => $threat_state,
            ],
            'cache'      => 300
        ]);

		$response_array['threat_level'] = $threat_level_obj->pk_id;
		$response_array['threat_copy'] = $threat_level_obj->description;
		$response_array['threat_image'] = 'https://www.todyl.com' . $threat_level_obj->image_path;

		//Fetch the user device name
		$sql= "
			SELECT user_device_name as name
			FROM ent_agent
			WHERE UDID = '".$UDID."';
		";
		$response_array['userDeviceName'] = $this->db->fetchOne($sql)['name'];

		// Fetch the IP from CF headers otherwise use client IP
		$header = $request->getHeader('CF-Connecting-IP');
		$response_array['IP'] = ($request->getHeader('CF-Connecting-IP') ? $request->getHeader('CF-Connecting-IP') : $request->getClientAddress());

		//Get the connected datacenter location
		$sql = "
			SELECT attr_datacenter_details.location as location
			FROM log_agent_connections
			INNER JOIN attr_datacenter_details ON log_agent_connections.fk_attr_datacenter_details_name = attr_datacenter_details.name
			WHERE fk_ent_agent_UDID = '".$UDID."'
			AND datetime_disconnected is NULL
			ORDER BY pk_id desc
			LIMIT 1;
		";
		$response_array['cloudLocation'] = $this->db->fetchOne($sql)['location'];

		//Harcode the fileScan for now ToDo: build tool to control
		$response_array['fileScan'] = 1;

		//Fetch the debug logging setting
		$sql= "
			SELECT enable_debug_logging as debugLogging
			FROM ent_agent
			WHERE UDID = '".$UDID."';
		";
		$response_array['debugLogging'] = $this->db->fetchOne($sql)['debugLogging'];

		//Harcode the forceFullScan for now ToDo: build tool to control
		$response_array['forceFullScan'] = '2017-12-19 00:00:00';

		// Fetch the blocks for the past 24 hours
		$sql = "
			SELECT SUM(a.block_count) as block_count, a.hour
			FROM (
				SELECT
					COUNT(pk_id) AS block_count,
					DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as hour
				FROM
					ent_alert
				WHERE
					fk_ent_agent_UDID = '".$UDID."'
					AND fk_attr_alert_action_id = 1
					AND timestamp > DATE_FORMAT(NOW() - INTERVAL 24 HOUR,'%Y-%m-%d %H:00:00')
				GROUP BY
					DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00')

				UNION

				SELECT *
				FROM (
					SELECT 0 as block_count, DATE_FORMAT(DATE_ADD(NOW() - INTERVAL 24 HOUR, INTERVAL @rownum := @rownum + 1 HOUR),'%Y-%m-%d %H:00:00')  as hour
					FROM ent_alert
					JOIN (SELECT @rownum := -1) r
					LIMIT 24
				) a
			) a
			GROUP BY hour;
        ";
        $result = $this->db->fetchAll($sql);

		if ($result === false) {
			return false;
		} else {
			$block_array = array();
			foreach($result as $array)
			{
				$block_array[$array['hour']] = $array['block_count'];
			}
			$response_array['past24hBlocks'] = $block_array;

			return json_encode($response_array);
		}
	}

	public function getSubscription($UDID)
	{
		$sql = "
			SELECT ent_marketplace_item.name
			FROM map_subscription
			INNER JOIN ent_subscription on ent_subscription.pk_id = map_subscription.fk_ent_subscription_id
			INNER JOIN ent_marketplace_item on ent_subscription.fk_ent_marketplace_item_id = ent_marketplace_item.pk_id
			WHERE 
				(fk_ent_agent_UDID is null OR fk_ent_agent_UDID = '$UDID')
				AND (fk_ent_users_id is null OR fk_ent_users_id in (
					select fk_ent_users_id 
					from ent_agent 
					where UDID = '$UDID'
				)) 
				AND (fk_ent_organization_id is null OR fk_ent_organization_id in (
					select fk_ent_organization_id 
					from ent_users 
					inner join ent_agent on ent_users.pk_id = ent_agent.fk_ent_users_id
					inner join ent_organization on ent_users.fk_ent_organization_id = ent_organization.pk_id
					where ent_agent.UDID = '$UDID'
				));
		";
		$result = $this->db->fetchAll($sql);
		$response_array = array();
		foreach ($result as $key => $value) {
			$response_array['subscriptions'] = $value['name'];
		}
		echo json_encode($response_array); exit;
	}

	private function generatePin()
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$generatedPin = '';
		for ($i = 0; $i < 4; $i++) {
			$generatedPin .= $characters[rand(0, $charactersLength - 1)];
		}
		//Make sure PIN is not already available
		$pin = $this->db->escapeString($generatedPin);
		if ($this->checkPIN($pin)) {
			$this->generatePin();
		}
		return $generatedPin;
	}

	private function generateUDID()
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$UDID = '';
		for ($i = 0; $i < 10; $i++) {
			$UDID .= $characters[rand(0, $charactersLength - 1)];
		}
		$UDID = hash('sha256', $UDID);
		return $UDID;
}

	private function checkPIN($pin)
	{
		$sql = "SELECT install_pin FROM ent_agent WHERE install_pin = $pin and pin_used = 0";
		$result = $this->db->fetchOne($sql);
		return $result;
	}

	public function deviceNameTaken($pin, $userDeviceName, $UDID = "''")
	{
		$sql = "
			SELECT *
			FROM ent_agent, ent_users, ent_organization
			WHERE ent_agent.fk_ent_users_id = ent_users.pk_id

	    	AND ent_agent.is_active = 1
	    	AND ent_agent.is_deleted = 0
	    	AND ent_users.is_active = 1
	    	AND ent_users.is_deleted = 0
	    	AND ent_organization.is_active = 1
	   		AND ent_organization.is_deleted = 0

			AND ent_users.fk_ent_organization_id = ent_organization.pk_id
			AND ent_agent.user_device_name = $userDeviceName
			AND ent_agent.UDID != $UDID
			AND ent_organization.pk_id = (
				SELECT ent_organization.pk_id
				FROM ent_agent, ent_users, ent_organization
				WHERE ent_agent.fk_ent_users_id = ent_users.pk_id
				AND ent_users.fk_ent_organization_id = ent_organization.pk_id
				AND install_pin = $pin
			)
		";
		$result = $this->db->fetchOne($sql);
		return $result;
	}

	public function oldUdidCheck($pin, $UDID)
	{
		$sql = "
			SELECT *
			FROM ent_agent, ent_users, ent_organization
			WHERE ent_agent.fk_ent_users_id = ent_users.pk_id
	    	AND ent_users.is_active = 1
	    	AND ent_users.is_deleted = 0
	    	AND ent_organization.is_active = 1
	   		AND ent_organization.is_deleted = 0
			AND ent_users.fk_ent_organization_id = ent_organization.pk_id
			AND ent_agent.UDID = $UDID
			AND ent_organization.pk_id = (
				SELECT ent_organization.pk_id
				FROM ent_agent, ent_users, ent_organization
				WHERE ent_agent.fk_ent_users_id = ent_users.pk_id
				AND ent_users.fk_ent_organization_id = ent_organization.pk_id
				AND install_pin = $pin
			)
		";
		$result = $this->db->fetchOne($sql);
		return $result;
	}

	public function mobileCarrier($UDID, $request)
	{
		//Check if passed through CF
		$IP = ($request->getHeader("CF-Connecting-IP")) ? $request->getHeader("CF-Connecting-IP") :  $request->getClientAddress();

		//Check cache for IP
		$cache = $this->cache->get('mobileCarrier-'.$IP);
		if ( ! $this->cache->get('mobileCarrier-'.$IP)) {
			//No cache entry - Setup call to ipinfo.io
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'ipinfo.io/'.$IP.'/carrier');
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$response = curl_exec($ch);	
			if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
				return false;
			} else {
				$this->cache->save('mobileCarrier-'.$IP, $response, 604800);
				return $response;
			}
		} else {
			return $cache;
		}
	}

	private function setupAndStorePKI($UDID, $serial, $userDeviceName, $os)
	{
		$tc_ca = $this->config->tinycert->ca_id;
		$tc = new TinyCert();
		$tc->connect();
		$ca_cert = $tc->ca_get($tc_ca, 'cert');
		$new_cert_id = $tc->cert_new($tc_ca, "$UDID", "Security Operations", "Todyl, Inc", "New York", "New York", "US", null);
		$new_cert = $tc->cert_get($new_cert_id, 'cert');
		$new_pk = $tc->cert_get($new_cert_id, 'key.dec');
		$result = $this->db->execute("UPDATE ent_agent SET ca = '$ca_cert', cert = '$new_cert', private_key = '$new_pk', device_serial = $serial, user_device_name = $userDeviceName, fk_attr_os_type_id = '$os', datetime_updated = now(), updated_by = 'thrust' WHERE UDID = '$UDID'");
		if ($result === false) {
			return $result;
		} else {
			$arr = array('ca' => $ca_cert, 'cert' => $new_cert, 'pk' => $new_pk);
			return $arr;
		}
	}

	private function createOvpnFile($CA, $CERT, $PK, $os, $orgID)
	{
		//Route Todyl to test domain
		if ($orgID == 1) {
			$domain = "guardian.eymber.net";
		} else {
			$domain = "guardian.todyl.net";
		}

		//OS Specific Settings
		if ($os == 1) {
			$BOD = "push 'dhcp-option DNS 192.0.2.1'
block-outside-dns
resolv-retry infinite
";
		} else {
			$BOD = "push 'dhcp-option DNS 192.0.2.1'
resolv-retry 0
";
		}

		//Create the file
		$file = "
################
#Todyl Defender
#Todyl Inc
################
client
dev tun
dev-type tun
proto udp
remote ".$domain." 1194
connect-retry 2
connect-timeout 5
nobind
mute-replay-warnings
".$BOD."
<ca>
".trim($CA)."
</ca>
<cert>
".trim($CERT)."
</cert>
<key>
".trim($PK)."
</key>
<tls-auth>
#
# 2048 bit OpenVPN static key
#
-----BEGIN OpenVPN Static key V1-----
c9a68ce7bfebe672969bb9961b7c9e39
3b50c01b0eac20618c063a0c11c2bda7
f43be5810a683ecee63bdbd6680441b4
7c17343b76cca6429232ea8ec3c04cab
6d322e018e98752506ff472f15e25ff8
fab6b70f6b009b05f2cbfe78ba4898ed
2267833ffeb4b6ca641305adbec2c75a
84fdc8a4e6161e3cbdc3f4f7a28f8f3c
7bc6e800605a98fcc79b08ca7ce849e1
f316b1a1f01fb478a9d074c341b326ee
10553b617f45f4cbab6bbb4ae7d99512
16291262ec6472a739057ef809a97eec
00aa27286cdf7eba7a59b075a52b12e8
8de57a2a4445e449286ecb09e33be672
6944772f4f0f3e03132d5fb58a9b9f21
24a43ae76afd04934e994f9c606cfeb7
-----END OpenVPN Static key V1-----
</tls-auth>
key-direction 1
cipher AES-256-CBC
compress lz4-v2
verb 3";
		return $file;
	}

	public function fileWhitelist($UDID)
	//Ability to pull org, user, and device level whitelists
	{
		$sql = "
			SELECT 
    			ent_organization.pk_id as fk_ent_organization_id,
    			ent_users.pk_id as fk_ent_users_id
			FROM ent_organization
				INNER JOIN ent_users ON ent_users.fk_ent_organization_id = ent_organization.pk_id
				INNER JOIN ent_agent ON ent_agent.fk_ent_users_id = ent_users.pk_id
			WHERE 
				ent_agent.UDID = '$UDID'
		";
		$result = $this->db->fetchAll($sql);

		$sql = "
			SELECT filename
			FROM ent_filescan_whitelist
				LEFT JOIN ent_organization on ent_organization.pk_id = ent_filescan_whitelist.fk_ent_organization_id
				LEFT JOIN ent_users ON ent_users.fk_ent_organization_id = ent_filescan_whitelist.fk_ent_users_id
				LEFT JOIN ent_agent ON ent_agent.UDID = ent_filescan_whitelist.fk_ent_agent_UDID
			WHERE
				ent_filescan_whitelist.is_active = 1
			    AND ent_filescan_whitelist.is_deleted = 0
			    AND (ent_filescan_whitelist.fk_ent_organization_id IS NULL OR
					ent_filescan_whitelist.fk_ent_organization_id = ".$result[0]['fk_ent_organization_id'].") 
				AND (ent_filescan_whitelist.fk_ent_users_id IS NULL OR
					ent_filescan_whitelist.fk_ent_users_id = ".$result[0]['fk_ent_users_id'].")
				AND (ent_filescan_whitelist.fk_ent_agent_UDID IS NULL OR
					ent_filescan_whitelist.fk_ent_agent_UDID = '$UDID')
			    AND ent_filescan_whitelist.is_active = 1
			    AND ent_filescan_whitelist.is_deleted = 0
		";
		$result = $this->db->fetchAll($sql);

		//Format the JSON return array
		$return_array = array();
		foreach ($result as $key => $value) {
			array_push($return_array, $value['filename']);
		}
		return json_encode($return_array);
	}		

}
