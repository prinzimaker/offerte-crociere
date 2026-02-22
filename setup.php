<?php
/**
 * Script di setup iniziale del progetto MSC API Proxy.
 *
 * Eseguire da linea di comando: php setup.php
 *
 * Operazioni:
 * 1. Verifica la connessione al database
 * 2. Crea la tabella api_logs se non esiste
 * 3. Crea la tabella admin_users se non esiste
 * 4. Crea l'utente admin iniziale (se non esiste)
 *
 * ATTENZIONE: eseguire una sola volta o in sicurezza (le query usano IF NOT EXISTS).
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    die('Questo script deve essere eseguito da linea di comando.');
}

echo "=== MSC API Proxy — Setup ===\n\n";

// Carica la configurazione
$config = require __DIR__ . '/shared/Config.php';

// 1. Connessione al database
echo "1. Connessione al database...\n";
try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['db']['host'],
        $config['db']['dbname'],
        $config['db']['charset']
    );
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "   OK — Connesso a {$config['db']['host']}/{$config['db']['dbname']}\n\n";
} catch (PDOException $e) {
    echo "   ERRORE: {$e->getMessage()}\n";
    echo "   Verifica le credenziali in shared/Config.php\n";
    exit(1);
}

// 2. Creazione tabella api_logs
echo "2. Creazione tabella api_logs...\n";
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `api_logs` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `function_name` VARCHAR(255) NOT NULL,
        `data` LONGTEXT NOT NULL,
        `ip_address` VARCHAR(45) NULL,
        `http_method` VARCHAR(10) DEFAULT 'POST',
        `headers` TEXT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_function` (`function_name`),
        INDEX `idx_created_at` (`created_at`),
        INDEX `idx_function_date` (`function_name`, `created_at`),
        INDEX `idx_ip` (`ip_address`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "   OK\n\n";

// 3. Creazione tabella admin_users
echo "3. Creazione tabella admin_users...\n";
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `admin_users` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(100) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `display_name` VARCHAR(255) NULL,
        `role` ENUM('admin', 'viewer') NOT NULL DEFAULT 'viewer',
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `last_login` DATETIME NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_username` (`username`),
        INDEX `idx_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "   OK\n\n";

// 4. Creazione utente admin iniziale
echo "4. Creazione utente admin iniziale...\n";
$stmt = $pdo->prepare('SELECT id FROM admin_users WHERE username = :username');
$stmt->execute([':username' => 'admin']);

if ($stmt->fetch()) {
    echo "   SKIP — L'utente 'admin' esiste gia'\n\n";
} else {
    // Chiedi la password
    echo "   Inserisci la password per l'utente admin: ";
    if (function_exists('readline')) {
        $adminPassword = readline();
    } else {
        // Fallback
        $adminPassword = trim(fgets(STDIN));
    }

    if (empty($adminPassword) || strlen($adminPassword) < 6) {
        echo "   La password deve avere almeno 6 caratteri. Uso password di default: 'admin123'\n";
        $adminPassword = 'admin123';
    }

    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $insert = $pdo->prepare(
        'INSERT INTO admin_users (username, password_hash, display_name, role, is_active)
         VALUES (:username, :password_hash, :display_name, :role, 1)'
    );
    $insert->execute([
        ':username'      => 'admin',
        ':password_hash' => $hash,
        ':display_name'  => 'Amministratore',
        ':role'          => 'admin',
    ]);
    echo "   OK — Utente admin creato\n\n";
}

// 5. Verifica
echo "5. Verifica finale...\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "   Tabelle presenti: " . implode(', ', $tables) . "\n";
$userCount = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
echo "   Utenti admin: {$userCount}\n\n";

echo "=== Setup completato con successo ===\n";
echo "\nProssimi passi:\n";
echo "1. Configura i VirtualHost Apache per mscapi.fttn.it e msclog.fttn.it\n";
echo "2. Aggiorna i token e le credenziali in shared/Config.php\n";
echo "3. Testa l'API con: curl -X POST -H 'Authorization: Bearer {token}' -H 'Content-Type: application/json' -d '{\"function\":\"test\",\"data\":{}}' https://mscapi.fttn.it/log\n";
