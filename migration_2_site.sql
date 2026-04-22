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
