<?php
// db/SettingsRepository.php

require_once __DIR__ . '/BaseRepository.php';

final class SettingsRepository extends BaseRepository
{
    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT id, skey, svalue, label, description, updated_at
             FROM settings ORDER BY id ASC',
        );
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $row = $this->fetchOne('SELECT svalue FROM settings WHERE skey = ?', [$key]);
        return $row ? (string) $row['svalue'] : $default;
    }

    public function set(string $key, string $value): void
    {
        $exists = $this->fetchOne('SELECT id FROM settings WHERE skey = ?', [$key]);
        if ($exists) {
            $this->execute(
                'UPDATE settings SET svalue = ?, updated_at = GETDATE() WHERE skey = ?',
                [$value, $key],
            );
        } else {
            $this->execute(
                'INSERT INTO settings (skey, svalue, updated_at) VALUES (?, ?, GETDATE())',
                [$key, $value],
            );
        }
    }

    /** @param array<string,string> $pairs */
    public function setMany(array $pairs): void
    {
        foreach ($pairs as $k => $v) {
            $this->set($k, $v);
        }
    }
}
