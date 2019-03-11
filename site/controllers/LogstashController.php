<?php

namespace Thrust\Controllers;

use Thrust\Models\Api\Logstash;

class LogstashController extends ApiBaseController
{

   /*
	* endpoint: /logstash/openvpnConnect
	* method: POST
	* header: internalApiSecret
	* params: $UDID, $host, $CLIENT_IP, $UDID_IP, $timestamp
	*/
	public function openvpnConnectAction()
    {
		if ($this->request->isPost()) {
			$params = $this->request->getPost();
			$logstash = new Logstash();
			$result = $logstash->openvpnConnect($params['UDID'], $params['host'], $params['datacenter'], $params['CLIENT_IP'], $params['UDID_IP'], $params['timestamp']);
			if ($result) { $this->sendResponse(); } else { $this->sendResponse(500, "Error"); }
		} else {
            return $this->sendResponse(400, "Bad Method");
		}
	}

   /*
	* endpoint: /logstash/openvpnDisconnect
	* method: POST
	* header: internalApiSecret
	* params: UDID, $host, $UDID_IP, $bytes_received, $bytes_sent, $timestamp
	*/
	public function openvpnDisconnectAction()
    {
		if ($this->request->isPost()) {
			$params = $this->request->getPost();
			$logstash = new Logstash();
			$result = $logstash->openvpnDisconnect($params['UDID'], $params['host'], $params['UDID_IP'], $params['bytes_received'], $params['bytes_sent'], $params['timestamp']);
			if ($result) { $this->sendResponse(); } else { $this->sendResponse(500, "Error"); }
		} else {
            return $this->sendResponse(400, "Bad Method");
		}
	}

   /*
	* endpoint: /logstash/openvpnTransfer
	* method: POST
	* header: internalApiSecret
	* params: UDID, $host, $bytes_received, $bytes_sent
	*/
	public function openvpnTransferAction()
    {
		if ($this->request->isPost()) {
			$params = $this->request->getPost();
			$logstash = new Logstash();
			$result = $logstash->openvpnTransfer($params['UDID'], $params['host'], $params['bytes_received'], $params['bytes_sent']);
			if ($result) { $this->sendResponse(); } else { $this->sendResponse(500, "Error"); }
		} else {
            return $this->sendResponse(400, "Bad Method");
		}
	}

	/*
	* endpoint: /logstash/snort
	* method: POST
	* header: internalApiSecret
	* params: raw, timestamp, host, gid, sid, alert, classification, protocol, srcIP, srcPort, dstIP, dstPort, action
	*/
	public function snortAction()
    {
		if ($this->request->isPost()) {
			$params = $this->request->getPost();
			$logstash = new Logstash();
			$result = $logstash->storeSnortLog($params);
			if ($result) { $this->sendResponse(); } else { $this->sendResponse(500, "Error"); }
		} else {
            return $this->sendResponse(400, "Bad Method");
		}
	}

   /*
	* endpoint: /logstash/avProxy
	* method: POST
	* header: internalApiSecret
	* params: raw, redirectUrl, host, timestamp, url, UDID_IP, malware
	*/
	public function avProxyAction()
    {
		if ($this->request->isPost()) {
			$params = $this->request->getPost();
			$logstash = new Logstash();
			$result = $logstash->storeAvProxyLog($params);
			if ($result) { $this->sendResponse(); } else { $this->sendResponse(500, "Error"); }
		} else {
            return $this->sendResponse(400, "Bad Method");
		}
	}

   /*
	* endpoint: /logstash/bind
	* method: POST
	* header: internalApiSecret
	* params: raw, host, UDID_IP, client_port, domain, RPZ, timestamp
	*/
	public function bindAction()
    {
		if ($this->request->isPost()) {
			$params = $this->request->getPost();
			$logstash = new Logstash();
			$result = $logstash->storeBindLog($params);
			if ($result) { $this->sendResponse(); } else { $this->sendResponse(500, "Error"); }
		} else {
            return $this->sendResponse(400, "Bad Method");
		}
	}
}
