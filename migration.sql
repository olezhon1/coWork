-- ============================================================
-- МІГРАЦІЯ: додає нові поля до існуючих таблиць
-- Запускати ПІСЛЯ основного schema.sql
-- ============================================================

-- 1. Додаємо role до users (якщо ще немає)
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='users' AND COLUMN_NAME='role'
)
BEGIN
    ALTER TABLE [dbo].[users] ADD [role] NVARCHAR(20) NOT NULL DEFAULT 'user';
END
GO

-- 2. Додаємо is_24_7 до coworkings (якщо ще немає)
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='coworkings' AND COLUMN_NAME='is_24_7'
)
BEGIN
    ALTER TABLE [dbo].[coworkings] ADD [is_24_7] BIT DEFAULT 0;
END
GO

-- 3. Перейменовуємо type -> type_key в workspaces (якщо ще не перейменовано)
IF EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='workspaces' AND COLUMN_NAME='type'
)
AND NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='workspaces' AND COLUMN_NAME='type_key'
)
BEGIN
    EXEC sp_rename 'workspaces.type', 'type_key', 'COLUMN';
END
GO

-- 4. Виправляємо features: видаляємо description, додаємо icon_key (якщо потрібно)
IF EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='features' AND COLUMN_NAME='description'
)
BEGIN
    ALTER TABLE [dbo].[features] DROP COLUMN [description];
END
GO

IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='features' AND COLUMN_NAME='icon_key'
)
BEGIN
    ALTER TABLE [dbo].[features] ADD [icon_key] NVARCHAR(50);
END
GO

-- 5. Виправляємо subscriptions: expire_date -> end_date (якщо потрібно)
IF EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='subscriptions' AND COLUMN_NAME='expire_date'
)
AND NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='subscriptions' AND COLUMN_NAME='end_date'
)
BEGIN
    EXEC sp_rename 'subscriptions.expire_date', 'end_date', 'COLUMN';
END
GO

-- Додаємо status до subscriptions якщо немає
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='subscriptions' AND COLUMN_NAME='status'
)
BEGIN
    ALTER TABLE [dbo].[subscriptions] ADD [status] NVARCHAR(50) DEFAULT 'active';
END
GO

-- 6. Видаляємо date з booking_slots (зберігаємо start_time/end_time як DATETIME)
IF EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='booking_slots' AND COLUMN_NAME='date'
)
BEGIN
    ALTER TABLE [dbo].[booking_slots] DROP COLUMN [date];
END
GO

-- 7. Додаємо user_id до reviews якщо немає
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='reviews' AND COLUMN_NAME='user_id'
)
BEGIN
    ALTER TABLE [dbo].[reviews] ADD [user_id] INT FOREIGN KEY REFERENCES [users]([id]);
END
GO

-- 8. Створюємо адміністратора (замінити email та пароль!)
-- Пароль нижче = password_hash('Admin@2025', PASSWORD_BCRYPT)
-- Запустіть в PHP: echo password_hash('Ваш_Пароль', PASSWORD_BCRYPT);
-- і вставте хеш сюди:
/*
INSERT INTO [dbo].[users] (full_name, email, password_hash, phone, role)
VALUES (
    N'Адміністратор',
    'admin@cowork.ua',
    '$2y$10$ЗАМІНИТИ_НА_РЕАЛЬНИЙ_BCRYPT_ХЕШ',
    '+380XXXXXXXXX',
    'admin'
);
*/
