<?php
// db/ReviewRepository.php

require_once __DIR__ . '/BaseRepository.php';

class ReviewRepository extends BaseRepository
{
    public function findAll(int $offset = 0, int $limit = 20, array $filters = []): array
    {
        [$where, $params, $order] = $this->buildFilters($filters);
        return $this->fetchAll(
            "SELECT r.*, c.name AS coworking_name, u.full_name AS user_name
             FROM reviews r
             LEFT JOIN coworkings c ON c.id = r.coworking_id
             LEFT JOIN users u ON u.id = r.user_id
             WHERE {$where} ORDER BY {$order}
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$params, $offset, $limit]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT r.*, c.name AS coworking_name, u.full_name AS user_name
             FROM reviews r
             LEFT JOIN coworkings c ON c.id = r.coworking_id
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.id = ?',
            [$id]
        );
    }

    public function total(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        return (int) ($this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM reviews r
             LEFT JOIN coworkings c ON c.id = r.coworking_id
             LEFT JOIN users u ON u.id = r.user_id
             WHERE {$where}",
            $params
        )['cnt'] ?? 0);
    }

    public function avgRatingForCoworking(int $coworkingId): ?float
    {
        $row = $this->fetchOne(
            'SELECT AVG(CAST(rating AS FLOAT)) AS avg FROM reviews WHERE coworking_id = ?',
            [$coworkingId]
        );
        return $row ? round((float) $row['avg'], 1) : null;
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM reviews WHERE id = ?', [$id]);
    }

    private function buildFilters(array $filters): array
    {
        $conds  = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $conds[]  = "(r.comment LIKE ? OR u.full_name LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params[] = $s; $params[] = $s;
        }
        if (!empty($filters['coworking_id'])) {
            $conds[]  = 'r.coworking_id = ?';
            $params[] = (int) $filters['coworking_id'];
        }
        if (!empty($filters['rating'])) {
            $conds[]  = 'r.rating = ?';
            $params[] = (int) $filters['rating'];
        }

        $allowedSort = ['r.id', 'r.rating', 'r.created_at'];
        $sortRaw = $filters['sort'] ?? 'r.id';
        $sort = in_array($sortRaw, $allowedSort) ? $sortRaw : 'r.id';
        $dir  = strtoupper($filters['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        return [implode(' AND ', $conds), $params, "{$sort} {$dir}"];
    }
}
