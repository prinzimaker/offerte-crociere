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
        'dbname'   => getenv('MSCAPI_DB_NAME') ?: 'oc_log',
        'user'     => getenv('MSCAPI_DB_USER') ?: 'oc_api_user',
        'password' => getenv('MSCAPI_DB_PASS') ?: '0fFcR0c2026',
        'charset'  => 'utf8mb4',
    ],
    'api' => [
        'token' => getenv('MSCAPI_API_TOKEN') ?: 'octk_A7d0F39e-8c1b-Ba2b-9f5e-1234567890ab',
    ],
    'app' => [
        'base_path' => dirname(__DIR__),
        'log_file'  => getenv('MSCAPI_LOG_FILE') ?: '
        tail ',
        'per_page'  => 50,
    ],
];
