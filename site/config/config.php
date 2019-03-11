<?php

use Phalcon\Config;
use Phalcon\Logger;
use Phalcon\Config\Adapter\Ini;

// $secureXml = file_get_contents('/etc/secure.xml');
// $secureParams = simplexml_load_string($secureXml);
// $environmentIni = new Ini("/etc/php.d/environment.ini");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('xdebug.var_display_max_depth', 5);

$params = [
    'database' => [
        'oltp_read' => [
            'adapter'  => 'Mysql',
            'host'     => 'localhost',
            'dbname'   => 'todyl_db',
            'username' => 'root',
            'password' => 'root',
        ],
        'oltp_write' => [
            'adapter'  => 'Mysql',
            'host'     => 'localhost',
            'dbname'   => 'todyl_db',
            'username' => 'root',
            'password' => 'root',
        ],
        'ioc_read' => [
            'adapter'  => 'Mysql',
            'host'     => 'localhost',
            'dbname'   => 'IOC_V1_DEV',
            'username' => 'root',
            'password' => 'root',
        ],
        'ioc_write' => [
            'adapter'  => 'Mysql',
            'host'     => 'localhost',
            'dbname'   => 'IOC_V1_DEV',
            'username' => 'root',
            'password' => 'root',
        ],
        'agentFiles' => [
            'adapter'  => 'Mysql',
            'host'     => 'localhost',
            'dbname'   => 'IOC_V1_DEV',
            'username' => 'root',
            'password' => 'root',
        ],
    ],

    'memcache' => [
        'host' => 'localhost',
        'port' => 11211
    ],

    'aws' => [
        'ses' => [
            'endpoint' => 'email.us-east-1.amazonaws.com',
            'access_key' => 'AKIAIPVJ7V4OVFPQDB5A',
            'access_secret' => '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'
        ],
        's3' => [
            'access_key' => 'AKIAIPVJ7V4OVFPQDB5A',
            'access_secret' => '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',
            'crash_bucket' => 'test_bucket',
        ],
    ],

    'leonidas' => [
        'host' => 'leonidas.todyl.com',
        'port' => 8000,
        'username' => 'thrust',
        'password' => 'uK6lep3W8binSEbFnQoz'
    ],

    'appsec' => [
        'cryptSalt' => 'eEAfR|_&G&f,+vU]:jFr!!A&+71w1Ms9~8_4L!<@[N@DyaIP_2My|:+.u>/6m,$D',
        'recaptchaSecret' => '6LdLSBYTAAAAALRftK8xgUlKZ-waUhOyCdQE1wRi',
        'internalApiSecret' => 'None',
    ],

    'hanzo' => [
        'endpoint' => 'http://localhost:8888'
    ],

];

$secureParams = new stdClass();
foreach ($params as $key => $value) {
    if (is_array($value)) {
        $temp = new stdClass();
        foreach ($value as $k => $v) {
            $temp->$k = $v;
        }
        $secureParams->$key = $temp;
    } else {
        $secureParams->$key = $value;
    }
}

return new Config([
    'database' => [
        'adapter'  => 'Mysql',
        'host'     => 'localhost',
        'dbname'   => 'todyl_db',
        'username' => 'root',
        'password' => 'root',
        'oltp-read' => [
            'adapter'  => (string) $secureParams->database->oltp_read['adapter'],
            'host'     => (string) $secureParams->database->oltp_read['host'],
            'dbname'   => (string) $secureParams->database->oltp_read['dbname'],
            'username' => (string) $secureParams->database->oltp_read['username'],
            'password' => (string) $secureParams->database->oltp_read['password'],
        ],
        'oltp-write' => [
            'adapter'  => (string) $secureParams->database->oltp_write['adapter'],
            'host'     => (string) $secureParams->database->oltp_write['host'],
            'dbname'   => (string) $secureParams->database->oltp_write['dbname'],
            'username' => (string) $secureParams->database->oltp_write['username'],
            'password' => (string) $secureParams->database->oltp_write['password'],
        ],
        'ioc-read' => [
            'adapter'  => (string) $secureParams->database->ioc_read['adapter'],
            'host'     => (string) $secureParams->database->ioc_read['host'],
            'dbname'   => (string) $secureParams->database->ioc_read['dbname'],
            'username' => (string) $secureParams->database->ioc_read['username'],
            'password' => (string) $secureParams->database->ioc_read['password'],
        ],
        'ioc-write' => [
            'adapter'  => (string) $secureParams->database->ioc_write['adapter'],
            'host'     => (string) $secureParams->database->ioc_write['host'],
            'dbname'   => (string) $secureParams->database->ioc_write['dbname'],
            'username' => (string) $secureParams->database->ioc_write['username'],
            'password' => (string) $secureParams->database->ioc_write['password'],
        ],
        'agentFiles-read' => [
            'adapter'  => (string) $secureParams->database->agentFiles['adapter'],
            'host'     => (string) $secureParams->database->agentFiles['host'],
            'dbname'   => (string) $secureParams->database->agentFiles['dbname'],
            'username' => (string) $secureParams->database->agentFiles['username'],
            'password' => (string) $secureParams->database->agentFiles['password'],
        ],
        'agentFiles-write' => [
            'adapter'  => (string) $secureParams->database->agentFiles['adapter'],
            'host'     => (string) $secureParams->database->agentFiles['host'],
            'dbname'   => (string) $secureParams->database->agentFiles['dbname'],
            'username' => (string) $secureParams->database->agentFiles['username'],
            'password' => (string) $secureParams->database->agentFiles['password'],
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
        'controllersDir' => APP_DIR . '/controllers/',
        'modelsDir'      => APP_DIR . '/models/',
        'formsDir'       => APP_DIR . '/forms/',
        'elementDir'     => APP_DIR . '/forms/element/',
        'helpersDir'     => APP_DIR . '/helpers/',
        'viewsDir'       => APP_DIR . '/views/',
        'libraryDir'     => APP_DIR . '/library/',
        'pluginsDir'     => APP_DIR . '/plugins/',
        'cacheDir'       => APP_DIR . '/cache/',
        'validatorsDir'  => APP_DIR . '/validators/',
        'downloadDir'    => APP_DIR . '/installer/',
        'baseUri'        => '/',
        'publicUrl'      => 'todyl.com',
        'cryptSalt'      => (string) $secureParams->appsec->cryptSalt,

        'uploadDir'      => APP_DIR . '/public/ct-upld/',
        'uploadUrl'      => '/ct-upld/',
    ],
    'mail' => [
        'fromName'  => 'Todyl',
        'fromEmail' => 'no-reply@todyl.com'
    ],
    'awsSes' => [
        'region'    => 'us-east-1',
        'key'       => (string) $secureParams->aws->ses['access_key'],
        'secret'    => (string) $secureParams->aws->ses['access_secret'],
        'interval'  => 75000,
    ],
    'awsS3' => [
        'region'     => 'us-east-1',
        'endpoint'   => 's3.amazonaws.com',
        'key'        => (string) $secureParams->aws->s3['access_key'],
        'secret'     => (string) $secureParams->aws->s3['access_secret'],
        'crashBucket'=> (string) $secureParams->aws->s3['crash_bucket'],
    ],
    'logger' => [
        'path'     => '/var/log/httpd/',
        'format'   => '%date% [%type%] %message%',
        'date'     => 'D j H:i:s',
        'logLevel' => Logger::INFO,
        'filename' => 'app_log',
    ],
    'environment' => [
        'env' => 'dev',
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
        'secretKey' => 'sk_test_eBgGGkyoLtnP2xsMYh4noWiF',
        'publishKey' => 'pk_test_dlO4CiOVdAhV9Z3tOjphVogm',
        'webhookSecret' => 'whsec_r5NMwNyfcnjIfi1XtoUem9OevPvc7WtV',
        'suspension_coupon' => 'DEACTIVATED',
		// 'endpoint' => (string) $secureParams->stripe->endpoint,
    ],
	'tinycert' => [
        // 'email' => (string) $secureParams->tinycert->email,
        // 'password' => (string) $secureParams->tinycert->password,
        // 'key' => (string) $secureParams->tinycert->key,
        // 'ca_id' => (string) $secureParams->tinycert->ca_id,
    ],

    // constants
    'meta_tags' => $meta_tags,
    'private_navigation' => $private_navigation,
]);
