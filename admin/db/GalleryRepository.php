<?php
// db/GalleryRepository.php

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../enums/GalleryEntityType.php';

class GalleryRepository extends BaseRepository
{
    public function findAll(int $offset = 0, int $limit = 20, array $filters = []): array
    {
        [$where, $params] = $this->buildFilters($filters);
        return $this->fetchAll(
            "SELECT * FROM gallery WHERE {$where} ORDER BY is_main DESC, id DESC
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$params, $offset, $limit]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM gallery WHERE id = ?', [$id]);
    }

    public function total(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        return $this->countWhere('gallery', $where, $params);
    }

    public function entityExists(GalleryEntityType $type, int $entityId): bool
    {
        return $this->existsById($type->dbTable(), $entityId);
    }

    public function create(int $entityId, string $imageUrl, bool $isMain = false): int
    {
        if ($isMain) {
            $this->execute(
                'UPDATE gallery SET is_main = 0 WHERE entity_id = ?',
                [$entityId]
            );
        }

        $this->execute(
            'INSERT INTO gallery (entity_id, image_url, is_main) VALUES (?, ?, ?)',
            [$entityId, $imageUrl, $isMain ? 1 : 0]
        );

        return $this->lastId();
    }

    public function update(int $id, int $entityId, string $imageUrl, bool $isMain): void
    {
        if ($isMain) {
            $this->execute(
                'UPDATE gallery 
             SET is_main = 0 
             WHERE entity_id = ? AND id <> ?',
                [$entityId, $id]
            );
        }

        $this->execute(
            'UPDATE gallery 
         SET entity_id = ?, image_url = ?, is_main = ? 
         WHERE id = ?',
            [$entityId, $imageUrl, $isMain ? 1 : 0, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM gallery WHERE id = ?', [$id]);
    }

    private function buildFilters(array $filters): array
    {
        $conds  = ['1=1'];
        $params = [];

        if (!empty($filters['coworking_id'])) {
            $conds[]  = 'coworking_id = ?';
            $params[] = (int) $filters['coworking_id'];
        }

        if (isset($filters['is_main']) && $filters['is_main'] !== '') {
            $conds[]  = 'is_main = ?';
            $params[] = (int) $filters['is_main'];
        }

        return [implode(' AND ', $conds), $params];
    }
}
