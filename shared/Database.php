<?php
/**
 * Classe Database condivisa — gestisce la connessione PDO al database MySQL/MariaDB.
 *
 * Utilizza il pattern Singleton per evitare connessioni multiple.
 * Tutte le query devono usare prepared statements.
 */

declare(strict_types=1);

namespace Shared;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Impedisce l'istanziazione diretta.
     */
    private function __construct() {}

    /**
     * Inizializza la configurazione del database.
     */
    public static function init(array $dbConfig): void
    {
        self::$config = $dbConfig;
        self::$instance = null;
    }

    /**
     * Restituisce l'istanza PDO (Singleton).
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            if (empty(self::$config)) {
                throw new RuntimeException('Database non inizializzato. Chiamare Database::init() prima.');
            }

            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::$config['host'],
                self::$config['dbname'],
                self::$config['charset']
            );

            try {
                self::$instance = new PDO($dsn, self::$config['user'], self::$config['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                throw new RuntimeException('Errore connessione database: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * Chiude la connessione.
     */
    public static function close(): void
    {
        self::$instance = null;
    }
}
