<?php
/**
 * Template pagina statistiche.
 *
 * Mostra contatori dettagliati per function con percentuali.
 */
?>
<h2>Statistiche</h2>

<div class="stats-grid">
    <div class="stat-card">
        <div class="number"><?= number_format($totalLogs) ?></div>
        <div class="label">Log totali</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= count($functionStats) ?></div>
        <div class="label">Function distinte</div>
    </div>
</div>

<?php if (!empty($functionStats)): ?>
<h3>Dettaglio per function</h3>
<div style="overflow-x:auto">
<table role="grid">
    <thead>
        <tr>
            <th>Function</th>
            <th style="text-align:right">Chiamate</th>
            <th style="text-align:right">%</th>
            <th>Ultima chiamata</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($functionStats as $stat):
            $pct = $totalLogs > 0 ? round((int)$stat['total_calls'] / $totalLogs * 100, 1) : 0;
        ?>
        <tr>
            <td><code><?= htmlspecialchars($stat['function_name']) ?></code></td>
            <td style="text-align:right"><strong><?= number_format((int)$stat['total_calls']) ?></strong></td>
            <td style="text-align:right"><?= $pct ?>%</td>
            <td class="text-muted"><?= htmlspecialchars($stat['last_call']) ?></td>
            <td>
                <a href="/logs?function_name=<?= urlencode($stat['function_name']) ?>"
                   style="font-size:0.8rem;">Vedi log &rarr;</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php else: ?>
    <p class="text-muted">Nessun dato disponibile.</p>
<?php endif; ?>
