<?php
// db/BaseRepository.php

require_once __DIR__ . '/../config/database.php';

abstract class BaseRepository
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /**
     * Приватний метод для автоматичного визначення типів даних (INT vs STR)
     * Це критично важливо для SQL Server та пагінації.
     */
    private function bindAutoParams(PDOStatement $st, array $params): void
    {
        foreach ($params as $index => $value) {
            // Визначаємо тип: якщо число (int) — PDO::PARAM_INT, інакше — PDO::PARAM_STR
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;

            // Плейсхолдери '?' в PDO починаються з 1
            $st->bindValue($index + 1, $value, $type);
        }
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $st = $this->db->prepare($sql);
        $this->bindAutoParams($st, $params);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $st = $this->db->prepare($sql);
        $this->bindAutoParams($st, $params);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    protected function execute(string $sql, array $params = []): int
    {
        $st = $this->db->prepare($sql);
        $this->bindAutoParams($st, $params);
        $st->execute();
        return (int) $st->rowCount();
    }

    protected function lastId(): int
    {
        return (int) ($this->fetchOne('SELECT SCOPE_IDENTITY() AS id')['id'] ?? 0);
    }

    protected function countWhere(string $table, string $where = '1=1', array $params = []): int
    {
        return (int) ($this->fetchOne("SELECT COUNT(*) AS cnt FROM {$table} WHERE {$where}", $params)['cnt'] ?? 0);
    }

    public function existsById(string $table, int $id): bool
    {
        // Передаємо масив з ID, автоматика сама зробить його INT
        return $this->countWhere($table, 'id = ?', [$id]) > 0;
    }

    public function totalRows(string $table): int
    {
        return $this->countWhere($table);
    }
}