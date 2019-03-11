<?php

namespace Thrust\Controllers;

use Thrust\Models\LogAgent;
use Thrust\Models\LogAgentCrash;
use Thrust\Models\Api\Agent;
use Thrust\Models\Api\AgentFiles;


class AgentController extends ApiBaseController
{

   /*
	* endpoint: /agent/ovpnFile
	* method: POST
	* header: UDID
	* v1 params: isMac
	* v2 params: none
	*/
	public function ovpnFileAction()
    {
		if ($this->request->isPost()) {
			$params = $this->request->getPost();
			$agent = new Agent();
			$result = $agent->verifyUDID($this->UDID);
			if ($result) {
				if (isset($params['isMac']))
				{
					$file = $agent->getOvpnFile($this->UDID, $params['isMac']);
				} else {
					$file = $agent->getOvpnFile($this->UDID);
				}
				return $this->sendResponse($file[0], $file[1]);
			} else {
				$this->sendResponse(400, "Invalid or deactivated UDID");
				return;
			}
		} else {
            return $this->sendResponse(400, "Bad Method");
        }
    }

   /*
	* endpoint: /agent/register2
	* method: POST
	* header: UDID
	* params: pin, serial, userDeviceName, os
	*/
	public function register2Action()
    {
		if ($this->request->isPost()) {
			$params = $this->request->getPost();
			$agent = new Agent();
			$result = $agent->registerAgent2($params['pin'], $params['serial'], $params['userDeviceName'], $params['os']);
			//Response
			$response = new \Phalcon\Http\Response();
			$response->setStatusCode($result[0]);
			$response->setContent($result[1]);
			$response->send();
	        exit;
		} else {
            return $this->sendResponse(400, "Bad Method");
		}
	}

   /*
	* endpoint: /agent/reregister
	* method: POST
	* header: UDID
	* params: pin, serial, userDeviceName, os, UDID
	*/
	public function reregisterAction()
    {
		if ($this->request->isPost()) {
			$params = $this->request->getPost();
			$agent = new Agent();
			$result = $agent->reregisterAgent($params['pin'], $params['serial'], $params['userDeviceName'], $params['os'], $params['UDID']);
			//Response
			$response = new \Phalcon\Http\Response();
			$response->setStatusCode($result[0]);
			$response->setContent($result[1]);
			$response->send();
	        exit;
		} else {
            return $this->sendResponse(400, "Bad Method");
		}
	}

   /*
	* endpoint: /agent/update
	* method: POST
	* header: UDID
	* params: os(int), osEdition(string), osBuild(string), deviceType(int), defenderVersion(string), serial(string, optional)
	*/
	public function updateAction()
    {
		if ($this->request->isPost()) {
			$params = $this->request->getPost();
			$agent = new Agent();
			isset($params['serial']) ? $serial = $params['serial'] : $serial = "null";
			$result = $agent->updateAgent(
				$this->UDID,
				$params['os'],
				$params['osEdition'],
				$params['osBuild'],
				$params['deviceType'],
				$params['defenderVersion'],
				$serial
			);
			return $this->sendResponse($result[0], $result[1]);
		} else {
            return $this->sendResponse(400, "Bad Method");
		}
	}

	/*
	* endpoint: /agent/log
	* method: POST
	* header: UDID
	* params: message
	*/
    public function logAction()
    {
        if ($this->request->isPost()) {
            $params = $this->request->getPost();
			$log = new LogAgent();
			$log->assign([
				'client_ip' => $this->sourceIP,
				'message' => $params['message'],
				'fk_ent_agent_UDID' => $this->UDID,
				'created_by' => 'thrust',
				'updated_by' => 'thrust'
			]);
			$result = $log->save();
			foreach ($log->getMessages() as $message) {
				echo $message;
			}
			if ($result) {
				$this->sendResponse();
				return;
			} else {
				$this->sendResponse(500, "Error");
				return;
			}
        } else {
            $this->sendResponse(400, "Bad Method");
			return;
        }
    }

	/*
	* endpoint: /agent/version
	* method: GET
	* header: UDID
	*/
	public function versionAction()
    {
        if ($this->request->isGet()) {
			$agent = new Agent();
			$result = $agent->getVersion($this->UDID);
			if ($result) {
				$this->sendResponse($result[0], $result[1]);
				return;
			//Error geting file version
			} else {
				$this->sendResponse(500, "Error");
				return;
			}
		//Not GET
        } else {
            $this->sendResponse(400, "Bad Method");
			return;
        }
    }

	/*
	* endpoint: /agent/download
	* method: GET
	* header: UDID
	* param: file
	*/
	public function downloadAction()
    {
        if ($this->request->isGet()) {
			if (isset ($this->request->getQuery()['file'])) {
				$agent = new Agent();
				$result = $agent->downloadVersion($this->UDID, $this->request->getQuery()['file']);
				return $this->sendResponse($result[0], $result[1]);
			} else {
				return $this->sendResponse(400, "Invalid Request");
			}
		//Not GET
        } else {
            $this->sendResponse(400, "Bad Method");
			return;
        }
    }


	/*
	* endpoint: /agent/fileScan
	* method: POST
	* header: UDID
	* params: file_array
	* format: json
		[{
			"fileName": "test.log",
			"extension": "log",
			"fullPath": "C:\\Users\\john_todyl.com\\Desktop\\test.log",
			"md5": "2f8222b4f275c4f18e69c34f66d2631b",
			"sha1": "b77f151a15e240c19e170611a6681e6d6e7ff296",
			"sha256": "898ed3bdcb749c665866ee2750ab50d7ac5da6b666546fcd952cfc4cbc0c33b4",
			"size": "241176",
			"created": "2017-06-09 12:25:51",
			"modified": "2017-06-09 12:41:58",
			"accessed": "2017-06-09 12:36:38"
		}]
	*/
	public function fileScanAction()
    {
        if ($this->request->isPost()) {
            $params = $this->request->getPost();
            $AgentFiles = new AgentFiles();
			$result = $AgentFiles->processFiles($this->UDID, $params['file_array']);
			if (! is_array($result)) {
				return $this->sendResponse(200, json_encode(array("Result" => "Success")));
			} else {
				$this->logger->error('[API FileScan] 400 Bad Request'.
									' UDID:' . $this->UDID .
									' URI:'. $this->dispatcher-> getControllerName() . $this->dispatcher-> getActionName() . 
									' SourceIP:' . $this->sourceIP .
									' Params:' . $params['file_array'].
									' Method:' . $this->request->getMethod()
				);
				return $this->sendResponse($result[0], $result[1]);
			}
        } else {
    		$this->logger->error('[API FileScan] 400 Bad Request'.
    							' UDID:' . $this->UDID .
								' URI:'. $this->dispatcher-> getControllerName() . $this->dispatcher-> getActionName() . 
								' SourceIP:' . $this->sourceIP .
								' Params:' . $params['file_array'] . 
								' Method:' . $this->request->getMethod()
			);
           return $this->sendResponse(400, json_encode(array("Result" => "Bad Method")));
        }
    }

   /*
	* endpoint: /agent/verify
	* method: GET
	* header: UDID
	*/
	public function verifyAction()
    {
        if ($this->request->isGet()) {
			$agent = new Agent();
			$result = $agent->verifyUDID($this->request->getHeader("UDID"));
			if ($result) {
				$this->sendResponse(200, $result);
				return;
			} else {
				$this->sendResponse(400, "Bad Method");
				return;
			}
		//Not GET
        } else {
            $this->sendResponse(400, "Bad Method");
			return;
        }
    }

    /*
	* endpoint: /agent/home
	* method: GET
	* header: UDID
	* format: json
	{
	    "threat_level": 1,
	    "threat_copy": "No significant new threats have been discovered. Todyl is continuing to monitor and block threat activity.",
	    "threat_image": "https://www.todyl.com/img/dashboard/threat_levels/low.svg",
	    "userDeviceName": "device-80",
	    "ip": "x.x.x.x",
	    "cloudLocation": null,
	    "fileScan": 1,
	    "debugLogging: "0",
	    "forceFullScan": "2017-12-19 00:00:00"
	    "past24hBlocks": {
	        "2017-08-07 18:00:00": "0",
	        "2017-08-07 19:00:00": "0",
	        "2017-08-07 20:00:00": "0",
	        "2017-08-07 21:00:00": "0",
	        "2017-08-07 22:00:00": "0",
	        "2017-08-07 23:00:00": "0",
	        "2017-08-08 00:00:00": "0",
	        "2017-08-08 01:00:00": "0",
	        "2017-08-08 02:00:00": "0",
	        "2017-08-08 03:00:00": "0",
	        "2017-08-08 04:00:00": "0",
	        "2017-08-08 05:00:00": "0",
	        "2017-08-08 06:00:00": "0",
	        "2017-08-08 07:00:00": "0",
	        "2017-08-08 08:00:00": "0",
	        "2017-08-08 09:00:00": "0",
	        "2017-08-08 10:00:00": "0",
	        "2017-08-08 11:00:00": "0",
	        "2017-08-08 12:00:00": "0",
	        "2017-08-08 13:00:00": "0",
	        "2017-08-08 14:00:00": "0",
	        "2017-08-08 15:00:00": "0",
	        "2017-08-08 16:00:00": "0",
	        "2017-08-08 17:00:00": "0"
	    }
	}
	*/
	public function homeAction()
    {
        if ($this->request->isGet()) {
			$agent = new Agent();
			$result = $agent->home($this->UDID, $this->request);
			if ($result) {
				$this->sendResponse(200, $result);
				return;
			} else {
				$this->sendResponse(200, "Error");
				return;
			}
		//Not GET
        } else {
            $this->sendResponse(400, "Bad Method");
			return;
        }
    }

   /*
	* endpoint: /agent/mobileCarrier
	* method: GET
	* header: UDID
	*/
	public function mobileCarrierAction()
    {
        if ($this->request->isGet()) {
			$agent = new Agent();
			$result = $agent->mobileCarrier($this->UDID, $this->request);
			if ($result) {
				if ($result = "undefined") {
					$this->sendResponse(200, json_encode(array("Result" => "false")));
				} else {
					$this->sendResponse(200, json_encode(array("Result" => "true")));
				}
				return;
			//Error geting file version
			} else {
				$this->sendResponse(500, json_encode(array("Result" => "Error")));
				return;
			}
		//Not GET
        } else {
            return $this->sendResponse(400, json_encode(array("Result" => "Bad Method")));
        }
    }

   /*
	* endpoint: /agent/fileWhitelist
	* method: GET
	* header: UDID
	*/
	public function fileWhitelistAction()
    {
        if ($this->request->isGet()) {
			$agent = new Agent();
			$result = $agent->fileWhitelist($this->request->getHeader("UDID"));
			if ($result) {
				$this->sendResponse(200, $result);
				return;
			} else {
				$this->sendResponse(400, "Bad Method");
				return;
			}
		//Not GET
        } else {
            $this->sendResponse(400, "Bad Method");
			return;
        }
    }

   /*
	* endpoint: /agent/crash
	* method: POST
	* header: UDID
	* body: form-data
	*/
    public function crashAction()
    {
        if ($this->request->isPost()) {
        	// Check if the there are uploaded files
        	if ($this->request->hasFiles() == true) {
        		// Upload each file
            	foreach ($this->request->getUploadedFiles() as $file) {
            		$key = $this->UDID.'_'.$file->getName();
            		$result = $this->S3->upload($this->config->awsS3->crashBucket, $key, $file->getTempName());
            	}
            	$this->logger->info('[API] [Crash] New Defender Crash '. $this->UDID);
				$this->sendResponse();
				return;
       		} else {
       			$this->sendResponse(400, "No Attached Files");
				return;
       		}
        } else {
            $this->sendResponse(400, "Bad Method");
			return;
        }
    }

   /*
	* endpoint: /agent/settings
	* method: GET
	* header: UDID
	*/
    public function settingsAction()
    {
        if ($this->request->isGet()) {
        	// Create the JSON resonse - ToDo: Make Dynamic
        	$settingsArray = array(
        		'retry-api-time'       => 5000,
        		'upload-crash-timeout' => 120000,
				'home-timeout'         => 10000,
				'verify-timeout'       => 10000,
				'register-timeout'     => 10000,
				'send-log-timeout'     => 10000,
				'get-ovpn-timeout'     => 10000,
				'whitelist-timeout'    => 10000,
				'send-update-timeout'  => 10000,
				'version-timeout'      => 10000,
				'settings-timeout'     => 10000
        	);
        	$this->sendResponse(200, json_encode($settingsArray));
        } else {
            $this->sendResponse(400, "Bad Method");
			return;
        }
    } 
}
