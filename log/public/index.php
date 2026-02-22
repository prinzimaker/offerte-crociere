<?php
/**
 * Entry point del Log Viewer (msclog.fttn.it).
 *
 * Gestisce il routing di tutte le pagine:
 * - Login/Logout
 * - Dashboard
 * - Lista log con filtri e paginazione
 * - Dettaglio singolo log
 * - Statistiche
 * - Admin: gestione utenti
 * - Export CSV
 */

declare(strict_types=1);

// Carica le classi
require_once __DIR__ . '/../../shared/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/LogViewer.php';
require_once __DIR__ . '/../src/AdminManager.php';

use Shared\Database;
use LogViewer\Auth;
use LogViewer\LogViewer;
use LogViewer\AdminManager;

// Carica la configurazione
$config = require __DIR__ . '/../../shared/Config.php';

// Inizializza il database
Database::init($config['db']);
$db = Database::getConnection();

// Determina la route
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rtrim($uri, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

// Gestione messaggi flash
Auth::startSession();
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

/**
 * Helper per impostare un messaggio flash e redirigere.
 */
function flashRedirect(string $message, string $type, string $url): never
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: {$url}");
    exit;
}

/**
 * Renderizza un template dentro il layout.
 */
function render(string $template, array $vars = []): void
{
    global $flashMessage, $flashType;
    extract($vars);

    ob_start();
    require __DIR__ . "/../templates/{$template}.php";
    $content = ob_get_clean();

    require __DIR__ . '/../templates/layout.php';
}

// ============================================================
// ROUTING
// ============================================================

// --- Login ---
if ($uri === '/login') {
    if (Auth::isLoggedIn()) {
        header('Location: /');
        exit;
    }

    $error = '';
    $username = '';

    if ($method === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $auth = new Auth($db);
        $user = $auth->login($username, $password);

        if ($user) {
            header('Location: /');
            exit;
        }

        $error = 'Credenziali non valide';
    }

    require __DIR__ . '/../templates/login.php';
    exit;
}

// --- Logout ---
if ($uri === '/logout') {
    Auth::logout();
    header('Location: /login');
    exit;
}

// --- Tutte le altre pagine richiedono autenticazione ---
Auth::requireLogin();

$viewer = new LogViewer($db, $config['app']['per_page']);

// --- Dashboard ---
if ($uri === '/' || $uri === '/dashboard') {
    $functionStats = $viewer->getFunctionStats();
    $totalLogs = $viewer->getTotalCount();
    $totalFunctions = count($functionStats);

    // Conteggio log di oggi
    $todayStmt = $db->prepare(
        'SELECT COUNT(*) FROM api_logs WHERE DATE(created_at) = CURDATE()'
    );
    $todayStmt->execute();
    $todayCount = (int) $todayStmt->fetchColumn();

    // Ultimi 10 log
    $recentResult = $viewer->getLogs([], 1);
    $recentLogs = array_slice($recentResult['logs'], 0, 10);

    $pageTitle = 'Dashboard — MSC API Log Viewer';
    render('dashboard', compact('functionStats', 'totalLogs', 'totalFunctions', 'todayCount', 'recentLogs'));
    exit;
}

// --- Lista log ---
if ($uri === '/logs') {
    $filters = [
        'date_from'     => $_GET['date_from'] ?? '',
        'date_to'       => $_GET['date_to'] ?? '',
        'function_name' => $_GET['function_name'] ?? '',
        'search'        => $_GET['search'] ?? '',
        'ip_address'    => $_GET['ip_address'] ?? '',
    ];
    $page = max(1, (int) ($_GET['page'] ?? 1));

    $result = $viewer->getLogs($filters, $page);
    $functions = $viewer->getDistinctFunctions();
    $ips = $viewer->getDistinctIps();

    $pageTitle = 'Log API — MSC API Log Viewer';
    render('logs', compact('result', 'filters', 'functions', 'ips'));
    exit;
}

// --- Dettaglio singolo log ---
if (preg_match('#^/logs/(\d+)$#', $uri, $matches)) {
    $logId = (int) $matches[1];
    $log = $viewer->getLogDetail($logId);

    if (!$log) {
        http_response_code(404);
        $pageTitle = 'Log non trovato';
        render('log_detail', ['log' => ['id' => $logId, 'function_name' => '?', 'data' => '', 'created_at' => '', 'ip_address' => '', 'http_method' => '', 'headers' => '']]);
        exit;
    }

    $pageTitle = "Log #{$logId} — MSC API Log Viewer";
    render('log_detail', compact('log'));
    exit;
}

// --- Statistiche ---
if ($uri === '/stats') {
    $functionStats = $viewer->getFunctionStats();
    $totalLogs = $viewer->getTotalCount();

    $pageTitle = 'Statistiche — MSC API Log Viewer';
    render('stats', compact('functionStats', 'totalLogs'));
    exit;
}

// --- Export CSV ---
if ($uri === '/export') {
    $filters = [
        'date_from'     => $_GET['date_from'] ?? '',
        'date_to'       => $_GET['date_to'] ?? '',
        'function_name' => $_GET['function_name'] ?? '',
        'search'        => $_GET['search'] ?? '',
        'ip_address'    => $_GET['ip_address'] ?? '',
    ];

    $viewer->exportCsv($filters);
    exit;
}

// ============================================================
// ADMIN — Gestione Utenti
// ============================================================

// --- Lista utenti ---
if ($uri === '/admin/users') {
    Auth::requireAdmin();
    $adminManager = new AdminManager($db);
    $users = $adminManager->getAllUsers();

    $pageTitle = 'Gestione Utenti — Admin';
    render('admin_users', compact('users'));
    exit;
}

// --- Nuovo utente (form) ---
if ($uri === '/admin/users/new') {
    Auth::requireAdmin();

    $formData = [];

    if ($method === 'POST') {
        $formData = [
            'username'     => trim($_POST['username'] ?? ''),
            'display_name' => trim($_POST['display_name'] ?? ''),
            'role'         => $_POST['role'] ?? 'viewer',
        ];
        $password = $_POST['password'] ?? '';

        if (empty($formData['username']) || empty($password)) {
            flashRedirect('Username e password sono obbligatori', 'error', '/admin/users/new');
        }

        if (strlen($password) < 6) {
            flashRedirect('La password deve avere almeno 6 caratteri', 'error', '/admin/users/new');
        }

        try {
            $adminManager = new AdminManager($db);
            $adminManager->createUser(
                $formData['username'],
                $password,
                $formData['display_name'],
                $formData['role']
            );
            flashRedirect('Utente creato con successo', 'success', '/admin/users');
        } catch (\RuntimeException $e) {
            flashRedirect($e->getMessage(), 'error', '/admin/users/new');
        }
    }

    $pageTitle = 'Nuovo Utente — Admin';
    render('admin_user_form', ['user' => null, 'formData' => $formData]);
    exit;
}

// --- Modifica utente ---
if (preg_match('#^/admin/users/(\d+)/edit$#', $uri, $matches)) {
    Auth::requireAdmin();
    $userId = (int) $matches[1];
    $adminManager = new AdminManager($db);
    $user = $adminManager->getUserById($userId);

    if (!$user) {
        flashRedirect('Utente non trovato', 'error', '/admin/users');
    }

    if ($method === 'POST') {
        $displayName = trim($_POST['display_name'] ?? '');
        $role = $_POST['role'] ?? 'viewer';
        $isActive = isset($_POST['is_active']);

        $adminManager->updateUser($userId, $displayName, $role, $isActive);
        flashRedirect('Utente aggiornato con successo', 'success', '/admin/users');
    }

    $pageTitle = "Modifica Utente — Admin";
    render('admin_user_form', ['user' => $user, 'formData' => []]);
    exit;
}

// --- Cambio password utente ---
if (preg_match('#^/admin/users/(\d+)/password$#', $uri, $matches)) {
    Auth::requireAdmin();
    $userId = (int) $matches[1];
    $adminManager = new AdminManager($db);
    $user = $adminManager->getUserById($userId);

    if (!$user) {
        flashRedirect('Utente non trovato', 'error', '/admin/users');
    }

    if ($method === 'POST') {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 6) {
            flashRedirect('La password deve avere almeno 6 caratteri', 'error', "/admin/users/{$userId}/password");
        }

        if ($newPassword !== $confirmPassword) {
            flashRedirect('Le password non coincidono', 'error', "/admin/users/{$userId}/password");
        }

        $adminManager->changePassword($userId, $newPassword);
        flashRedirect('Password aggiornata con successo', 'success', '/admin/users');
    }

    $pageTitle = "Cambia Password — Admin";
    render('admin_password', compact('user'));
    exit;
}

// --- Elimina utente ---
if (preg_match('#^/admin/users/(\d+)/delete$#', $uri, $matches)) {
    Auth::requireAdmin();
    $userId = (int) $matches[1];

    try {
        $adminManager = new AdminManager($db);
        $adminManager->deleteUser($userId);
        flashRedirect('Utente eliminato', 'success', '/admin/users');
    } catch (\RuntimeException $e) {
        flashRedirect($e->getMessage(), 'error', '/admin/users');
    }
}

// --- 404 ---
http_response_code(404);
$pageTitle = 'Pagina non trovata';
$content = '<h2>404 — Pagina non trovata</h2><p><a href="/">Torna alla dashboard</a></p>';
require __DIR__ . '/../templates/layout.php';
