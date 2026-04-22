<?php
// site/models/UserModel.php

class UserModel extends Db
{
    public function findById(int $id): ?array
    {
        return $this->one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->one('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public function emailExists(string $email): bool
    {
        return !is_null($this->one('SELECT id FROM users WHERE email = ?', [$email]));
    }

    public function register(string $fullName, string $email, string $phone, string $passwordHash): int
    {
        return $this->insertReturningId(
            'INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)',
            [$fullName, $email, $passwordHash, $phone, 'user']
        );
    }

    public function updateProfile(int $id, string $fullName, string $email, string $phone): void
    {
        $this->exec(
            'UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?',
            [$fullName, $email, $phone, $id]
        );
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->exec(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [$passwordHash, $id]
        );
    }
}
