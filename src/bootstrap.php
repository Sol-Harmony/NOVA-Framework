<?php

/**
 * This project is licensed under the GNU LGPL v2.1.
 * You may use, modify, and distribute it under the terms of this license.
 * Modifications must remain open-source under the same license.
 *
 * Copyright (C) 2025 Hamzah Mansor
 **/

//shared start of index.php (website) and nova (terminal): autoloader, config, error handling

if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__));
}

require_once __DIR__ . '/core/Autoloader.php';
require_once __DIR__ . '/core/helpers.php';

$autoloader = new Autoloader(BASEPATH);     //loads the other classes automatically when they are used
$autoloader->trigger();

//debug is OFF unless config/config.ini (which is not in git) says "debug = 1" under [app].
//so a live server where nobody changed anything never shows error details to visitors
define('DEBUG', Config::bool('app.debug', false));

ini_set('display_errors', (DEBUG || PHP_SAPI === 'cli') ? '1' : '0');
ini_set('display_startup_errors', (DEBUG || PHP_SAPI === 'cli') ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Berlin');

//errors go to storage/logs (not readable from the web). the file is moved aside at 5 MB so it can't grow forever
$logDir = BASEPATH . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
if (is_dir($logDir) && is_writable($logDir)) {
    $logFile = $logDir . '/php-error.log';
    if (is_file($logFile) && filesize($logFile) > 5 * 1024 * 1024) {
        @rename($logFile, $logFile . '.1');
    }
    ini_set('error_log', $logFile);
}

//fatal errors (out of memory, parse errors) can't be caught with try/catch, they would show a blank page
if (PHP_SAPI !== 'cli') {
    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true) || headers_sent()) {
            return;
        }
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error 500</title></head><body><h1>Error 500</h1><p>Something went wrong.</p>';
        if (DEBUG) {
            echo '<pre>' . htmlspecialchars($error['message'] . ' in ' . $error['file'] . ':' . $error['line']) . '</pre>';
        }
        echo '</body></html>';
    });
}
