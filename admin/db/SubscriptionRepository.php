<?php
// db/SubscriptionRepository.php

require_once __DIR__ . '/BaseRepository.php';

class SubscriptionRepository extends BaseRepository
{
    public function findAll(int $offset = 0, int $limit = 20, array $filters = []): array
    {
        [$where, $params, $order] = $this->buildFilters($filters);
        return $this->fetchAll(
            "SELECT s.*, c.name AS coworking_name,
                    u.full_name AS user_name, u.email AS user_email
             FROM subscriptions s
             LEFT JOIN coworkings c ON c.id = s.coworking_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE {$where} ORDER BY {$order}
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$params, $offset, $limit]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT s.*, c.name AS coworking_name, u.full_name AS user_name
             FROM subscriptions s
             LEFT JOIN coworkings c ON c.id = s.coworking_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE s.id = ?',
            [$id]
        );
    }

    public function total(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        return (int) ($this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM subscriptions s
             LEFT JOIN coworkings c ON c.id = s.coworking_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE {$where}",
            $params
        )['cnt'] ?? 0);
    }

    public function coworkingExists(int $coworkingId): bool
    {
        return $this->existsById('coworkings', $coworkingId);
    }

    public function userExists(int $userId): bool
    {
        return $this->existsById('users', $userId);
    }

    public function create(int $userId, int $coworkingId, int $hoursLeft, string $endDate, string $status): int
    {
        $this->execute(
            'INSERT INTO subscriptions (user_id, coworking_id, hours_left, end_date, status) VALUES (?, ?, ?, ?, ?)',
            [$userId, $coworkingId, $hoursLeft, $endDate, $status]
        );
        return $this->lastId();
    }

    public function update(int $id, int $userId, int $coworkingId, int $hoursLeft, string $endDate, string $status): void
    {
        $this->execute(
            'UPDATE subscriptions SET user_id=?, coworking_id=?, hours_left=?, end_date=?, status=? WHERE id=?',
            [$userId, $coworkingId, $hoursLeft, $endDate, $status, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM subscriptions WHERE id = ?', [$id]);
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
        if (!empty($filters['coworking_id'])) {
            $conds[]  = 's.coworking_id = ?';
            $params[] = (int) $filters['coworking_id'];
        }
        if (!empty($filters['status'])) {
            $conds[]  = 's.status = ?';
            $params[] = $filters['status'];
        }

        $allowedSort = ['s.id', 's.hours_left', 's.end_date', 's.status'];
        $sortRaw = $filters['sort'] ?? 's.id';
        $sort = in_array($sortRaw, $allowedSort) ? $sortRaw : 's.id';
        $dir  = strtoupper($filters['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        return [implode(' AND ', $conds), $params, "{$sort} {$dir}"];
    }
}
