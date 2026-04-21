<?php
// db/CoworkingRepository.php

require_once __DIR__ . '/BaseRepository.php';

class CoworkingRepository extends BaseRepository
{
    public function findAll(int $offset = 0, int $limit = 20, array $filters = []): array
    {
        [$where, $params, $order] = $this->buildFilters($filters);
        return $this->fetchAll(
            "SELECT * FROM coworkings WHERE {$where} ORDER BY {$order}
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$params, $offset, $limit]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM coworkings WHERE id = ?', [$id]);
    }

    public function total(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        return $this->countWhere('coworkings', $where, $params);
    }

    public function workspaceCount(int $coworkingId): int
    {
        return $this->countWhere('workspaces', 'coworking_id = ?', [$coworkingId]);
    }

    public function distinctCities(): array
    {
        return $this->fetchAll('SELECT DISTINCT city FROM coworkings WHERE city IS NOT NULL AND city <> \'\' ORDER BY city');
    }

    public function create(string $name, string $address, string $city, string $description, int $is24_7): int
    {
        $this->execute(
            'INSERT INTO coworkings (name, address, city, description, is_24_7) VALUES (?, ?, ?, ?, ?)',
            [$name, $address, $city, $description, $is24_7]
        );
        return $this->lastId();
    }

    public function update(int $id, string $name, string $address, string $city, string $description, int $is24_7): void
    {
        $this->execute(
            'UPDATE coworkings SET name=?, address=?, city=?, description=?, is_24_7=? WHERE id=?',
            [$name, $address, $city, $description, $is24_7, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM coworkings WHERE id = ?', [$id]);
    }

    public function allForSelect(): array
    {
        return $this->fetchAll('SELECT id, name FROM coworkings ORDER BY name');
    }

    private function buildFilters(array $filters): array
    {
        $conds  = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $conds[]  = "(name LIKE ? OR city LIKE ? OR address LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params[] = $s; $params[] = $s; $params[] = $s;
        }
        if (!empty($filters['city'])) {
            $conds[]  = 'city = ?';
            $params[] = $filters['city'];
        }
        if (isset($filters['is_24_7']) && $filters['is_24_7'] !== '') {
            $conds[]  = 'is_24_7 = ?';
            $params[] = (int) $filters['is_24_7'];
        }

        $allowedSort = ['id', 'name', 'city', 'created_at'];
        $sort  = in_array($filters['sort'] ?? '', $allowedSort) ? $filters['sort'] : 'id';
        $dir   = strtoupper($filters['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        return [implode(' AND ', $conds), $params, "{$sort} {$dir}"];
    }
}
