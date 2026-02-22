<?php
/**
 * Helper per risposte JSON standardizzate dell'API Server.
 *
 * Gestisce la formattazione uniforme di tutte le risposte HTTP,
 * sia di successo che di errore, in formato JSON.
 */

declare(strict_types=1);

namespace Api;

class Response
{
    /**
     * Invia una risposta JSON con il codice HTTP specificato.
     *
     * @param int   $statusCode Codice HTTP (200, 400, 401, 500, ecc.)
     * @param array $data       Dati da includere nella risposta
     */
    public static function json(int $statusCode, array $data): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Risposta di successo.
     */
    public static function success(string $message, array $extra = []): never
    {
        self::json(200, array_merge([
            'status'  => 'ok',
            'message' => $message,
        ], $extra));
    }

    /**
     * Risposta di errore.
     */
    public static function error(int $statusCode, string $message): never
    {
        self::json($statusCode, [
            'status'  => 'error',
            'message' => $message,
        ]);
    }
}
