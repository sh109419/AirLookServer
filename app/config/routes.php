<?php

$router = $di->getRouter();

foreach ($application->getModules() as $key => $module) {
    $namespace = preg_replace('/Module$/', 'Controllers', $module['className']);

    $router->add('/'.$key.'/:params', [
        'namespace' => $namespace,
        'module' => $key,
        'controller' => 'index',
        'action' => 'index',
        'params' => 1
    ])->setName($key);

    $router->add('/'.$key.'/:controller/:params', [
        'namespace' => $namespace,
        'module' => 'api',
        'controller' => 1,
        'action' => 'index',
        'params' => 2
    ]);

    $router->add('/'.$key.'/:controller/:action/:params', [
        'namespace' => $namespace,
        'module' => $key,
        'controller' => 1,
        'action' => 2,
        'params' => 3
    ]);
}


// speical routers
/*
 * http://localhost/airlook/api/airdata/3303 未能匹配路由$router->add('/'.$key.'/:controller/:params', [
 * 路由的匹配与路由记录的顺序有关，原因未知
 */
/*
// default module is 'api'
//localhost/airlook/airdata/3303
$router->add('/:controller/:params', array(
    'namespace' => 'AirLook\Modules\Frontend\Controllers',
    'module' => 'api',
    'controller' => 1,
    'action' => 'index',
    'params' => 2
));

//localhost/airlook/api/airdata/3303
$router->add('/api/:controller/:params', array(
    'namespace' => 'AirLook\Modules\Frontend\Controllers',
    'module' => 'api',
    'controller' => 1,
    'action' => 'index',
    'params' => 2
));
*/

