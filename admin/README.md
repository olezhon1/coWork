# CoWork Admin Panel v2

## Фінальна архітектура

```
coworking-admin/
│
├── config/
│   ├── bootstrap.php       ← єдина точка входу (підключай тільки це)
│   ├── database.php        ← PDO підключення до SQL Server
│   └── design.php          ← токени: кольори, шрифти, радіуси, тіні
│
├── enums/                  ← PHP 8.1 Backed Enums (string)
│   ├── AdminTable.php      ← всі таблиці + label() icon() isReadOnly()
│   ├── BookingStatus.php   ← pending/confirmed/cancelled + badgeClass()
│   ├── WorkspaceType.php   ← open/cabinet/conference/... + badgeClass()
│   ├── GalleryEntityType.php ← coworking/workspace + dbTable()
│   ├── FormFieldType.php   ← text/number/select/textarea/...
│   ├── FlashType.php       ← ok/err/warn/info + alertClass() iconName()
│   └── WarnReason.php      ← всі причини помилок + title() message()
│
├── db/
│   ├── BaseRepository.php          ← PDO-хелпери, existsById(), totalRows()
│   ├── CoworkingRepository.php
│   ├── WorkspaceRepository.php     ← create/update приймають WorkspaceType
│   ├── BookingRepository.php       ← create/update приймають BookingStatus
│   ├── BookingSlotRepository.php   ← bookingExists() перед create()
│   ├── ReviewRepository.php        ← тільки findAll/findById/delete
│   ├── FeatureRepository.php
│   └── GalleryRepository.php       ← entityExists(GalleryEntityType, id)
│
├── ui/
│   ├── table_config.php            ← конфіг форм через enum::options()
│   ├── components/
│   │   ├── form_field.php          ← renderFormField()
│   │   └── cell_renderer.php       ← renderCell() + enum::tryFrom() для бейджів
│   ├── views/
│   │   ├── view_dashboard.php
│   │   ├── view_list.php
│   │   ├── view_form.php
│   │   └── view_warning.php        ← сторінка попередження (entity not found)
│   └── partials/
│       ├── layout_head.php
│       └── layout_foot.php
│
├── assets/
│   ├── icons/icons.php     ← всі SVG іконки: змінюй тут, зміниться скрізь
│   └── css/
│       ├── admin.css       ← стилі без хардкоду кольорів (тільки var(--*))
│       └── variables.php   ← генерує CSS :root{} з config/design.php
│
├── index.php               ← роутер + контролер (всі дії в одному файлі)
├── login.php
├── logout.php
└── .htaccess
```

---

## Запуск в PhpStorm

1. `File → Open` → папка `coworking-admin`
2. `Settings → PHP` → PHP 8.1+, розширення `pdo_sqlsrv`
3. `Run → Edit Configurations → PHP Built-in Web Server`
   - Document root: папка проекту
   - Port: `8080`
4. Відкрий `http://localhost:8080/login.php`
   - Логін: `admin`, Пароль: `admin123`

---

## Як додати нову таблицю

1. **`enums/AdminTable.php`** — додай новий case + label() + icon() + navGroup()
2. **`db/NewRepository.php`** — extend BaseRepository, реалізуй findAll/findById/total/create/update/delete
3. **`ui/table_config.php`** — додай case у tableFormConfig() з FormFieldType
4. **`ui/views/view_list.php`** — додай колонки у `$columns` match
5. **`index.php`** — додай у getRepo() + обробку POST

---

## Enum-захист

Всі рядки з форм проходять через `SomeEnum::tryFrom()`:
- Невідомий статус → `BookingStatus::Pending` (fallback)
- Невідомий тип → `WorkspaceType::Open` (fallback)

Жоден string не потрапляє напряму в SQL без проходження через enum.
