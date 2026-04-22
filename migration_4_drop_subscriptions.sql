-- ============================================================
-- МІГРАЦІЯ 4: прибираємо повністю систему абонементів.
-- Запускати ПІСЛЯ попередніх міграцій.
-- ============================================================

-- 1. Спершу видаляємо "дочірню" таблицю (посилається на subscription_plans)
IF EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME='subscriptions'
)
BEGIN
    DROP TABLE [dbo].[subscriptions];
END
GO

-- 2. Потім таблицю тарифних планів
IF EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME='subscription_plans'
)
BEGIN
    DROP TABLE [dbo].[subscription_plans];
END
GO
