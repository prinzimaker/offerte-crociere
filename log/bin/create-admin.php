#!/usr/bin/env php
<?php
/**
 * Crea un utente admin per il Log Viewer.
 *
 * Uso:
 *   php create-admin.php
 *   php create-admin.php --username=admin --password=secret --name="Amministratore"
 */

declare(strict_types=1);

// Carica le dipendenze
require_once __DIR__ . '/../../shared/Database.php';
require_once __DIR__ . '/../src/AdminManager.php';

use Shared\Database;
use LogViewer\AdminManager;

$config = require __DIR__ . '/../../shared/Config.php';

// Parsing argomenti da riga di comando
$opts = getopt('', ['username:', 'password:', 'name:']);

// Se mancano argomenti, chiedi in modo interattivo
if (empty($opts['username'])) {
    echo "Username: ";
    $opts['username'] = trim(fgets(STDIN));
}

if (empty($opts['password'])) {
    // Disabilita echo per la password se possibile
    if (function_exists('readline') === false && PHP_OS_FAMILY !== 'Windows') {
        echo "Password: ";
        system('stty -echo');
        $opts['password'] = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    } else {
        echo "Password: ";
        $opts['password'] = trim(fgets(STDIN));
    }
}

if (empty($opts['name'])) {
    echo "Nome visualizzato (invio per usare lo username): ";
    $opts['name'] = trim(fgets(STDIN));
    if ($opts['name'] === '') {
        $opts['name'] = $opts['username'];
    }
}

// Validazione
if (strlen($opts['username']) < 2) {
    fwrite(STDERR, "Errore: lo username deve avere almeno 2 caratteri.\n");
    exit(1);
}

if (strlen($opts['password']) < 6) {
    fwrite(STDERR, "Errore: la password deve avere almeno 6 caratteri.\n");
    exit(1);
}

// Connessione al database e creazione utente
try {
    Database::init($config['db']);
    $db = Database::getConnection();

    $manager = new AdminManager($db);
    $id = $manager->createUser($opts['username'], $opts['password'], $opts['name'], 'admin');

    echo "\nUtente admin creato con successo!\n";
    echo "  ID:       {$id}\n";
    echo "  Username: {$opts['username']}\n";
    echo "  Ruolo:    admin\n";
} catch (\RuntimeException $e) {
    fwrite(STDERR, "Errore: {$e->getMessage()}\n");
    exit(1);
} catch (\PDOException $e) {
    fwrite(STDERR, "Errore database: {$e->getMessage()}\n");
    exit(1);
}
