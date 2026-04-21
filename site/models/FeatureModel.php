<?php
// site/models/FeatureModel.php

class FeatureModel extends Db
{
    public function findAll(): array
    {
        return $this->all('SELECT id, name, icon_key FROM features ORDER BY name');
    }

    public function top(int $limit = 8): array
    {
        return $this->all(
            'SELECT TOP ' . (int) $limit . ' f.id, f.name, f.icon_key, COUNT(cf.coworking_id) AS cnt
             FROM features f LEFT JOIN coworking_features cf ON cf.feature_id = f.id
             GROUP BY f.id, f.name, f.icon_key
             ORDER BY cnt DESC, f.name ASC'
        );
    }

    public function forCoworking(int $coworkingId, int $limit = 0): array
    {
        $limitSql = $limit > 0 ? 'TOP ' . (int) $limit : '';
        return $this->all(
            "SELECT {$limitSql} f.id, f.name, f.icon_key
             FROM features f
             JOIN coworking_features cf ON cf.feature_id = f.id
             WHERE cf.coworking_id = ?
             ORDER BY f.name",
            [$coworkingId]
        );
    }
}
