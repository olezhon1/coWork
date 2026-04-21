<?php
// db/CoworkingFeatureRepository.php

require_once __DIR__ . '/BaseRepository.php';

class CoworkingFeatureRepository extends BaseRepository
{
    public function findAll(int $offset = 0, int $limit = 20, array $filters = []): array
    {
        [$where, $params] = $this->buildFilters($filters);
        return $this->fetchAll(
            "SELECT cf.coworking_id, cf.feature_id,
                    c.name AS coworking_name,
                    f.name AS feature_name, f.icon_key
             FROM coworking_features cf
             LEFT JOIN coworkings c ON c.id = cf.coworking_id
             LEFT JOIN features f ON f.id = cf.feature_id
             WHERE {$where}
             ORDER BY cf.coworking_id, cf.feature_id
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$params, $offset, $limit]
        );
    }

    public function findById(int $id): ?array
    {
        // id тут — coworking_id для перегляду зв'язків одного коворкінгу
        return $this->fetchOne(
            'SELECT cf.coworking_id, cf.feature_id,
                    c.name AS coworking_name, f.name AS feature_name
             FROM coworking_features cf
             LEFT JOIN coworkings c ON c.id = cf.coworking_id
             LEFT JOIN features f ON f.id = cf.feature_id
             WHERE cf.coworking_id = ? AND cf.feature_id = ?',
            [$id, $id]
        );
    }

    public function total(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        return (int) ($this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM coworking_features cf
             LEFT JOIN coworkings c ON c.id = cf.coworking_id
             LEFT JOIN features f ON f.id = cf.feature_id
             WHERE {$where}",
            $params
        )['cnt'] ?? 0);
    }

    public function exists(int $coworkingId, int $featureId): bool
    {
        return $this->countWhere(
            'coworking_features',
            'coworking_id = ? AND feature_id = ?',
            [$coworkingId, $featureId]
        ) > 0;
    }

    public function create(int $coworkingId, int $featureId): void
    {
        if (!$this->exists($coworkingId, $featureId)) {
            $this->execute(
                'INSERT INTO coworking_features (coworking_id, feature_id) VALUES (?, ?)',
                [$coworkingId, $featureId]
            );
        }
    }

    public function delete(int $coworkingId, int $featureId): void
    {
        $this->execute(
            'DELETE FROM coworking_features WHERE coworking_id = ? AND feature_id = ?',
            [$coworkingId, $featureId]
        );
    }

    public function deleteByCoworking(int $coworkingId): void
    {
        $this->execute('DELETE FROM coworking_features WHERE coworking_id = ?', [$coworkingId]);
    }

    public function featuresForCoworking(int $coworkingId): array
    {
        return $this->fetchAll(
            'SELECT f.id, f.name, f.icon_key FROM features f
             JOIN coworking_features cf ON cf.feature_id = f.id
             WHERE cf.coworking_id = ? ORDER BY f.name',
            [$coworkingId]
        );
    }

    private function buildFilters(array $filters): array
    {
        $conds  = ['1=1'];
        $params = [];
        if (!empty($filters['coworking_id'])) {
            $conds[]  = 'cf.coworking_id = ?';
            $params[] = (int) $filters['coworking_id'];
        }
        if (!empty($filters['feature_id'])) {
            $conds[]  = 'cf.feature_id = ?';
            $params[] = (int) $filters['feature_id'];
        }
        return [implode(' AND ', $conds), $params];
    }
}
