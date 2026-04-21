<?php
// db/FeatureRepository.php

require_once __DIR__ . '/BaseRepository.php';

class FeatureRepository extends BaseRepository
{
    public function findAll(int $offset = 0, int $limit = 20, array $filters = []): array
    {
        [$where, $params] = $this->buildFilters($filters);
        return $this->fetchAll(
            "SELECT * FROM features WHERE {$where} ORDER BY name ASC
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$params, $offset, $limit]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM features WHERE id = ?', [$id]);
    }

    public function total(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        return $this->countWhere('features', $where, $params);
    }

    public function create(string $name, string $iconKey): int
    {
        $this->execute(
            'INSERT INTO features (name, icon_key) VALUES (?, ?)',
            [$name, $iconKey]
        );
        return $this->lastId();
    }

    public function update(int $id, string $name, string $iconKey): void
    {
        $this->execute(
            'UPDATE features SET name=?, icon_key=? WHERE id=?',
            [$name, $iconKey, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM features WHERE id = ?', [$id]);
    }

    public function allForSelect(): array
    {
        return $this->fetchAll('SELECT id, name FROM features ORDER BY name');
    }

    private function buildFilters(array $filters): array
    {
        $conds  = ['1=1'];
        $params = [];
        if (!empty($filters['search'])) {
            $conds[]  = 'name LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }
        return [implode(' AND ', $conds), $params];
    }
}
