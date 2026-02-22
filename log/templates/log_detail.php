<?php
/**
 * Template dettaglio di un singolo log.
 *
 * Mostra tutti i campi del log con il JSON formattato e colorato.
 */
?>
<p><a href="/logs">&larr; Torna alla lista</a></p>

<h2>Log #<?= $log['id'] ?></h2>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <strong>Function:</strong><br>
        <code style="font-size:1.1rem;"><?= htmlspecialchars($log['function_name']) ?></code>
    </div>
    <div>
        <strong>Data/Ora:</strong><br>
        <?= htmlspecialchars($log['created_at']) ?>
    </div>
    <div>
        <strong>IP di origine:</strong><br>
        <?= htmlspecialchars($log['ip_address'] ?? 'N/D') ?>
    </div>
    <div>
        <strong>Metodo HTTP:</strong><br>
        <span class="badge badge-<?= strtolower($log['http_method'] ?? 'post') ?>">
            <?= htmlspecialchars($log['http_method'] ?? 'POST') ?>
        </span>
    </div>
</div>

<h3>Dati (JSON)</h3>
<pre class="json"><?= htmlspecialchars($log['data']) ?></pre>

<?php if (!empty($log['headers'])): ?>
<details>
    <summary><strong>Header della richiesta</strong></summary>
    <pre class="json"><?= htmlspecialchars($log['headers']) ?></pre>
</details>
<?php endif; ?>
