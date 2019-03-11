<?php

namespace Thrust\Controllers;

use Phalcon\Http\Response;
use Thrust\Models\Api\ApiAuth;

abstract class ApiBaseController extends ControllerBase
{
	protected $UDID;
	protected $config;
	protected $sourceIP;

    public function initialize()
    {

		//Setup DI config
		$this->config = \Phalcon\Di::getDefault()->get('config');

		//Disable the view
        $this->view->setTemplateBefore('public');
		$this->view->disable();

		//Set the UDID
		$this->UDID = $this->request->getHeader("UDID");

		//Set the sourceIP
		$this->sourceIP = ($this->request->getHeader('CF-Connecting-IP') ? $this->request->getHeader('CF-Connecting-IP') : $this->request->getClientAddress());

		//Check hostname
		if ($this->getDI()->get('config')->environment->env == "dev") {
			$host = array('dev.api.todyl.com', 'api.todyl.com');
		} elseif ($this->getDI()->get('config')->environment->env == "prd") {
			$host = array('api.todyl.com');
		}
		if(! in_array($this->request->getServer("HTTP_HOST"), $host)) {
			$this->logger->info('[API BASE] 401 Bad Host ' . $host . 
								' UDID:' . $this->UDID .
								' URI:'. $this->dispatcher-> getControllerName() ."/". $this->dispatcher-> getActionName() . 
								' SourceIP:' . $this->sourceIP
			);
			$this->sendResponse(401, "Unauthorized");
			return;
		}

		//Auth UDID or internal IP
		if (
			$this->config->environment->env != "dev" &&
			$this->request->getHeader("internalApiSecret") != $this->config->internalApiSecret->key
			)
			{
				//External API call
				if ($this->dispatcher->getActionName() != "register2" AND $this->dispatcher->getActionName() != "reregister" AND $this->dispatcher->getActionName() != "verify") { //THIS IS BAD AND NEEDS TO BE FIXED
					if ($this->UDID) {
						//Bad UDID
						$ApiAuth = new ApiAuth;
						if ($ApiAuth->checkUDID($this->UDID) === false) {
							$this->logger->info('[API BASE] 401 Bad UDID UDID:' . $this->UDID .
								' URI:'. $this->dispatcher-> getControllerName() ."/". $this->dispatcher-> getActionName() . 
								' SourceIP:' . $this->sourceIP
							);
							$this->sendResponse(401, "Unauthorized");
							return;
						}
					//No UDID
					} else {
						$this->logger->info('[API BASE] 401 No UDID'.
											' URI:'. $this->dispatcher-> getControllerName() ."/". $this->dispatcher-> getActionName() . 
											' SourceIP:' . $this->sourceIP
						);
						$this->sendResponse(401, "Unauthorized");
						return;
					}
				}
			}

		//Add UDID for Dev
		if ( $this->config->environment->env == "dev" && $this->request->getHeader("UDID") ) $this->UDID = $this->request->getHeader("UDID");
    }

	protected function sendResponse($code = 200, $body = null)
	{
		$this->response->setContentType("application/json", "UTF-8");
		switch ($code) {
			case 200:
				$this->response->setStatusCode($code, "OK");
				break;
			case 400:
				$this->response->setStatusCode($code, "Bad Request");
				break;
			case 401:
				$this->response->setStatusCode($code, "Unauthorized");
				break;
			case 500:
				$this->response->setStatusCode($code, "Error");
				break;
		}
		if ($code != 200) {
			$this->response->setContent(json_encode(array('Error' => "$body")));
		} else {
			$this->response->setContent($body);
		}
		$this->response->send();
		exit;
	}
}
