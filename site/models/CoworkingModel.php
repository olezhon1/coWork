<?php
// site/models/CoworkingModel.php

class CoworkingModel extends Db
{
    /** Базовий SELECT з головним фото і середньою оцінкою */
    private function baseSelect(): string
    {
        return "SELECT c.*,
                       (SELECT TOP 1 image_url FROM gallery
                        WHERE entity_type='coworking' AND entity_id=c.id
                        ORDER BY is_main DESC, id DESC) AS main_image,
                       (SELECT AVG(CAST(rating AS FLOAT)) FROM reviews WHERE coworking_id=c.id) AS avg_rating,
                       (SELECT COUNT(*) FROM reviews WHERE coworking_id=c.id) AS reviews_count,
                       (SELECT MIN(price_per_hour) FROM workspaces WHERE coworking_id=c.id) AS price_from
                FROM coworkings c";
    }

    public function findById(int $id): ?array
    {
        return $this->one($this->baseSelect() . ' WHERE c.id = ?', [$id]);
    }

    /**
     * @param array $filters: city, is_24_7, feature_ids (int[]), workspace_type_key, search
     * @param string $sort: rating|price|new|name
     */
    public function search(array $filters = [], string $sort = 'rating', int $offset = 0, int $limit = 20): array
    {
        [$where, $whereParams, $joins, $joinParams] = $this->buildFilters($filters);
        $order = $this->buildOrder($sort);

        return $this->all(
            $this->baseSelect() . " {$joins} WHERE {$where}
             ORDER BY {$order}
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$joinParams, ...$whereParams, $offset, $limit]
        );
    }

    public function count(array $filters = []): int
    {
        [$where, $whereParams, $joins, $joinParams] = $this->buildFilters($filters);
        $row = $this->one(
            "SELECT COUNT(DISTINCT c.id) AS cnt FROM coworkings c {$joins} WHERE {$where}",
            [...$joinParams, ...$whereParams]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    public function popular(int $limit = 20, ?string $city = null): array
    {
        $filters = [];
        if ($city) $filters['city'] = $city;
        return $this->search($filters, 'rating', 0, $limit);
    }

    public function distinctCities(): array
    {
        $rows = $this->all(
            "SELECT city, COUNT(*) AS cnt FROM coworkings
             WHERE city IS NOT NULL AND city <> ''
             GROUP BY city ORDER BY cnt DESC, city ASC"
        );
        return array_map(fn($r) => $r['city'], $rows);
    }

    public function withCoordinates(?string $city = null): array
    {
        $where = "c.latitude IS NOT NULL AND c.longitude IS NOT NULL";
        $params = [];
        if ($city) { $where .= ' AND c.city = ?'; $params[] = $city; }
        return $this->all(
            "SELECT c.id, c.name, c.address, c.city, c.latitude, c.longitude,
                    (SELECT TOP 1 image_url FROM gallery
                     WHERE entity_type='coworking' AND entity_id=c.id
                     ORDER BY is_main DESC, id DESC) AS main_image
             FROM coworkings c WHERE {$where}",
            $params
        );
    }

    /**
     * @return array{0:string, 1:array, 2:string, 3:array}
     *   [WHERE, whereParams, JOINS, joinParams]
     *   Окремі масиви параметрів для JOIN і WHERE, оскільки в SQL `?` у JOIN
     *   зʼявляються раніше, ніж у WHERE — тому їх не можна змішувати в один масив.
     */
    private function buildFilters(array $f): array
    {
        $conds = ['1=1'];
        $whereParams = [];
        $joins = '';
        $joinParams = [];

        if (!empty($f['city'])) {
            $conds[] = 'c.city = ?';
            $whereParams[] = $f['city'];
        }
        if (isset($f['is_24_7']) && $f['is_24_7'] !== '') {
            $conds[] = 'c.is_24_7 = ?';
            $whereParams[] = (int) $f['is_24_7'];
        }
        if (!empty($f['search'])) {
            $conds[] = '(c.name LIKE ? OR c.address LIKE ? OR c.city LIKE ?)';
            $s = '%' . $f['search'] . '%';
            $whereParams[] = $s; $whereParams[] = $s; $whereParams[] = $s;
        }
        if (!empty($f['workspace_type_key'])) {
            $joins .= ' INNER JOIN workspaces w_ft ON w_ft.coworking_id = c.id ';
            $conds[] = 'w_ft.type_key = ?';
            $whereParams[] = $f['workspace_type_key'];
        }
        if (!empty($f['feature_ids']) && is_array($f['feature_ids'])) {
            foreach ($f['feature_ids'] as $idx => $fid) {
                $alias = "cf_{$idx}";
                $joins .= " INNER JOIN coworking_features {$alias} ON {$alias}.coworking_id = c.id AND {$alias}.feature_id = ? ";
                $joinParams[] = (int) $fid;
            }
        }
        if (!empty($f['price_max'])) {
            $conds[] = '(SELECT MIN(price_per_hour) FROM workspaces WHERE coworking_id=c.id) <= ?';
            $whereParams[] = (float) $f['price_max'];
        }

        return [implode(' AND ', $conds), $whereParams, $joins, $joinParams];
    }

    private function buildOrder(string $sort): string
    {
        return match ($sort) {
            'price'  => '(SELECT MIN(price_per_hour) FROM workspaces WHERE coworking_id=c.id) ASC, c.id DESC',
            'new'    => 'c.id DESC',
            'name'   => 'c.name ASC',
            default  => '(SELECT AVG(CAST(rating AS FLOAT)) FROM reviews WHERE coworking_id=c.id) DESC, c.id DESC',
        };
    }
}
