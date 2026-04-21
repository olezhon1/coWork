<?php
// enums/SubscriptionStatus.php

enum SubscriptionStatus: string
{
    case Active   = 'active';
    case Expired  = 'expired';
    case Paused   = 'paused';

    public function label(): string
    {
        return match($this) {
            self::Active  => 'Активний',
            self::Expired => 'Закінчився',
            self::Paused  => 'Призупинено',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Active  => 'b-green',
            self::Expired => 'b-red',
            self::Paused  => 'b-amber',
        };
    }

    public static function options(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->label();
        }
        return $result;
    }
}
