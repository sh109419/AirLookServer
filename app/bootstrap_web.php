<?php
declare(strict_types=1);

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Application;

error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

try {
    /**
     *  加载 vendor 目录的自动加载
     */
    require_once BASE_PATH . '/vendor/autoload.php';

    /**
     * Load .env configurations
     */
    Dotenv\Dotenv::createImmutable(BASE_PATH)->load();

    /**
     * The FactoryDefault Dependency Injector automatically registers the services that
     * provide a full stack framework. These default services can be overidden with custom ones.
     */
    $di = new FactoryDefault();

    /**
     * Include general services
     */
    require APP_PATH . '/config/services.php';

    /**
     * Include web environment specific services
     */
    require APP_PATH . '/config/services_web.php';

    /**
     * Get config service for use in inline setup below
     */
    $config = $di->getConfig();

    /**
     * Include Autoloader
     */
    include APP_PATH . '/config/loader.php';

    /**
     * Handle the request
     */
    $application = new Application($di);

    /**
     * Register application modules
     */
    $application->registerModules([
        // 为前台提供API
        'api' => ['className' => 'AirLook\Modules\Frontend\Module'],
        // 后台管理模块
        'admin' => ['className' => 'AirLook\Modules\Backend\Module'],
    ]);

    /**
     * Include routes
     */
    require APP_PATH . '/config/routes.php';

    echo $application->handle($_GET['_url'] ?? '/')->getContent();
    //var_dump($router->getMatches());
    //var_dump($router->getMatchedRoute());
} catch (\Exception $e) {
    echo $e->getMessage() . '<br>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}
