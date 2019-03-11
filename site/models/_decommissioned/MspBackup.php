<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

class MspBackup extends ModelBase
{

	//Properties
	private $db;
	private $config;
	private $endpoint;
	private $login;
	private $password;
	private $logger;
	private $cache;
	private $msp_access_token; 
	private $S3;

	//Initialize
    public function initialize()
    {
    	//Populate properties
    	$this->endpoint = $this->di->get('config')->mspbackup->endpoint;
    	$this->login = $this->di->get('config')->mspbackup->login;
    	$this->password = $this->di->get('config')->mspbackup->password;
    	$this->cache = $this->di->get('modelsCache');
    	$this->logger = $this->di->getShared('logger');
    	$this->config = \Phalcon\Di::getDefault()->get('config');
    	$this->S3 = $this->di->get('S3');

        //Setup the DB connections
        $this->db = \Phalcon\Di::getDefault()->get('oltp-read');

        //Authenticate
        if ($this->Authenticate() === false) 
        {
        	throw new \Exception('MSP Backup: API Login Failure');
        }
    }

	public function registerDevice(array $udidArray)
	{
		foreach ($udidArray as $UDID) 
		{
			//Register the UDID
			$result = $this->Register($UDID);
			if ($result === false) {
				throw new \Exception('MSP Backup: Registration Failure - '.$UDID);
			} else {
				return true;
			}
		}
	}

	public function removeDevice(array $udidArray)
	{
		foreach ($udidArray as $UDID) 
		{
			//Remove the UDID
			$result = $this->Remove($UDID);
			if ($result === false) {
				throw new \Exception('MSP Backup: Removal Failure - '.$UDID);
			} else {
				return true;
			}
		}
	}

	private function Authenticate() 
	{
		//Check if the Auth token is already cached
		$msp_access_token = $this->cache->get('msp_access_token');

		//Authenticate if missing
		if ($msp_access_token == null) 
		{
			//Create the auth JSON body
			$auth_array = array('UserName' => $this->login, 'Password' => $this->password);
			$json_body = json_encode($auth_array);

			//cURL Call
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, $this->endpoint.'/api/Provider/Login'); 
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);     
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
    			'Content-Type: application/json',                                                                                
    			'Content-Length: ' . strlen($json_body))                                                                       
			);      
			$response = curl_exec($ch);

			//Error Handling / Save token
			if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) 
			{
				$this->logger->error('MSP Backup: Authentication Error - ' . $response);
				curl_close($ch);
				return false;
			} else {
				$response = json_decode($response);
				$this->msp_access_token = $response->access_token;
				$this->cache->save('msp_access_token',$response->access_token, $response->expires_in);
				curl_close($ch);
			}
		//Pull from cache and set if exists
		} else {
			$this->msp_access_token = $msp_access_token;
		}
	}

	private function Register($UDID)
	{
		//Create the JSON body
		$password = $this->generatePassword();
		$register_array = array(
			'Email' => $UDID,
			'Enabled' => true, 
			'Password' => $password,
			'DestinationList' => []
		);
	 	$json_body = json_encode($register_array);

		//Register Device API Call
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->endpoint.'/api/Users');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
    		'Authorization: Bearer '.$this->msp_access_token,
    	));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body); 
		$response = curl_exec($ch);

		//Error Handling / Save Info
		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) 
		{
			$this->logger->error('MSP Backup: Device Registration Error - ' . $response);
			curl_close($ch);
			return false;
		} else {
			//Check if already registered
			$response = json_decode($response);
			if ($response == "Email already exist.") 
			{
				//Error handling if UDID already registered
				$this->logger->error('MSP Backup: Device Already Registered - ' . $UDID);
				curl_close($ch);
				return false;
			} else {
				//Create the request to assign the user to a storage bucket
				$json_body = array();
				$json_body['UserID'] = $response; //UserID from above call
				$json_body['AccountID'] = $this->config->mspbackup->imageBackupAccountId;
				$json_body['Destination'] = $this->config->mspbackup->imageBackupDestination;
				$json_body['PackageID'] = $this->config->mspbackup->imageBackupPackageID;
				$json_body = json_encode($json_body);

				//Assign the user to a storage bucket
				curl_setopt($ch, CURLOPT_URL, $this->endpoint.'/api/Destinations');
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/json',                                                                   
    				'Content-Length: ' . strlen($json_body),  
    				'Authorization: Bearer '.$this->msp_access_token,
    			));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body); 
				$response2 = curl_exec($ch);

				//Error Handling
				if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) 
				{
					$this->logger->error('MSP Backup: Failed adding UDID ' . $UDID . ' to a bucket - '. curl_getinfo($ch, CURLINFO_HTTP_CODE) . $response2);
					curl_close($ch);
					return false;
				}
				else
				{
					//Store the MSP Backup ID & Add the log entry
					$sql = "
						INSERT INTO ent_mspbackup 
						(fk_ent_agent_UDID, msp_agent_ID, password, created_by, updated_by) 
						SELECT '".$UDID."', '".$response."', SecureEncrypt('".$password."'), 'thrust', 'thrust';

						INSERT INTO log_mspbackup
						(fk_ent_mspbackup_id, action_taken, created_by, updated_by)
						SELECT pk_id, 'Device Added to MSP Backup via API', 'thrust', 'thrust'
						FROM ent_mspbackup
						WHERE fk_ent_agent_UDID = '".$UDID."' and is_active = 1 and is_deleted = 0;

					";
					$result = $this->rawQuery($sql, 'execute', 0);
					curl_close($ch);
					return true;
				}
			}
		}

	} 

	private function generatePassword() 
	{
		$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789!@#$";
    	for ($i = 0; $i < 8; $i++) {
        	$n = rand(0, strlen($alphabet)-1);
        	$pass[$i] = $alphabet[$n];
    	}
    	return implode($pass);
	}

	private function Remove($UDID)
	{
		//Fetch the MSPbackup UserID
		$sql = "
				SELECT msp_agent_id 
				FROM ent_mspbackup 
				WHERE fk_ent_agent_UDID = '".$UDID."' AND is_active = 1 AND is_deleted = 0
			   ";
		$msp_agent_id = $this->rawQuery($sql, 'fetchOne', 1)['msp_agent_id'];

		//Error handling
		if ($msp_agent_id == null) 
		{
			$this->logger->error('MSP Backup: Device Delete Error - '.$UDID.' not available to delete');
			return false;
		}
/*
	 	//Remove Device API Call
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->endpoint.'/api/Users/'.$msp_agent_id);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    		'Authorization: Bearer '.$this->msp_access_token,
    	));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);

		//Error Handling
		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) 
		{
			$this->logger->error('MSP Backup: Device Delete Error - ' . $response);
			curl_close($ch);
			return false;
		} else { */
			//Delete from AWS S3
			$this->S3->matchDelete($this->config->mspbackup->imageBackupDestination, "MBS-".$msp_agent_id."/");
	/*		$sql = "
				INSERT INTO log_mspbackup
				(fk_ent_mspbackup_id, log, created_by, updated_by)
				SELECT pk_id, 'Device Deleted from MSP Backup via API', 'thrust', 'thrust'
				FROM ent_mspbackup
				WHERE fk_ent_agent_UDID = '".$UDID."' and is_active = 1 and is_deleted = 0;

				UPDATE ent_mspbackup
				SET is_active = 0, datetime_updated = now() 
				WHERE fk_ent_agent_UDID = '".$UDID."' AND is_active = 1 AND is_deleted = 0;
			   ";
			$result = $this->rawQuery($sql, 'execute', 0);
			curl_close($ch); */
			return true;
	//	}
	}
}