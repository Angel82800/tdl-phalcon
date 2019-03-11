<?php

$loader = new \Phalcon\Loader();

/*
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerNamespaces(array(
    'Thrust\Models'         => $config->application->modelsDir,
    'Thrust\Controllers'    => $config->application->controllersDir,
    'Thrust\Forms'          => $config->application->formsDir,
    'Thrust\Forms\Element'  => $config->application->elementDir,
    'Thrust\Validators'     => $config->application->validatorsDir,
    'Thrust\Helpers'        => $config->application->helpersDir,
    'Thrust'                => $config->application->libraryDir,
    'Phalcon'               => __DIR__ . '../../_vendor/phalcon/incubator/Library/Phalcon',
));

$loader->register();

// Use composer autoloader to load vendor classes
require_once __DIR__ . '/../../_platform/vendor/autoload.php';
