<?php
// enums/GalleryEntityType.php

enum GalleryEntityType: string
{
    case Coworking = 'coworking';
    case Workspace = 'workspace';

    public function label(): string
    {
        return match($this) {
            self::Coworking => 'Коворкінг',
            self::Workspace => 'Робоче місце',
        };
    }

    /** Відповідна таблиця в БД для перевірки існування */
    public function dbTable(): string
    {
        return match($this) {
            self::Coworking => 'coworkings',
            self::Workspace => 'workspaces',
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
