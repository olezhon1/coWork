<?php
// assets/icons/icons.php
// ─────────────────────────────────────────────────────────────────────────────
// Центральний реєстр SVG-іконок. Змінюй тут — застосовується скрізь.
// Використання: icon('edit')   або   icon('warning', 'color:red')
// ─────────────────────────────────────────────────────────────────────────────

function icon(string $name, string $extraStyle = ''): string
{
    $style = $extraStyle ? " style=\"{$extraStyle}\"" : '';

    $paths = match($name) {
        // ── Таблиці / навігація ────────────────────────────────────────────
        'users'             => '<path d="M5 6a3 3 0 106 0A3 3 0 005 6z"/><path d="M1 14a7 7 0 0114 0"/>',
        'operating_hours'   => '<circle cx="8" cy="8" r="6"/><path d="M8 4v4l3 1.5"/>',
        'coworking_features'=> '<path d="M3 8h10M8 3v10"/><circle cx="8" cy="8" r="6"/>',
        'coworkings'    => '<rect x="2" y="2" width="12" height="12" rx="2"/><path d="M2 7h12"/><path d="M6 7v7"/>',
        'workspaces'    => '<rect x="1" y="9" width="14" height="5" rx="1"/><path d="M4 9V5a2 2 0 014 0v4"/><path d="M8 9V5"/>',
        'bookings'      => '<rect x="2" y="3" width="12" height="11" rx="1.5"/><path d="M5 2v2M11 2v2M2 7h12"/>',
        'booking_slots' => '<circle cx="8" cy="8" r="6"/><path d="M8 5v3l2 2"/>',
        'reviews'       => '<path d="M8 2l1.5 3 3.5.5-2.5 2.5.5 3.5L8 10l-3 1.5.5-3.5L3 5.5l3.5-.5z"/>',
        'features'      => '<path d="M8 2a6 6 0 100 12A6 6 0 008 2z"/><path d="M8 6v4M6 8h4"/>',
        'gallery'       => '<rect x="2" y="3" width="12" height="10" rx="1.5"/><circle cx="6" cy="7" r="1.5"/><path d="M2 12l4-3 3 2.5 2-2 3 3"/>',
        'subscriptions' => '<rect x="2" y="4" width="12" height="9" rx="1.5"/><path d="M5 4V3a1 1 0 011-1h4a1 1 0 011 1v1"/><path d="M2 8h12"/>',
        'dashboard'     => '<rect x="2" y="2" width="5" height="5" rx="1"/><rect x="9" y="2" width="5" height="5" rx="1"/><rect x="2" y="9" width="5" height="5" rx="1"/><rect x="9" y="9" width="5" height="5" rx="1"/>',

        // ── Дії ───────────────────────────────────────────────────────────
        'add'           => '<path d="M8 3v10M3 8h10"/>',
        'edit'          => '<path d="M11 2l3 3-8 8H3v-3l8-8z"/>',
        'delete'        => '<path d="M3 4h10M6 4V3h4v1M5 4l.5 9h5l.5-9"/><path d="M7 7v4M9 7v4"/>',
        'save'          => '<rect x="2" y="2" width="12" height="12" rx="1.5"/><rect x="5" y="2" width="6" height="4" rx=".5"/><rect x="4" y="9" width="8" height="5" rx=".5"/>',
        'back'          => '<path d="M10 3L5 8l5 5"/>',
        'cancel'        => '<path d="M4 4l8 8M12 4l-8 8"/>',
        'logout'        => '<path d="M6 3H3a1 1 0 00-1 1v8a1 1 0 001 1h3"/><path d="M10 11l3-3-3-3M13 8H6"/>',
        'search'        => '<circle cx="7" cy="7" r="4"/><path d="M10.5 10.5l2.5 2.5"/>',
        'view'          => '<ellipse cx="8" cy="8" rx="6" ry="4"/><circle cx="8" cy="8" r="1.5"/>',
        'link'          => '<path d="M7 9a3 3 0 004.24.06l2-2a3 3 0 00-4.24-4.24L7.5 4.5"/><path d="M9 7a3 3 0 00-4.24-.06l-2 2a3 3 0 004.24 4.24l1.5-1.5"/>',
        'chevron_right' => '<path d="M6 4l4 4-4 4"/>',
        'chevron_left'  => '<path d="M10 4L6 8l4 4"/>',

        // ── Стани ─────────────────────────────────────────────────────────
        'warning'       => '<path d="M8 2L1.5 13h13z"/><path d="M8 6v3.5"/><circle cx="8" cy="11" r=".6" fill="currentColor"/>',
        'error'         => '<circle cx="8" cy="8" r="6"/><path d="M8 5v3.5"/><circle cx="8" cy="11" r=".6" fill="currentColor"/>',
        'success'       => '<circle cx="8" cy="8" r="6"/><path d="M5 8l2 2 4-4"/>',
        'info'          => '<circle cx="8" cy="8" r="6"/><path d="M8 7v4"/><circle cx="8" cy="5.5" r=".6" fill="currentColor"/>',
        'user'          => '<circle cx="8" cy="5" r="3"/><path d="M2 14a6 6 0 0112 0"/>',
        'star_filled'   => '<path d="M8 2l1.5 3 3.5.5-2.5 2.5.5 3.5L8 10l-3 1.5.5-3.5L3 5.5l3.5-.5z" fill="currentColor"/>',
        'clock'         => '<circle cx="8" cy="8" r="6"/><path d="M8 5v3l2 2"/>',
        'location'      => '<path d="M8 2a4 4 0 100 8A4 4 0 008 2z"/><path d="M8 10v4"/>',
        'history'       => '<circle cx="8" cy="8" r="6"/><path d="M8 4v4l2.5 1.5"/><path d="M3 3l1 2"/>',
        'database'      => '<ellipse cx="8" cy="4" rx="5" ry="2"/><path d="M3 4v8c0 1.1 2.2 2 5 2s5-.9 5-2V4"/><path d="M3 8c0 1.1 2.2 2 5 2s5-.9 5-2"/>',
        'settings'      => '<circle cx="8" cy="8" r="2.2"/><path d="M8 1.5l1 2 2-.5.5 2 2 1-1 2 1 2-2 1-.5 2-2-.5-1 2-1-2-2 .5-.5-2-2-1 1-2-1-2 2-1 .5-2 2 .5 1-2z"/>',
        'download'      => '<path d="M8 2v8M4 7l4 4 4-4"/><path d="M3 13h10"/>',
        'upload'        => '<path d="M8 12V4M4 7l4-4 4 4"/><path d="M3 13h10"/>',
        'archive'       => '<rect x="2" y="3" width="12" height="3" rx="1"/><path d="M3 6v7h10V6"/><path d="M6 9h4"/>',

        default         => '<circle cx="8" cy="8" r="6"/>',
    };

    return <<<HTML
<span class="icon"{$style}>
  <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" xmlns="http://www.w3.org/2000/svg">
    {$paths}
  </svg>
</span>
HTML;
}

