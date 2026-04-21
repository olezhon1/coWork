<?php
// db/WorkspaceRepository.php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../enums/WorkspaceType.php';

class WorkspaceRepository extends BaseRepository
{
    public function findAll(int $offset = 0, int $limit = 20, array $filters = []): array
    {
        [$where, $params, $order] = $this->buildFilters($filters);
        return $this->fetchAll(
            "SELECT w.*, c.name AS coworking_name
             FROM workspaces w
             LEFT JOIN coworkings c ON c.id = w.coworking_id
             WHERE {$where} ORDER BY {$order}
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$params, $offset, $limit]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT w.*, c.name AS coworking_name
             FROM workspaces w
             LEFT JOIN coworkings c ON c.id = w.coworking_id
             WHERE w.id = ?',
            [$id]
        );
    }

    public function total(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        return (int) ($this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM workspaces w
             LEFT JOIN coworkings c ON c.id = w.coworking_id
             WHERE {$where}", $params
        )['cnt'] ?? 0);
    }

    public function create(int $coworkingId, string $name, WorkspaceType $type, float $pricePerHour, int $capacity): int
    {
        $this->execute(
            'INSERT INTO workspaces (coworking_id, name, type_key, price_per_hour, capacity) VALUES (?, ?, ?, ?, ?)',
            [$coworkingId, $name, $type->value, $pricePerHour, $capacity]
        );
        return $this->lastId();
    }

    public function update(int $id, int $coworkingId, string $name, WorkspaceType $type, float $pricePerHour, int $capacity): void
    {
        $this->execute(
            'UPDATE workspaces SET coworking_id=?, name=?, type_key=?, price_per_hour=?, capacity=? WHERE id=?',
            [$coworkingId, $name, $type->value, $pricePerHour, $capacity, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM workspaces WHERE id = ?', [$id]);
    }

    public function allForSelect(): array
    {
        return $this->fetchAll(
            "SELECT w.id, w.name + ' / ' + c.name AS label
             FROM workspaces w
             JOIN coworkings c ON c.id = w.coworking_id
             ORDER BY c.name, w.name"
        );
    }

    private function buildFilters(array $filters): array
    {
        $conds  = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $conds[]  = 'w.name LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['coworking_id'])) {
            $conds[]  = 'w.coworking_id = ?';
            $params[] = (int) $filters['coworking_id'];
        }
        if (!empty($filters['type_key'])) {
            $conds[]  = 'w.type_key = ?';
            $params[] = $filters['type_key'];
        }

        $allowedSort = ['w.id', 'w.name', 'w.price_per_hour', 'w.capacity'];
        $sortRaw = $filters['sort'] ?? 'w.id';
        $sort = in_array($sortRaw, $allowedSort) ? $sortRaw : 'w.id';
        $dir  = strtoupper($filters['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        return [implode(' AND ', $conds), $params, "{$sort} {$dir}"];
    }
}
