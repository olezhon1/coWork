<?php
// site/models/WorkspaceModel.php

class WorkspaceModel extends Db
{
    public function findById(int $id): ?array
    {
        return $this->one(
            'SELECT w.*, c.name AS coworking_name, c.city AS coworking_city, c.is_24_7
             FROM workspaces w JOIN coworkings c ON c.id = w.coworking_id
             WHERE w.id = ?',
            [$id]
        );
    }

    public function findByCoworking(int $coworkingId): array
    {
        return $this->all(
            'SELECT w.*
         FROM workspaces w
         WHERE w.coworking_id = ?
         ORDER BY w.price_per_hour ASC',
            [$coworkingId]
        );
    }

    /** Підрахунок коворкінгів, які мають хоча б один воркспейс заданого типу */
    public function coworkingsCountByType(string $typeKey, ?string $city = null): int
    {
        $sql = 'SELECT COUNT(DISTINCT w.coworking_id) AS cnt FROM workspaces w';
        $params = [];
        $where = ' WHERE w.type_key = ?';
        $params[] = $typeKey;
        if ($city) {
            $sql .= ' JOIN coworkings c ON c.id = w.coworking_id';
            $where .= ' AND c.city = ?';
            $params[] = $city;
        }
        $row = $this->one($sql . $where, $params);
        return (int) ($row['cnt'] ?? 0);
    }

    public function minPriceByType(string $typeKey, ?string $city = null): ?float
    {
        $sql = 'SELECT MIN(w.price_per_hour) AS p FROM workspaces w';
        $params = [];
        $where = ' WHERE w.type_key = ?';
        $params[] = $typeKey;
        if ($city) {
            $sql .= ' JOIN coworkings c ON c.id = w.coworking_id';
            $where .= ' AND c.city = ?';
            $params[] = $city;
        }
        $row = $this->one($sql . $where, $params);
        return isset($row['p']) ? (float) $row['p'] : null;
    }
}
