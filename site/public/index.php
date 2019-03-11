<?php

try {

  /**
   * Define some useful constants
   */
  define('BASE_DIR', dirname(__DIR__) . '/..');
  define('APP_DIR', BASE_DIR . '/site');
  define('PUBLIC_DIR', APP_DIR . '/public');

  /**
   * Load constants
   */
  include APP_DIR . '/config/constants.php';

	/**
	 * Read the configuration
	 */
	$config = include APP_DIR . '/config/config.php';

	/**
	 * Read auto-loader
	 */
	include APP_DIR . '/config/loader.php';

	/**
	 * Read services
	 */
	include APP_DIR . '/config/services.php';

	/**
	 * Handle the request
	 */
	$application = new \Phalcon\Mvc\Application($di);

	echo $application->handle()->getContent();

} catch (Exception $e) {
	echo $e->getMessage(), '<br>';
	echo nl2br(htmlentities($e->getTraceAsString()));
}
