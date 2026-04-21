<?php
// site/models/BookingModel.php

class BookingModel extends Db
{
    public function findById(int $id): ?array
    {
        return $this->one(
            'SELECT b.*, w.name AS workspace_name, w.type_key AS workspace_type, w.price_per_hour,
                    c.id AS coworking_id, c.name AS coworking_name, c.address AS coworking_address
             FROM bookings b
             JOIN workspaces w ON w.id = b.workspace_id
             JOIN coworkings c ON c.id = w.coworking_id
             WHERE b.id = ?',
            [$id]
        );
    }

    public function findByUser(int $userId): array
    {
        return $this->all(
            "SELECT b.*, w.name AS workspace_name, w.type_key AS workspace_type,
                    c.id AS coworking_id, c.name AS coworking_name,
                    (SELECT MIN(start_time) FROM booking_slots WHERE booking_id=b.id) AS first_slot_start,
                    (SELECT MAX(end_time) FROM booking_slots WHERE booking_id=b.id) AS last_slot_end
             FROM bookings b
             JOIN workspaces w ON w.id = b.workspace_id
             JOIN coworkings c ON c.id = w.coworking_id
             WHERE b.user_id = ?
             ORDER BY b.id DESC",
            [$userId]
        );
    }

    public function create(int $userId, int $workspaceId, string $status, float $totalPrice): int
    {
        return $this->insertReturningId(
            'INSERT INTO bookings (user_id, workspace_id, status, total_price) VALUES (?, ?, ?, ?)',
            [$userId, $workspaceId, $status, $totalPrice]
        );
    }

    public function cancel(int $bookingId, int $userId): bool
    {
        $n = $this->exec(
            "UPDATE bookings SET status = 'cancelled'
             WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')",
            [$bookingId, $userId]
        );
        return $n > 0;
    }

    /** Чи мав користувач бронювання в цьому коворкінгу (для права лишати відгук) */
    public function userHasBookingInCoworking(int $userId, int $coworkingId): bool
    {
        $row = $this->one(
            'SELECT TOP 1 b.id FROM bookings b
             JOIN workspaces w ON w.id = b.workspace_id
             WHERE b.user_id = ? AND w.coworking_id = ?',
            [$userId, $coworkingId]
        );
        return $row !== null;
    }
}
