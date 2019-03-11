<?php

namespace Thrust\Models\Api;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

/**
 * JobModel
 * handle database operations required for API jobs
 */

class JobModel extends Model
{

    //How many positive hits on AV before we alert
    private $av_detection_threshold = 1; //More than one engine detected an issue

	protected $db;
    protected $ioc;
    protected $agentFiles;
	protected $config;

	public function initialize()
    {
		$this->db = \Phalcon\Di::getDefault()->get('oltp-read');
        $this->oltpWrite = \Phalcon\Di::getDefault()->get('oltp-write');
        $this->ioc = \Phalcon\Di::getDefault()->get('ioc-read');
        $this->agentFiles = \Phalcon\Di::getDefault()->get('agentFiles-write');
		$this->config = \Phalcon\Di::getDefault()->get('config');
    }

    /* Start Weekly Summary Stats */

    public function getUDIDsForOrg()
    {
        $query = '
            SELECT
                agent.UDID, user.pk_id AS userId, user.fk_ent_organization_id AS orgId, user.firstName, user.email, user.is_active, user.is_deleted
            FROM
                ent_agent agent
            LEFT JOIN
                ent_users user ON agent.fk_ent_users_id = user.pk_id
            LEFT JOIN
                ent_organization organization ON user.fk_ent_organization_id = organization.pk_id
            WHERE agent.is_active = 1 AND agent.is_deleted = 0 AND organization.is_active = 1 AND organization.is_deleted = 0
        ';
        $result = $this->db->fetchAll($query, \Phalcon\Db::FETCH_ASSOC);

        $organizations = [];
        foreach ($result as $data) {
            // only send email to active users
            if ($data['is_active'] != 1 || $data['is_deleted'] != 0) {
                continue;
            }

            if (! isset($organizations[$data['orgId']])) {
                $organizations[$data['orgId']] = [
                    'orgId'     => $data['orgId'],
                    'users'     => [],
                    'UDIDs'     => [],
                ];
            }

            $organizations[$data['orgId']]['UDIDs'][] = $data['UDID'];
            $organizations[$data['orgId']]['users'][$data['userId']] = [
                'userId'    => $data['userId'],
                'firstName' => $data['firstName'],
                'email'     => $data['email'],
            ];
        }

        return $organizations;
    }

    public function getUDIDsForUsers()
    {
        $query = '
            SELECT
                agent.UDID, user.pk_id AS userId, user.fk_ent_organization_id AS orgId, user.firstName, user.email, user.is_active, user.is_deleted
            FROM
                ent_agent agent
            LEFT JOIN
                ent_users user ON agent.fk_ent_users_id = user.pk_id
            LEFT JOIN
                ent_organization organization ON user.fk_ent_organization_id = organization.pk_id
            WHERE agent.is_active = 1 AND agent.is_deleted = 0 AND organization.is_active = 1 AND organization.is_deleted = 0
        ';
        $result = $this->db->fetchAll($query, \Phalcon\Db::FETCH_ASSOC);

        $users = [];
        foreach ($result as $data) {
            // only send email to active users
            if ($data['is_active'] != 1 || $data['is_deleted'] != 0) {
                continue;
            }

            if (! isset($users[$data['userId']])) {
                $users[$data['userId']] = [
                    'orgId'     => $data['orgId'],
                    'userId'    => $data['userId'],
                    'firstName' => $data['firstName'],
                    'email'     => $data['email'],
                    'UDIDs'     => [],
                ];
            }

            $users[$data['userId']]['UDIDs'][] = $data['UDID'];
        }

        return $users;
    }

    public function getConnectedDevices($udids)
    {
        // get used udid count
        $query = '
            SELECT
                COUNT(pk_id) AS connected_count
            FROM
                ent_agent
            WHERE
                UDID IN (\'' . implode('\',\'', $udids) . '\') AND pin_used = 1
        ';

        $result = $this->db->fetchOne($query, \Phalcon\Db::FETCH_ASSOC);

        return $result['connected_count'];
    }

    public function getPastWeekAlerts($udids)
    {
        // alerts and blocks

        $alert_types = [
            1 => 'block',
            2 => 'alert',
        ];

        $query = '
            SELECT
                COUNT(pk_id) AS alert_count,
                fk_attr_alert_action_id AS alert_type
            FROM
                ent_alert
            WHERE
                datetime_updated >= DATE_SUB(NOW(), INTERVAL 6 DAY) AND fk_ent_agent_UDID IN (\'' . implode('\',\'', $udids) . '\')
            GROUP BY
                fk_attr_alert_action_id
        ';

        $result = $this->db->fetchAll($query, \Phalcon\Db::FETCH_ASSOC);

        $user_alerts = [];
        foreach ($result as $alert) {
            $user_alerts[$alert_types[$alert['alert_type']]] = $alert['alert_count'];
        }

        return $user_alerts;
    }

    public function getPastAvgBlocks($udids)
    {
        // average past daily blocks

        $query = '
            SELECT
                COUNT(pk_id) AS block_count,
                DATEDIFF(MAX(datetime_created), MIN(datetime_created)) AS duration
            FROM
                ent_alert
            WHERE
                fk_ent_agent_UDID IN (\'' . implode('\',\'', $udids) . '\') AND fk_attr_alert_action_id = 1
        ';

        $result = $this->db->fetchOne($query, \Phalcon\Db::FETCH_ASSOC);

        $avg_blocks = $result['duration'] ? round($result['block_count'] / $result['duration']) : 0;

        return $avg_blocks;
    }

    public function getPastWeekBlocksBreakdown($udids)
    {
        $query = '
            SELECT
                COUNT(alert.pk_id) AS block_count,
                classification.priority
            FROM ent_alert alert
            LEFT JOIN attr_alert_classification classification ON alert.fk_attr_alert_classification_id = classification.pk_id
            WHERE alert.datetime_updated >= DATE_SUB(NOW(), INTERVAL 6 DAY) AND alert.fk_attr_alert_action_id = 1 AND alert.fk_ent_agent_UDID IN (\'' . implode('\',\'', $udids) . '\')
            GROUP BY classification.priority
        ';

        $result = $this->db->fetchAll($query, \Phalcon\Db::FETCH_ASSOC);

        return $result;
    }

    public function getPastWeekDataTotal($udids)
    {
        $query = '
                SELECT sum(bytes_sent) + sum(bytes_received) AS total
                FROM log_agent_connections
                WHERE datetime_connected >= DATE_SUB(NOW(), INTERVAL 6 DAY)
                AND fk_ent_agent_UDID IN (\'' . implode('\',\'', $udids) . '\')
        ';
        $pastdatatotal = $this->db->fetchOne($query, \Phalcon\Db::FETCH_ASSOC);
        $dataTotal = round($pastdatatotal['total']);

        return $dataTotal;
    }

    public function getPast2WeeksDataTotal($udids)
    {
        $query = '
            SELECT sum(bytes_sent) + sum(bytes_received) AS total
            FROM log_agent_connections
            WHERE datetime_connected <= DATE_SUB(NOW(), INTERVAL 6 DAY) AND datetime_connected >= DATE_SUB(NOW(), INTERVAL 13 DAY)
                AND fk_ent_agent_UDID IN (\'' . implode('\',\'', $udids) . '\')
        ';
        $pastdatatotal = $this->db->fetchOne($query, \Phalcon\Db::FETCH_ASSOC);
        $dataTotal = round($pastdatatotal['total']);

        return $dataTotal;
    }

    public function getPastWeekThreatIndicators()
    {
        // threat indicators added

        $query = '
            SELECT
                COUNT(pk_id) as indicator_count,
                SUM(positives) as positives,
                SUM(total) as total
            FROM VirusTotal
            WHERE datetime_created >= DATE_SUB(NOW(), INTERVAL 6 DAY)
        ';

        $result = $this->ioc->fetchOne($query, \Phalcon\Db::FETCH_ASSOC);

        return $result;
    }

    public function getPastWeekAlerts2($udids)
    {
        $threatsBlocked = $alerts = 0;

        $query = '
            SELECT attr_alert_action.action_taken, COUNT(*) AS total FROM ent_alert
                JOIN attr_alert_action ON ent_alert.fk_attr_alert_action_id = attr_alert_action.pk_id
                WHERE ent_alert.datetime_created >= NOW() - INTERVAL 6 DAY
                AND fk_ent_agent_UDID IN
                (\'' . implode('\',\'', $udids) . '\')
                GROUP BY attr_alert_action.action_taken;
        ';
        $pastalert_result = $this->db->fetchAll($query, \Phalcon\Db::FETCH_ASSOC);

        foreach ($pastalert_result as $past_alert) {
            if ($pastalert['action_taken'] === 'alerted') {
                $alerts = $pastalert['total'];
            } else if ($pastalert['action_taken']) {
                $threatsBlocked = $pastalert['total'];
            }
        }

        return [
        	'threatsBlocked'	=> $threatsBlocked,
        	'alerts'			=> $alerts,
        ];
    }

    public function getPastWeekOnlineTime($udids)
    {
        $query = '
           SELECT sum(datetime_disconnected - log_agent_connections.datetime_connected) AS total
               FROM log_agent_connections
               WHERE datetime_connected >= NOW() - INTERVAL 6 DAY
               AND fk_ent_agent_UDID IN (\'' . implode('\',\'', $udids) . '\')
        ';
        $pastonlinetime = $this->db->fetchOne($query, \Phalcon\Db::FETCH_ASSOC);

        $onlineTime = round($pastonlinetime['total'][0] / 60 / 60 * 100) / 100;

        return $onlineTime;
    }

    /* End Weekly Summary Stats */

    /* Start Notify First Time User Stats */

    public function getUsersWithDeviceString()
    {
        $query = '
            SELECT
                u.pk_id,
                u.GUID,
                u.email,
                GROUP_CONCAT(CONCAT(d.pin_used, ":", d.device_count)) AS device_string,
                (
                    SELECT datetime_created
                    FROM ent_agent
                    WHERE fk_ent_users_id = u.pk_id
                    ORDER BY datetime_created DESC
                    LIMIT 1
                ) AS device_created
            FROM ent_users u
            JOIN
                (
                    SELECT
                        fk_ent_users_id AS user_id,
                        COUNT(*) AS device_count,
                        pin_used
                    FROM ent_agent
                    GROUP BY fk_ent_users_id, pin_used
                ) d ON u.pk_id = d.user_id
            WHERE u.is_active = 1 AND u.is_deleted = 0
            GROUP BY u.pk_id
        ';
        $result = $this->db->fetchAll($query, \Phalcon\Db::FETCH_ASSOC);

        return $result;
    }

    public function getOrgswithNoDevices()
    {
        $query = '
            SELECT
                org.pk_id AS org_id,
                org_admin.pk_id AS admin_id,
                org_admin.GUID AS admin_GUID,
                COUNT(agent.pk_id) AS device_count,
                org.datetime_created
            FROM ent_organization org
            LEFT JOIN
                ent_users u ON org.pk_id = u.fk_ent_organization_id
            LEFT JOIN
                ent_agent agent ON u.pk_id = agent.fk_ent_users_id
            JOIN
                (
                    SELECT u2.pk_id, u2.fk_ent_organization_id, u2.GUID
                    FROM ent_users u2
                    LEFT JOIN attr_roles r ON u2.fk_attr_roles_id = r.pk_id
                    WHERE r.name = \'admin\' AND u2.is_active = 1 AND u2.is_deleted = 0
                ) org_admin ON org_admin.fk_ent_organization_id = org.pk_id
            WHERE org.is_active = 1 AND org.is_deleted = 0 AND org.stripe_customer_id != \'\'
            GROUP BY org.pk_id
        ';
        $result = $this->db->fetchAll($query, \Phalcon\Db::FETCH_ASSOC);

        return $result;
    }

    /* End Notify First Time User Stats */

    /* Start Data Queries for Sync */

    public function getUserDeviceCount()
    {
        $query = '
            SELECT
                user.*,
                COALESCE(COUNT(agent.pk_id), 0) AS device_count
            FROM ent_users user
            LEFT JOIN
                ent_organization org ON org.pk_id = user.fk_ent_organization_id
            LEFT JOIN
                ent_agent agent ON agent.fk_ent_users_id = user.pk_id
            WHERE org.is_active = 1 AND org.is_deleted = 0 AND user.is_active = 1 AND user.is_deleted = 0 AND agent.is_active = 1 AND agent.is_deleted = 0
            GROUP BY user.pk_id
        ';
        $result = $this->db->fetchAll($query, \Phalcon\Db::FETCH_ASSOC);

        return $result;
    }

    public function getOrgDeviceCount()
    {
        $query = '
            SELECT
                organization.*,
                SUM(CASE WHEN agent.pk_id THEN 1 ELSE 0 END) AS device_count
            FROM ent_organization organization
            LEFT JOIN ent_users user ON organization.pk_id = user.fk_ent_organization_id AND (user.is_active = 1 OR user.is_invited = 1) AND user.is_deleted = 0
            LEFT JOIN ent_agent agent ON agent.fk_ent_users_id = user.pk_id AND agent.is_active = 1 AND agent.is_deleted = 0
            WHERE organization.is_active = 1 AND organization.is_deleted = 0 AND organization.stripe_customer_id IS NOT NULL
            GROUP BY organization.pk_id
        ';
        $result = $this->db->fetchAll($query, \Phalcon\Db::FETCH_ASSOC);

        return $result;
    }

    /* End Data Queries for Sync */

    /* Sales Report Queries */

    public function getNewSalesLeads()
    {
        $result = [];

        $leads_query = 'SELECT * FROM ent_leads WHERE datetime_created > NOW() - INTERVAL 5 MINUTE';
        $result['leads'] = $this->db->fetchAll($leads_query, \Phalcon\Db::FETCH_ASSOC);

        $users_query = 'SELECT * FROM ent_users WHERE datetime_created > NOW() - INTERVAL 5 MINUTE';
        $result['users'] = $this->db->fetchAll($users_query, \Phalcon\Db::FETCH_ASSOC);

        return $result;
    }

    /* End Sales Report Queries */

    /* Alert Review Queries */
    public function getNewReviewAlerts()
    {
        $sql = "
            SELECT
                ent_users.email as email,
                ent_agent.user_device_name as user_device_name,
                CONVERT_TZ(ent_alert.datetime_updated,'GMT','EST') as datetime_updated,
                ent_alert.short_alert_summary as short_alert_summary,
                ent_alert.raw as raw
            FROM ent_incident
                INNER JOIN ent_alert ON ent_incident.fk_ent_alert_id = ent_alert.pk_id
                INNER JOIN ent_agent ON ent_alert.fk_ent_agent_UDID = ent_agent.UDID
                INNER JOIN ent_users ON ent_agent.fk_ent_users_id = ent_users.pk_id
            WHERE 1=1
                AND fk_attr_incident_state_id = 1
                AND alert_sent = 0
        ";

        $result = $this->db->fetchAll($sql, \Phalcon\Db::FETCH_ASSOC);
        return $result;
    }

    /* Clear Alert Review */
    public function setClearAlerts()
    {
        $sql = "
            UPDATE ent_incident
            SET
                alert_sent = 1,
                datetime_updated = now(),
                updated_by = 'thrust'
            WHERE 1=1
                AND fk_attr_incident_state_id = 1
                AND alert_sent = 0
        ";
        $result = $this->db->execute($sql, \Phalcon\Db::FETCH_ASSOC);
    }

    /* Get the new suspicious file hashes - limit to 1000 at a time per OpSwat max */
    public function getNewSuspiciousFileHashes()
    {
        $sql = "
            SELECT DISTINCT(sha256), md5, sha1
            FROM map_agent_files_multiscan
            INNER JOIN ent_agent_files on ent_agent_files.pk_guid = map_agent_files_multiscan.fk_ent_agent_files_guid
            WHERE
                fk_ent_multiscan_guid is null and
                map_agent_files_multiscan.is_active = 1 and
                map_agent_files_multiscan.is_deleted = 0 and
                ent_agent_files.is_active = 1 and
                ent_agent_files.is_deleted = 0
            LIMIT 0,1000;
        ";
        $result = $this->agentFiles->fetchAll($sql, \Phalcon\Db::FETCH_ASSOC);
        return $result;
    }

    /* Persist the OpSwat scan results */
    public function persistOpswatResults($OpSwatResult)
    {
        $sql = "
            INSERT INTO ent_multiscan
            (pk_guid, fk_attr_multiscan_source_guid, sha256, scan_id, total_detected, created_by, updated_by)
            VALUES (
                UUID(),
                'ceae7db3-deb6-11e7-9bea-0a238d54a6ea',
                '".strtolower($OpSwatResult->hash)."',
                '".strtolower($OpSwatResult->data_id)."',
                '$OpSwatResult->total_detected_avs',
                'thrust',
                'thrust'
            );

            INSERT INTO map_multiscan_results
                    (
                        pk_guid,
                        fk_ent_multiscan_guid,
                        product_name,
                        scan_time,
                        definition_time,
                        threat_found,
                        threat_name,
                        created_by,
                        updated_by
                    )
                    SELECT * FROM
                    (
            ";

            foreach($OpSwatResult->scan_details as $key => $value) {
                //Format the columns for nulls
                $threat_found = ($value->scan_result_i == 0 ? 0 : 1);
                $threat_name = ($value->threat_found == '' ? 'NULL' : "'".$value->threat_found."'");

                $sql = $sql."
                    SELECT
                        UUID() as pk_guid,
                        pk_guid as fk_ent_multiscan_guid,
                        '$key' as product_name,
                        '$value->scan_time' as scan_time,
                        '$value->def_time' as definition_time,
                        '$threat_found' as threat_found,
                        $threat_name as threat_name,
                        'thrust' as created_by,
                        'thrust' as updated_by
                    FROM ent_multiscan
                    WHERE scan_id = '".strtolower($OpSwatResult->data_id)."'
                    UNION";
            }

            //Replace the last comma with the correct syntax
            $sql = substr($sql, 0, -5).")a;";

            //Map the new scans to existing files
            $sql = $sql."
                UPDATE map_agent_files_multiscan
                INNER JOIN ent_agent_files on ent_agent_files.pk_guid = map_agent_files_multiscan.fk_ent_agent_files_guid
                INNER JOIN ent_multiscan on ent_agent_files.sha256 = ent_multiscan.sha256
                SET map_agent_files_multiscan.fk_ent_multiscan_guid = ent_multiscan.pk_guid
                WHERE
                    map_agent_files_multiscan.fk_ent_multiscan_guid IS NULL and
                    map_agent_files_multiscan.is_active = 1 and
                    map_agent_files_multiscan.is_deleted = 0 and
                    ent_multiscan.is_active = 1 and
                    ent_multiscan.is_deleted = 0 and
                    ent_agent_files.is_active = 1 and
                    ent_agent_files.is_deleted = 0;
            ";

            $this->agentFiles->execute($sql, \Phalcon\Db::FETCH_ASSOC);
    }

    /* Map the scan results to the suspicious hashes */
    public function createFileScanAlerts()
    {
        //Fetch the potential threats to create an alert for
        $sql = "
            SELECT
                map_agent_files_multiscan.pk_guid as map_agent_files_multiscan_guid,
                fk_ent_agent_udid,
                full_path,
                total_detected
            FROM map_agent_files_multiscan
            INNER JOIN ent_agent_files on ent_agent_files.pk_guid = map_agent_files_multiscan.fk_ent_agent_files_guid
            INNER JOIN ent_multiscan on ent_multiscan.pk_guid = map_agent_files_multiscan.fk_ent_multiscan_guid
            WHERE
                ent_multiscan.total_detected > $this->av_detection_threshold and
                map_agent_files_multiscan.fk_ent_alert_id is null and
                map_agent_files_multiscan.is_active = 1 and
                map_agent_files_multiscan.is_deleted = 0 and
                ent_multiscan.is_active = 1 and
                ent_multiscan.is_deleted = 0 and
                ent_agent_files.is_active = 1 and
                ent_agent_files.is_deleted = 0;
        ";
        $result = $this->agentFiles->fetchAll($sql, \Phalcon\Db::FETCH_ASSOC);

        //Create the alerts - grab the threat and scan names for the alert text
        foreach ($result as $alert) {
            $sql = "
                SELECT product_name, threat_name
                FROM map_multiscan_results
                INNER JOIN ent_multiscan on ent_multiscan.pk_guid = map_multiscan_results.fk_ent_multiscan_guid
                INNER JOIN map_agent_files_multiscan on map_agent_files_multiscan.fk_ent_multiscan_guid = ent_multiscan.pk_guid
                WHERE map_agent_files_multiscan.pk_guid = '".$alert['map_agent_files_multiscan_guid']."' and threat_found = 1
                ORDER BY definition_time desc
                LIMIT 5;
            ";
            $scanResult = $this->agentFiles->fetchAll($sql, \Phalcon\Db::FETCH_ASSOC);

            //Setup $raw for insert
            $raw = array();
            $raw['fullPath'] = $alert['full_path'];
            $raw['map_agent_files_multiscan_guid'] = $alert['map_agent_files_multiscan_guid'];
            $raw['totalDetectedAvs'] = $alert['total_detected'];
            $raw['top5results'] = $scanResult;
            $raw = addslashes(json_encode($raw));

            //Insert alert into ent_alert
            $sql = "
                INSERT INTO ent_alert (
                    fk_ent_agent_udid,
                    fk_attr_alert_classification_id,
                    fk_attr_alert_action_id,
                    fk_attr_alert_type_id,
                    timestamp,
                    raw,
                    short_alert_summary,
                    created_by,
                    updated_by
                )
                SELECT
                    '".$alert['fk_ent_agent_udid']."',
                    9,
                    2,
                    2,
                    now(),
                    '$raw',
                    'A potentially malicious file was detected',
                    'thrust',
                    'thrust'
            ";
            $this->oltpWrite->execute($sql, \Phalcon\Db::FETCH_ASSOC);

            //Get the alert ID
            $sql = "
                SELECT pk_id
                FROM ent_alert
                WHERE raw = '$raw'
            ";
            $alertId = $this->db->fetchOne($sql, \Phalcon\Db::FETCH_ASSOC)['pk_id'];

            //Insert the alert id into map_agent_files_multiscan
            $sql = "
                UPDATE map_agent_files_multiscan
                SET fk_ent_alert_id = $alertId
                WHERE pk_guid = '".$alert['map_agent_files_multiscan_guid']."'
            ";
            $this->agentFiles->execute($sql, \Phalcon\Db::FETCH_ASSOC);
        }
    }

    /**
     * Get active organizations along with admin info
     * @return array
     */
    public function getActiveOrgsWithAdmin()
    {
        $query = '
            SELECT
                org.pk_id AS org_id,
                org.stripe_customer_id,
                org_admin.pk_id AS admin_id,
                org_admin.email AS admin_email,
                org_admin.firstName AS admin_first_name,
                org.datetime_created AS org_created
            FROM ent_organization org
            JOIN
                (
                    SELECT u2.pk_id, u2.fk_ent_organization_id, u2.email, u2.firstName
                    FROM ent_users u2
                    LEFT JOIN attr_roles r ON u2.fk_attr_roles_id = r.pk_id
                    WHERE r.name = \'admin\' AND u2.is_active = 1 AND u2.is_deleted = 0
                ) org_admin ON org_admin.fk_ent_organization_id = org.pk_id
            WHERE org.is_active = 1 AND org.is_deleted = 0 AND org.stripe_customer_id != \'\' AND org.pk_id = 186
            GROUP BY org.pk_id
        ';
        $result = $this->db->fetchAll($query, \Phalcon\Db::FETCH_ASSOC);

        return $result;
    }

}
