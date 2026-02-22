<?php
/**
 * Autenticazione per il Log Viewer e la piattaforma Admin.
 *
 * Gestisce login, sessioni e verifica dei permessi utente
 * utilizzando la tabella admin_users nel database.
 */

declare(strict_types=1);

namespace LogViewer;

use PDO;

class Auth
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Avvia la sessione se non ancora attiva.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('msclog_session');
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict',
                'gc_maxlifetime' => 3600,
            ]);
        }
    }

    /**
     * Verifica le credenziali e crea la sessione utente.
     *
     * @return array|null Dati utente se login ok, null altrimenti
     */
    public function login(string $username, string $password): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, password_hash, display_name, role, is_active
             FROM admin_users
             WHERE username = :username AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        // Aggiorna last_login
        $update = $this->db->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = :id');
        $update->execute([':id' => $user['id']]);

        // Crea sessione
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['role'] = $user['role'];

        return $user;
    }

    /**
     * Verifica se l'utente è autenticato.
     */
    public static function isLoggedIn(): bool
    {
        self::startSession();
        return isset($_SESSION['user_id']);
    }

    /**
     * Verifica se l'utente corrente è admin.
     */
    public static function isAdmin(): bool
    {
        self::startSession();
        return ($_SESSION['role'] ?? '') === 'admin';
    }

    /**
     * Restituisce i dati dell'utente corrente dalla sessione.
     */
    public static function getCurrentUser(): ?array
    {
        self::startSession();
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id'           => $_SESSION['user_id'],
            'username'     => $_SESSION['username'],
            'display_name' => $_SESSION['display_name'],
            'role'         => $_SESSION['role'],
        ];
    }

    /**
     * Distrugge la sessione (logout).
     */
    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Richiede autenticazione. Redirige al login se non autenticato.
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Richiede ruolo admin. Mostra errore 403 se non autorizzato.
     */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo 'Accesso negato: permessi insufficienti.';
            exit;
        }
    }
}
