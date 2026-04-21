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

    public function create(GalleryEntityType $type, int $entityId, string $imageUrl, bool $isMain = false): int
    {
        // якщо is_main=1, скидаємо попереднє головне фото цього entity
        if ($isMain) {
            $this->execute(
                'UPDATE gallery SET is_main=0 WHERE entity_type=? AND entity_id=?',
                [$type->value, $entityId]
            );
        }
        $this->execute(
            'INSERT INTO gallery (entity_type, entity_id, image_url, is_main) VALUES (?, ?, ?, ?)',
            [$type->value, $entityId, $imageUrl, $isMain ? 1 : 0]
        );
        return $this->lastId();
    }

    public function update(int $id, GalleryEntityType $type, int $entityId, string $imageUrl, bool $isMain): void
    {
        if ($isMain) {
            $this->execute(
                'UPDATE gallery SET is_main=0 WHERE entity_type=? AND entity_id=? AND id<>?',
                [$type->value, $entityId, $id]
            );
        }
        $this->execute(
            'UPDATE gallery SET entity_type=?, entity_id=?, image_url=?, is_main=? WHERE id=?',
            [$type->value, $entityId, $imageUrl, $isMain ? 1 : 0, $id]
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
        if (!empty($filters['entity_type'])) {
            $conds[]  = 'entity_type = ?';
            $params[] = $filters['entity_type'];
        }
        if (!empty($filters['entity_id'])) {
            $conds[]  = 'entity_id = ?';
            $params[] = (int) $filters['entity_id'];
        }
        if (isset($filters['is_main']) && $filters['is_main'] !== '') {
            $conds[]  = 'is_main = ?';
            $params[] = (int) $filters['is_main'];
        }
        return [implode(' AND ', $conds), $params];
    }
}
