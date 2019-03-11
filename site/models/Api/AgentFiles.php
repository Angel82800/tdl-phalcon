<?php

namespace Thrust\Models\Api;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

/**
 * AgentFiles
 * This model processes and inserts the files 
 */
 
class AgentFiles extends Model
{

	private $db;
	private $UDID;

	public function initialize() 
	{
        $this->db = \Phalcon\Di::getDefault()->get('agentFiles-write');
	}
	
	/**
     * MAIN, process the files sent via API
     */
	public function processFiles($UDID, $array) 
	{
		//Setup variables
		$this->setupVariables($UDID);

		//Send a copy of the JSON to SQS
		$this->sendToSQS($UDID, json_decode($array));

		//Check JSON 
		$decodedJSON = $this->checkJSON($array);
		if ($decodedJSON) {
			//Process data
			$this->processJSON($decodedJSON);
			return true;
			//Error
		}  else {
			return array($this->returnCode, $this->returnMessage);
		}		
	}

	/**
     * FUNCTIONS
     */
	private function setupVariables($UDID)
	{
		//Set the UDID
        $this->UDID = $UDID;
	}

	private function sendToSQS($UDID, $array)
	{

		//Form the SQS JSON
		$SQS = json_encode(
			array(
				'udid'  => $UDID,
				'array' => $array
			)
		);

		//ToDo Setup SQS
	}

	private function checkJSON($array)
	{
 		$decodedJSON = json_decode($array);

 		if (json_last_error() == JSON_ERROR_NONE) {
			return $decodedJSON;
 		} else {
 			$this->returnCode = 400;
 			$this->returnMessage = 'JSON failed validation check';
 			return false;
 		}
	}

	private function processJSON($decodedJSON)
	{
		foreach ($decodedJSON as $JSON) {
			$JSON = $this->sanitizeJSON($JSON);
			$fileInfo = $this->getFileInfo($JSON);

			//File does not exist
			if ($fileInfo === false) {
				$this->createNewEntry($fileInfo, $JSON);
			
			//File exists and is unchanged
			} elseif ($fileInfo['sha256'] == str_replace("'", "", $JSON->sha256)) {
				$this->updateFileInfo($fileInfo, $JSON);
		
			//File has changed
			} elseif ($fileInfo['sha256'] != str_replace("'", "", $JSON->sha256)) {
				$this->modifiedFile($fileInfo, $JSON);
			}
		}
 			$this->returnCode = 200;
 			$this->returnMessage = 'OK';
 			return;
	}

	private function sanitizeJSON($JSON)
	{
		foreach ($JSON as $key => $value) {
			$JSON->$key = $this->db->escapeString($value);
		}
		return $JSON;
	}

	private function getFileInfo($JSON)
	{
		$sql = "
			SELECT sha256, pk_guid
			FROM ent_agent_files
			WHERE fk_ent_agent_udid = '$this->UDID'
			AND full_path = $JSON->fullPath
		";
		return $this->db->fetchOne($sql);
	}

	private function updateFileInfo($fileInfo, $JSON)
	{
		$sql = "
			UPDATE ent_agent_files 
			SET 
  				datetime_updated = now(),
  				updated_by = 'thrust'
  			WHERE 
  				pk_guid = '".$fileInfo['pk_guid']."'
  			";
		$this->db->execute($sql);
	}

	private function createNewEntry($fileInfo, $JSON)
	{
		$sql = "
			INSERT INTO ent_agent_files (
				pk_guid,
				fk_ent_agent_UDID, 
				full_path, 
				file_name, 
				extension, 
				md5, 
				sha1, 
				sha256, 
				size, 
				created, 
				modified,
				accessed, 
				created_by, 
				updated_by
      		) VALUES (
      			uuid(),
	      		'$this->UDID',
	      		$JSON->fullPath, 
	      		$JSON->fileName, 
	      		$JSON->extension, 
	      		$JSON->md5, 
	      		$JSON->sha1, 
	      		$JSON->sha256, 
	      		$JSON->size, 
	      		$JSON->created, 
	      		$JSON->modified, 
	      		$JSON->accessed, 
	      		'thrust',
	      		'thrust'
  			)
  		";
		$this->db->execute($sql);
	}

	private function modifiedFile($fileInfo, $JSON)
	{
		$sql = "
			INSERT INTO ent_agent_file_hash_history 
			SELECT uuid(), pk_guid, md5, sha1, sha256, size, created, modified, accessed, now(), 'thrust', now(), 'thrust', 1, 0
			FROM ent_agent_files
  			WHERE 
  				pk_guid = '".$fileInfo['pk_guid']."';

  			UPDATE ent_agent_files
  			SET 
		      	md5 = $JSON->md5, 
	      		sha1 = $JSON->sha1, 
	      		sha256 = $JSON->sha256, 
	      		size = $JSON->size, 
	      		created = $JSON->created, 
	      		modified = $JSON->modified, 
	      		accessed = $JSON->accessed,
	      		datetime_updated = now()
	      	WHERE pk_guid = '".$fileInfo['pk_guid']."';
  			";
		$this->db->execute($sql);
	}

}