<?php
/**
 * Logica di salvataggio dei log API nel database.
 *
 * Gestisce l'inserimento delle chiamate API nella tabella api_logs,
 * con validazione dei dati e gestione degli errori.
 */

declare(strict_types=1);

namespace Api;

use PDO;
use PDOException;

class Logger
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Salva una chiamata API nel database.
     *
     * @param string $functionName Nome della funzione/operazione
     * @param string $data         Dati JSON della richiesta
     * @param string $ipAddress    IP del chiamante
     * @param string $httpMethod   Metodo HTTP (POST, GET, ecc.)
     * @param string $headers      Header della richiesta (JSON)
     *
     * @return int ID del log inserito
     */
    public function log(
        string $functionName,
        string $data,
        string $ipAddress,
        string $httpMethod,
        string $headers
    ): int {
        $sql = 'INSERT INTO `api_logs` (`function_name`, `data`, `ip_address`, `http_method`, `headers`, `created_at`)
                VALUES (:function_name, :data, :ip_address, :http_method, :headers, NOW())';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':function_name' => $functionName,
            ':data'          => $data,
            ':ip_address'    => $ipAddress,
            ':http_method'   => $httpMethod,
            ':headers'       => $headers,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Scrive un errore nel file di log dell'applicazione.
     */
    public static function logError(string $message, string $logFile): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] ERROR: {$message}" . PHP_EOL;

        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
