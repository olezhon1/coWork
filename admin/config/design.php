<?php
// config/design.php — Warm Minimal. Узгоджено з shared/css/tokens.css
return [
    'colors' => [
        // Base
        '--bg'          => '#F7F4EF',
        '--bg2'         => '#EFEAE0',
        '--bg3'         => '#E6DED1',
        '--bg4'         => '#D8CCB8',
        '--surface'     => '#FFFDF8',
        '--surface2'    => '#FBF8F1',
        '--border'      => '#E2DACE',
        '--border2'     => '#CEC3B1',
        // Text
        '--text'        => '#1C1A17',
        '--text2'       => '#3A3630',
        '--text3'       => '#7B756D',
        // Accent (deep green)
        '--accent'      => '#2F6B55',
        '--accent-lt'   => '#E4EDE8',
        '--accent-dk'   => '#234F40',
        // Secondary (terracotta)
        '--warm'        => '#C2662B',
        '--warm-lt'     => '#F6E9DD',
        '--warm-dk'     => '#8F4816',
        // Semantic
        '--green'       => '#4A7A3E',
        '--green-lt'    => '#E8F0E1',
        '--red'         => '#A63437',
        '--red-lt'      => '#F6E3E3',
        '--amber'       => '#B07620',
        '--amber-lt'    => '#F4E8D4',
        '--blue'        => '#3A6A8A',
        '--blue-lt'     => '#E3ECF3',
    ],
    'fonts' => [
        '--font-sans'   => "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
        '--font-serif'  => "'IBM Plex Serif', Georgia, 'Times New Roman', serif",
        '--font-mono'   => "'JetBrains Mono', 'Fira Code', ui-monospace, monospace",
        '--fs-xs'       => '11px',
        '--fs-sm'       => '13px',
        '--fs-base'     => '14px',
        '--fs-md'       => '15px',
        '--fs-lg'       => '17px',
        '--fs-xl'       => '20px',
        '--fs-2xl'      => '24px',
    ],
    'radii' => [
        '--radius'      => '8px',
        '--radius-lg'   => '12px',
        '--radius-xl'   => '18px',
        '--radius-pill' => '999px',
    ],
    'shadows' => [
        '--shadow-sm'   => '0 1px 4px rgba(28,26,23,.05)',
        '--shadow-md'   => '0 4px 16px rgba(28,26,23,.07)',
        '--shadow-lg'   => '0 12px 36px rgba(28,26,23,.10)',
    ],
];
