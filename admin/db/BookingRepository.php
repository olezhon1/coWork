<?php
// db/BookingRepository.php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../enums/BookingStatus.php';

class BookingRepository extends BaseRepository
{
    public function findAll(int $offset = 0, int $limit = 20, array $filters = []): array
    {
        [$where, $params, $order] = $this->buildFilters($filters);
        return $this->fetchAll(
            "SELECT b.*, w.name AS workspace_name, c.name AS coworking_name,
                    u.full_name AS user_name, u.email AS user_email
             FROM bookings b
             LEFT JOIN workspaces w ON w.id = b.workspace_id
             LEFT JOIN coworkings c ON c.id = w.coworking_id
             LEFT JOIN users u ON u.id = b.user_id
             WHERE {$where} ORDER BY {$order}
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$params, $offset, $limit]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT b.*, w.name AS workspace_name, c.name AS coworking_name,
                    u.full_name AS user_name
             FROM bookings b
             LEFT JOIN workspaces w ON w.id = b.workspace_id
             LEFT JOIN coworkings c ON c.id = w.coworking_id
             LEFT JOIN users u ON u.id = b.user_id
             WHERE b.id = ?',
            [$id]
        );
    }

    public function total(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        return (int) ($this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM bookings b
             LEFT JOIN workspaces w ON w.id = b.workspace_id
             LEFT JOIN coworkings c ON c.id = w.coworking_id
             LEFT JOIN users u ON u.id = b.user_id
             WHERE {$where}",
            $params
        )['cnt'] ?? 0);
    }

    public function create(int $userId, int $workspaceId, BookingStatus $status, float $totalPrice): int
    {
        $this->execute(
            'INSERT INTO bookings (user_id, workspace_id, status, total_price) VALUES (?, ?, ?, ?)',
            [$userId, $workspaceId, $status->value, $totalPrice]
        );
        return $this->lastId();
    }

    public function update(int $id, int $userId, int $workspaceId, BookingStatus $status, float $totalPrice): void
    {
        $this->execute(
            'UPDATE bookings SET user_id=?, workspace_id=?, status=?, total_price=? WHERE id=?',
            [$userId, $workspaceId, $status->value, $totalPrice, $id]
        );
    }

    public function updateStatus(int $id, BookingStatus $status): void
    {
        $this->execute('UPDATE bookings SET status=? WHERE id=?', [$status->value, $id]);
    }

    /**
     * Перераховує total_price як (сума тривалостей усіх слотів у годинах) × price_per_hour воркспейсу.
     * Викликається після будь-якої зміни в booking_slots.
     */
    public function recalcTotalPrice(int $bookingId): void
    {
        $this->execute(
            "UPDATE b
             SET total_price = ROUND(
                 ISNULL((
                     SELECT SUM(DATEDIFF(SECOND, bs.start_time, bs.end_time) / 3600.0)
                     FROM booking_slots bs
                     WHERE bs.booking_id = b.id
                 ), 0) * ISNULL(w.price_per_hour, 0),
                 2
             )
             FROM bookings b
             JOIN workspaces w ON w.id = b.workspace_id
             WHERE b.id = ?",
            [$bookingId]
        );
    }

    public function getTotalPrice(int $id): float
    {
        $row = $this->fetchOne('SELECT total_price FROM bookings WHERE id = ?', [$id]);
        return (float) ($row['total_price'] ?? 0);
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM bookings WHERE id = ?', [$id]);
    }

    private function buildFilters(array $filters): array
    {
        $conds  = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $conds[]  = "(u.full_name LIKE ? OR u.email LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params[] = $s; $params[] = $s;
        }
        if (!empty($filters['status'])) {
            $conds[]  = 'b.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['coworking_id'])) {
            $conds[]  = 'c.id = ?';
            $params[] = (int) $filters['coworking_id'];
        }
        if (!empty($filters['workspace_id'])) {
            $conds[]  = 'b.workspace_id = ?';
            $params[] = (int) $filters['workspace_id'];
        }

        $allowedSort = ['b.id', 'b.total_price', 'b.created_at', 'b.status'];
        $sortRaw = $filters['sort'] ?? 'b.id';
        $sort = in_array($sortRaw, $allowedSort) ? $sortRaw : 'b.id';
        $dir  = strtoupper($filters['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        return [implode(' AND ', $conds), $params, "{$sort} {$dir}"];
    }
}
