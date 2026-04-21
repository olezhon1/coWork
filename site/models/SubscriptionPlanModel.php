<?php
// site/models/SubscriptionPlanModel.php

class SubscriptionPlanModel extends Db
{
    public function allActive(): array
    {
        return $this->all(
            'SELECT sp.*, c.name AS coworking_name
             FROM subscription_plans sp
             LEFT JOIN coworkings c ON c.id = sp.coworking_id
             WHERE sp.is_active = 1
             ORDER BY sp.price ASC'
        );
    }

    public function findById(int $id): ?array
    {
        return $this->one(
            'SELECT sp.*, c.name AS coworking_name
             FROM subscription_plans sp
             LEFT JOIN coworkings c ON c.id = sp.coworking_id
             WHERE sp.id = ?',
            [$id]
        );
    }
}
