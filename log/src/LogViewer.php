<?php
/**
 * Logica di lettura e filtraggio dei log API.
 *
 * Gestisce le query per la dashboard: lista paginata, filtri,
 * conteggi per function e dettaglio singolo log.
 */

declare(strict_types=1);

namespace LogViewer;

use PDO;

class LogViewer
{
    private PDO $db;
    private int $perPage;

    public function __construct(PDO $db, int $perPage = 50)
    {
        $this->db = $db;
        $this->perPage = $perPage;
    }

    /**
     * Recupera i log paginati con filtri opzionali.
     *
     * @param array $filters Filtri: date_from, date_to, function_name, search, ip_address
     * @param int   $page    Numero di pagina (1-based)
     *
     * @return array{logs: array, total: int, pages: int, page: int}
     */
    public function getLogs(array $filters = [], int $page = 1): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['function_name'])) {
            $where[] = 'function_name = :function_name';
            $params[':function_name'] = $filters['function_name'];
        }

        if (!empty($filters['search'])) {
            $where[] = 'data LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['ip_address'])) {
            $where[] = 'ip_address = :ip_address';
            $params[':ip_address'] = $filters['ip_address'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Conta totale
        $countSql = "SELECT COUNT(*) FROM api_logs {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Calcola paginazione
        $pages = max(1, (int) ceil($total / $this->perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $this->perPage;

        // Recupera i log
        $sql = "SELECT id, function_name, LEFT(data, 200) AS data_preview, ip_address, http_method, created_at
                FROM api_logs
                {$whereClause}
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $this->perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'logs'  => $stmt->fetchAll(),
            'total' => $total,
            'pages' => $pages,
            'page'  => $page,
        ];
    }

    /**
     * Recupera il dettaglio completo di un singolo log.
     */
    public function getLogDetail(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM api_logs WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $log = $stmt->fetch();

        return $log ?: null;
    }

    /**
     * Recupera i conteggi raggruppati per function_name.
     *
     * @return array<array{function_name: string, count: int, last_call: string}>
     */
    public function getFunctionStats(): array
    {
        $stmt = $this->db->query(
            'SELECT function_name,
                    COUNT(*) AS total_calls,
                    MAX(created_at) AS last_call
             FROM api_logs
             GROUP BY function_name
             ORDER BY total_calls DESC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Recupera l'elenco delle function_name distinte (per il dropdown filtro).
     *
     * @return array<string>
     */
    public function getDistinctFunctions(): array
    {
        $stmt = $this->db->query(
            'SELECT DISTINCT function_name FROM api_logs ORDER BY function_name'
        );

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Recupera gli IP distinti (per il dropdown filtro).
     *
     * @return array<string>
     */
    public function getDistinctIps(): array
    {
        $stmt = $this->db->query(
            'SELECT DISTINCT ip_address FROM api_logs WHERE ip_address IS NOT NULL ORDER BY ip_address'
        );

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Conteggio totale dei log.
     */
    public function getTotalCount(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM api_logs')->fetchColumn();
    }

    /**
     * Esporta i log filtrati in formato CSV.
     *
     * @param array $filters Stessi filtri di getLogs()
     */
    public function exportCsv(array $filters = []): void
    {
        $where = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['function_name'])) {
            $where[] = 'function_name = :function_name';
            $params[':function_name'] = $filters['function_name'];
        }
        if (!empty($filters['search'])) {
            $where[] = 'data LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['ip_address'])) {
            $where[] = 'ip_address = :ip_address';
            $params[':ip_address'] = $filters['ip_address'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT id, function_name, data, ip_address, http_method, created_at
                FROM api_logs {$whereClause} ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="api_logs_' . date('Y-m-d_His') . '.csv"');

        $output = fopen('php://output', 'w');
        // BOM per Excel
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['ID', 'Function', 'Data', 'IP', 'Method', 'Data/Ora']);

        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['id'],
                $row['function_name'],
                $row['data'],
                $row['ip_address'],
                $row['http_method'],
                $row['created_at'],
            ]);
        }

        fclose($output);
        exit;
    }
}
