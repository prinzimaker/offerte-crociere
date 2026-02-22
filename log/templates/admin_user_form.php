<?php
/**
 * Template form creazione/modifica utente.
 */
$isEdit = !empty($user);
?>
<p><a href="/admin/users">&larr; Torna alla lista utenti</a></p>

<h2><?= $isEdit ? 'Modifica utente' : 'Nuovo utente' ?></h2>

<form method="post" style="max-width:500px;">
    <?php if (!$isEdit): ?>
    <label for="username">Username</label>
    <input type="text" id="username" name="username" required
           value="<?= htmlspecialchars($formData['username'] ?? '') ?>"
           pattern="[a-zA-Z0-9_]+" title="Solo lettere, numeri e underscore">
    <?php else: ?>
    <label>Username</label>
    <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
    <?php endif; ?>

    <label for="display_name">Nome visualizzato</label>
    <input type="text" id="display_name" name="display_name"
           value="<?= htmlspecialchars($formData['display_name'] ?? $user['display_name'] ?? '') ?>">

    <?php if (!$isEdit): ?>
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required minlength="6">
    <?php endif; ?>

    <label for="role">Ruolo</label>
    <select id="role" name="role">
        <?php $currentRole = $formData['role'] ?? $user['role'] ?? 'viewer'; ?>
        <option value="viewer" <?= $currentRole === 'viewer' ? 'selected' : '' ?>>Viewer — solo consultazione</option>
        <option value="admin" <?= $currentRole === 'admin' ? 'selected' : '' ?>>Admin — gestione completa</option>
    </select>

    <?php if ($isEdit): ?>
    <fieldset>
        <label>
            <input type="checkbox" name="is_active" value="1"
                   <?= ($user['is_active'] ?? 1) ? 'checked' : '' ?>>
            Utente attivo
        </label>
    </fieldset>
    <?php endif; ?>

    <button type="submit"><?= $isEdit ? 'Salva modifiche' : 'Crea utente' ?></button>
</form>
