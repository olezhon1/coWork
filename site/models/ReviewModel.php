<?php
// site/models/ReviewModel.php

class ReviewModel extends Db
{
    public function findByCoworking(int $coworkingId, int $limit = 20): array
    {
        return $this->all(
            "SELECT r.*, u.full_name AS user_name
             FROM reviews r LEFT JOIN users u ON u.id = r.user_id
             WHERE r.coworking_id = ?
             ORDER BY r.created_at DESC, r.id DESC
             OFFSET 0 ROWS FETCH NEXT ? ROWS ONLY",
            [$coworkingId, $limit]
        );
    }

    public function avgRating(int $coworkingId): ?float
    {
        $r = $this->one(
            'SELECT AVG(CAST(rating AS FLOAT)) AS avg, COUNT(*) AS cnt
             FROM reviews WHERE coworking_id = ?',
            [$coworkingId]
        );
        return isset($r['avg']) && $r['avg'] !== null ? round((float) $r['avg'], 1) : null;
    }

    public function userHasReviewedCoworking(int $userId, int $coworkingId): bool
    {
        $r = $this->one(
            'SELECT TOP 1 id FROM reviews WHERE user_id = ? AND coworking_id = ?',
            [$userId, $coworkingId]
        );
        return $r !== null;
    }

    public function create(int $userId, int $coworkingId, int $rating, string $comment): int
    {
        $this->exec(
            'INSERT INTO reviews (user_id, coworking_id, rating, comment) VALUES (?, ?, ?, ?)',
            [$userId, $coworkingId, $rating, $comment]
        );
        return (int) ($this->one('SELECT SCOPE_IDENTITY() AS id')['id'] ?? 0);
    }
}
