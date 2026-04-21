<?php
// enums/AdminTable.php

enum AdminTable: string
{
    case Users              = 'users';
    case Coworkings         = 'coworkings';
    case Workspaces         = 'workspaces';
    case OperatingHours     = 'operating_hours';
    case Features           = 'features';
    case CoworkingFeatures  = 'coworking_features';
    case Gallery            = 'gallery';
    case Bookings           = 'bookings';
    case BookingSlots       = 'booking_slots';
    case Subscriptions      = 'subscriptions';
    case Reviews            = 'reviews';

    public function label(): string
    {
        return match($this) {
            self::Users             => 'Користувачі',
            self::Coworkings        => 'Коворкінги',
            self::Workspaces        => 'Робочі місця',
            self::OperatingHours    => 'Графік роботи',
            self::Features          => 'Зручності',
            self::CoworkingFeatures => 'Зручності коворкінгів',
            self::Gallery           => 'Галерея',
            self::Bookings          => 'Бронювання',
            self::BookingSlots      => 'Слоти',
            self::Subscriptions     => 'Абонементи',
            self::Reviews           => 'Відгуки',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Users             => 'users',
            self::Coworkings        => 'coworkings',
            self::Workspaces        => 'workspaces',
            self::OperatingHours    => 'operating_hours',
            self::Features          => 'features',
            self::CoworkingFeatures => 'features',
            self::Gallery           => 'gallery',
            self::Bookings          => 'bookings',
            self::BookingSlots      => 'booking_slots',
            self::Subscriptions     => 'subscriptions',
            self::Reviews           => 'reviews',
        };
    }

    public function isReadOnly(): bool
    {
        return match($this) {
            self::Reviews => true,
            default       => false,
        };
    }

    public function readOnlyNote(): ?string
    {
        return match($this) {
            self::Reviews => 'Відгуки залишають користувачі. Адмін може переглядати та видаляти спам.',
            default       => null,
        };
    }

    public function navGroup(): string
    {
        return match($this) {
            self::Users                             => 'users',
            self::Coworkings, self::Workspaces,
            self::OperatingHours, self::Features,
            self::CoworkingFeatures, self::Gallery  => 'catalog',
            default                                 => 'service',
        };
    }

    public static function tryFromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
