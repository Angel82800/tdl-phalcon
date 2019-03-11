<?php

namespace Thrust\Saltstack;

use Phalcon\Mvc\User\Component;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use GuzzleHttp\TransferStats;
use Namshi\Cuzzle\Middleware\CurlFormatterMiddleware;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use GuzzleHttp\HandlerStack;

/**
 * Thrust\Saltstack\Api
 * Handles making calls to Saltstack API.
 */
class Api extends Component
{
    private $username;

    private $password;

    private $saltUrl;

    private $testHandler;

    const CONNECT_TIMEOUT = 10;

    const REQUEST_TIMEOUT = 30;

    public function __construct()
    {
        $config = $this->getDi()->get('config');

        $this->username = $config->salt->username;
        $this->password = $config->salt->password;
        $this->saltUrl = 'https://' . $config->salt->host . ':' . $config->salt->port;
    }

    /**
     * Creates a Guzzle client that is configured for debugging. After making a
     * request you can dump the request as a CURL command by running:.
     *
     *   var_dump($this->testHandler->getRecords()); exit;
     *
     * @see https://github.com/namshi/cuzzle
     *
     * @return \GuzzleHttp\Client - client configured to debug requests
     */
    private function createDebugClient()
    {
        $logger = new Logger('guzzle.to.curl');
        $this->testHandler = new TestHandler();
        $logger->pushHandler($this->testHandler);

        $handler = HandlerStack::create();
        $handler->after('cookies', new CurlFormatterMiddleware($logger));
        $client = new \GuzzleHttp\Client(['handler' => $handler]);

        return $client;
    }

    /**
     * Adds a URL to the blacklist for a shield machine.
     *
     * @param string $url - New URL to blacklist
     *
     * @return bool - Returns false if error or true if successful
     */
    public function updateBlacklist($url, $newUrl = null)
    {
        if ($newUrl === null) {
            $newUrl = $url;
        }
        $response = null;
        $client = new \GuzzleHttp\Client();
        $requestData = null;

        try {
            $response = $client->post(
                $this->saltUrl . '/run',
                [
                    'json' => [
                        'client'    => 'local',
                        'expr_form' => 'grain',
                        'tgt'       => 'shield-release:demo',
                        'fun'       => 'file.replace',
                        'arg'       => [
                            '/opt/etc/squidGuard/db/user-blacklist',
                            'pattern="' . $url . '"',
                            'repl="' . $newUrl . '"',
                            'append_if_not_found=true',
                        ],
                        'username' => $this->username,
                        'password' => $this->password,
                        'eauth'    => 'pam',
                    ],
                    'headers'         => ['Accept: application/x-yaml'],
                    'content-type'    => 'application/json',
                    'connect_timeout' => self::CONNECT_TIMEOUT,
                    'timeout'         => self::REQUEST_TIMEOUT,
                    'on_stats'        => function (TransferStats $stats) use (&$requestData) {
                        $requestData = $stats->getRequest();
                    }
                ]
            );
        } catch (RequestException $e) {
            $this->logger->error('Exception for blocked site request with message: ' . $e->getMessage());
            $this->logger->error('Exception for blocked site request with request: ' . Psr7\str($e->getRequest()));
            if ($e->hasResponse()) {
                $this->logger->error('Exception for blocked site request with response: ' . Psr7\str($e->getResponse()));
            }

            return false;
        }

        // Grab response body and return it
        $bodyContents = json_decode($response->getBody()->getContents());
        $success = $this->validateFunctionResponse($bodyContents->return[0]);

        // Log relevant data for debugging
        if (!$success) {
            $this->logger->error('Failed blocked sites request with error in body!');
            $this->logger->error('Request body: ' . (string) $requestData->getBody() . ', headers: ' . json_encode($requestData->getHeaders()) . ' and response: ' . json_encode($bodyContents->return[0]));
        } else {
            $this->logger->info('Successful blocked sites request!');
            $this->logger->info('Request body: ' . (string) $requestData->getBody() . ', headers: ' . json_encode($requestData->getHeaders()) . ' and response: ' . json_encode($bodyContents->return[0]));
        }

        return $success;
    }

    /**
     * Makes call to Salt API to update the wireless network name.
     *
     * @param string $name - New wireless network name
     *
     * @return bool - Returns false if error or true if successful
     */
    public function updateWirelessName($name)
    {
        $response = null;
        $client = new \GuzzleHttp\Client();
        $requestData = null;

        try {
            $response = $client->post(
                $this->saltUrl . '/run',
                [
                    'json' => [
                        'client'    => 'local',
                        'expr_form' => 'grain',
                        'tgt'       => 'shield-release:demo',
                        'fun'       => 'file.replace',
                        'arg'       => [
                            '/etc/salt/grains',
                            'pattern="wireless-ssid.*"',
                            'repl="wireless-ssid: ' . $name . '"',
                        ],
                        'username' => $this->username,
                        'password' => $this->password,
                        'eauth'    => 'pam',
                    ],
                    'headers'         => ['Accept: application/x-yaml'],
                    'content-type'    => 'application/json',
                    'connect_timeout' => self::CONNECT_TIMEOUT,
                    'timeout'         => self::REQUEST_TIMEOUT,
                    'on_stats'        => function (TransferStats $stats) use (&$requestData) {
                        $requestData = $stats->getRequest();
                    }
                ]
            );
        } catch (RequestException $e) {
            $this->logger->error('Exception for update wireless name request with message: ' . $e->getMessage());
            $this->logger->error('Exception for update wireless name request with request: ' . Psr7\str($e->getRequest()));
            if ($e->hasResponse()) {
                $this->logger->error('Exception for update wireless name request with response: ' . Psr7\str($e->getResponse()));
            }

            return false;
        }

        // Grab response body and return it
        $bodyContents = json_decode($response->getBody()->getContents());
        $success = $this->validateFunctionResponse($bodyContents->return[0]);

        // Log relevant data for debugging
        if (!$success) {
            $this->logger->error('Failed update wireless name request with error in body!');
            $this->logger->error('Request body: ' . (string) $requestData->getBody() . ', headers: ' . json_encode($requestData->getHeaders()) . ' and response: ' . json_encode($bodyContents->return[0]));

            return false;
        } else {
            $this->logger->info('Successful update wireless name request!');
            $this->logger->info('Request body: ' . (string) $requestData->getBody() . ', headers: ' . json_encode($requestData->getHeaders()) . ' and response: ' . json_encode($bodyContents->return[0]));
        }

        // Make salt call to restart services
        $restartSuccess = $this->applyWirelessControlState();

        // Rollback previous salt grain changes
        if (!$restartSuccess) {
            // TODO: Implement rollback of salt grain changes
        }

        return $restartSuccess;
    }

    /**
     * Makes call to Salt API to update the wireless network password.
     *
     * @param string $password - New wireless network password
     *
     * @return bool - Returns false if error or true if successful
     */
    public function updateWirelessPassword($password)
    {
        $response = null;
        $client = new \GuzzleHttp\Client();
        $requestData = null;

        try {
            $response = $client->post(
                $this->saltUrl . '/run',
                [
                    'json' => [
                        'client'    => 'local',
                        'expr_form' => 'grain',
                        'tgt'       => 'shield-release:demo',
                        'fun'       => 'file.replace',
                        'arg'       => [
                            '/etc/salt/grains',
                            'pattern="wireless-pass.*"',
                            'repl="wireless-pass: ' . $password . '"',
                        ],
                        'username' => $this->username,
                        'password' => $this->password,
                        'eauth'    => 'pam',
                    ],
                    'headers'         => ['Accept: application/x-yaml'],
                    'content-type'    => 'application/json',
                    'connect_timeout' => self::CONNECT_TIMEOUT,
                    'timeout'         => self::REQUEST_TIMEOUT,
                    'on_stats'        => function (TransferStats $stats) use (&$requestData) {
                        $requestData = $stats->getRequest();
                    }
                ]
            );
        } catch (RequestException $e) {
            $this->logger->error('Exception for update wireless password request with message: ' . $e->getMessage());
            $this->logger->error('Exception for update wireless password request with request: ' . Psr7\str($e->getRequest()));
            if ($e->hasResponse()) {
                $this->logger->error('Exception for update wireless password request with response: ' . Psr7\str($e->getResponse()));
            }

            return false;
        }

        // Grab response body and return it
        $bodyContents = json_decode($response->getBody()->getContents());
        $success = $this->validateFunctionResponse($bodyContents->return[0]);

        // Log relevant data for debugging
        if (!$success) {
            $this->lopgger->error('Failed update wireless password request with error in body!');
            $this->lopgger->error('Request body: ' . (string) $requestData->getBody() . ', headers: ' . json_encode($requestData->getHeaders()) . ' and response: ' . json_encode($bodyContents->return[0]));

            return false;
        } else {
            $this->logger->info('Successful update wireless password request!');
            $this->logger->info('Request body: ' . (string) $requestData->getBody() . ', headers: ' . json_encode($requestData->getHeaders()) . ' and response: ' . json_encode($bodyContents->return[0]));
        }

        // Make salt call to restart services
        $restartSuccess = $this->applyWirelessControlState();

        // Rollback previous salt grain changes
        if (!$restartSuccess) {
            // TODO: Implement rollback of salt grain changes
        }

        return $restartSuccess;
    }

    /**
     * Makes call to Salt API to update setting for filtered content.
     *
     * @param string $filter - filter to update
     * @param bool   $value  - value to set for filter
     *
     * @return bool - Returns false if error or true if successful
     */
    public function updateFilteredContent($filter, $value)
    {
        $response = null;
        $client = new \GuzzleHttp\Client();
        $requestData = null;

        try {
            $response = $client->post(
                $this->saltUrl . '/run',
                [
                    'json' => [
                        'client'    => 'local',
                        'expr_form' => 'grain',
                        'tgt'       => 'shield-release:demo',
                        'fun'       => 'file.replace',
                        'arg'       => [
                            '/etc/salt/grains',
                            'pattern="filter-' . $filter . ':.*"',
                            'repl="filter-' . $filter . ': ' . $value . '"',
                            'append_if_not_found=true',
                        ],
                        'username' => $this->username,
                        'password' => $this->password,
                        'eauth'    => 'pam',
                    ],
                    'headers'         => ['Accept: application/x-yaml'],
                    'content-type'    => 'application/json',
                    'connect_timeout' => self::CONNECT_TIMEOUT,
                    'timeout'         => self::REQUEST_TIMEOUT,
                    'on_stats'        => function (TransferStats $stats) use (&$requestData) {
                        $requestData = $stats->getRequest();
                    }
                ]
            );
        } catch (RequestException $e) {
            $this->logger->error('Exception for update wireless password request with message: ' . $e->getMessage());
            $this->logger->error('Exception for update wireless password request with request: ' . Psr7\str($e->getRequest()));
            if ($e->hasResponse()) {
                $this->logger->error('Exception for update wireless password request with response: ' . Psr7\str($e->getResponse()));
            }

            return false;
        }

        // Grab response body and validate it
        $bodyContents = json_decode($response->getBody()->getContents());
        $success = $this->validateFunctionResponse($bodyContents->return[0]);

        // Log relevant data for debugging
        if (!$success) {
            $this->logger->error('Failed update filtered content with error in body!');
            $this->logger->error('Request body: ' . (string) $requestData->getBody() . ', headers: ' . json_encode($requestData->getHeaders()) . ' and response: ' . json_encode($bodyContents->return[0]));

            return false;
        } else {
            $this->logger->info('Successful filtered content request!');
            $this->logger->info('Request body: ' . (string) $requestData->getBody() . ', headers: ' . json_encode($requestData->getHeaders()) . ' and response: ' . json_encode($bodyContents->return[0]));
        }

        return $success;
    }

    /**
     * Makes call to restart wireless-control services to apply any grain changes.
     *
     * @return bool - Returns false if error or true if successful
     */
    public function applyWirelessControlState()
    {
        $response = null;
        $client = new \GuzzleHttp\Client();
        $requestData = null;

        try {
            $response = $client->post(
                $this->saltUrl . '/run',
                [
                    'json' => [
                        'client'    => 'local',
                        'expr_form' => 'grain',
                        'tgt'       => 'shield-release:demo',
                        'fun'       => 'state.apply',
                        'arg'       => [
                            'mgmt/wireless-control',
                        ],
                        'username' => $this->username,
                        'password' => $this->password,
                        'eauth'    => 'pam',
                    ],
                    'headers'         => ['Accept: application/x-yaml'],
                    'content-type'    => 'application/json',
                    'connect_timeout' => self::CONNECT_TIMEOUT,
                    'timeout'         => self::REQUEST_TIMEOUT,
                    'on_stats'        => function (TransferStats $stats) use (&$requestData) {
                        $requestData = $stats->getRequest();
                    }
                ]
            );
        } catch (RequestException $e) {
            $this->logger->error('Exception for wireless control state request with message: ' . $e->getMessage());
            $this->logger->error('Exception for wireless control state request with request: ' . Psr7\str($e->getRequest()));
            if ($e->hasResponse()) {
                $this->logger->error('Exception for wireless control state request with response: ' . Psr7\str($e->getResponse()));
            }

            return false;
        }

        // Grab response body and return it
        $bodyContents = json_decode($response->getBody()->getContents());
        $success = $this->validateStateResponse($bodyContents->return[0]);

        // Log relevant data for debugging
        if (!$success) {
            $this->logger->error('Failed wireless control states request with error in body!');
            $this->logger->error('Request body: ' . (string) $requestData->getBody() . ', headers: ' . json_encode($requestData->getHeaders()) . ' and response: ' . json_encode($bodyContents->return[0]));
        } else {
            $this->logger->info('Successful wireless control states request!');
            $this->logger->info('Request body: ' . (string) $requestData->getBody() . ', headers: ' . json_encode($requestData->getHeaders()) . ' and response: ' . json_encode($bodyContents->return[0]));
        }

        return $success;
    }

    /**
     * Makes call to restart filter-control services to apply any grain changes.
     *
     * @return bool - Returns false if error or true if successful
     */
    public function applyFilterControlState()
    {
        $response = null;
        $client = new \GuzzleHttp\Client();
        $requestData = null;

        try {
            $response = $client->post(
                $this->saltUrl . '/run',
                [
                    'json' => [
                        'client'    => 'local',
                        'expr_form' => 'grain',
                        'tgt'       => 'shield-release:demo',
                        'fun'       => 'state.apply',
                        'arg'       => [
                            'mgmt/filter-control',
                        ],
                        'username' => $this->username,
                        'password' => $this->password,
                        'eauth'    => 'pam',
                    ],
                    'headers'         => ['Accept: application/x-yaml'],
                    'content-type'    => 'application/json',
                    'connect_timeout' => self::CONNECT_TIMEOUT,
                    'timeout'         => self::REQUEST_TIMEOUT,
                    'on_stats'        => function (TransferStats $stats) use (&$requestData) {
                        $requestData = $stats->getRequest();
                    }
                ]
            );
        } catch (RequestException $e) {
            $this->logger->error('Exception for filter control state request with message: ' . $e->getMessage());
            $this->logger->error('Exception for filter control state request with request: ' . Psr7\str($e->getRequest()));
            if ($e->hasResponse()) {
                $this->logger->error('Exception for filter control state request with response: ' . Psr7\str($e->getResponse()));
            }

            return false;
        }

        // Grab response body and return it
        $bodyContents = json_decode($response->getBody()->getContents());
        $success = $this->validateStateResponse($bodyContents->return[0]);

        // Log relevant data for debugging
        if (!$success) {
            $this->logger->error('Failed filter control states request with error in body!');
            $this->logger->error('Request body: ' . (string) $requestData->getBody() . ', headers: ' . json_encode($requestData->getHeaders()) . ' and response: ' . json_encode($bodyContents->return[0]));
        } else {
            $this->logger->info('Successful filter control states request!');
            $this->logger->info('Request body: ' . (string) $requestData->getBody() . ', headers: ' . json_encode($requestData->getHeaders()) . ' and response: ' . json_encode($bodyContents->return[0]));
        }

        return $success;
    }

    /**
     * Checks response of salt function call to see if it contains any known errors from Salt
     * API since even failed requests can return 200 responses with "OK" message.
     *
     * @param stdClass $response - Response returned from Salt API call
     *
     * @return bool - Returns false if error detected or true if no error detected
     */
    private function validateFunctionResponse($response)
    {

        // If no object in response assume failed
        if (!is_object($response)) {
            return false;
        }

        // List of known error "codes" that come back in repsonse body
        $errorArr = [
            'TypeError'
        ];

        $nonEmptyFound = false;
        foreach ($response as $command => $result) {
            if (empty($command)) {
                continue;
            } else {
                $nonEmptyFound = true;
                preg_match('/' . implode('|', $errorArr) . '/i', json_encode($result), $matches);
                if (is_array($matches) && (count($matches) > 0)) {
                    $this->logger->error('Invalid repsonse found in function validate for command: ' . $command . ' and result: ' . $result);

                    return false;
                }
            }
        }

        // If we get to here we found no matching error field so just return
        // if we found a non-empty result field
        return $nonEmptyFound;
    }

    /**
     * Checks response of salt state call and verifies each state response included
     * the field "result: true".
     *
     * @param stdClass $response - Response returned from Salt state API call
     *
     * @return bool - Returns false if response: true not found in every response else true
     */
    private function validateStateResponse($response)
    {

        // If no object in response assume failed
        if (!is_object($response)) {
            return false;
        }

        $respArray = (array) $response;

        // Grab response from call (first element of array)
        $commResult = reset($respArray);

        if (count($commResult) < 1) {
            $this->logger->error('State validation failed for command: ' . json_encode($commResult) . ' with no apparent commands executed');

            return false;
        }

        foreach ($commResult as $command => $result) {
            if (!is_object($result)) {
                $this->logger->error('State validation failed for command: ' . $command . ' with result: ' . $result);

                return false;
            }

            if (!isset($result->result) || $result->result !== true) {
                $this->logger->error('State validation failed for command: ' . $command . ' with result: ' . json_encode($result));

                return false;
            }
        }

        return true;
    }
}
