<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Resultset;

use Thrust\Models\EntThreatLevel;

class DashboardStatistics extends ModelBase
{
  protected $db;
  protected $cache;

  public function initialize()
  {
      $this->db = \Phalcon\Di::getDefault()->get('oltp-read');
      $this->cache = \Phalcon\Di::getDefault()->get('modelsCache');
  }

  /**
   * get device count per status for user
   */
  public function userDevices($userId, $method = 'fetchOne')
  {
    $sql = '
      SELECT
        COUNT(*) AS total_count,
        SUM(CASE WHEN pin_used = 1 THEN 1 ELSE 0 END) AS used_count,
        SUM(CASE WHEN pin_used = 0 THEN 1 ELSE 0 END) AS unused_count
      FROM ent_agent
      WHERE is_active = 1 AND is_deleted = 0 AND fk_ent_users_id = ' . $userId
    ;

    $result = $this->rawQuery($sql, $method, 0);

    return $result;
  }

  /**
   * Fetch device status
   * @param  integer $queryId - entity id to fetch devices for (user / org)
   * @param  array   $options - query options to build where statement
   * @param  string  $method  - action to run on the query
   * @return array            - device list or action result
   */
	public function deviceStatus($queryId, $options = [], $method = 'fetchAll')
	{
		// connected devices

    $filters = [];

    // agent.pin_used
    if (isset($options['pin_used']) && $options['pin_used'] === false) {
      // do not check for pin_used status
    } else {
      $filters[] = 'agent.pin_used = ' . (isset($options['pin_used']) ? $options['pin_used'] : 1);
    }

    // agent.is_active
    $filters[] = 'agent.is_active = ' . (isset($options['is_active']) ? $options['is_active'] : 1);

    // agent.is_deleted
    $filters[] = 'agent.is_deleted = ' . (isset($options['is_deleted']) ? $options['is_deleted'] : 0);

    // is queryId for organization or user
    if (isset($options['type']) && $options['type'] == 'organization') {
      // for organization
      $filters[] = 'user.fk_ent_organization_id = ' . $queryId;
    } else {
      // for user
      $filters[] = 'user.GUID = "' . $queryId . '"';
    }

    $filters[] = 'user.is_active = 1';

    $filters[] = 'user.is_deleted = 0';

    $where = implode(' AND ', $filters);

		$sql = '
			SELECT
				agent.pk_id AS device_id,
				agent.user_device_name,
				agent.UDID,
        agent.pin_used,
        user.GUID as user_id,
        user.email as user_email,
				COALESCE(device_type.deviceTypeName, "desktop") AS device_type,
		    connections.datetime_connected,
		    connections.datetime_disconnected
			FROM ent_agent agent
      LEFT JOIN ent_users user ON agent.fk_ent_users_id = user.pk_id
			LEFT JOIN attr_device_type device_type ON agent.fk_attr_device_type_id = device_type.pk_id
			LEFT JOIN (SELECT MAX(datetime_connected) AS last_connect_time, fk_ent_agent_UDID
				FROM log_agent_connections
				GROUP BY fk_ent_agent_UDID
				) last_connection ON last_connection.fk_ent_agent_UDID = agent.UDID
			LEFT JOIN log_agent_connections connections ON connections.datetime_connected = last_connection.last_connect_time AND connections.fk_ent_agent_UDID = agent.UDID
			WHERE ' . $where . '
			GROUP BY agent.pk_id
			ORDER BY connections.datetime_connected IS NULL, connections.datetime_disconnected IS NOT NULL, connections.datetime_disconnected DESC
		';

    // do not cache device status
		$result = $this->rawQuery($sql, $method, 0);

		return $result;
	}

  /**
   * Fetch device summary for dashboard charts
   * @param  integer $queryId - entity id to fetch devices for (user / org)
   * @param  array   $options - query options to build where statement
   * @param  string  $method  - action to run on the query
   * @return array            - device list or action result
   */
  public function deviceSummary($queryId, $options = [], $method = 'fetchAll')
  {
    $filters = [];

    // agent.pin_used
    if (isset($options['pin_used']) && $options['pin_used'] === false) {
      // do not check for pin_used status
    } else {
      $filters[] = 'agent.pin_used = ' . (isset($options['pin_used']) ? $options['pin_used'] : 1);
    }

    // agent.is_active
    $filters[] = 'agent.is_active = ' . (isset($options['is_active']) ? $options['is_active'] : 1);

    // agent.is_deleted
    $filters[] = 'agent.is_deleted = ' . (isset($options['is_deleted']) ? $options['is_deleted'] : 0);

    // is queryId for organization or user
    if (isset($options['type']) && $options['type'] == 'organization') {
      // for organization
      $filters[] = 'user.fk_ent_organization_id = ' . $queryId;
    } else {
      // for user
      $filters[] = 'user.GUID = "' . $queryId . '"';
    }

    $filters[] = 'user.is_active = 1';

    $filters[] = 'user.is_deleted = 0';

    $where = implode(' AND ', $filters);

    $sql = '
     SELECT
        agent.pk_id AS device_id,
        agent.user_device_name,
        agent.UDID,
        agent.pin_used,
        user.GUID as user_id,
        user.email as user_email,
        COALESCE(device_type.deviceTypeName, "desktop") AS device_type,
        data_transfer.protected_data AS protected_data,
        connections.datetime_connected,
        connections.datetime_disconnected
      FROM ent_agent agent
      LEFT JOIN ent_users user ON agent.fk_ent_users_id = user.pk_id
      LEFT JOIN attr_device_type device_type ON agent.fk_attr_device_type_id = device_type.pk_id
      LEFT JOIN (SELECT MAX(datetime_connected) AS last_connect_time, fk_ent_agent_UDID
        FROM log_agent_connections
        GROUP BY fk_ent_agent_UDID
        ) last_connection ON last_connection.fk_ent_agent_UDID = agent.UDID
      LEFT JOIN log_agent_connections connections ON connections.datetime_connected = last_connection.last_connect_time AND connections.fk_ent_agent_UDID = agent.UDID
      LEFT JOIN (SELECT SUM(bytes_received) + SUM(bytes_sent) AS protected_data, fk_ent_agent_UDID
        FROM log_agent_connections
        GROUP BY fk_ent_agent_UDID
        ) data_transfer ON data_transfer.fk_ent_agent_UDID = agent.UDID
      WHERE ' . $where . '
      GROUP BY agent.pk_id
      ORDER BY connections.datetime_connected IS NULL, connections.datetime_disconnected IS NOT NULL, connections.datetime_disconnected DESC, protected_data DESC
    ';

    $result = $this->rawQuery($sql, $method, 60);

    return $result;
  }

	// get user's device count
  public function deviceCount($queryId, $type = 'user')
  {
    $where = $type == 'user' ? 'agent.fk_ent_users_id = ' . $queryId : 'user.fk_ent_organization_id = ' . $queryId;

    $sql = '
      SELECT
        COUNT(*) AS device_count,
        user.GUID AS user_id,
        CONCAT(user.firstName, " ", user.lastName) AS user_name,
        user.email AS user_email
      FROM ent_agent agent
      RIGHT JOIN ent_users user ON agent.fk_ent_users_id = user.pk_id
      WHERE agent.is_active = 1 AND agent.is_deleted = 0 AND ' . $where . '
      GROUP BY agent.fk_ent_users_id
    ';

    $result = $this->rawQuery($sql, $type == 'user' ? 'fetchOne' : 'fetchAll', 60);

    return $type == 'user' ? $result['device_count'] : $result;
  }

  // get organization's pending invitations
  public function pendingInvitations($orgId)
  {
    $sql = '
      SELECT
        user.GUID AS user_id,
        user.email AS user_email,
        COUNT(agent.fk_ent_users_id) AS device_count
      FROM ent_users user
      LEFT JOIN ent_agent agent ON agent.fk_ent_users_id = user.pk_id
      WHERE user.is_invited = 1 AND user.is_active = 0 AND user.is_deleted = 0 AND agent.is_active = 1 AND agent.is_deleted = 0 AND user.fk_ent_organization_id = ' . $orgId . '
      GROUP BY user.pk_id'
    ;

    $result = $this->rawQuery($sql, 'fetchAll', 60);

    return $result;
  }

  // get user's pending devices
  // public function pendingDevices($userId)
  // {
  //   $sql = '
  //     SELECT
  //       user.max_devices - COUNT(agent.pk_id) AS pending_count
  //     FROM ent_users user
  //     LEFT JOIN ent_agent agent ON user.pk_id = agent.fk_ent_users_id
  //     WHERE user.is_active = 1 AND user.is_deleted = 0 AND user.pk_id = ' . $userId
  //   ;

  //   $result = $this->rawQuery($sql, 'fetchOne', 60);

  //   return $result;
  // }

  public function getOrgDevicesPerUser($orgId, $method = 'fetchAll', $only_pending = false)
  {
    $where = '
      user.fk_ent_organization_id = ' . $orgId . ' AND
      user.is_deleted = 0 AND
      (
        user.is_active = 1 OR user.is_invited = 1
      )
    ';

    if ($only_pending) {
      $where .= ' AND agent.pin_used = 0';
    }

    $sql = '
      SELECT
        user.GUID AS user_id,
        user.fk_ent_organization_id AS org_id,
        user.firstName,
        user.lastName,
        CONCAT(user.firstName, " ", user.lastName) AS user_name,
        user.email AS user_email,
        SUM(CASE WHEN agent.pk_id THEN 1 ELSE 0 END) AS device_count,
        SUM(CASE WHEN agent.pin_used = 1 THEN 1 ELSE 0 END) AS used_device_count,
        role.name AS user_role,
        user.is_active
      FROM ent_users user
      LEFT JOIN ent_agent agent ON user.pk_id = agent.fk_ent_users_id AND agent.is_active = 1 AND agent.is_deleted = 0
      LEFT JOIN attr_roles role ON user.fk_attr_roles_id = role.pk_id
      WHERE ' . $where . '
      GROUP BY user.pk_id
      ORDER BY user.is_active DESC, ISNULL(user.firstName) ASC
      '
    ;

    $result = $this->rawQuery($sql, $method, 0);

    return $result;
  }

	// check if first time user
	public function isFtu($userId)
	{
		// $sql = '
		// 	SELECT
		// 		COUNT(agent.pk_id) AS device_count
		// 	FROM ent_agent agent
		// 	WHERE agent.pin_used = 1 AND agent.is_deleted = 0 AND agent.fk_ent_users_id = ' . $userId . '
		// 	GROUP BY agent.fk_ent_users_id
		// ';

      $sql = '
        SELECT
          COUNT(agent.pk_id) AS device_count
        FROM ent_agent agent
        WHERE agent.fk_ent_users_id = ' . $userId . '
        GROUP BY agent.fk_ent_users_id
      ';

		$result = $this->rawQuery($sql, 'fetchOne', 60);

		return (! $result['device_count']);
	}

	public function blockedThreats($queryId, $options = [], $method = 'fetchAll')
	{
		// potential threats blocked

    $filters = [];

    // interval
    $filters[] = 'date >= DATE_SUB(DATE(NOW()), INTERVAL ' . (isset($options['interval']) ? $options['interval'] : 6) . ' DAY)';

    // summary.fk_attr_alert_action_id
    $filters[] = 'summary.fk_attr_alert_action_id = ' . (isset($options['alert_action_id']) ? $options['alert_action_id'] : 1);

    // is queryId for organization or user
    if (isset($options['type']) && $options['type'] == 'organization') {
      // for organization
      $filters[] = 'user.fk_ent_organization_id = ' . $queryId;
    } else {
      // for user
      $filters[] = 'user.pk_id = ' . $queryId;
    }

    $filters[] = 'user.is_active = 1';

    $filters[] = 'user.is_deleted = 0';

    $where = implode(' AND ', $filters);

		$sql = '
			SELECT SUM(block_count) as block_count, blocked_date
      FROM
        (
          SELECT
            SUM(action_count) AS block_count,
            summary.date as blocked_date
          FROM summary_agent_alerts summary
          INNER JOIN ent_agent agent ON agent.UDID = summary.fk_ent_agent_UDID
          INNER JOIN ent_users user ON user.pk_id = agent.fk_ent_users_id
          WHERE ' . $where . '
          GROUP BY summary.date
          UNION
          SELECT *
          FROM (
            SELECT 0, DATE(ADDDATE(DATE_SUB(NOW(), INTERVAL 6 DAY), @rownum := @rownum + 1)) as block_count
            FROM ent_alert
            JOIN (SELECT @rownum := -1) r
            LIMIT 7) a
        ) b
      GROUP BY blocked_date
      ORDER BY blocked_date ASC
			';

		$result = $this->rawQuery($sql, 'fetchAll', 60);

		return $result;
	}

	public function threatIndicators()
	{
		// threat indicators added

		$sql = '
            SELECT SUM(indicator_count) as indicator_count, added_date
            FROM
				(
                SELECT
					COUNT(*) as indicator_count,
					DATE(datetime_created) as added_date
				FROM VirusTotal
				WHERE datetime_created >= DATE_SUB(NOW(), INTERVAL 6 DAY)
				GROUP BY DATE(datetime_created)
                UNION
				SELECT *
                FROM (
						SELECT 0, DATE(ADDDATE(DATE_SUB(NOW(), INTERVAL 6 DAY), @rownum := @rownum + 1)) as added_date
						FROM VirusTotal
						JOIN (SELECT @rownum := -1) r
                    	LIMIT 7
                      ) a
				) b
			GROUP BY added_date
			ORDER BY added_date ASC
		';

		$result = $this->rawQuery($sql, 'fetchAll', 60, 'ioc');

		return $result;
	}

	public function lifetimeUserData($userId)
    {
		$sql = '
			SELECT
				sum_bytes_received + sum_bytes_sent AS transfer_bytes
			FROM
				summary_agent_transfer transfer
			LEFT JOIN
				ent_agent agent ON agent.UDID = transfer.fk_ent_agent_UDID
			WHERE
				agent.fk_ent_users_id = ' . $userId;

		$result = $this->rawQuery($sql, 'fetchOne', 60);

		return $this->formatBytes($result['transfer_bytes'], 1);
    }

	public function lifetimeData()
    {
		$sql = 'SELECT SUM(bytes_received) + SUM(bytes_sent) AS transfer_bytes FROM log_agent_connections;';

		$result = $this->rawQuery($sql, 'fetchOne', 60);

		return $this->formatBytes($result['transfer_bytes'], 0);
    }

	public function totalBlockedThreats()
	{
		$sql = 'SELECT COUNT(*) AS blocked_threats FROM ent_alert WHERE fk_attr_alert_action_id = 1';

		$result = $this->rawQuery($sql, 'fetchOne', 60);

		return $result['blocked_threats'];
	}

	public function totalUserAlerts($userId, $interval = false)
	{
		$alert_types = [
			1 => 'block',
			2 => 'alert',
		];

		$additional_query = '';
		if ($interval !== false) {
			$additional_query = ' AND alert.datetime_created >= DATE_SUB(NOW(), INTERVAL ' . ($interval - 1) . ' DAY)';
		}

		$sql = '
			SELECT
				SUM(action_count) AS alert_count,
				alert.fk_attr_alert_action_id AS alert_type
			FROM
				summary_agent_alerts alert
			LEFT JOIN
				ent_agent agent ON agent.UDID = alert.fk_ent_agent_UDID
			WHERE
				agent.fk_ent_users_id = ' . $userId . $additional_query . '
			GROUP BY
				alert.fk_attr_alert_action_id
  		';

		$result = $this->rawQuery($sql, 'fetchAll', 60);

		$user_alerts = [];
		foreach ($result as $alert) {
			$user_alerts[$alert_types[$alert['alert_type']]] = $alert['alert_count'];
		}

		return $user_alerts;
	}

	public function maliciousfilesAll()
	{
		$sql = 'SELECT COUNT(*) AS malicious_files FROM ent_av_proxy';

		$result = $this->rawQuery($sql, 'fetchOne', 60);

		return $result['malicious_files'];
	}

  public function latestThreat()
	{
		$result = VirusTotal::findFirst([
			'conditions' => "positives > 0 AND round(positives / total * 100) >= 50",
			'order'      => "scan_date DESC",
			'cache'      => 30,
		])->toArray();

		if (! $result) {
			$result = VirusTotal::findFirst([
				'conditions' => "positives > 0",
				'order'      => "scan_date DESC",
				'cache'      => 30,
			])->toArray();
		}

		$detectedPercentage = round($result['positives'] / $result['total'] * 100);
		$nameArray = array();
		foreach ($result as $key => $value) {
			if (strpos($key, '_result') && $value != "") {
				array_push($nameArray, $value);
			}
		}

		$c = array_count_values($nameArray);
		$name = array_search(max($c), $c);

		$returnArray = array('name' => $name, 'percentage' => $detectedPercentage, 'dateDetected' => $result['scan_date']);

		return $returnArray;
	}

	public function threatState()
	{
    $threat_state = $this->cache->get('internet_threat_level');

    if ($threat_state === null) {
  		$threat_level = EntThreatLevel::findFirst([
  			'conditions' => 'is_active = 1',
  			'cache'      => false
  		]);

  		if ($threat_level) {
  			$threat_state = strtolower($threat_level->title);
  		} else {
        $threat_states = [
          'test'      => 'low',
          'green'     => 'low',
          'yellow'    => 'medium',
          'orange'    => 'high',
          'red'       => 'critical',
        ];

        $infocon_state = file_get_contents('https://isc.sans.edu/infocon.txt');
        $threat_state = $threat_states[$infocon_state];
		  }

      $this->cache->save('internet_threat_level', $threat_state, 3600);
    }

    return $threat_state;
	}

	public function indicatorCount ()
	{
		$result = SummaryStatistics::findFirst([
			'order'      => "datetime_updated DESC",
			'cache'      => 30,
		]);
		return $result->indicatorCount;
	}

	//--- activity page

	// hours protected
  public function getPastMonthOnlineTime($userId)
  {
    $query = '
       SELECT sum(connection.datetime_disconnected - connection.datetime_connected) AS total
           FROM log_agent_connections connection
           LEFT JOIN ent_agent agent ON connection.fk_ent_agent_UDID = agent.UDID
           WHERE connection.datetime_connected >= NOW() - INTERVAL 30 DAY
           AND agent.fk_ent_users_id = ' . $userId
    ;
  	$pastonlinetime = $this->rawQuery($query, 'fetchOne', 60);

    $onlineTime = round($pastonlinetime['total'] / 60 / 60 / 60);

    return $onlineTime;
  }

  public function connectedDevices($userId)
  {
  	$query = '
  		SELECT
  			COUNT(DISTINCT(agent.UDID)) AS connected_cnt
  		FROM ent_agent agent
  		LEFT JOIN (SELECT MAX(datetime_connected) AS last_connect_time, fk_ent_agent_UDID
  			FROM log_agent_connections
  			GROUP BY fk_ent_agent_UDID
  			) last_connection ON last_connection.fk_ent_agent_UDID = agent.UDID
  		LEFT JOIN log_agent_connections connections ON connections.datetime_connected = last_connection.last_connect_time AND connections.fk_ent_agent_UDID = agent.UDID
  		WHERE agent.pin_used = 1 AND agent.is_active = 1 AND agent.is_deleted = 0 AND connections.datetime_connected is not null AND connections.datetime_disconnected is null AND agent.fk_ent_users_id = ' . $userId . '
  		GROUP BY agent.fk_ent_users_id
    ';

    $result = $this->rawQuery($query, 'fetchOne', 60);

    return $result['connected_cnt'];
  }

  public function activityList($userId, $filters = [])
  {
  	$additional_query = '';
  	if (isset($filters['interval'])) {
  		$additional_query .= ' AND alert.datetime_created >= NOW() - INTERVAL ' . $filters['interval'] . ' DAY';
  	}

  	$sql = '
  		SELECT
  			alert.datetime_created,
  			alert_class.priority,
  			concat(ent_ips.dstCityName, ", ", dstCountryCode2) AS location,
  			alert.short_alert_summary,
  			alert_class.description,
  			alert_action.action_taken AS result
  		FROM
  			ent_alert alert
  		LEFT JOIN
  			attr_alert_classification alert_class ON alert.fk_attr_alert_classification_id = alert_class.pk_id
  		LEFT JOIN
  			ent_ips ON ent_ips.fk_ent_alert_id = alert.pk_id
  		LEFT JOIN
  			attr_alert_action alert_action ON alert.fk_attr_alert_action_id = alert_action.pk_id
  		LEFT JOIN
  			ent_agent agent ON alert.fk_ent_agent_UDID = agent.UDID
  		WHERE agent.fk_ent_users_id = ' . $userId . $additional_query . '
  		ORDER BY alert.datetime_created DESC
  		LIMIT 0, 50
  	';

		$result = $this->rawQuery($sql, 'fetchAll', 60);

		return $result;
  }

  public function incidentList($filters = [])
  {
    $additional_query = '';

    // show resolved
    $additional_query .= isset($filters['show_resolved']) && $filters['show_resolved'] == 'true' ? '' : ' AND incident.assigned_to IS NULL';

    $sql = '
      SELECT
        incident.pk_id as incident_id,
        incident.datetime_created,
        user.email,
        alert.short_alert_summary,
        alert_action.action_taken
      FROM
        ent_incident incident
      LEFT JOIN
        ent_alert alert ON incident.fk_ent_alert_id = alert.pk_id
      LEFT JOIN
        ent_agent agent ON agent.UDID = alert.fk_ent_agent_UDID
      LEFT JOIN
        ent_users user ON agent.fk_ent_users_id = user.pk_id
      LEFT JOIN
        attr_alert_action alert_action ON alert.fk_attr_alert_action_id = alert_action.pk_id
      WHERE incident.fk_attr_incident_classification_id = 1' . $additional_query . '
      ORDER BY incident.datetime_created DESC
    ';

    $result = $this->rawQuery($sql, 'fetchAll', false);

    return $result;
  }

}
