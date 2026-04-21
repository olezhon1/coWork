<?php
// db/OperatingHoursRepository.php

require_once __DIR__ . '/BaseRepository.php';

class OperatingHoursRepository extends BaseRepository
{
    private static array $DAY_NAMES = [
        1 => 'Понеділок', 2 => 'Вівторок', 3 => 'Середа',
        4 => 'Четвер', 5 => 'П\'ятниця', 6 => 'Субота', 7 => 'Неділя',
    ];

    public function findAll(int $offset = 0, int $limit = 20, array $filters = []): array
    {
        [$where, $params] = $this->buildFilters($filters);
        $rows = $this->fetchAll(
            "SELECT oh.*, c.name AS coworking_name
             FROM operating_hours oh
             LEFT JOIN coworkings c ON c.id = oh.coworking_id
             WHERE {$where}
             ORDER BY oh.coworking_id, oh.day_of_week
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$params, $offset, $limit]
        );
        foreach ($rows as &$r) {
            $r['day_name'] = self::$DAY_NAMES[$r['day_of_week']] ?? $r['day_of_week'];
        }
        return $rows;
    }

    public function findById(int $id): ?array
    {
        $row = $this->fetchOne(
            'SELECT oh.*, c.name AS coworking_name
             FROM operating_hours oh
             LEFT JOIN coworkings c ON c.id = oh.coworking_id
             WHERE oh.id = ?',
            [$id]
        );
        if ($row) $row['day_name'] = self::$DAY_NAMES[$row['day_of_week']] ?? $row['day_of_week'];
        return $row;
    }

    public function findByCoworking(int $coworkingId): array
    {
        $rows = $this->fetchAll(
            'SELECT * FROM operating_hours WHERE coworking_id = ? ORDER BY day_of_week',
            [$coworkingId]
        );
        foreach ($rows as &$r) {
            $r['day_name'] = self::$DAY_NAMES[$r['day_of_week']] ?? $r['day_of_week'];
        }
        return $rows;
    }

    public function total(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        return (int) ($this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM operating_hours oh
             LEFT JOIN coworkings c ON c.id = oh.coworking_id
             WHERE {$where}",
            $params
        )['cnt'] ?? 0);
    }

    public function coworkingExists(int $id): bool
    {
        return $this->existsById('coworkings', $id);
    }

    public function create(int $coworkingId, int $dayOfWeek, ?string $openTime, ?string $closeTime, bool $isClosed): int
    {
        $this->execute(
            'INSERT INTO operating_hours (coworking_id, day_of_week, open_time, close_time, is_closed)
             VALUES (?, ?, ?, ?, ?)',
            [$coworkingId, $dayOfWeek, $openTime, $closeTime, $isClosed ? 1 : 0]
        );
        return $this->lastId();
    }

    public function update(int $id, int $coworkingId, int $dayOfWeek, ?string $openTime, ?string $closeTime, bool $isClosed): void
    {
        $this->execute(
            'UPDATE operating_hours SET coworking_id=?, day_of_week=?, open_time=?, close_time=?, is_closed=? WHERE id=?',
            [$coworkingId, $dayOfWeek, $openTime, $closeTime, $isClosed ? 1 : 0, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM operating_hours WHERE id = ?', [$id]);
    }

    public static function dayOptions(): array
    {
        return self::$DAY_NAMES;
    }

    private function buildFilters(array $filters): array
    {
        $conds  = ['1=1'];
        $params = [];
        if (!empty($filters['coworking_id'])) {
            $conds[]  = 'oh.coworking_id = ?';
            $params[] = (int) $filters['coworking_id'];
        }
        if (isset($filters['is_closed']) && $filters['is_closed'] !== '') {
            $conds[]  = 'oh.is_closed = ?';
            $params[] = (int) $filters['is_closed'];
        }
        return [implode(' AND ', $conds), $params];
    }
}
