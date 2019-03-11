<?php

namespace Thrust\Models\Api;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;
use Thrust\Models\ModelBase;

class RapidRecovery extends ModelBase
{

	//Properties
	private $db;
	private $logger;
	private $cache;
    private $config;

	//Initialize
    public function initialize()
    {
    	//Populate properties
    	$this->cache = $this->di->get('modelsCache');
    	$this->logger = $this->di->getShared('logger');
        $this->config = \Phalcon\Di::getDefault()->get('config');

        //Setup the DB connections
        $this->db = \Phalcon\Di::getDefault()->get('oltp-read');

    }

    //Ensure that the UDID is the correct state to setup RapidRecovery
    public function verifyUdidForSetup($UDID)
    {
        $sql = "
            SELECT count(*) as count
            FROM ent_mspbackup
                INNER JOIN ent_agent ON ent_agent.UDID = ent_mspbackup.fk_ent_agent_UDID
                INNER JOIN ent_users ON ent_users.pk_id = ent_agent.fk_ent_users_id
                INNER JOIN ent_organization ON ent_organization.pk_id = ent_users.fk_ent_organization_id
            WHERE
                ent_mspbackup.fk_attr_mspbackup_state_id = 1 AND
                ent_mspbackup.is_active = 1 AND
                ent_mspbackup.is_deleted = 0 AND
                ent_users.is_active = 1 AND
                ent_users.is_deleted = 0 AND
                ent_organization.is_active = 1 AND
                ent_organization.is_deleted = 0 AND
                ent_mspbackup.fk_ent_agent_UDID = '$UDID';
        ";
        return ($this->rawQuery($sql, 'fetchOne', 0)['count'] = 1 ? true : false);
    }

    public function downloadInfo($UDID)
    {
        //Get the OS of the UDID
        $sql = "
            SELECT fk_attr_os_type_id as type
            FROM ent_agent
            WHERE UDID = '$UDID';
        ";
        $os_type_id = $this->rawQuery($sql, 'fetchOne', 0)['type'];

        //Set the proper extension
        switch ($os_type_id) {
            case 1:
                $extension = ".exe";
                break;
            case 2:
                $extension = ".dmg";
                break;
        }

        //Get the latest file from the directory
        $files = scandir($this->config->application->downloadDir."/mspBackup/");
        $file_names = array_filter($files, function ($haystack) use ($extension) {
            return(strpos($haystack, $extension));
        });
        $return_array = array();
        $return_array['installer'] = "https://".$_SERVER["HTTP_HOST"]."/rapidrecovery/download?file=".$this->findLatestByTime($file_names);
        if ($os_type_id = 1)
        {
            $return_array['script'] = "https://".$_SERVER["HTTP_HOST"]."/rapidrecovery/download?file=".$this->config->mspbackup->imageBackupWinConfigScript;
        } else if ($os_type_id = 2) {
            $return_array['script'] = "https://".$_SERVER["HTTP_HOST"]."/rapidrecovery/download?file=".$this->config->mspbackup->imageBackupMacConfigScript;
        }
        
        return $return_array;
    }

    public function getPassword($UDID)
    {
        //Fetch and decrypt the password for the UDID
        $sql = "
            SELECT SecureDecrypt(password) as password
            FROM ent_mspbackup
            WHERE fk_ent_agent_UDID = '$UDID'
            AND is_active = 1
            AND is_deleted = 0;
        ";
        return $this->rawQuery($sql, 'fetchOne', 1)['password'];
    }

    public function downloadInstaller($file)
    {
        //Prevent directory traversal attack
        if( ($file == '') || (strpos($file, '../') !== false) ) {
            return array(400, "Invalid Request");
        }
        //Check if exists
        $path = $this->config->application->downloadDir."/mspBackup/";
        if (! file_exists($path."/".$file)) { return array(400, "Invalid Request"); }

        //Start file download
        $file_path  = $path . $file;
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

    public function status($UDID, $state, $log)
    {
        //Make sure that there is a valid state
        $state = $this->db->escapeString($state);
        $log = $this->db->escapeString($log);
        $sql = "
            SELECT count(*) as count
            FROM attr_mspbackup_state
            WHERE pk_id = $state
            AND is_active = 1
            AND is_deleted = 0;
        ";
        if ($this->rawQuery($sql, 'fetchOne', 1)['count'] = 1)
        {
           //Update the state - not using is_active for decom race condition
            $sql = "
                UPDATE ent_mspbackup
                SET 
                    fk_attr_mspbackup_state_id = $state,
                    datetime_updated = now()
                WHERE fk_ent_agent_UDID = '$UDID'
                ORDER BY pk_id desc
                limit 1;
            "; 
            //Add logs if any
            if ($log != "''") 
            {
                $sql = $sql."
                    INSERT INTO log_mspbackup
                    (fk_ent_mspbackup_id, log, created_by, updated_by)
                    SELECT pk_id, $log, 'thrust', 'thrust'
                    FROM ent_mspbackup
                    WHERE fk_ent_agent_UDID = '$UDID'
                    ORDER BY pk_id desc
                    limit 1;
                ";
            } 
            return $this->rawQuery($sql, 'execute', 0);
        } else {
            return false;
        }

    }

    private function findLatestByTime($file_names)
    {
        $time = 0;
        foreach ($file_names as $key => $value) {
            $new_time = filemtime($this->config->application->downloadDir."/mspBackup/".$value);
            if ($new_time > $time) {
                $time = $new_time;
                $latest = $value;
            }
        }
        return $latest;
    }
}