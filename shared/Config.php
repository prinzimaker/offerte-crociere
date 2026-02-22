<?php
/**
 * Configurazione condivisa per il progetto MSC API Proxy.
 *
 * Questo file contiene tutte le credenziali e i parametri di configurazione
 * per i componenti API Server e Log Viewer.
 *
 * IMPORTANTE: questo file NON deve essere nella document root dei VirtualHost.
 * Deve essere accessibile solo via PHP, mai via browser.
 */

return [
    'db' => [
        'host'     => getenv('MSCAPI_DB_HOST') ?: 'localhost',
        'dbname'   => getenv('MSCAPI_DB_NAME') ?: 'mscapi',
        'user'     => getenv('MSCAPI_DB_USER') ?: 'mscapi_user',
        'password' => getenv('MSCAPI_DB_PASS') ?: 'DA_DEFINIRE',
        'charset'  => 'utf8mb4',
    ],
    'api' => [
        'token' => getenv('MSCAPI_API_TOKEN') ?: 'DA_DEFINIRE',
    ],
    'app' => [
        'base_path' => dirname(__DIR__),
        'log_file'  => getenv('MSCAPI_LOG_FILE') ?: '/var/log/mscapi/error.log',
        'per_page'  => 50,
    ],
];
