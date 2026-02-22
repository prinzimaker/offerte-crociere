<?php
/**
 * Template base HTML per il Log Viewer.
 *
 * Fornisce la struttura HTML comune a tutte le pagine:
 * header, navigazione, container e footer.
 * Utilizza Pico CSS da CDN per uno stile minimale e funzionale.
 */
?>
<!DOCTYPE html>
<html lang="it" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'MSC API Log Viewer') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <style>
        :root {
            --pico-font-size: 14px;
        }
        nav {
            background: var(--pico-primary-background);
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
        }
        nav ul {
            margin: 0;
        }
        nav a, nav strong {
            color: var(--pico-primary-inverse) !important;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            padding: 1rem;
            border-radius: var(--pico-border-radius);
            background: var(--pico-card-background-color);
            border: 1px solid var(--pico-muted-border-color);
            text-align: center;
        }
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--pico-primary);
        }
        .stat-card .label {
            font-size: 0.85rem;
            color: var(--pico-muted-color);
        }
        .badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-admin { background: #e3f2fd; color: #1565c0; }
        .badge-viewer { background: #e8f5e9; color: #2e7d32; }
        .badge-active { background: #e8f5e9; color: #2e7d32; }
        .badge-inactive { background: #fce4ec; color: #c62828; }
        .badge-post { background: #fff3e0; color: #e65100; }
        .badge-get { background: #e3f2fd; color: #1565c0; }
        pre.json {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 1rem;
            border-radius: var(--pico-border-radius);
            overflow-x: auto;
            font-size: 0.85rem;
            line-height: 1.5;
        }
        .json-key { color: #9cdcfe; }
        .json-string { color: #ce9178; }
        .json-number { color: #b5cea8; }
        .json-boolean { color: #569cd6; }
        .json-null { color: #569cd6; }
        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 0.75rem;
            align-items: end;
            margin-bottom: 1rem;
        }
        .filters-form label {
            margin-bottom: 0;
        }
        .filters-form input,
        .filters-form select {
            margin-bottom: 0;
        }
        .pagination {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            align-items: center;
            margin-top: 1.5rem;
        }
        .pagination a, .pagination span {
            padding: 0.3rem 0.7rem;
            border-radius: var(--pico-border-radius);
            text-decoration: none;
        }
        .pagination .current {
            background: var(--pico-primary);
            color: var(--pico-primary-inverse);
        }
        table {
            font-size: 0.85rem;
        }
        .text-muted {
            color: var(--pico-muted-color);
        }
        .text-truncate {
            max-width: 350px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: var(--pico-border-radius);
            margin-bottom: 1rem;
        }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .alert-error { background: #fce4ec; color: #c62828; border: 1px solid #ef9a9a; }
        .alert-info { background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; }
    </style>
</head>
<body>
    <nav>
        <ul>
            <li><strong>MSC API Log Viewer</strong></li>
        </ul>
        <ul>
            <li><a href="/">Dashboard</a></li>
            <li><a href="/logs">Log</a></li>
            <li><a href="/stats">Statistiche</a></li>
            <?php if (\LogViewer\Auth::isAdmin()): ?>
            <li><a href="/admin/users">Admin</a></li>
            <?php endif; ?>
            <li>
                <a href="/logout">
                    Esci (<?= htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username'] ?? '') ?>)
                </a>
            </li>
        </ul>
    </nav>

    <main class="container">
        <?php if (!empty($flashMessage)): ?>
            <div class="alert alert-<?= htmlspecialchars($flashType ?? 'info') ?>">
                <?= htmlspecialchars($flashMessage) ?>
            </div>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </main>

    <footer class="container">
        <hr>
        <p class="text-muted" style="text-align:center; font-size:0.8rem;">
            MSC API Proxy — Log Viewer — Fase 1
        </p>
    </footer>

    <script>
    // Syntax highlighting per JSON
    function highlightJson(jsonStr) {
        try {
            const obj = JSON.parse(jsonStr);
            const formatted = JSON.stringify(obj, null, 2);
            return formatted
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"([^"]+)"(?=\s*:)/g, '"<span class="json-key">$1</span>"')
                .replace(/:\s*"([^"]*)"/g, ': "<span class="json-string">$1</span>"')
                .replace(/:\s*(\d+\.?\d*)/g, ': <span class="json-number">$1</span>')
                .replace(/:\s*(true|false)/g, ': <span class="json-boolean">$1</span>')
                .replace(/:\s*(null)/g, ': <span class="json-null">$1</span>');
        } catch (e) {
            return jsonStr.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
    }

    document.querySelectorAll('pre.json').forEach(function(el) {
        el.innerHTML = highlightJson(el.textContent);
    });
    </script>
</body>
</html>
