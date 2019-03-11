<?php

namespace Thrust\Models\Api;

use Phalcon\Mvc\Model;

/**
 * Logstash
 * This model ingests log entries from Guardian and Shield
 */
class Logstash extends Model
{
	
	private $db;
	private $config;
	private $logger;
	
	public function initialize()
    {
		$this->db = \Phalcon\Di::getDefault()->get('oltp-write'); //TODO UPDATE TO MODEL 
		$this->config = \Phalcon\Di::getDefault()->get('config');
		$this->logger = \Phalcon\Di::getDefault()->get('logger');
		$this->cache = \Phalcon\Di::getDefault()->get('modelsCache');
    }
	
	public function openvpnConnect($UDID, $host, $datacenter, $CLIENT_IP, $UDID_IP, $timestamp) 
	{
		//Sanitize
		$UDID = $this->db->escapeString($UDID);
		$host = $this->db->escapeString($host);
		$datacenter = $this->db->escapeString($datacenter);
		$CLIENT_IP = $this->db->escapeString($CLIENT_IP);
		$UDID_IP = $this->db->escapeString($UDID_IP);
		$timestamp = $this->db->escapeString($timestamp);
		
		//Connect - check if open connection is already there
		//this is a fix where the machine reboots and we don't get a disconnect log
		$sql = "UPDATE log_agent_connections
				SET datetime_disconnected = FROM_UNIXTIME($timestamp)
				WHERE fk_ent_agent_UDID = $UDID AND datetime_disconnected IS NULL;
				";
		
		//Log the connection and make sure only a single entry exists
		$sql = $sql." INSERT INTO log_agent_connections (
				fk_ent_agent_UDID, 
				host, 
				fk_attr_datacenter_details_name,
				client_ip,
				UDID_IP, 
				datetime_connected, 
				created_by,
				updated_by
				)
				SELECT * FROM (SELECT $UDID, $host as col1, $datacenter as col2, $CLIENT_IP, $UDID_IP, FROM_UNIXTIME($timestamp), 'thrust' as col3, 'thrust' as col4) AS tmp 
				WHERE NOT EXISTS (
					SELECT fk_ent_agent_UDID, datetime_connected 
					FROM log_agent_connections 
					WHERE fk_ent_agent_UDID = $UDID 
					AND datetime_connected = FROM_UNIXTIME($timestamp)
				) LIMIT 1
			";
		if ($this->db->execute($sql)) {
			return true;
		} else {
			return array(500, "Error");
		}
	}
	
	public function openvpnDisconnect($UDID, $host, $UDID_IP, $bytes_received, $bytes_sent, $timestamp)
	{
		//Sanitize
		$UDID = $this->db->escapeString($UDID);
		$host = $this->db->escapeString($host);
		$UDID_IP = $this->db->escapeString($UDID_IP);
		$bytes_received = $this->db->escapeString($bytes_received);
		$bytes_sent = $this->db->escapeString($bytes_sent);
		$timestamp = $this->db->escapeString($timestamp);
		
		$sql = "UPDATE log_agent_connections 
				SET datetime_disconnected = FROM_UNIXTIME($timestamp), 
					bytes_received = $bytes_received,
					bytes_sent = $bytes_sent
				WHERE host = $host
					AND fk_ent_agent_UDID = $UDID 
					AND datetime_disconnected IS NULL 
					AND datetime_connected < FROM_UNIXTIME($timestamp)
		";
		if ($this->db->execute($sql)) {
			return true;
		} else {
			return array(500, "Error");
		}
	}

	public function openvpnTransfer($UDID, $host, $bytes_received, $bytes_sent)
	{
		//Sanitize
		$UDID = $this->db->escapeString($UDID);
		$host = $this->db->escapeString($host);
		$bytes_received = $this->db->escapeString($bytes_received);
		$bytes_sent = $this->db->escapeString($bytes_sent);
		
		$sql = "UPDATE log_agent_connections 
				SET bytes_received = $bytes_received,
					bytes_sent = $bytes_sent
				WHERE host = $host
					AND fk_ent_agent_UDID = $UDID 
					AND datetime_disconnected IS NULL 
		";
		if ($this->db->execute($sql)) {
			return true;
		} else {
			return array(500, "Error");
		}
	}

	public function storeSnortLog ($params)
	{
		//Params
		//raw, timestamp, host, gid, sid, alert, classification, protocol, srcIP, srcPort, dstIP, dstPort, action, dstCountryCode2, dstCountryCode3, dstCountryName, dstContinentCode, 
		//dstRegionName, dstCityName, dstPostalCode, dstLatitude, dstLongitude, dstDmaCode, dstAreaCode, dstTimezone, dstRealRegionName, srcCountryCode2, srcCountryCode3, srcCountryName
		//srcContinentCode, srcRegionName, srcCityName, srcPostalCode, srcLatitude, srcLongitude, srcDmaCode, srcAreaCode, srcTimezone, srcRealRegionName 

		//Sanitize
        $params = $this->sanitizeParams($params);
		
		//Cleanup Logstash
		$params['dstCountryCode2'] = ( $params['dstCountryCode2'] == "'%{[DstIpGeo][country_code2]}'" ? "NULL" : $params['dstCountryCode2'] );
		$params['dstCountryCode3'] = ( $params['dstCountryCode3'] == "'%{[DstIpGeo][country_code3]}'" ? "NULL" : $params['dstCountryCode3'] );
		$params['dstCountryName'] = ( $params['dstCountryName'] == "'%{[DstIpGeo][country_name]}'" ? "NULL" : $params['dstCountryName'] );
		$params['dstContinentCode'] = ( $params['dstContinentCode'] == "'%{[DstIpGeo][continent_code]}'" ? "NULL" : $params['dstContinentCode'] );
		$params['dstRegionName'] = ( $params['dstRegionName'] == "'%{[DstIpGeo][region_name]}'" ? "NULL" : $params['dstRegionName'] );
		$params['dstCityName'] = ( $params['dstCityName'] == "'%{[DstIpGeo][city_name]}'" ? "NULL" : $params['dstCityName'] );
		$params['dstPostalCode'] = ( $params['dstPostalCode'] == "'%{[DstIpGeo][postal_code]}'" ? "NULL" : $params['dstPostalCode'] );
		$params['dstLatitude'] = ( $params['dstLatitude'] == "'%{[DstIpGeo][latitude]}'" ? "NULL" : $params['dstLatitude'] );
		$params['dstLongitude'] = ( $params['dstLongitude'] == "'%{[DstIpGeo][longitude]}'" ? "NULL" : $params['dstLongitude'] );
		$params['dstDmaCode'] = ( $params['dstDmaCode'] == "'%{[DstIpGeo][dma_code]}'" ? "NULL" : $params['dstDmaCode'] );
		$params['dstAreaCode'] = ( $params['dstAreaCode'] == "'%{[DstIpGeo][area_code]}'" ? "NULL" : $params['dstAreaCode'] );
		$params['dstTimezone'] = ( $params['dstTimezone'] == "'%{[DstIpGeo][timezone]}'" ? "NULL" : $params['dstTimezone'] );
		$params['dstRealRegionName'] = ( $params['dstRealRegionName'] == "'%{[DstIpGeo][real_region_name]}'" ? "NULL" : $params['dstRealRegionName'] );
		$params['srcCountryCode2'] = ( $params['srcCountryCode2'] == "'%{[SrcIpGeo][country_code2]}'" ? "NULL" : $params['srcCountryCode2'] );
        $params['srcCountryCode3'] = ( $params['srcCountryCode3'] == "'%{[SrcIpGeo][country_code3]}'" ? "NULL" : $params['srcCountryCode3'] );
		$params['srcCountryName'] = ( $params['srcCountryName'] == "'%{[SrcIpGeo][country_name]}'" ? "NULL" : $params['srcCountryName'] );
        $params['srcContinentCode'] = ( $params['srcContinentCode'] == "'%{[SrcIpGeo][continent_code]}'" ? "NULL" : $params['srcContinentCode'] );
		$params['srcRegionName'] = ( $params['srcRegionName'] == "'%{[SrcIpGeo][region_name]}'" ? "NULL" : $params['srcRegionName'] );
		$params['srcCityName'] = ( $params['srcCityName'] == "'%{[SrcIpGeo][city_name]}'" ? "NULL" : $params['srcCityName'] );
		$params['srcPostalCode'] = ( $params['srcPostalCode'] == "'%{[SrcIpGeo][postal_code]}'" ? "NULL" : $params['srcPostalCode'] );
		$params['srcLatitude'] = ( $params['srcLatitude'] == "'%{[SrcIpGeo][latitude]}'" ? "NULL" : $params['srcLatitude'] );
 		$params['srcLongitude'] = ( $params['srcLongitude'] == "'%{[SrcIpGeo][longitude]}'" ? "NULL" : $params['srcLongitude'] );
		$params['srcDmaCode'] = ( $params['srcDmaCode'] == "'%{[SrcIpGeo][dma_code]}'" ? "NULL" : $params['srcDmaCode'] );
		$params['srcAreaCode'] = ( $params['srcAreaCode'] == "'%{[SrcIpGeo][area_code]}'" ? "NULL" : $params['srcAreaCode'] );
		$params['srcTimezone'] = ( $params['srcTimezone'] == "'%{[SrcIpGeo][timezone]}'" ? "NULL" : $params['srcTimezone'] ); 
		$params['srcRealRegionName'] = ( $params['srcRealRegionName'] == "'%{[SrcIpGeo][real_region_name]}'" ? "NULL" : $params['srcRealRegionName'] ); 

		//Lookup Classification
		$sql = "SELECT pk_id FROM attr_alert_classification WHERE description = ".$params['classification'];
		$classification_id = $this->db->fetchOne($sql)['pk_id'];
		
		//Check to see if logstash is inserting multiple times -- THIS NEEDS TO BE CLEANED UP AND IS A WORK AROUND
		$sql = "SELECT COUNT(pk_id) as COUNT FROM ent_alert WHERE raw = ".$params['raw'];
		if ($this->db->fetchOne($sql)['COUNT'] > 0) return array(200, "VERIFIED");

		//Get UDID
        $UDID = $this->lookupUDID($params['srcIP'], $params['host'], $params['timestamp']);

        //Failure to get UDID error_logging
        if ($UDID == '') {
        	$this->logger->error("[API] [Logstash] Failed to find UDID Request: " . json_encode($params));
        } 
		
		//Insert Alert
		$sql = "INSERT INTO ent_alert (
					fk_ent_agent_UDID, 
					fk_attr_alert_classification_id, 
					fk_attr_alert_action_id, 
					fk_attr_alert_type_id,
					hostname,
					timestamp,
					raw,
					short_alert_summary,
					created_by,
					updated_by
					) values (
						'$UDID', 
						$classification_id,
						".$params['action'].", 
						1,
						".$params['host'].", 
						convert(".$params['timestamp'].", datetime),
						".$params['raw'].", 
						".$params['alert'].",
						'thrust', 
						'thrust'
					)
				";
		if ($this->db->execute($sql) === false) return array(500, "Error");
		
		//Get Alert ID
		$sql = "SELECT pk_id FROM ent_alert WHERE raw = ".$params['raw'];
		$alert_id = $this->db->fetchOne($sql)['pk_id'];
		
		//Insert Snort Details - USING INSERT IGNORE FOR NOW AS LOGSTASH IS SENDING DUPLICATE ENTRIES
		$sql = "INSERT INTO ent_ips
					(fk_ent_alert_id,
					gid,
					sid,
					alert,
					classification,
					protocol,
					srcIP,
					srcPort,
					dstIP,
					dstPort,
					action,
					dstCountryCode2, 
					dstCountryCode3, 
					dstCountryName, 
					dstContinentCode, 
		            dstRegionName, 
					dstCityName, 
					dstPostalCode, 
					dstLatitude, 
					dstLongitude, 
					dstDmaCode, 
					dstAreaCode, 
					dstTimezone, 
					dstRealRegionName, 
					srcCountryCode2, 
					srcCountryCode3, 
					srcCountryName,
		            srcContinentCode, 
					srcRegionName, 
					srcCityName, 
					srcPostalCode, 
					srcLatitude, 
					srcLongitude, 
					srcDmaCode, 
					srcAreaCode, 
					srcTimezone, 
					srcRealRegionName, 
					created_by,
					updated_by
					) VALUES (
						$alert_id, 
						".$params['gid'].",
						".$params['sid'].",
						".$params['alert'].",
						".$params['classification'].",
						".$params['protocol'].",
						".$params['srcIP'].",
						".$params['srcPort'].",
						".$params['dstIP'].",
						".$params['dstPort'].",
						".$params['action'].",
						".$params['dstCountryCode2'].", 
						".$params['dstCountryCode3'].",
						".$params['dstCountryName'].", 
						".$params['dstContinentCode'].", 
						".$params['dstRegionName'].", 
						".$params['dstCityName'].", 
						".$params['dstPostalCode'].", 
						".$params['dstLatitude'].", 
						".$params['dstLongitude'].", 
						".$params['dstDmaCode'].", 
						".$params['dstAreaCode'].", 
						".$params['dstTimezone'].", 
						".$params['dstRealRegionName'].", 
						".$params['srcCountryCode2'].", 
						".$params['srcCountryCode3'].", 
						".$params['srcCountryName'].",
						".$params['srcContinentCode'].", 
						".$params['srcRegionName'].", 
						".$params['srcCityName'].", 
						".$params['srcPostalCode'].", 
						".$params['srcLatitude'].", 
						".$params['srcLongitude'].", 
						".$params['srcDmaCode'].", 
						".$params['srcAreaCode'].", 
						".$params['srcTimezone'].", 
						".$params['srcRealRegionName'].",
						'thrust', 
						'thrust'
					) 
				";
		if ($this->db->execute($sql)) {
			return true;
		} else {
			$this->logger->error("Unable to insert Alert.\n $sql");
			return array(500, "Error");
		}
	}

	public function storeAvProxyLog ($params)
	{
		//Sanitize
        $params = $this->sanitizeParams($params);

        //Get UDID
        $UDID = $this->lookupUDID($params['UDID_IP'], $params['host'], $params['timestamp']);
		
		//Insert Alert
		$sql = "INSERT INTO ent_alert (
					fk_ent_agent_UDID, 
					fk_attr_alert_classification_id, 
					fk_attr_alert_action_id, 
					fk_attr_alert_type_id,
					hostname,
					timestamp,
					raw,
					short_alert_summary,
					created_by,
					updated_by
					) values (
						'$UDID', 
						9,
						1,
						3,
						".$params['host'].", 
						convert(".$params['timestamp'].", datetime),
						".$params['raw'].", 
						'A potentially malicious download has been blocked',
						'thrust', 
						'thrust'
					)
				";
		$this->db->execute($sql);
		
		//Get Alert ID
		$sql = "SELECT pk_id FROM ent_alert WHERE raw = ".$params['raw'];
		$alert_id = $this->db->fetchOne($sql)['pk_id'];

		//Insert avProxy details
		$sql = "INSERT INTO ent_av_proxy
					(fk_ent_alert_id,
					srcIP,
					url,
					redirectUrl,
					virusName,
					created_by,
					updated_by
					) VALUES (
						$alert_id, 
						convert(".$params['timestamp'].", datetime),
						".$params['host'].",
						".$params['UDID_IP'].",
						".$params['url'].",
						".$params['redirectUrl'].",
						".$params['malware'].",
						'thrust', 
						'thrust'
					)";
		if ($this->db->execute($sql)) {
			return true;
		} else {
			return array(500, "Error");
		}
	}

	private function sanitizeParams($params)
	{
		foreach ($params as $key => $value) {
			$params[$key] = $this->db->escapeString($value);
		}
		return $params;
	}

	private function lookupUDID($IP, $host, $timestamp) {
		$sql = "SELECT fk_ent_agent_UDID 
				FROM log_agent_connections 
				WHERE host = ".$host." 
				AND UDID_IP = ".$IP." 
				AND datetime_connected <= convert(".$timestamp.", datetime) 
				AND (
					datetime_disconnected is null OR
					datetime_disconnected >= convert(".$timestamp.", datetime)
				)
				ORDER BY pk_id desc
				LIMIT 1";
		$UDID = $this->db->fetchOne($sql)['fk_ent_agent_UDID'];
		return $UDID;
	}

		public function storeBindLog ($params)
	{
		//Sanitize
        $params = $this->sanitizeParams($params);

        //Get UDID
        $UDID = $this->lookupUDID($params['UDID_IP'], $params['host'], $params['timestamp']);

        //Check for duplicates
        $last_bind_log = $this->cache->get('logstash.storeBindLog.last_bind_log.'.$params['host'].'.'.$params['UDID_IP']);
        $cache_value = md5($params['domain']);

        if ($last_bind_log != $cache_value) {
		
			//Insert Alert
			$sql = "INSERT INTO ent_alert (
						fk_ent_agent_UDID, 
						fk_attr_alert_classification_id, 
						fk_attr_alert_action_id, 
						fk_attr_alert_type_id,
						hostname,
						timestamp,
						raw,
						short_alert_summary,
						created_by,
						updated_by
						) values (
							'$UDID', 
							36,
							1,
							4,
							".$params['host'].", 
							convert(".$params['timestamp'].", datetime),
							".$params['raw'].", 
							CONCAT('Access to the domain ',".$params['domain'].",' has been blocked'),
							'thrust', 
							'thrust'
						)
					";
			if ($this->db->execute($sql) === false) return array(500, "Error");
			
			//Add memcache check to prevent duplicate entries
			$this->cache->save('logstash.storeBindLog.last_bind_log.'.$params['host'].'.'.$params['UDID_IP'], $cache_value, 10);

			//Get Alert ID
			$sql = "SELECT pk_id FROM ent_alert WHERE raw = ".$params['raw'];
			$alert_id = $this->db->fetchOne($sql)['pk_id'];

			//Format RPZ
			$raw_domain = str_replace("'","",$params['domain']);
			$RPZ = str_replace($raw_domain.'.',"",$params['RPZ']);

			//Insert bind details
			$sql = "INSERT INTO ent_bind
						(fk_ent_alert_id,
						srcIP,
						srcPort,
						domain,
						RPZ,
						created_by,
						updated_by
						) VALUES (
							$alert_id, 
							".$params['UDID_IP'].",
							".$params['client_port'].",
							".$params['domain'].",
							$RPZ,
							'thrust', 
							'thrust'
						)";

			if ($this->db->execute($sql)) {
				
				return true;
			} else {
				return array(500, "Error");
			}
		//Skip due to duplicate entry
		} else {
			return true;
		}
	}
}
