<?php
// db/BookingSlotRepository.php

require_once __DIR__ . '/BaseRepository.php';

class BookingSlotRepository extends BaseRepository
{
    public function findAll(int $offset = 0, int $limit = 20, array $filters = []): array
    {
        [$where, $params, $order] = $this->buildFilters($filters);
        return $this->fetchAll(
            "SELECT bs.*, b.status AS booking_status, b.user_id,
                    u.full_name AS user_name,
                    w.name AS workspace_name
             FROM booking_slots bs
             LEFT JOIN bookings b ON b.id = bs.booking_id
             LEFT JOIN users u ON u.id = b.user_id
             LEFT JOIN workspaces w ON w.id = b.workspace_id
             WHERE {$where} ORDER BY {$order}
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$params, $offset, $limit]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT bs.*, b.status AS booking_status, b.user_id
             FROM booking_slots bs
             LEFT JOIN bookings b ON b.id = bs.booking_id
             WHERE bs.id = ?',
            [$id]
        );
    }

    public function findByBookingId(int $bookingId): array
    {
        return $this->fetchAll(
            'SELECT * FROM booking_slots WHERE booking_id = ? ORDER BY start_time',
            [$bookingId]
        );
    }

    public function total(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        return (int) ($this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM booking_slots bs
             LEFT JOIN bookings b ON b.id = bs.booking_id
             LEFT JOIN users u ON u.id = b.user_id
             LEFT JOIN workspaces w ON w.id = b.workspace_id
             WHERE {$where}",
            $params
        )['cnt'] ?? 0);
    }

    public function bookingExists(int $bookingId): bool
    {
        return $this->existsById('bookings', $bookingId);
    }

    public function create(int $bookingId, string $startTime, string $endTime): int
    {
        $this->execute(
            'INSERT INTO booking_slots (booking_id, start_time, end_time) VALUES (?, ?, ?)',
            [$bookingId, $startTime, $endTime]
        );
        return $this->lastId();
    }

    public function update(int $id, int $bookingId, string $startTime, string $endTime): void
    {
        $this->execute(
            'UPDATE booking_slots SET booking_id=?, start_time=?, end_time=? WHERE id=?',
            [$bookingId, $startTime, $endTime, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM booking_slots WHERE id = ?', [$id]);
    }

    private function buildFilters(array $filters): array
    {
        $conds  = ['1=1'];
        $params = [];

        if (!empty($filters['booking_id'])) {
            $conds[]  = 'bs.booking_id = ?';
            $params[] = (int) $filters['booking_id'];
        }
        if (!empty($filters['status'])) {
            $conds[]  = 'b.status = ?';
            $params[] = $filters['status'];
        }

        $allowedSort = ['bs.id', 'bs.start_time', 'bs.end_time', 'bs.booking_id'];
        $sortRaw = $filters['sort'] ?? 'bs.id';
        $sort = in_array($sortRaw, $allowedSort) ? $sortRaw : 'bs.id';
        $dir  = strtoupper($filters['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        return [implode(' AND ', $conds), $params, "{$sort} {$dir}"];
    }
}
