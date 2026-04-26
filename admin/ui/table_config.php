<?php
// ui/table_config.php

require_once __DIR__ . '/../enums/FormFieldType.php';
require_once __DIR__ . '/../enums/BookingStatus.php';
require_once __DIR__ . '/../enums/WorkspaceType.php';
require_once __DIR__ . '/../enums/GalleryEntityType.php';
require_once __DIR__ . '/../enums/UserRole.php';

function tableFormConfig(AdminTable $table): array
{
    return match ($table) {

        AdminTable::Users => [
            'full_name' => ['type' => FormFieldType::Text,  'label' => 'Повне ім\'я', 'req' => true, 'span' => 'full'],
            'email'     => ['type' => FormFieldType::Email, 'label' => 'Email',       'req' => true],
            'password'  => ['type' => FormFieldType::Password,'label'=> 'Пароль (залиш порожнім щоб не змінювати)', 'req' => false,
                            'hint' => 'Мінімум 8 символів'],
            'phone'     => ['type' => FormFieldType::Text,  'label' => 'Телефон',     'req' => false],
            'role'      => ['type' => FormFieldType::Select,'label' => 'Роль',        'req' => true,
                            'options' => UserRole::options()],
        ],

        AdminTable::Coworkings => [
            'name'        => ['type' => FormFieldType::Text,     'label' => 'Назва коворкінгу', 'req' => true, 'span' => 'full'],
            'city'        => ['type' => FormFieldType::Text,     'label' => 'Місто',             'req' => false],
            'address'     => ['type' => FormFieldType::Text,     'label' => 'Адреса',            'req' => false, 'span' => 'full'],
            'latitude'    => ['type' => FormFieldType::Number,   'label' => 'Широта',            'req' => false, 'hint' => 'напр. 48.46132'],
            'longitude'   => ['type' => FormFieldType::Number,   'label' => 'Довгота',           'req' => false, 'hint' => 'напр. 35.04618'],
            'is_24_7'     => ['type' => FormFieldType::Select,   'label' => 'Цілодобово?',       'req' => false,
                              'options' => ['0' => 'Ні', '1' => 'Так']],
            'description' => ['type' => FormFieldType::Textarea, 'label' => 'Опис',              'req' => false, 'span' => 'full'],
        ],

        AdminTable::Workspaces => [
            'coworking_id'   => ['type' => FormFieldType::SelectCoworkings, 'label' => 'Коворкінг',         'req' => true, 'span' => 'full'],
            'name'           => ['type' => FormFieldType::Text,             'label' => 'Назва місця',       'req' => true],
            'type_key'       => ['type' => FormFieldType::Select,           'label' => 'Тип простору',      'req' => false,
                                 'options' => WorkspaceType::options()],
            'price_per_hour' => ['type' => FormFieldType::Number,           'label' => 'Ціна за годину, грн','req' => false,
                                 'hint' => 'Десятковий дріб через крапку, напр. 150.00'],
            'capacity'       => ['type' => FormFieldType::Number,           'label' => 'Місткість (осіб)',  'req' => false],
        ],

        AdminTable::OperatingHours => [
            'coworking_id' => ['type' => FormFieldType::SelectCoworkings, 'label' => 'Коворкінг',   'req' => true, 'span' => 'full'],
            'day_of_week'  => ['type' => FormFieldType::Select,           'label' => 'День тижня',  'req' => true,
                               'options' => [1=>'Понеділок',2=>'Вівторок',3=>'Середа',4=>'Четвер',5=>'П\'ятниця',6=>'Субота',7=>'Неділя']],
            'open_time'    => ['type' => FormFieldType::Time,             'label' => 'Відкриття',   'req' => false],
            'close_time'   => ['type' => FormFieldType::Time,             'label' => 'Закриття',    'req' => false],
            'is_closed'    => ['type' => FormFieldType::Select,           'label' => 'Вихідний?',   'req' => false,
                               'options' => ['0' => 'Ні', '1' => 'Так']],
        ],

        AdminTable::Features => [
            'name'     => ['type' => FormFieldType::Text, 'label' => 'Назва зручності', 'req' => true, 'span' => 'full'],
            'icon_key' => ['type' => FormFieldType::Text, 'label' => 'Ключ іконки',     'req' => false,
                           'hint' => 'Текстовий ключ для відображення іконки, напр. wifi, parking, coffee'],
        ],

        AdminTable::CoworkingFeatures => [
            'coworking_id' => ['type' => FormFieldType::SelectCoworkings, 'label' => 'Коворкінг', 'req' => true],
            'feature_id'   => ['type' => FormFieldType::SelectFeatures,   'label' => 'Зручність', 'req' => true],
        ],

        AdminTable::Gallery => [
            'entity_id' => [
                'type' => FormFieldType::Number,
                'label' => "ID коворкінгу",
                'req' => true,
                'hint' => 'ID коворкінгу (entity_id тепер завжди = coworking_id)'
            ],

            'image_url' => [
                'type' => FormFieldType::Url,
                'label' => 'URL фото',
                'req' => true,
                'span' => 'full',
                'hint' => 'Пряме посилання на зображення (https://...)'
            ],

            'is_main' => [
                'type' => FormFieldType::Select,
                'label' => 'Головне фото?',
                'req' => false,
                'options' => ['0' => 'Ні', '1' => 'Так']
            ],
        ],

        AdminTable::Bookings => [
            'user_id'      => ['type' => FormFieldType::SelectUsers,            'label' => 'Користувач',    'req' => true, 'span' => 'full'],
            'workspace_id' => ['type' => FormFieldType::SelectWorkspaceCascade, 'label' => 'Робоче місце',  'req' => true, 'span' => 'full'],
            'status'       => ['type' => FormFieldType::Select,                 'label' => 'Статус',        'req' => false,
                               'options' => BookingStatus::options()],
            'total_price'  => ['type' => FormFieldType::Number,                 'label' => 'Загальна сума, грн','req' => false,
                               'readonly' => true,
                               'hint' => 'Розраховується автоматично: сумарні години слотів × ціна за годину воркспейсу. Щоб змінити суму — додайте/відредагуйте слоти.'],
        ],

        AdminTable::BookingSlots => [
            'booking_id' => ['type' => FormFieldType::SelectBookings, 'label' => 'Бронювання',  'req' => true, 'span' => 'full'],
            'start_time' => ['type' => FormFieldType::Text,           'label' => 'Початок',     'req' => true,
                             'hint' => 'Формат: YYYY-MM-DD HH:MM'],
            'end_time'   => ['type' => FormFieldType::Text,           'label' => 'Кінець',      'req' => true,
                             'hint' => 'Формат: YYYY-MM-DD HH:MM'],
        ],

        AdminTable::Reviews => [],
    };
}

function loadSelectOptions(FormFieldType $type): array
{
    return match ($type) {
        FormFieldType::SelectCoworkings => (function () {
            require_once __DIR__ . '/../db/CoworkingRepository.php';
            return array_column((new CoworkingRepository())->allForSelect(), 'name', 'id');
        })(),
        FormFieldType::SelectWorkspaces => (function () {
            require_once __DIR__ . '/../db/WorkspaceRepository.php';
            return array_column((new WorkspaceRepository())->allForSelect(), 'label', 'id');
        })(),
        FormFieldType::SelectWorkspaceCascade => (function () {
            require_once __DIR__ . '/../db/WorkspaceRepository.php';
            require_once __DIR__ . '/../db/CoworkingRepository.php';
            return [
                'coworkings' => array_column((new CoworkingRepository())->allForSelect(), 'name', 'id'),
                'workspaces' => (new WorkspaceRepository())->allForCascade(),
            ];
        })(),
        FormFieldType::SelectFeatures => (function () {
            require_once __DIR__ . '/../db/FeatureRepository.php';
            return array_column((new FeatureRepository())->allForSelect(), 'name', 'id');
        })(),
        FormFieldType::SelectBookings => (function () {
            require_once __DIR__ . '/../db/BookingRepository.php';
            $rows = (new BookingRepository())->findAll(0, 200);
            $opts = [];
            foreach ($rows as $r) {
                $opts[$r['id']] = '#' . $r['id'] . ' — ' . ($r['workspace_name'] ?? '?') . ' / ' . ($r['user_name'] ?? $r['user_id']);
            }
            return $opts;
        })(),
        FormFieldType::SelectUsers => (function () {
            require_once __DIR__ . '/../db/UserRepository.php';
            $rows = (new UserRepository())->findAll(0, 500);
            $opts = [];
            foreach ($rows as $r) {
                $opts[$r['id']] = $r['full_name'] . ' (' . $r['email'] . ')';
            }
            return $opts;
        })(),
        default => [],
    };
}
