<?php
/**
 * Template form cambio password utente.
 */
?>
<p><a href="/admin/users">&larr; Torna alla lista utenti</a></p>

<h2>Cambia password: <?= htmlspecialchars($user['username']) ?></h2>

<form method="post" style="max-width:400px;">
    <label for="new_password">Nuova password</label>
    <input type="password" id="new_password" name="new_password" required minlength="6">

    <label for="confirm_password">Conferma password</label>
    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">

    <button type="submit">Cambia password</button>
</form>
