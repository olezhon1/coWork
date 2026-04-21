-- ============================================================
-- МІГРАЦІЯ 2: нові поля/таблиці для публічного сайту
-- Запускати ПІСЛЯ migration.sql
-- ============================================================

-- 1. Координати коворкінгу (для мапи)
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='coworkings' AND COLUMN_NAME='latitude'
)
BEGIN
    ALTER TABLE [dbo].[coworkings] ADD [latitude] DECIMAL(10,7) NULL;
END
GO

IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='coworkings' AND COLUMN_NAME='longitude'
)
BEGIN
    ALTER TABLE [dbo].[coworkings] ADD [longitude] DECIMAL(10,7) NULL;
END
GO

-- 2. bookings.created_at (якщо немає)
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='bookings' AND COLUMN_NAME='created_at'
)
BEGIN
    ALTER TABLE [dbo].[bookings] ADD [created_at] DATETIME DEFAULT GETDATE();
END
GO

-- 3. reviews.created_at (якщо немає)
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='reviews' AND COLUMN_NAME='created_at'
)
BEGIN
    ALTER TABLE [dbo].[reviews] ADD [created_at] DATETIME DEFAULT GETDATE();
END
GO

-- 4. Нова таблиця subscription_plans (шаблони тарифних планів)
-- Існуюча таблиця subscriptions — це КУПЛЕНІ користувачем абонементи
-- subscription_plans — це ТАРИФИ, які можна купити
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME='subscription_plans'
)
BEGIN
    CREATE TABLE [dbo].[subscription_plans] (
        [id]              INT IDENTITY(1,1) PRIMARY KEY,
        [coworking_id]    INT NULL FOREIGN KEY REFERENCES [coworkings]([id]) ON DELETE CASCADE, -- NULL = глобальний план
        [name]            NVARCHAR(120) NOT NULL,
        [description]     NVARCHAR(500) NULL,
        [hours_included]  INT NOT NULL,
        [duration_days]   INT NOT NULL,            -- скільки днів діє з моменту купівлі
        [price]           DECIMAL(10,2) NOT NULL,
        [is_active]       BIT DEFAULT 1,
        [created_at]      DATETIME DEFAULT GETDATE()
    );
END
GO

-- 5. Додаємо plan_id у subscriptions (зв'язок з планом, з якого куплено)
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='subscriptions' AND COLUMN_NAME='plan_id'
)
BEGIN
    ALTER TABLE [dbo].[subscriptions] ADD [plan_id] INT NULL FOREIGN KEY REFERENCES [subscription_plans]([id]);
END
GO

-- 6. Демо-дані для тарифних планів (тільки якщо таблиця порожня)
IF NOT EXISTS (SELECT 1 FROM [dbo].[subscription_plans])
BEGIN
    INSERT INTO [dbo].[subscription_plans] (coworking_id, name, description, hours_included, duration_days, price, is_active)
    VALUES
        (NULL, N'Starter 10', N'10 годин на місяць у будь-якому коворкінгу', 10, 30, 600.00, 1),
        (NULL, N'Standard 40', N'40 годин на місяць + економія 20%', 40, 30, 2000.00, 1),
        (NULL, N'Pro 100',  N'100 годин + економія 30%, ідеально для постійних клієнтів', 100, 30, 4500.00, 1),
        (NULL, N'Day Pass 8', N'8 годин у межах одного дня', 8, 1, 400.00, 1);
END
GO
