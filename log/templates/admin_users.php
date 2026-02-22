<?php
/**
 * Template gestione utenti (piattaforma Admin).
 *
 * Lista utenti con azioni: modifica, cambio password, attiva/disattiva, elimina.
 * Accessibile solo agli utenti con ruolo "admin".
 */
?>
<h2>Gestione Utenti</h2>

<p><a href="/admin/users/new" role="button">+ Nuovo utente</a></p>

<?php if (empty($users)): ?>
    <p class="text-muted">Nessun utente trovato.</p>
<?php else: ?>
<div style="overflow-x:auto">
<table role="grid">
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Nome</th>
            <th>Ruolo</th>
            <th>Stato</th>
            <th>Ultimo accesso</th>
            <th>Azioni</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
            <td><?= htmlspecialchars($user['display_name'] ?? '-') ?></td>
            <td>
                <span class="badge badge-<?= $user['role'] ?>">
                    <?= $user['role'] === 'admin' ? 'Admin' : 'Viewer' ?>
                </span>
            </td>
            <td>
                <span class="badge badge-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                    <?= $user['is_active'] ? 'Attivo' : 'Disattivato' ?>
                </span>
            </td>
            <td class="text-muted">
                <small><?= $user['last_login'] ? htmlspecialchars($user['last_login']) : 'Mai' ?></small>
            </td>
            <td>
                <a href="/admin/users/<?= $user['id'] ?>/edit" style="font-size:0.8rem;">Modifica</a>
                &nbsp;
                <a href="/admin/users/<?= $user['id'] ?>/password" style="font-size:0.8rem;">Password</a>
                <?php if ((int)$user['id'] !== ($_SESSION['user_id'] ?? 0)): ?>
                &nbsp;
                <a href="/admin/users/<?= $user['id'] ?>/delete"
                   style="font-size:0.8rem; color:var(--pico-del-color);"
                   onclick="return confirm('Eliminare questo utente?')">Elimina</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
