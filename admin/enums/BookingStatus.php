<?php
// enums/BookingStatus.php

enum BookingStatus: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Очікує',
            self::Confirmed => 'Підтверджено',
            self::Cancelled => 'Скасовано',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Pending   => 'b-amber',
            self::Confirmed => 'b-green',
            self::Cancelled => 'b-red',
        };
    }

    /** Повертає [value => label] для <select> */
    public static function options(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->label();
        }
        return $result;
    }
}
