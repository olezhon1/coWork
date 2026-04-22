<?php
// db/UserRepository.php

require_once __DIR__ . '/BaseRepository.php';

class UserRepository extends BaseRepository
{
    /** Пошук користувача з правами на адмінку (admin | content_manager). */
    public function findAdminByEmail(string $email): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM users WHERE email = ? AND role IN ('admin','content_manager')",
            [$email]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public function findAll(int $offset = 0, int $limit = 20, array $filters = []): array
    {
        [$where, $params] = $this->buildFilters($filters);
        return $this->fetchAll(
            "SELECT * FROM users WHERE {$where} ORDER BY id DESC
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [...$params, $offset, $limit]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function total(array $filters = []): int
    {
        [$where, $params] = $this->buildFilters($filters);
        return $this->countWhere('users', $where, $params);
    }

    public function create(string $fullName, string $email, string $passwordHash, string $phone, string $role): int
    {
        $this->execute(
            'INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)',
            [$fullName, $email, $passwordHash, $phone, $role]
        );
        return $this->lastId();
    }

    public function update(int $id, string $fullName, string $email, string $phone, string $role): void
    {
        $this->execute(
            'UPDATE users SET full_name=?, email=?, phone=?, role=? WHERE id=?',
            [$fullName, $email, $phone, $role, $id]
        );
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->execute('UPDATE users SET password_hash=? WHERE id=?', [$passwordHash, $id]);
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM users WHERE id = ?', [$id]);
    }

    private function buildFilters(array $filters): array
    {
        $conds  = ['1=1'];
        $params = [];
        if (!empty($filters['search'])) {
            $conds[]  = "(full_name LIKE ? OR email LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['role'])) {
            $conds[]  = 'role = ?';
            $params[] = $filters['role'];
        }
        return [implode(' AND ', $conds), $params];
    }
}
