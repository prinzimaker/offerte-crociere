<?php
/**
 * Template dashboard principale del Log Viewer.
 *
 * Mostra: contatori globali, statistiche per function, ultimi log.
 */
?>
<h2>Dashboard</h2>

<!-- Contatori globali -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="number"><?= number_format($totalLogs) ?></div>
        <div class="label">Log totali</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= number_format($totalFunctions) ?></div>
        <div class="label">Function distinte</div>
    </div>
    <?php if (!empty($todayCount)): ?>
    <div class="stat-card">
        <div class="number"><?= number_format($todayCount) ?></div>
        <div class="label">Oggi</div>
    </div>
    <?php endif; ?>
</div>

<!-- Statistiche per function -->
<?php if (!empty($functionStats)): ?>
<details open>
    <summary><strong>Chiamate per function</strong></summary>
    <table role="grid">
        <thead>
            <tr>
                <th>Function</th>
                <th style="text-align:right">Chiamate</th>
                <th>Ultima chiamata</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($functionStats as $stat): ?>
            <tr>
                <td><code><?= htmlspecialchars($stat['function_name']) ?></code></td>
                <td style="text-align:right"><strong><?= number_format((int)$stat['total_calls']) ?></strong></td>
                <td class="text-muted"><?= htmlspecialchars($stat['last_call']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</details>
<?php endif; ?>

<!-- Ultimi log -->
<h3>Ultimi log</h3>
<?php if (empty($recentLogs)): ?>
    <p class="text-muted">Nessun log registrato.</p>
<?php else: ?>
    <div style="overflow-x:auto">
    <table role="grid">
        <thead>
            <tr>
                <th>ID</th>
                <th>Function</th>
                <th>Anteprima dati</th>
                <th>IP</th>
                <th>Data/Ora</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentLogs as $log): ?>
            <tr>
                <td><a href="/logs/<?= $log['id'] ?>">#<?= $log['id'] ?></a></td>
                <td><code><?= htmlspecialchars($log['function_name']) ?></code></td>
                <td class="text-truncate"><?= htmlspecialchars($log['data_preview'] ?? '') ?></td>
                <td><small><?= htmlspecialchars($log['ip_address'] ?? '-') ?></small></td>
                <td><small><?= htmlspecialchars($log['created_at']) ?></small></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <p style="text-align:center"><a href="/logs">Vedi tutti i log &rarr;</a></p>
<?php endif; ?>
