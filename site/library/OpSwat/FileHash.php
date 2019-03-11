<?php

namespace Thrust\OpSwat;

use Phalcon\Mvc\User\Component;
use Phalcon\Http\Client\Request;

/**
 * Thrust\OpSwat\FileHash
 * Interacts with OpSwat File Hash APIs
 */
class FileHash extends Component
{

    private $logger;

    function __construct()
    {
        $this->logger = \Phalcon\Di::getDefault()->getShared('logger');
    }

    /**
     * Get hash info for a list of files
	 * @param string $json
     * @return json
     */
    public function getHashData($json)
    {

        try {
            // get available provider Curl or Stream
            $provider = Request::getProvider();

            $provider->setBaseUri('https://api.metadefender.com/');

            $provider->header->set('apikey', $this->config->opswat->key);
            $provider->header->set('x-include-scan-details', '1');

            //Post the JSON Array of file hashes
            $response = $provider->post('v2/hash', $json);

            //Catch errors
            if ($response->header->statusCode != 200) {
                $this->logger->info('OpSwat Error: '. $response->header->statusCode . " " . $response->body);
                return false;
            } else {
                return $response->body;
            }
        } catch (\Exception $e) {
            $this->logger->error('OpSwat Error: ' . $e->getMessage());
        }
    }
}