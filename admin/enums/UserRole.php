<?php
// enums/UserRole.php

enum UserRole: string
{
    case Admin  = 'admin';
    case User   = 'user';

    public function label(): string
    {
        return match($this) {
            self::Admin => 'Адміністратор',
            self::User  => 'Користувач',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Admin => 'b-warm',
            self::User  => 'b-blue',
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
