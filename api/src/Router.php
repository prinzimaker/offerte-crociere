<?php
/**
 * Router minimale per l'API Server.
 *
 * Gestisce il routing delle richieste HTTP verso i controller appropriati.
 * Supporta route con metodo HTTP e path.
 */

declare(strict_types=1);

namespace Api;

class Router
{
    /** @var array<string, array<string, callable>> Route registrate [method => [path => handler]] */
    private array $routes = [];

    /**
     * Registra una route POST.
     */
    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * Registra una route GET.
     */
    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /**
     * Esegue il routing della richiesta corrente.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getCleanUri();

        // Gestione preflight CORS (OPTIONS)
        if ($method === 'OPTIONS') {
            $this->sendCorsHeaders();
            http_response_code(204);
            exit;
        }

        $this->sendCorsHeaders();

        if (!isset($this->routes[$method])) {
            Response::error(405, 'Metodo non consentito');
        }

        foreach ($this->routes[$method] as $path => $handler) {
            if ($uri === $path) {
                $handler();
                return;
            }
        }

        Response::error(404, 'Endpoint non trovato');
    }

    /**
     * Pulisce l'URI rimuovendo query string e normalizzando.
     */
    private function getCleanUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        $uri = rtrim($uri, '/') ?: '/';

        return $uri;
    }

    /**
     * Invia gli header CORS per le richieste cross-origin.
     */
    private function sendCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }
}
