<?php
/**
 * Template lista log con filtri e paginazione.
 */
?>
<h2>Log API</h2>

<!-- Filtri -->
<form method="get" action="/logs" class="filters-form">
    <div>
        <label for="date_from">Da</label>
        <input type="date" id="date_from" name="date_from"
               value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
    </div>
    <div>
        <label for="date_to">A</label>
        <input type="date" id="date_to" name="date_to"
               value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
    </div>
    <div>
        <label for="function_name">Function</label>
        <select id="function_name" name="function_name">
            <option value="">Tutte</option>
            <?php foreach ($functions as $fn): ?>
            <option value="<?= htmlspecialchars($fn) ?>"
                <?= ($filters['function_name'] ?? '') === $fn ? 'selected' : '' ?>>
                <?= htmlspecialchars($fn) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="ip_address">IP</label>
        <select id="ip_address" name="ip_address">
            <option value="">Tutti</option>
            <?php foreach ($ips as $ip): ?>
            <option value="<?= htmlspecialchars($ip) ?>"
                <?= ($filters['ip_address'] ?? '') === $ip ? 'selected' : '' ?>>
                <?= htmlspecialchars($ip) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="search">Cerca nei dati</label>
        <input type="text" id="search" name="search" placeholder="testo..."
               value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
    </div>
    <div>
        <button type="submit">Filtra</button>
    </div>
    <div>
        <a href="/logs" role="button" class="outline secondary">Reset</a>
    </div>
</form>

<!-- Info risultati e export -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
    <small class="text-muted"><?= number_format($result['total']) ?> risultati</small>
    <?php
    $exportParams = http_build_query(array_filter($filters));
    if ($exportParams) $exportParams = '&' . $exportParams;
    ?>
    <a href="/export?format=csv<?= $exportParams ?>" role="button" class="outline" style="font-size:0.8rem; padding:0.3rem 0.7rem;">
        Esporta CSV
    </a>
</div>

<!-- Tabella log -->
<?php if (empty($result['logs'])): ?>
    <p class="text-muted">Nessun log trovato con i filtri selezionati.</p>
<?php else: ?>
    <div style="overflow-x:auto">
    <table role="grid">
        <thead>
            <tr>
                <th>ID</th>
                <th>Function</th>
                <th>Anteprima dati</th>
                <th>IP</th>
                <th>Metodo</th>
                <th>Data/Ora</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($result['logs'] as $log): ?>
            <tr>
                <td><a href="/logs/<?= $log['id'] ?>">#<?= $log['id'] ?></a></td>
                <td><code><?= htmlspecialchars($log['function_name']) ?></code></td>
                <td class="text-truncate"><?= htmlspecialchars($log['data_preview'] ?? '') ?></td>
                <td><small><?= htmlspecialchars($log['ip_address'] ?? '-') ?></small></td>
                <td>
                    <span class="badge badge-<?= strtolower($log['http_method']) ?>">
                        <?= htmlspecialchars($log['http_method']) ?>
                    </span>
                </td>
                <td><small><?= htmlspecialchars($log['created_at']) ?></small></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- Paginazione -->
    <?php if ($result['pages'] > 1): ?>
    <div class="pagination">
        <?php
        $queryBase = array_filter($filters);
        $buildUrl = function(int $p) use ($queryBase) {
            $queryBase['page'] = $p;
            return '/logs?' . http_build_query($queryBase);
        };
        ?>

        <?php if ($result['page'] > 1): ?>
            <a href="<?= $buildUrl(1) ?>">&laquo;</a>
            <a href="<?= $buildUrl($result['page'] - 1) ?>">&lsaquo;</a>
        <?php endif; ?>

        <?php
        $start = max(1, $result['page'] - 2);
        $end = min($result['pages'], $result['page'] + 2);
        for ($p = $start; $p <= $end; $p++):
        ?>
            <?php if ($p === $result['page']): ?>
                <span class="current"><?= $p ?></span>
            <?php else: ?>
                <a href="<?= $buildUrl($p) ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($result['page'] < $result['pages']): ?>
            <a href="<?= $buildUrl($result['page'] + 1) ?>">&rsaquo;</a>
            <a href="<?= $buildUrl($result['pages']) ?>">&raquo;</a>
        <?php endif; ?>

        <small class="text-muted" style="margin-left:0.5rem;">
            Pagina <?= $result['page'] ?> di <?= $result['pages'] ?>
        </small>
    </div>
    <?php endif; ?>
<?php endif; ?>
