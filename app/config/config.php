<?php
/*
 * Modified: prepend directory path of current file, because of this file own different ENV under between Apache and command line.
 * NOTE: please remove this comment.
 */
defined('BASE_PATH') || define('BASE_PATH', realpath(dirname(__FILE__) . '/../..'));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');

return new \Phalcon\Config([
    'version' => '1.3',

    'aqicntoken' => $_SERVER['AQICN_TOKEN'],

    'database'    => [
        'adapter'  => $_SERVER['DB_ADAPTER'],
        'host'     => $_SERVER['DB_HOST'],
        'port'     => $_SERVER['DB_PORT'],
        'username' => $_SERVER['DB_USERNAME'],
        'password' => $_SERVER['DB_PASSWORD'],
        'dbname'   => $_SERVER['DB_NAME'],
    ],

    'mail'        => [
        'fromName'  => $_SERVER['MAIL_FROM_NAME'],
        'fromEmail' => $_SERVER['MAIL_FROM_EMAIL'],
        'smtp'      => [
            'server'   => $_SERVER['MAIL_SMTP_SERVER'],
            'port'     => $_SERVER['MAIL_SMTP_PORT'],
            'security' => $_SERVER['MAIL_SMTP_SECURITY'],
            'username' => $_SERVER['MAIL_SMTP_USERNAME'],
            'password' => $_SERVER['MAIL_SMTP_PASSWORD'],
        ],
    ],

    'application' => [
        'appDir'         => APP_PATH . '/',
        'modelsDir'      => APP_PATH . '/common/models/',
        'migrationsDir'  => APP_PATH . '/migrations/',
        'cacheDir'       => BASE_PATH . '/cache/',
        'baseUri'        => '/AirLook/',
    ],

    /**
     * if true, then we print a new line at the end of each CLI execution
     *
     * If we dont print a new line,
     * then the next command prompt will be placed directly on the left of the output
     * and it is less readable.
     *
     * You can disable this behaviour if the output of your application needs to don't have a new line at end
     */
    'printNewLine' => true
]);
