<?php
/**
 * Entry point dell'API Server (mscapi.fttn.it).
 *
 * Riceve le chiamate da OCM e le registra nel database.
 * Endpoint principale: POST /log
 *
 * Ogni richiesta deve contenere un Bearer token valido nell'header Authorization.
 */

declare(strict_types=1);

// Carica le classi
require_once __DIR__ . '/../../shared/Database.php';
require_once __DIR__ . '/../src/Response.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/Logger.php';

use Api\Response;
use Api\Auth;
use Api\Router;
use Api\Logger;
use Shared\Database;

// Carica la configurazione
$config = require __DIR__ . '/../../shared/Config.php';

// Inizializza il database
Database::init($config['db']);

// Crea il router
$router = new Router();

// --- Route: Health check ---
$router->get('/health', function () {
    Response::success('API Server attivo');
});

// --- Route: POST /log ---
$router->post('/log', function () use ($config) {
    // 1. Autenticazione
    $auth = new Auth($config['api']['token']);
    if (!$auth->validateRequest()) {
        Response::error(401, 'Token di autenticazione mancante o non valido');
    }

    // 2. Leggi il body della richiesta
    $rawBody = file_get_contents('php://input');
    if (empty($rawBody)) {
        Response::error(400, 'Body della richiesta vuoto');
    }

    $body = json_decode($rawBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        Response::error(400, 'JSON non valido: ' . json_last_error_msg());
    }

    // 3. Validazione parametri obbligatori
    if (empty($body['function']) || !is_string($body['function'])) {
        Response::error(400, 'Parametro "function" mancante o non valido');
    }

    if (!isset($body['data'])) {
        Response::error(400, 'Parametro "data" mancante');
    }

    // 4. Prepara i dati
    $functionName = trim($body['function']);
    $data = is_string($body['data']) ? $body['data'] : json_encode($body['data'], JSON_UNESCAPED_UNICODE);
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'POST';

    // Cattura gli header rilevanti (esclusi quelli sensibili)
    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (str_starts_with($key, 'HTTP_') && $key !== 'HTTP_AUTHORIZATION') {
            $headerName = str_replace('_', '-', substr($key, 5));
            $headers[$headerName] = $value;
        }
    }
    $headersJson = json_encode($headers, JSON_UNESCAPED_UNICODE);

    // 5. Salva nel database
    try {
        $db = Database::getConnection();
        $logger = new Logger($db);
        $logId = $logger->log($functionName, $data, $ipAddress, $httpMethod, $headersJson);

        Response::success('Logged successfully', ['log_id' => $logId]);
    } catch (\Throwable $e) {
        // Log dell'errore su file
        Logger::logError(
            "Errore salvataggio log: {$e->getMessage()}",
            $config['app']['log_file']
        );

        Response::error(500, 'Errore interno del server');
    }
});

// Dispatch della richiesta
$router->dispatch();
