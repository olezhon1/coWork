<?php
// enums/FormFieldType.php

enum FormFieldType: string
{
    case Text               = 'text';
    case Number             = 'number';
    case Date               = 'date';
    case Time               = 'time';
    case Url                = 'url';
    case Email              = 'email';
    case Password           = 'password';
    case Textarea           = 'textarea';
    case Select             = 'select';
    case Checkbox           = 'checkbox';
    case SelectCoworkings   = 'select_coworkings';
    case SelectWorkspaces   = 'select_workspaces';
    case SelectFeatures     = 'select_features';
    case SelectBookings     = 'select_bookings';
    case SelectUsers        = 'select_users';

    public function isInput(): bool
    {
        return match($this) {
            self::Text, self::Number, self::Date, self::Time,
            self::Url, self::Email, self::Password => true,
            default => false,
        };
    }

    public function isSelect(): bool
    {
        return match($this) {
            self::Select, self::SelectCoworkings, self::SelectWorkspaces,
            self::SelectFeatures, self::SelectBookings, self::SelectUsers => true,
            default => false,
        };
    }

    public function isRelationalSelect(): bool
    {
        return match($this) {
            self::SelectCoworkings, self::SelectWorkspaces,
            self::SelectFeatures, self::SelectBookings, self::SelectUsers => true,
            default => false,
        };
    }
}
