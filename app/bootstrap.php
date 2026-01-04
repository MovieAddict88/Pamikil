<?php
declare(strict_types=1);

$__configFile = __DIR__ . '/config/config.php';
$__configExampleFile = __DIR__ . '/config/config.example.php';

$config = is_file($__configFile) ? require $__configFile : require $__configExampleFile;
$GLOBALS['APP_CONFIG'] = $config;

date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

$debug = (bool)($config['app']['debug'] ?? false);
if ($debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);

    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    ini_set('log_errors', '1');
    ini_set('error_log', $logDir . '/php-error.log');
}

require __DIR__ . '/lib/Database.php';
require __DIR__ . '/lib/Security.php';
require __DIR__ . '/lib/Csrf.php';
require __DIR__ . '/lib/Validator.php';
require __DIR__ . '/lib/Auth.php';
require __DIR__ . '/lib/ActivityEngine.php';
require __DIR__ . '/lib/helpers.php';

Security::startSession($config);

$db = Database::instance($config['db']);
$GLOBALS['DB'] = $db;

$auth = new Auth($db, $config);
$GLOBALS['AUTH'] = $auth;

$auth->enforceSessionTimeout();
