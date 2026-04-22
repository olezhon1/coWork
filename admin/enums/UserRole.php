<?php
// enums/UserRole.php

enum UserRole: string
{
    case Admin          = 'admin';
    case ContentManager = 'content_manager';
    case User           = 'user';

    public function label(): string
    {
        return match($this) {
            self::Admin          => 'Адміністратор',
            self::ContentManager => 'Контент-менеджер',
            self::User           => 'Користувач',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Admin          => 'b-warm',
            self::ContentManager => 'b-green',
            self::User           => 'b-blue',
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

    /** Чи має ця роль право заходити в адмін-панель */
    public function canAccessAdmin(): bool
    {
        return match($this) {
            self::Admin, self::ContentManager => true,
            self::User                        => false,
        };
    }

    /** Суперадмін (доступ до всіх таблиць, Сервіс, Налаштування, Журнал дій) */
    public function isSuperAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Дозволені таблиці для цієї ролі.
     * @return AdminTable[]
     */
    public function allowedTables(): array
    {
        return match($this) {
            self::Admin => AdminTable::cases(),
            self::ContentManager => [
                AdminTable::Coworkings,
                AdminTable::Workspaces,
                AdminTable::OperatingHours,
                AdminTable::Features,
                AdminTable::CoworkingFeatures,
                AdminTable::Gallery,
            ],
            self::User => [],
        };
    }

    public function canAccessTable(AdminTable $t): bool
    {
        foreach ($this->allowedTables() as $allowed) {
            if ($allowed === $t) return true;
        }
        return false;
    }

    /** Доступ до системних розділів (audit / service / settings). Тільки суперадмін. */
    public function canAccessSystemSection(): bool
    {
        return $this->isSuperAdmin();
    }
}
