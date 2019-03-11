<?php

use Phalcon\Di;
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Dispatcher as PhDispatcher;
use Phalcon\Mvc\View;
use Phalcon\Crypt;
use Phalcon\Logger\Adapter\File as FileLogger;
use Phalcon\Logger\Formatter\Line as FormatterLine;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Dispatcher\Exception as DispatchException;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\Model\Metadata\Files as MetaDataAdapter;
use Phalcon\Flash\Direct as Flash;
use Thrust\Auth\Auth;
use Thrust\Acl\Acl;
use Thrust\Mail\Mail;
use Thrust\S3\S3;
use Thrust\Phalcon\TVolt;
use Thrust\Phalcon\TTags;
use Phalcon\Cache\Frontend\Data as FrontendData;
use Phalcon\Cache\Backend\Memcache as BackendMemcache;

/*
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */
$di = new FactoryDefault();

/*
 * Register the global configuration as config
 */
$di->set('config', $config);

$loader = new Phalcon\Loader();
$loader->registerNamespaces([
    'Phalcon' => '/../../_platform/vendor/phalcon/incubator/Library/Phalcon'
]);

$loader->register();

/*
 * The URL component is used to generate all kind of urls in the application
 */
$di->set('url', function () use ($config) {
    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);

    return $url;
}, true);

$di->set('tag', function () {
    return new TTags();
}, true);

/*
 * Setting up the view component
 */
$di->set('view', function () use ($config) {
    $view = new View();
    $view->setViewsDir($config->application->viewsDir);
    $view->registerEngines(array(
        '.volt' => function ($view, $di) use ($config) {
            $volt = new TVolt($view, $di, $config);

            $volt->setOptions(array(
                'compiledPath'      => $config->application->cacheDir . 'volt/',
                'compiledSeparator' => '_'
            ));

            return $volt;
        }
    ));

    return $view;
}, true);

/*
 * Dynamically create DB connections based on the attributes from config.php
 */
foreach ($config->database as $key => $value) {
    $di->set($key, function () use ($value) {
       return new DbAdapter(array(
            'host'     => $value['host'],
            'username' => $value['username'],
            'password' => $value['password'],
            'dbname'   => $value['dbname'],
            'charset' => 'utf8',
        ));
    });
}

/*
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
if ($config->environment->env != 'dev') {
    $di->set('modelsMetadata', function () use ($config) {
        return new MetaDataAdapter(array(
            'metaDataDir' => $config->application->cacheDir . 'metaData/'
        ));
    });
}

/*
 * Start the session the first time some component request the session service
 */
$di->setShared('session', function () use ($config) {
    $session = new \Phalcon\Session\Adapter\Memcache(array(
        'uniqueId'   => 'thrust',
        'host'       => $config->memcache->host,
        'port'       => $config->memcache->port,
        'persistent' => true,
        'lifetime'   => 10800,
        'prefix'     => 'thrust_session_'
    ));

    $session->start();

    return $session;
});

/*
 * Setup the model caching service
 */
$di->set("modelsCache", function () use ($config) {
	// Cache data for one hour by default
    // This is overridden in model base
	$frontCache = new FrontendData(
		[
			"lifetime" => 3600,
		]
	);

	$cache = new BackendMemcache(
		$frontCache,
		[
			'host'       => $config->memcache->host,
			'port'       => $config->memcache->port,
		    'persistent' => true,
			'prefix'     => 'thrust_model_'
		]
	);

	return $cache;
});

/*
 * Form Manager
 */
$di->set('forms', function () {
    return new \Phalcon\Forms\Manager();
});

/*
 * Crypt service
 */
$di->set('crypt', function () use ($config) {
    $crypt = new Crypt();
    $crypt->setKey($config->application->cryptSalt);

    return $crypt;
});

/*
 * Dispatcher use a default namespace
 */
$di->set(
    'dispatcher',
    function () use ($di) {

        $evManager = $di->getShared('eventsManager');

        $evManager->attach(
            'dispatch:beforeException',
            function ($event, $dispatcher, $exception) {
                switch ($exception->getCode()) {
                    case Dispatcher::EXCEPTION_NO_DI:
                        //This is before the DI container is fully setup so try logging to app_log, if fails, log to error_log
                        try {
                            $logger = Di::getDefault()->getShared('logger');
                            $logger->error('DI Exception: ' . $exception->getMessage() . ' with trace: ' . (string) $exception->getTraceAsString());
                        } catch (Exception $e) {
                            error_log($exception, 0);
                        }

                        $dispatcher->forward(
                            array(
                                'controller' => 'error',
                                'action' => 'index',
                            )
                        );
                        return false;

                    case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                    case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                        // Log uncaught exception before redirecting
                        $logger = Di::getDefault()->getShared('logger');
                        $logger->error('Not found exception: ' . $exception->getMessage() . ' with trace: ' . (string) $exception->getTraceAsString());

                        $dispatcher->forward(
                            array(
                                'controller' => 'error',
                                'action' => 'route404',
                            )
                        );
                        return false;

                    default:
                        // Log uncaught exception before redirecting to generic error page
                        $logger = Di::getDefault()->getShared('logger');
                        $logger->error('Exception: ' . $exception->getMessage() . ' with trace: ' . (string) $exception->getTraceAsString());

                        $dispatcher->forward(
                            array(
                                'controller' => 'error',
                                'action' => 'index',
                            )
                        );
                        return false;
                }
            }
        );
        $dispatcher = new PhDispatcher();
        $dispatcher->setEventsManager($evManager);
        $dispatcher->setDefaultNamespace('Thrust\Controllers');

        return $dispatcher;
    },
    true
);

/*
 * Loading routes from the routes.php file
 */
$di->set('router', function () {
    return require __DIR__ . '/routes.php';
});

/*
 * Flash service with custom CSS classes
 */
$di->set('flash', function () {
    return new Flash(array(
        'error'   => 'alert alert-danger',
        'success' => 'alert alert-success',
        'notice'  => 'alert alert-info',
        'warning' => 'alert alert-warning'
    ));
});

/*
 * Custom authentication component
 */
$di->set('auth', function () {
    return new Auth();
});

/*
 * Mail service uses AmazonSES
 */
$di->set('mail', function () {
    return new Mail();
});

/*
 * AmazonS3 Service
 */
$di->set('S3', function () {
    return new S3();
});


/*
 * Access Control List
 */
$di->set('acl', function () {
    return new Acl();
});

/*
 * Logger service
 */
$di->set('logger', function ($filename = null, $format = null) use ($config) {

    $format = $format ?: $config->get('logger')->format;
    $filename = trim($filename ?: $config->get('logger')->filename, '\\/');
    $path = rtrim($config->get('logger')->path, '\\/') . DIRECTORY_SEPARATOR;

    $formatter = new FormatterLine($format, $config->get('logger')->date);
    $logger = new FileLogger($path . $filename);

    $logger->setFormatter($formatter);
    $logger->setLogLevel($config->get('logger')->logLevel);

    return $logger;
});
