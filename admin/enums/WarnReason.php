<?php
// enums/WarnReason.php

enum WarnReason: string
{
    case BookingNotFound       = 'booking_not_found';
    case CoworkingNotFound     = 'coworking_not_found';
    case WorkspaceNotFound     = 'workspace_not_found';
    case GalleryEntityNotFound = 'gallery_entity_not_found';
    case RecordNotFound        = 'record_not_found';
    case InvalidTimeRange      = 'invalid_time_range';
    case DeleteFailed          = 'delete_failed';
    case ValidationFailed      = 'validation_failed';

    public function title(): string
    {
        return match($this) {
            self::BookingNotFound       => 'Бронювання не знайдено',
            self::CoworkingNotFound     => 'Коворкінг не знайдено',
            self::WorkspaceNotFound     => 'Робоче місце не знайдено',
            self::GalleryEntityNotFound => 'Об\'єкт для фото не знайдено',
            self::RecordNotFound        => 'Запис не знайдено',
            self::InvalidTimeRange      => 'Некоректний часовий діапазон',
            self::DeleteFailed          => 'Помилка видалення',
            self::ValidationFailed      => 'Помилка валідації',
        };
    }

    public function message(): string
    {
        return match($this) {
            self::BookingNotFound       => 'Бронювання з вказаним ID не існує.',
            self::CoworkingNotFound     => 'Коворкінг з вказаним ID не існує.',
            self::WorkspaceNotFound     => 'Робоче місце з вказаним ID не існує.',
            self::GalleryEntityNotFound => 'Об\'єкт (коворкінг або робоче місце) не знайдено.',
            self::RecordNotFound        => 'Запис із вказаним ID не знайдено в базі даних.',
            self::InvalidTimeRange      => 'Час початку не може бути пізніше або рівним часу кінця.',
            self::DeleteFailed          => 'Не вдалося видалити запис. Можливо, він пов\'язаний з іншими даними.',
            self::ValidationFailed      => 'Дані не пройшли перевірку. Виправте помилки та спробуйте ще раз.',
        };
    }
}
