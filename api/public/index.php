<?php

try {

   	/**
	 * Bootstrap
	 */
   	include '../bootstrap.php';

   	/**
	 * Handle the request
	 */
	$app = new Phalcon\Mvc\Micro();
	$app->setDI($di);
	$app->handle();

} catch (Exception $e) {
	echo $e->getMessage(), '<br>';
	echo nl2br(htmlentities($e->getTraceAsString()));
}