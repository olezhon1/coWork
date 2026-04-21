<?php
// site/models/BookingSlotModel.php

class BookingSlotModel extends Db
{
    public function create(int $bookingId, string $startTime, string $endTime): int
    {
        $this->exec(
            'INSERT INTO booking_slots (booking_id, start_time, end_time) VALUES (?, ?, ?)',
            [$bookingId, $startTime, $endTime]
        );
        return $this->lastId();
    }

    public function findByBooking(int $bookingId): array
    {
        return $this->all(
            'SELECT * FROM booking_slots WHERE booking_id = ? ORDER BY start_time ASC',
            [$bookingId]
        );
    }

    /**
     * Перевірка перетину зі існуючими слотами воркспейсу (крім скасованих).
     */
    public function hasConflict(int $workspaceId, string $startTime, string $endTime, ?int $excludeBookingId = null): bool
    {
        $params = [$workspaceId, $endTime, $startTime];
        $sql = "SELECT TOP 1 bs.id FROM booking_slots bs
                JOIN bookings b ON b.id = bs.booking_id
                WHERE b.workspace_id = ?
                  AND b.status <> 'cancelled'
                  AND bs.start_time < ?
                  AND bs.end_time > ?";
        if ($excludeBookingId) {
            $sql .= ' AND b.id <> ?';
            $params[] = $excludeBookingId;
        }
        return $this->one($sql, $params) !== null;
    }

    public function bookedSlotsForWorkspace(int $workspaceId, string $dayStart, string $dayEnd): array
    {
        return $this->all(
            "SELECT bs.start_time, bs.end_time
             FROM booking_slots bs
             JOIN bookings b ON b.id = bs.booking_id
             WHERE b.workspace_id = ?
               AND b.status <> 'cancelled'
               AND bs.start_time < ?
               AND bs.end_time > ?
             ORDER BY bs.start_time",
            [$workspaceId, $dayEnd, $dayStart]
        );
    }
}
