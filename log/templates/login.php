<?php
/**
 * Template pagina di login del Log Viewer.
 */
?>
<!DOCTYPE html>
<html lang="it" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — MSC API Log Viewer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .login-box { width: 100%; max-width: 400px; }
        .alert-error { background: #fce4ec; color: #c62828; border: 1px solid #ef9a9a; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <article class="login-box">
        <header>
            <h3 style="margin:0">MSC API Log Viewer</h3>
            <p style="margin:0; color: var(--pico-muted-color)">Accedi per consultare i log</p>
        </header>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/login">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus
                   value="<?= htmlspecialchars($username ?? '') ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Accedi</button>
        </form>
    </article>
</body>
</html>
