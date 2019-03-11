<?php

namespace Thrust\Hanzo;

use Phalcon\Mvc\User\Component;
use Phalcon\Http\Client\Provider\Curl;

/*
 * Thrust\Hanzo\Client
 *
 * cURL client to issue requests to Hanzo
 */
class Client extends Component
{

    /** @var String[] Supported HTTP request methods */
    const SUPPORTED_METHODS = ['head', 'get', 'post', 'put', 'patch', 'delete'];

    /** @var Thrust\Hanzo\Client The current instance */
    private static $_instance = null;

    /** @var Phalcon\Http\Client\Provider\Curl The current cURL handler */
    protected $curl;

    /** @var string The current HTTP request method */
    protected $method = 'get';

    /** @var string The relative path to the current Hanzo endpoint */
    protected $uri = '';

    /** @var array Any extra parameters for the request */
    protected $params = [];

    /** @var array Any extra HTTP headers for the request */
    protected $headers = [];

    /** Build the Hanzo client */
    private function __construct()
    {
        $this->curl = new Curl();
        $config = $this->getDi()->get('config');
        $this->curl->setBaseUri($config->hanzo->endpoint);
        // Do not enable this option until a response handler is built out to
        // properly validate any location headers sent back as valid redirects
        //
        // Hanzo shouldn't redirect anyway
        $this->curl->setOption(\CURLOPT_FOLLOWLOCATION, false);
    }

    /** Kill the cURL handler when the client is unset */
    public function __destroy()
    {
        unset($this->curl);
    }

    /** Override because this is a singleton */
    private function __clone() { }

    /** Override because this is a singleton */
    private function __wakeup() { }

    /**
     * Returns the current instance
     *
     * @return Thrust\Hanzo\Client
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new Client();
        }
        return self::$_instance;
    }

    /**
     * Magic helper to directly make the specified request to Hanzo
     *
     * @param string The HTTP method for the request
     * @param string The Hanzo endpoint
     * @param array (Optional) Any additional parameters
     * @param array (Optional) Any additional HTTP headers
     * @param bool (Optional) Return the full response
     * @return Phalcon\Http\Client\Response The Hanzo Response
     * @throws \Exception What you did wrong
     */
    public function __call($name, $args)
    {
        if (count($args) <= 4 && $this->setMethod($name)) {
            if (is_array($args[0]) && count($args[0] <=4)) {
                $args = $args[0];
            }
            if (isset($args[0])) {
                if (!$this->setUri($args[0])) {
                    throw new \Exception("Invalid uri path: $args[0]");
                }
                if (isset($args[1])) {
                    if (!$this->setParams($args[1])) {
                        throw new \Exception("Invalid params");
                    }
                }
                if (isset($args[2])) {
                    if (!$this->setHeaders($args[2])) {
                        throw new \Exception("Invalid headers");
                    }
                }
                $full_response = false;
                if (isset($args[3])) {
                    $full_response = (bool) $args[3];
                }
                return $this->sendRequest($full_response);
            } else {
                throw new \Exception("No uri path set");
            }
        } else {
            if (count($args) <= 4) {
                throw new \Exception("Invalid arguments");
            }
            throw new \Exception("Call to undefined method: $name");
        }

    }

    public static function __callStatic($name, $args)
    {
        $client = self::getInstance();
        return $client->{$name}($args);
    }

    /**
     * Set the current HTTP method for the request
     *
     * @param string The HTTP method
     * @return bool Whether or not the method was set
     */
    public function setMethod($method)
    {
        if (in_array($method, self::SUPPORTED_METHODS)) {
            $this->method = $method;
            return true;
        }
        return false;
    }

    /**
     * Get the current HTTP method for the request
     *
     * @return string The current HTTP method
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set the Hanzo endpoint
     *
     * @param string The endpoint route
     * @param bool Whether or not the route was set
     */
    public function setUri($uri)
    {
        // TODO: validate uri fragment
        if (is_string($uri)) {
            $this->uri = $uri;
            return true;
        }
        return false;
    }

    /**
     * Get the current Hanzo endpoint
     *
     * @return string The endpoint route
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set the additional query parameters
     * or post body of the request
     *
     * @param array The additional parameters
     * @return bool Whether or not the parameters were set
     */
    public function setParams($params)
    {
        // TODO: Additional validation
        // array(<string> => <string>)
        if (is_array($params)) {
            $this->params = array_merge($this->params, $params);
            return true;
        }
        return false;
    }

    /**
     * Get any additional request parameters
     *
     * @return array The additional parameters
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set any additioanl HTTP headers for the request
     *
     * @param array The additional headers
     * @return bool Whether or not the headers were set
     */
    public function setHeaders($headers)
    {
        // TODO: Additional validation
        // array(<string> => <string>)
        if(is_array($headers)) {
            $this->headers = array_merge($this->headers, $headers);
            return true;
        }
        return false;
    }

    /**
     * Get any additional HTTP headers
     *
     * @return array The additional headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Send the request to Hanzo
     *
     * @param bool Return the full HTTP response
     * @return Phalcon\Http\Client\Response The Hanzo response
     */
    public function sendRequest($full_response = false)
    {
        if ($this->uri !== '') {
            // Phalcon will try append params as a normal URL query
            // i.e. <endpoint>?key1=value1&key2=value2&...
            // Hanzo's looking for <endpoint>/key1/value1/key2/value2/...
            if (in_array($this->method, ['get', 'head', 'delete']) && count($this->params) > 0) {
                foreach($this->params as $key => $value) {
                    $this->uri .= "/$key/$value";
                }
                $this->params = [];
            }
            // Overwrite the default content type for post requests so Hanzo can parse the body
            if ($this->method === 'post') {
                $this->headers = array_merge($this->headers, ['Content-Type' => 'application/x-www-form-urlencoded']);
            }
            try {
                // if ($this->uri != '/registration/industries') {
                //     echo 'URI : ' . $this->uri;
                //     echo '<br />Params : <pre>';
                //     print_r($this->params);
                //     echo '</pre>';
                //     echo '<br />Headers : <pre>';
                //     print_r($this->headers);
                //     echo '</pre>';
                // }

                $response = $this->curl->{$this->method}(
                    $this->uri,
                    $this->params,
                    $this->headers,
                    (bool) $full_response
                );

                // if ($this->uri != '/registration/industries') {
                //     echo '<br /><br />Response : <pre>';
                //     print_r($response);
                //     echo '</pre>';
                //     exit;
                // }

                $curl_response = json_decode($response->body);

                if (is_scalar($curl_response)) {
                    // convert response to object
                    $temp = $curl_response;
                    $curl_response = new \stdClass;
                    $curl_response->result = $temp;
                }
                if (is_object($curl_response)) {
                    // include status code in the response
                    $curl_response->{'statusCode'} = $response->header->statusCode;
                }

                return $curl_response;
            } catch (\Exception $e) {
                var_dump($e->getMessage());
            }
        }
        return null;
    }

}
