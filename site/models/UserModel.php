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
        $this->exec(
            'INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)',
            [$fullName, $email, $passwordHash, $phone, 'user']
        );
        return $this->lastId();
    }
}
