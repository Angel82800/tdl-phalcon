<?php

namespace Thrust\Controllers;

use Thrust\Models\Api\RapidRecovery;

class RapidrecoveryController extends ApiBaseController
{

   /*
	* endpoint: /rapidrecovery/setup
	* method: GET
	* header: UDID
	*/
	public function setupAction()
    {
		if ($this->request->isGet()) {
			$RapidRecovery = new RapidRecovery();
			$result = $RapidRecovery->verifyUdidForSetup($this->UDID);
			if ($result) {
				//Assemble the JSON Response
				$response = array();
				$downloadInfo = $RapidRecovery->downloadInfo($this->UDID);
				$response['installer'] = $downloadInfo['installer'];
				$response['script'] = $downloadInfo['script'];
				$response['password'] = $RapidRecovery->getPassword($this->UDID);
				return $this->sendResponse(200, json_encode($response));
			} else {
				$this->sendResponse(400, json_encode(array("Result" =>"Invalid UDID")));
				return;
			}
		} else {
            $this->sendResponse(400, json_encode(array("Result" => "Bad Method")));
        }
    }

	/*
	* endpoint: /rapidrecovery/download
	* method: GET
	* header: UDID
	* param: file
	*/
	public function downloadAction()
    {
        if ($this->request->isGet()) {
			if (isset ($this->request->getQuery()['file'])) {
				$RapidRecovery = new RapidRecovery();
				$result = $RapidRecovery->downloadInstaller($this->request->getQuery()['file']);
				return $this->sendResponse($result[0], $result[1]);
			} else {
				return $this->sendResponse(400, json_encode(array("Result" => "Invalid Request")));
			}
		//Not GET
        } else {
            $this->sendResponse(400, json_encode(array("Result" => "Bad Method")));
			return;
        }
    }

   /*
	* endpoint: /rapidrecovery/status
	* method: POST
	* header: UDID
	* body (www-form-url-encoded):
	*	status pk_id from attr_mspbackup_state
	*	log: optional log statements to add
	*/
	public function statusAction()
    {
		if ($this->request->isPost()) {
			$params = $this->request->getPost();
			$RapidRecovery = new RapidRecovery();
			$log = (isset($params['log']) ? $log = $params['log'] : null);
			$result = $RapidRecovery->status($this->UDID, $params['state'], $log);
			if ($result) {
				return $this->sendResponse(200, json_encode(array("Result" => "Success")));
			} else {
				$this->sendResponse(400, json_encode(array("Result" => "Bad Method")));
				return;
			}
		} else {
            return $this->sendResponse(400, json_encode(array("Result" => "Bad Method")));
        }
    }
}
