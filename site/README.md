# coWork — публічний сайт

Публічний сайт бронювання коворкінгів. Реалізований у класичній MVC-архітектурі на PHP 8.1 без фреймворків, поверх тієї ж бази даних MS SQL Server, що й адмін-панель у `/admin/`.

## Структура

```
site/
├── index.php               # фронт-контролер (роутер)
├── .htaccess
├── config/
│   └── bootstrap.php       # session, автопідключення core + моделей + enum-ів
├── core/
│   ├── helpers.php         # e(), siteUrl(), csrfField(), flash(), isOpenNow()...
│   ├── Request.php
│   ├── Response.php
│   ├── View.php
│   ├── Controller.php
│   └── Auth.php            # сесійна аутентифікація
├── models/                 # спадкоємці абстрактного Db
│   ├── Db.php
│   ├── CoworkingModel.php
│   ├── WorkspaceModel.php
│   ├── UserModel.php
│   ├── BookingModel.php
│   ├── BookingSlotModel.php
│   ├── ReviewModel.php
│   ├── FeatureModel.php
│   ├── GalleryModel.php
│   └── OperatingHoursModel.php
├── controllers/
│   ├── HomeController.php
│   ├── CoworkingsController.php
│   ├── CoworkingController.php
│   ├── BookingController.php
│   ├── AuthController.php
│   ├── ProfileController.php
│   ├── ReviewController.php
│   └── CityController.php
├── views/
│   ├── layouts/main.php
│   ├── partials/{header.php, footer.php, flash.php}
│   ├── components/{coworking_card.php, workspace_card.php, rating.php}
│   ├── home/index.php
│   ├── coworkings/index.php         # каталог з фільтрами + сортуванням
│   ├── coworking/show.php           # деталі коворкінгу
│   ├── booking/form.php
│   ├── auth/{login.php, register.php}
│   └── profile/index.php
└── assets/
    ├── css/site.css
    └── js/site.js                    # бронювання, галерея, мапа (Leaflet)
```

## Роути (фронт-контролер, `index.php?page=…`)

| Page           | Метод | Дія                                |
|----------------|-------|------------------------------------|
| `home`         | GET   | Головна                            |
| `coworkings`   | GET   | Каталог з фільтрами та сортуванням |
| `coworking`    | GET   | Сторінка коворкінгу `id=…`         |
| `book`         | GET/POST | Бронювання `workspace_id=…`     |
| `login`        | GET/POST | Вхід                              |
| `register`     | GET/POST | Реєстрація                        |
| `logout`       | GET   | Вихід                              |
| `profile`      | GET   | Мої бронювання                     |
| `cancel_booking` | POST | Скасувати бронювання               |
| `review`       | POST  | Створення відгуку                  |
| `set_city`     | GET/POST | Зберегти місто в сесії/cookie     |

## Ключові сценарії

### Бронювання
1. Користувач вибирає коворкінг → воркспейс → тисне «Забронювати».
2. Заповнює `start_time`, `end_time` (datetime-local).
3. Серверна перевірка:
   - час у майбутньому,
   - потрапляє в графік роботи (`OperatingHoursModel::intervalWithinHours()`)
     або `is_24_7 = 1`,
   - немає перетину з існуючими слотами
     (`BookingSlotModel::hasConflict()`).
4. У транзакції створюється `bookings` (status=`pending`) + `booking_slots`.
5. Користувач бачить нове бронювання в профілі з можливістю скасувати.

### Відгуки
- Створити відгук можна лише при наявності хоча б одного бронювання у цьому коворкінгу й не більше ніж одного відгуку на коворкінг.

## Фронт-функціональність

- **Глобальний селектор міста** у шапці — зберігається в сесії + cookie, впливає на головну, каталог, мапу.
- **Галерея з навігацією і свайпом** на сторінці коворкінгу.
- **Інтерактивна мапа** (OpenStreetMap + Leaflet) — на головній (всі коворкінги з координатами) і на сторінці коворкінгу.
- **Живий калькулятор вартості** на формі бронювання.

## Безпека

- Усі SQL — лише через prepared statements (`PDO`).
- Весь вивід — через `e()` (htmlspecialchars).
- CSRF-токен в усіх POST-формах (`csrfField()` + `csrfCheck()`).
- Паролі — `password_hash(PASSWORD_BCRYPT)` / `password_verify`.
- Регенерація session id при логіні.

## БД

- Підключення — через `admin/config/database.php` (`getDB()`), спільне з адмінкою.
- Обов'язкові таблиці див. в `migration.sql` та `migration_2_site.sql`.
- Нові поля з `migration_2_site.sql`:
  - `coworkings.latitude`, `coworkings.longitude`
  - `bookings.created_at`, `reviews.created_at` (default `GETDATE()`)
- `migration_4_drop_subscriptions.sql` — повне видалення таблиць `subscriptions` та `subscription_plans` (фіча абонементів вимкнена).

## Як запустити локально

```bash
# У корені репозиторію
php -S localhost:8080

# Відкрити: http://localhost:8080/site/index.php
# Або перейти за http://localhost:8080/ (автоматичне перенаправлення).
# Адмінка: http://localhost:8080/admin/
```