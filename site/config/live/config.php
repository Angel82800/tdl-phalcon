<?php

use Phalcon\Config;
use Phalcon\Logger;
use Phalcon\Config\Adapter\Ini;

$secureXml = file_get_contents('/etc/secure.xml');
$secureParams = simplexml_load_string($secureXml);
$environmentIni = new Ini("/etc/php.d/environment.ini");

return new Config([
    'database' => [
        'oltp-read' => [
            'adapter'  => (string) $secureParams->database->oltp->read->adapter,
            'host'     => (string) $secureParams->database->oltp->read->host,
            'dbname'   => (string) $secureParams->database->oltp->read->dbname,
            'username' => (string) $secureParams->database->oltp->read->username,
            'password' => (string) $secureParams->database->oltp->read->password,
        ],
        'oltp-write' => [
            'adapter'  => (string) $secureParams->database->oltp->write->adapter,
            'host'     => (string) $secureParams->database->oltp->write->host,
            'dbname'   => (string) $secureParams->database->oltp->write->dbname,
            'username' => (string) $secureParams->database->oltp->write->username,
            'password' => (string) $secureParams->database->oltp->write->password,
        ],
        'ioc-read' => [
            'adapter'  => (string) $secureParams->database->ioc->read->adapter,
            'host'     => (string) $secureParams->database->ioc->read->host,
            'dbname'   => (string) $secureParams->database->ioc->read->dbname,
            'username' => (string) $secureParams->database->ioc->read->username,
            'password' => (string) $secureParams->database->ioc->read->password,
        ],
        'ioc-write' => [
            'adapter'  => (string) $secureParams->database->ioc->write->adapter,
            'host'     => (string) $secureParams->database->ioc->write->host,
            'dbname'   => (string) $secureParams->database->ioc->write->dbname,
            'username' => (string) $secureParams->database->ioc->write->username,
            'password' => (string) $secureParams->database->ioc->write->password,
        ],
        'agentFiles-read' => [
            'adapter'  => (string) $secureParams->database->agentFiles->read->adapter,
            'host'     => (string) $secureParams->database->agentFiles->read->host,
            'dbname'   => (string) $secureParams->database->agentFiles->read->dbname,
            'username' => (string) $secureParams->database->agentFiles->read->username,
            'password' => (string) $secureParams->database->agentFiles->read->password,
        ],
        'agentFiles-write' => [
            'adapter'  => (string) $secureParams->database->agentFiles->write->adapter,
            'host'     => (string) $secureParams->database->agentFiles->write->host,
            'dbname'   => (string) $secureParams->database->agentFiles->write->dbname,
            'username' => (string) $secureParams->database->agentFiles->write->username,
            'password' => (string) $secureParams->database->agentFiles->write->password,
        ],
    ],
    'memcache' => [
        'host' => (string) $secureParams->memcache->host,
        'port' => (string) $secureParams->memcache->port,
    ],
    'salt' => [
        'host'     => (string) $secureParams->leonidas->host,
        'port'     => (string) $secureParams->leonidas->port,
        'username' => (string) $secureParams->leonidas->username,
        'password' => (string) $secureParams->leonidas->password,
    ],
    'application' => [
        'controllersDir'      => APP_DIR . '/controllers/',
        'modelsDir'           => APP_DIR . '/models/',
        'formsDir'            => APP_DIR . '/forms/',
        'elementDir'          => APP_DIR . '/forms/element/',
        'helpersDir'          => APP_DIR . '/helpers/',
        'viewsDir'            => APP_DIR . '/views/',
        'libraryDir'          => APP_DIR . '/library/',
        'pluginsDir'          => APP_DIR . '/plugins/',
        'cacheDir'            => APP_DIR . '/cache/',
        'validatorsDir'       => APP_DIR . '/validators/',
        'downloadDir'         => APP_DIR . '/installer/defender/main/',
        'alphaDownloadDir'    => APP_DIR . '/installer/defender/alpha/',
        'baseUri'             => '/',
        'publicUrl'           => 'todyl.com',
        'cryptSalt'           => (string) $secureParams->appsec->cryptSalt,
        'uploadDir'      => APP_DIR . '/public/ct-upld/',
        'uploadUrl'      => '/ct-upld/',
    ],
    'mail' => [
        'fromName'  => 'Todyl',
        'fromEmail' => 'no-reply@todyl.com'
    ],
    'awsSes' => [
        'region'    => 'us-east-1',
        'key'       => (string) $secureParams->aws->ses->access_key,
        'secret'    => (string) $secureParams->aws->ses->access_secret,
        'interval'  => 75000,
    ],
    'awsS3' => [
        'region'          => 'us-east-1',
        'endpoint'        => 's3.amazonaws.com',
        'key'             => (string) $secureParams->aws->s3->access_key,
        'secret'          => (string) $secureParams->aws->s3->access_secret,
        'crashBucket'     => (string) $secureParams->aws->s3->crash_bucket,
    ],
    'logger' => [
        'path'     => '/var/log/httpd/',
        'format'   => '[%date%] [%type%] %message%',
        'date'     => 'D M d H:i:s.u Y',
        'logLevel' => Logger::INFO,
        'filename' => 'app_log',
    ],
    'environment' => [
        'env' => (string) $environmentIni->environment,
    ],
    'recaptcha' => [
        'publicKey' => '6LdLSBYTAAAAAJvpMP39FaoCALAi3I-2HYTU3sgc',
        'invisible_publicKey' => '6LcR3CIUAAAAANIipCKo0wQiZ-qp9dm5TmbT_vOo',
        'secretKey' => (string) $secureParams->appsec->recaptchaSecret,
        'verifyUrl' => 'https://www.google.com/recaptcha/api/siteverify',
    ],
    'hanzo' => [
        'endpoint'=> (string) $secureParams->hanzo->endpoint
    ],
    'internalApiSecret' => [
		'key' => (string) $secureParams->appsec->internalApiSecret,
	],
    'stripe' => [
        'secretKey' => (string) $secureParams->stripe->sk,
        'publishKey' => (string) $secureParams->stripe->pk,
        'webhookSecret' => (string) $secureParams->stripe->webhookSecret,
		'endpoint' => (string) $secureParams->stripe->endpoint,
        'suspension_coupon' => 'DEACTIVATED',
    ],
	'tinycert' => [
        'email' => (string) $secureParams->tinycert->email,
        'password' => (string) $secureParams->tinycert->password,
		'key' => (string) $secureParams->tinycert->key,
		'ca_id' => (string) $secureParams->tinycert->ca_id,
    ],
    'opswat' => [
        'key' => (string) $secureParams->opswat->key,
    ],
    // constants
    'meta_tags' => $meta_tags,
    'private_navigation' => $private_navigation,
]);
