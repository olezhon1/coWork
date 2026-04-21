<?php
// site/models/SubscriptionModel.php

class SubscriptionModel extends Db
{
    public function findByUser(int $userId): array
    {
        return $this->all(
            'SELECT s.*, c.name AS coworking_name, sp.name AS plan_name
             FROM subscriptions s
             LEFT JOIN coworkings c ON c.id = s.coworking_id
             LEFT JOIN subscription_plans sp ON sp.id = s.plan_id
             WHERE s.user_id = ?
             ORDER BY s.id DESC',
            [$userId]
        );
    }

    /** Купівля плану → створення запису в subscriptions */
    public function purchase(int $userId, array $plan): int
    {
        $endDate = date('Y-m-d H:i:s', strtotime('+' . (int) $plan['duration_days'] . ' days'));
        return $this->insertReturningId(
            'INSERT INTO subscriptions (user_id, coworking_id, hours_left, end_date, status, plan_id)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $plan['coworking_id'] ?? null,
                (int) $plan['hours_included'],
                $endDate,
                'active',
                (int) $plan['id'],
            ]
        );
    }
}
