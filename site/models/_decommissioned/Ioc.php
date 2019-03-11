<?php

namespace Thrust\Models\Api;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

/**
 * Ioc
 * This model checks for client IOCs via an API call from the client.
 */
 
class Ioc extends Model
{
	
	public static function processClientScan($UDID, $array) 
	{
		$json = json_decode($array);
		$db = \Phalcon\Di::getDefault()->get('ioc-write'); //TODO UPDATE TO MODEL 
		foreach($json as $obj){
			//Sanitize input
			$md5 = $db->escapeString($obj->md5);
			$path = $db->escapeString($obj->path);
			$short_alert_summary = $db->escapeString("Malicious File Detected: ".$obj->path);
			$created = $db->escapeString($obj->created);
			$modified = $db->escapeString($obj->modified);
			$accessed = $db->escapeString($obj->accessed);	
			$UDID = $db->escapeString($UDID);	
			$raw = $db->escapeString(json_encode($obj));

			if(self::md5Found($md5)) {
				//Malicious md5
				if (self::checkNoAlertOpen($UDID, $md5)) {
					//No alert currently open			
					$sql="
						INSERT INTO ent_alert (
						  fk_ent_agent_UDID,
						  fk_attr_alert_classification_id,
						  fk_attr_alert_action_id,
						  fk_attr_alert_state_id, 
						  fk_attr_alert_type_id,
						  raw, 
						  short_alert_summary, 
						  created_by, 
						  updated_by
						) values (
						  $UDID,
						  9, 
						  3, 
						  1,
						  2,
						  $raw, 
						  $short_alert_summary, 
						  'thrust', 
						  'thrust'
						)";
					$db->execute($sql);
					$sql="SELECT pk_id FROM ent_alert WHERE fk_ent_agent_UDID = $UDID AND raw = $raw";
					$result = $db->fetchOne($sql);
					$fk_ent_alert_id = $result['pk_id'];
					$sql="
						INSERT INTO ent_ioc (
						  fk_ent_alert_id,
						  md5,
						  path,
						  file_created,
						  file_modified,
						  file_accessed,
						  created_by, 
						  updated_by
						) values (
						  $fk_ent_alert_id,
						  $md5,
						  $path,
						  $created,
						  $modified,
						  $accessed,
						  'thrust', 
						  'thrust'
						 )";
					$db->execute($sql);
				}
			}
		}
	}
	
	private static function md5Found($md5) 
	{
		$sql = "SELECT count(*) as count FROM VirusShare_MD5 WHERE MD5 = $md5";
		$db = \Phalcon\Di::getDefault()->get('ioc_write'); //TODO UPDATE TO MODEL 
		$result = $db->fetchOne($sql);
		if ($result['count'] > 0) {
			return true;
		} else {
			return false;
		}
	}
	
	private static function checkNoAlertOpen($UDID, $md5)
	{
		$sql = "
				SELECT count(*) as count 
				FROM ent_alert, ent_ioc 
				WHERE ent_alert.pk_id = ent_ioc.fk_ent_alert_id
				AND ent_alert.fk_ent_agent_UDID = $UDID
				AND ent_ioc.md5 = $md5
				AND fk_attr_alert_state_id != 3
		";
		$db = \Phalcon\Di::getDefault()->get('oltp-write'); //TODO UPDATE TO MODEL 
		$result = $db->fetchOne($sql);
		if ($result['count'] == 0) {
			return true;
		} else {
			return false;
		}
	}
}