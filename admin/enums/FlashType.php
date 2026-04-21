<?php
// enums/FlashType.php

enum FlashType: string
{
    case Ok      = 'ok';
    case Error   = 'err';
    case Warning = 'warn';
    case Info    = 'info';

    public function alertClass(): string
    {
        return match($this) {
            self::Ok      => 'alert-ok',
            self::Error   => 'alert-err',
            self::Warning => 'alert-warn',
            self::Info    => 'alert-info',
        };
    }

    public function iconName(): string
    {
        return match($this) {
            self::Ok      => 'success',
            self::Error   => 'error',
            self::Warning => 'warning',
            self::Info    => 'info',
        };
    }
}
