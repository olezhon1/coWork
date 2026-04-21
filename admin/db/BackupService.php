<?php
// db/BackupService.php
// Обгортка над T-SQL BACKUP / RESTORE DATABASE.
// Використовується адмін-сторінками «Сервіс».

require_once __DIR__ . '/../config/database.php';

final class BackupService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /** Створює повну резервну копію БД у вказаний файл на сервері СКБД. */
    public function backupDatabase(string $filePath): void
    {
        $file = $this->sanitizeFilePath($filePath);
        // BACKUP DATABASE не підтримує параметри в prepared statement — тому формуємо
        // T-SQL безпечно з екранованих літералів.
        $sql = "BACKUP DATABASE [" . DB_NAME . "] TO DISK = N'{$file}' WITH INIT, FORMAT;";
        $this->runWithRowsetConsume($sql);
    }

    /**
     * Відновлює базу даних з .bak. УВАГА: фактично замінює поточну БД.
     * Використовує RESTORE ... WITH REPLACE, потрібні права sysadmin.
     */
    public function restoreDatabase(string $filePath): void
    {
        $file = $this->sanitizeFilePath($filePath);
        $db   = DB_NAME;
        // Встановити single-user щоб звільнити з'єднання, відновити, повернути multi-user.
        // Кожен statement виконуємо окремо — бо USE [master] змінює контекст у поточному
        // з'єднанні, а BACKUP/RESTORE повертає проміжні rowset'и.
        $this->runWithRowsetConsume("USE [master]");
        $this->runWithRowsetConsume("ALTER DATABASE [{$db}] SET SINGLE_USER WITH ROLLBACK IMMEDIATE");
        $this->runWithRowsetConsume("RESTORE DATABASE [{$db}] FROM DISK = N'{$file}' WITH REPLACE");
        $this->runWithRowsetConsume("ALTER DATABASE [{$db}] SET MULTI_USER");
    }

    /**
     * BACKUP/RESTORE у SQL Server повертають проміжні rowset-и з прогресом.
     * PDO (pdo_sqlsrv) НЕ виконає фактичну операцію, якщо ці rowset-и не прочитати
     * до кінця. Тому тут ми prepare+execute і викликаємо nextRowset() у циклі.
     */
    private function runWithRowsetConsume(string $sql): void
    {
        $st = $this->db->prepare($sql);
        $st->execute();
        do {
            try {
                while ($st->fetch(PDO::FETCH_ASSOC)) {
                    // споживаємо усі рядки поточного rowset
                }
            } catch (\Throwable) {
                // rowset без даних — нормально
            }
        } while ($st->nextRowset());
    }

    /**
     * Створює копію БД (для перенесення на інше робоче місце) через BACKUP у файл.
     * Фактично — еквівалент backupDatabase, окремий метод залишаю для семантики.
     */
    public function exportCopy(string $filePath): void
    {
        $this->backupDatabase($filePath);
    }

    /**
     * Архівує (експортує) одну таблицю у CSV-файл на сервері застосунку.
     * Повертає кількість рядків, що потрапили в файл.
     */
    public function archiveTableToCsv(string $tableName, string $targetFile): int
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
        if ($safe === '' || $safe !== $tableName) {
            throw new \InvalidArgumentException('Небезпечна назва таблиці: ' . $tableName);
        }

        $dir = dirname($targetFile);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("Не вдалося створити каталог {$dir}");
        }

        $stmt = $this->db->query("SELECT * FROM [{$safe}]");
        if ($stmt === false) {
            throw new \RuntimeException('Не вдалося прочитати таблицю');
        }

        $fh = fopen($targetFile, 'w');
        if ($fh === false) {
            throw new \RuntimeException("Не вдалося створити файл {$targetFile}");
        }

        $count = 0;
        $header = null;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($header === null) {
                $header = array_keys($row);
                fputcsv($fh, $header);
            }
            fputcsv($fh, array_map(
                static fn($v) => $v === null ? '' : (is_scalar($v) ? (string) $v : json_encode($v)),
                $row,
            ));
            $count++;
        }
        fclose($fh);
        return $count;
    }

    /** Перелік файлів у каталозі бекапу (ім'я + розмір + час) */
    public function listBackupFiles(string $dir): array
    {
        if (!is_dir($dir)) return [];
        $out = [];
        foreach (scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $f;
            if (is_file($path)) {
                $out[] = [
                    'name'  => $f,
                    'path'  => $path,
                    'size'  => filesize($path) ?: 0,
                    'mtime' => filemtime($path) ?: 0,
                ];
            }
        }
        usort($out, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
        return $out;
    }

    private function sanitizeFilePath(string $path): string
    {
        // SQL Server приймає шляхи як з прямими, так і зі зворотніми слешами.
        // Заборонено одинарний апостроф — щоб не ламати T-SQL літерал.
        if (str_contains($path, "'")) {
            throw new \InvalidArgumentException('Шлях містить недопустимий символ.');
        }
        return $path;
    }
}
