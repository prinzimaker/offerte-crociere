<?php
/**
 * Gestione utenti della piattaforma Admin.
 *
 * Permette di creare, modificare, disattivare e listare gli utenti
 * che hanno accesso al Log Viewer. Solo gli utenti con ruolo "admin"
 * possono gestire altri utenti.
 */

declare(strict_types=1);

namespace LogViewer;

use PDO;

class AdminManager
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Recupera la lista di tutti gli utenti.
     *
     * @return array<array>
     */
    public function getAllUsers(): array
    {
        $stmt = $this->db->query(
            'SELECT id, username, display_name, role, is_active, last_login, created_at
             FROM admin_users
             ORDER BY created_at ASC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Recupera un singolo utente per ID.
     */
    public function getUserById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, display_name, role, is_active, last_login, created_at
             FROM admin_users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Crea un nuovo utente.
     *
     * @return int ID dell'utente creato
     * @throws \RuntimeException se lo username esiste già
     */
    public function createUser(string $username, string $password, string $displayName, string $role): int
    {
        // Verifica username univoco
        $check = $this->db->prepare('SELECT id FROM admin_users WHERE username = :username');
        $check->execute([':username' => $username]);
        if ($check->fetch()) {
            throw new \RuntimeException('Username già esistente');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO admin_users (username, password_hash, display_name, role, is_active)
             VALUES (:username, :password_hash, :display_name, :role, 1)'
        );
        $stmt->execute([
            ':username'      => $username,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':display_name'  => $displayName,
            ':role'          => $role,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Aggiorna i dati di un utente.
     */
    public function updateUser(int $id, string $displayName, string $role, bool $isActive): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE admin_users
             SET display_name = :display_name, role = :role, is_active = :is_active
             WHERE id = :id'
        );

        return $stmt->execute([
            ':display_name' => $displayName,
            ':role'         => $role,
            ':is_active'    => $isActive ? 1 : 0,
            ':id'           => $id,
        ]);
    }

    /**
     * Cambia la password di un utente.
     */
    public function changePassword(int $id, string $newPassword): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE admin_users SET password_hash = :password_hash WHERE id = :id'
        );

        return $stmt->execute([
            ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':id'            => $id,
        ]);
    }

    /**
     * Attiva/disattiva un utente.
     */
    public function toggleActive(int $id, bool $active): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE admin_users SET is_active = :is_active WHERE id = :id'
        );

        return $stmt->execute([
            ':is_active' => $active ? 1 : 0,
            ':id'        => $id,
        ]);
    }

    /**
     * Elimina un utente (solo se non è l'ultimo admin).
     */
    public function deleteUser(int $id): bool
    {
        // Conta admin attivi
        $countStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM admin_users WHERE role = "admin" AND is_active = 1 AND id != :id'
        );
        $countStmt->execute([':id' => $id]);
        $adminCount = (int) $countStmt->fetchColumn();

        // Verifica che il target sia admin
        $user = $this->getUserById($id);
        if ($user && $user['role'] === 'admin' && $adminCount === 0) {
            throw new \RuntimeException('Impossibile eliminare l\'ultimo utente admin');
        }

        $stmt = $this->db->prepare('DELETE FROM admin_users WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}
