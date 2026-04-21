<?php
// enums/WorkspaceType.php

enum WorkspaceType: string
{
    case Open       = 'open';
    case Cabinet    = 'cabinet';
    case Conference = 'conference';
    case Silent     = 'silent';
    case Event      = 'event';
    case Photo      = 'photo';
    case Recording  = 'rec';
    case Collab     = 'collab';

    public function label(): string
    {
        return match($this) {
            self::Open       => 'Відкрита зона',
            self::Cabinet    => 'Кабінет',
            self::Conference => 'Конференц-зал',
            self::Silent     => 'Тихий простір',
            self::Event      => 'Подійний простір',
            self::Photo      => 'Фотостудія',
            self::Recording  => 'Рекорд-студія',
            self::Collab     => 'Колаб-зона',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Open       => 'b-green',
            self::Cabinet    => 'b-blue',
            self::Conference => 'b-warm',
            self::Silent     => 'b-gray',
            self::Event      => 'b-warm',
            self::Photo      => 'b-blue',
            self::Recording  => 'b-blue',
            self::Collab     => 'b-amber',
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
