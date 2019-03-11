<?php

namespace Thrust\Models\Api;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

/**
 * ApiAuth
 * This model validates external API requests
 */

class ApiAuth extends \Thrust\Models\ModelBase
{

   //Validate UDID
	public static function checkUDID($UDID)
	{
		$sql = "
			SELECT SUM(UDID) as udidCount
			FROM
			(
				SELECT COUNT(UDID) as UDID
				FROM ent_utm
				INNER JOIN ent_organization ON ent_organization.pk_id = ent_utm.fk_ent_organization_id
				WHERE
				ent_organization.is_active = 1 AND
			    ent_organization.is_deleted = 0 AND
			    ent_utm.is_active = 1 AND
			    ent_utm.is_deleted = 0 AND
			    ent_utm.UDID = '".$UDID."'
				UNION
				SELECT COUNT(UDID) as UDID
				FROM ent_agent
				INNER JOIN ent_users ON ent_users.pk_id = ent_agent.fk_ent_users_id
				INNER JOIN ent_organization ON ent_organization.pk_id = ent_users.fk_ent_organization_id
				WHERE
					ent_organization.is_active = 1 AND
			    ent_organization.is_deleted = 0 AND
					ent_users.is_active = 1 AND
			    ent_users.is_deleted = 0 AND
			    ent_agent.is_active = 1 AND
			    ent_agent.is_deleted = 0 AND
			    ent_agent.UDID = '".$UDID."'
			) a
			"
		;

    $result = \Thrust\Models\ModelBase::rawQuery($sql, 'fetchOne', 1);
    return ($result['udidCount'] > 0 ? true : false);
	}
}
