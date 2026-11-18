<?php

declare(strict_types=1);

/*
 * Préparation commune au site et aux scripts : chargement des classes, configuration, fuseau horaire.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('Europe/Paris');
mb_internal_encoding('UTF-8');

$config = require dirname(__DIR__) . '/config/config.php';

error_reporting(E_ALL);
ini_set('display_errors', $config['env'] === 'dev' ? '1' : '0');
ini_set('log_errors', '1');
if (PHP_SAPI !== 'cli') {
    $logDir = dirname(__DIR__) . '/var/log';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }
    ini_set('error_log', $logDir . '/php-error.log');
}

return $config;
