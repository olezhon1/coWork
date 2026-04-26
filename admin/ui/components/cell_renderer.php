<?php
// ui/components/cell_renderer.php

function renderCell(AdminTable $table, string $col, array $row): string
{
    $raw = $row[$col] ?? null;
    $str = (string) ($raw ?? '');

    return match (true) {

        // ── Обчислювані ───────────────────────────────────────────────────
        $col === '_workspace_count' => (function () use ($row): string {
            require_once __DIR__ . '/../../db/CoworkingRepository.php';
            $cnt = (new CoworkingRepository())->workspaceCount((int) $row['id']);
            return '<span style="color:var(--text3);">' . $cnt . '</span>';
        })(),

        // ── Статуси ───────────────────────────────────────────────────────
        in_array($col, ['status', 'booking_status']) && BookingStatus::tryFrom($str) !== null => (function () use ($str): string {
            $e = BookingStatus::from($str);
            return '<span class="badge ' . h($e->badgeClass()) . '">' . h($e->label()) . '</span>';
        })(),

        // ── Роль користувача ──────────────────────────────────────────────
        $col === 'role' && UserRole::tryFrom($str) !== null => (function () use ($str): string {
            $e = UserRole::from($str);
            return '<span class="badge ' . h($e->badgeClass()) . '">' . h($e->label()) . '</span>';
        })(),

        // ── Тип робочого місця ────────────────────────────────────────────
        in_array($col, ['type', 'type_key']) && $table === AdminTable::Workspaces && WorkspaceType::tryFrom($str) !== null => (function () use ($str): string {
            $e = WorkspaceType::from($str);
            return '<span class="badge ' . h($e->badgeClass()) . '">' . h($e->label()) . '</span>';
        })(),

        $col === 'entity_id' => '<span class="badge b-blue">Coworking</span>',

        // ── is_main галерея ───────────────────────────────────────────────
        $col === 'is_main' => (function () use ($raw): string {
            return $raw ? '<span class="badge b-warm">Головне</span>' : '<span style="color:var(--text3);">—</span>';
        })(),

        // ── is_24_7 коворкінг ─────────────────────────────────────────────
        $col === 'is_24_7' => (function () use ($raw): string {
            return $raw ? '<span class="badge b-green">24/7</span>' : '<span style="color:var(--text3);">—</span>';
        })(),

        // ── is_closed графік ─────────────────────────────────────────────
        $col === 'is_closed' => (function () use ($raw): string {
            return $raw ? '<span class="badge b-red">Вихідний</span>' : '<span class="badge b-green">Робочий</span>';
        })(),

        // ── День тижня ───────────────────────────────────────────────────
        $col === 'day_of_week' || $col === 'day_name' => (function () use ($row, $col, $raw): string {
            $names = [1=>'Пн',2=>'Вт',3=>'Ср',4=>'Чт',5=>'Пт',6=>'Сб',7=>'Нд'];
            $dayNum = $col === 'day_name' ? ($row['day_of_week'] ?? $raw) : $raw;
            $short  = $names[(int)$dayNum] ?? (string)$dayNum;
            $full   = $row['day_name'] ?? ($names[(int)$dayNum] ?? (string)$dayNum);
            return '<span title="' . h($full) . '">' . h($short) . '</span>';
        })(),

        // ── Рейтинг ───────────────────────────────────────────────────────
        $col === 'rating' && $raw !== null => (function () use ($raw): string {
            $r = (int) $raw;
            $stars = str_repeat('★', $r) . str_repeat('☆', 5 - $r);
            return '<span style="color:var(--amber); letter-spacing:1px;">' . $stars . '</span>';
        })(),

        // ── Ціна ──────────────────────────────────────────────────────────
        in_array($col, ['price_per_hour', 'total_price']) && $raw !== null => (function () use ($raw): string {
            return '<span>' . number_format((float) $raw, 2, ',', ' ') . ' грн</span>';
        })(),

        // ── URL фото ──────────────────────────────────────────────────────
        $col === 'image_url' && $str !== '' => (function () use ($str): string {
            return '<a href="' . h($str) . '" target="_blank" class="btn btn-sm btn-ghost">'
                . icon('link') . ' фото</a>';
        })(),

        // ── Дати/час ──────────────────────────────────────────────────────
        in_array($col, ['created_at']) && $str !== '' => (function () use ($str): string {
            $ts = strtotime($str);
            return $ts ? '<span style="color:var(--text2);">' . date('d.m.Y H:i', $ts) . '</span>' : h($str);
        })(),

        in_array($col, ['date', 'end_date', 'expire_date']) && $str !== '' => (function () use ($str): string {
            $ts = strtotime($str);
            return $ts ? '<span>' . date('d.m.Y', $ts) . '</span>' : h($str);
        })(),

        in_array($col, ['start_time', 'end_time']) && $str !== '' => (function () use ($str): string {
            // start_time/end_time — DATETIME в БД
            $ts = strtotime($str);
            return $ts ? '<span style="color:var(--text2);">' . date('d.m H:i', $ts) . '</span>' : h($str);
        })(),

        in_array($col, ['open_time', 'close_time']) && $str !== '' => (function () use ($str): string {
            // TIME поле — просто обрізаємо секунди
            return '<span>' . h(substr($str, 0, 5)) . '</span>';
        })(),

        // ── ID ────────────────────────────────────────────────────────────
        $col === 'id' || str_ends_with($col, '_id') => '<span style="color:var(--text3); font-family:var(--font-mono); font-size:var(--fs-xs);">' . h($str) . '</span>',

        // ── Довгі тексти ──────────────────────────────────────────────────
        in_array($col, ['description', 'comment', 'address']) => (function () use ($str): string {
            $short = mb_strimwidth($str, 0, 55, '…');
            return '<span title="' . h($str) . '">' . h($short) . '</span>';
        })(),

        // ── Email ─────────────────────────────────────────────────────────
        $col === 'email' && $str !== '' => '<a href="mailto:' . h($str) . '" style="color:var(--accent);">' . h($str) . '</a>',

        // ── Дефолт ────────────────────────────────────────────────────────
        default => h(mb_strimwidth($str, 0, 60, '…')),
    };
}
