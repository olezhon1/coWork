<?php
// site/models/OperatingHoursModel.php

class OperatingHoursModel extends Db
{
    public function forCoworking(int $coworkingId): array
    {
        return $this->all(
            'SELECT * FROM operating_hours WHERE coworking_id = ? ORDER BY day_of_week',
            [$coworkingId]
        );
    }

    /**
     * Повертає true, якщо інтервал [$startTime, $endTime] повністю попадає
     * в години роботи коворкінгу (або коворкінг 24/7).
     */
    public function intervalWithinHours(int $coworkingId, bool $is247, string $startTime, string $endTime): bool
    {
        if ($is247) return true;

        $hours = $this->forCoworking($coworkingId);
        if (empty($hours)) return true; // якщо графіка не заведено — дозволяємо (fallback)

        $map = [];
        foreach ($hours as $h) {
            $map[(int) $h['day_of_week']] = $h;
        }

        $cursor = strtotime($startTime);
        $end = strtotime($endTime);
        if ($cursor === false || $end === false || $cursor >= $end) return false;

        // Перевіряємо кожен день, який зачіпається інтервалом
        while ($cursor < $end) {
            $dayNum = (int) date('N', $cursor);
            $row = $map[$dayNum] ?? null;
            if (!$row || !empty($row['is_closed'])) return false;

            $open  = $row['open_time']  ?? '00:00:00';
            $close = $row['close_time'] ?? '23:59:59';

            $dayDate = date('Y-m-d', $cursor);
            $dayOpen  = strtotime($dayDate . ' ' . $open);
            $dayClose = strtotime($dayDate . ' ' . $close);

            $chunkStart = max($cursor, $dayOpen);
            $chunkEnd   = min($end, $dayClose);

            // Якщо наш інтервал на цей день виходить за межі робочих годин
            $intervalOnThisDayStart = max($cursor, strtotime($dayDate . ' 00:00:00'));
            $intervalOnThisDayEnd   = min($end,    strtotime($dayDate . ' 23:59:59'));
            if ($intervalOnThisDayStart < $dayOpen || $intervalOnThisDayEnd > $dayClose) {
                return false;
            }

            // Перехід на наступний день
            $cursor = strtotime($dayDate . ' 00:00:00 +1 day');
        }
        return true;
    }
}
