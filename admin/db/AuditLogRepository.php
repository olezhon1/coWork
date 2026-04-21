<?php
// db/AuditLogRepository.php

require_once __DIR__ . '/BaseRepository.php';

final class AuditLogRepository extends BaseRepository
{
    /**
     * Записує дію в журнал. Не кидає винятків назовні — щоб навіть
     * помилка логування не ламала бізнес-операцію.
     */
    public function log(
        ?int $userId,
        ?string $userName,
        string $action,
        ?string $tableName = null,
        ?int $recordId = null,
        ?string $details = null,
    ): void {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $this->execute(
                'INSERT INTO audit_log (user_id, user_name, action, table_name, record_id, details, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $userId ?? null,
                    $userName !== null ? mb_substr($userName, 0, 150) : null,
                    mb_substr($action, 0, 30),
                    $tableName !== null ? mb_substr($tableName, 0, 50) : null,
                    $recordId ?? null,
                    $details !== null ? mb_substr($details, 0, 1000) : null,
                    $ip !== null ? mb_substr($ip, 0, 45) : null,
                ],
            );
        } catch (\Throwable) {
            // тихо ігноруємо — відсутність логу не повинна падити UX
        }
    }

    /** @return array{rows: array<int,array<string,mixed>>, total:int} */
    public function findPaged(array $filters, int $page, int $perPage): array
    {
        [$where, $params] = $this->buildWhere($filters);

        $countRow = $this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM audit_log {$where}",
            $params,
        );
        $total = (int) ($countRow['cnt'] ?? 0);

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT id, user_id, user_name, action, table_name, record_id,
                       details, ip_address, created_at
                FROM audit_log
                {$where}
                ORDER BY id DESC
                OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
        $pagedParams = array_merge($params, [$offset, $perPage]);

        return ['rows' => $this->fetchAll($sql, $pagedParams), 'total' => $total];
    }

    /** @return array<int,string> */
    public function distinctActions(): array
    {
        $rows = $this->fetchAll('SELECT DISTINCT action FROM audit_log ORDER BY action');
        return array_column($rows, 'action');
    }

    /** @return array<int,string> */
    public function distinctTables(): array
    {
        $rows = $this->fetchAll(
            "SELECT DISTINCT table_name FROM audit_log
             WHERE table_name IS NOT NULL AND table_name <> ''
             ORDER BY table_name",
        );
        return array_column($rows, 'table_name');
    }

    /** @return array<int,array{id:int,name:string}> */
    public function activeUsers(): array
    {
        return $this->fetchAll(
            "SELECT DISTINCT u.id, u.full_name AS name
             FROM audit_log a
             JOIN users u ON u.id = a.user_id
             ORDER BY u.full_name",
        );
    }

    public function totalRecords(): int
    {
        return (int) ($this->fetchOne('SELECT COUNT(*) AS c FROM audit_log')['c'] ?? 0);
    }

    public function purgeOlderThan(int $days): int
    {
        return $this->execute(
            'DELETE FROM audit_log WHERE created_at < DATEADD(day, -?, GETDATE())',
            [$days],
        );
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{0:string,1:array<int,mixed>}
     */
    private function buildWhere(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['action'])) {
            $clauses[] = 'action = ?';
            $params[]  = (string) $filters['action'];
        }
        if (!empty($filters['table_name'])) {
            $clauses[] = 'table_name = ?';
            $params[]  = (string) $filters['table_name'];
        }
        if (!empty($filters['user_id'])) {
            $clauses[] = 'user_id = ?';
            $params[]  = (int) $filters['user_id'];
        }
        if (!empty($filters['date_from'])) {
            $clauses[] = 'created_at >= ?';
            $params[]  = (string) $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $clauses[] = 'created_at <= ?';
            $params[]  = (string) $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['search'])) {
            $clauses[] = '(details LIKE ? OR user_name LIKE ?)';
            $like      = '%' . $filters['search'] . '%';
            $params[]  = $like;
            $params[]  = $like;
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        return [$where, $params];
    }
}
