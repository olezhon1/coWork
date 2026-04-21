<?php
// site/models/Db.php
// Базовий клас для всіх моделей публічного сайту.
// Використовує getDB() з admin/config/database.php.

abstract class Db
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    private function bind(PDOStatement $st, array $params): void
    {
        foreach ($params as $i => $v) {
            $type = is_int($v) ? PDO::PARAM_INT : (is_bool($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            $st->bindValue($i + 1, is_bool($v) ? (int) $v : $v, $type);
        }
    }

    protected function all(string $sql, array $params = []): array
    {
        $st = $this->db->prepare($sql);
        $this->bind($st, $params);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function one(string $sql, array $params = []): ?array
    {
        $st = $this->db->prepare($sql);
        $this->bind($st, $params);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    protected function exec(string $sql, array $params = []): int
    {
        $st = $this->db->prepare($sql);
        $this->bind($st, $params);
        $st->execute();
        return (int) $st->rowCount();
    }

    protected function lastId(): int
    {
        return (int) ($this->one('SELECT SCOPE_IDENTITY() AS id')['id'] ?? 0);
    }

    /**
     * Виконує INSERT і повертає згенерований id.
     * Використовує OUTPUT INSERTED.id — надійно працює через pdo_sqlsrv
     * (на відміну від SCOPE_IDENTITY(), яке повертає NULL при виклику в окремому prepare-виклику).
     */
    protected function insertReturningId(string $sql, array $params = [], string $column = 'id'): int
    {
        $modified = preg_replace(
            '/\)\s+VALUES\s*\(/i',
            ') OUTPUT INSERTED.' . $column . ' VALUES (',
            $sql,
            1
        );
        $st = $this->db->prepare($modified);
        $this->bind($st, $params);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return (int) ($row[$column] ?? 0);
    }

    public function beginTransaction(): void { $this->db->beginTransaction(); }
    public function commit(): void            { $this->db->commit(); }
    public function rollBack(): void          { if ($this->db->inTransaction()) $this->db->rollBack(); }
}
