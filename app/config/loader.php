<?php

use Phalcon\Loader;

$loader = new Loader();

/**
 * Register Namespaces
 */
$loader->registerNamespaces([
    'AirLook\Models'  => APP_PATH . '/common/models/',
    'AirLook\Library' => APP_PATH . '/common/library/',
    'AirLook\AirData' => APP_PATH . '/common/airdata/',
]);

/**
 * Register module classes
 */
$loader->registerClasses([
    'AirLook\Modules\Frontend\Module' => APP_PATH . '/modules/frontend/Module.php',
    'AirLook\Modules\Backend\Module' => APP_PATH . '/modules/backend/Module.php',
    'AirLook\Modules\Cli\Module'      => APP_PATH . '/modules/cli/Module.php'
]);

$loader->register();
