-- ============================================================
-- МІГРАЦІЯ 3: Засоби безпеки, журнал дій, налаштування
-- Запускати ПІСЛЯ migration_2_site.sql
-- ============================================================

-- 1. Журнал дій користувачів (audit log)
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME='audit_log'
)
BEGIN
    CREATE TABLE [dbo].[audit_log] (
        [id]         INT IDENTITY(1,1) PRIMARY KEY,
        [user_id]    INT NULL FOREIGN KEY REFERENCES [users]([id]) ON DELETE SET NULL,
        [user_name]  NVARCHAR(150) NULL,        -- зафіксована назва на момент дії
        [action]     NVARCHAR(30)  NOT NULL,    -- LOGIN, LOGOUT, INSERT, UPDATE, DELETE, BACKUP, RESTORE, SETTINGS
        [table_name] NVARCHAR(50)  NULL,        -- яка таблиця (NULL для login/backup)
        [record_id]  INT NULL,                  -- який запис (NULL якщо неприкладно)
        [details]    NVARCHAR(1000) NULL,       -- людський опис або JSON
        [ip_address] NVARCHAR(45)  NULL,
        [created_at] DATETIME NOT NULL DEFAULT GETDATE()
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name='ix_audit_log_created' AND object_id=OBJECT_ID('audit_log'))
    CREATE INDEX ix_audit_log_created ON audit_log(created_at DESC);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name='ix_audit_log_user'    AND object_id=OBJECT_ID('audit_log'))
    CREATE INDEX ix_audit_log_user    ON audit_log(user_id);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name='ix_audit_log_action'  AND object_id=OBJECT_ID('audit_log'))
    CREATE INDEX ix_audit_log_action  ON audit_log(action);
GO

-- 2. Налаштування системи (ключ/значення)
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME='settings'
)
BEGIN
    CREATE TABLE [dbo].[settings] (
        [id]          INT IDENTITY(1,1) PRIMARY KEY,
        [skey]        NVARCHAR(100) NOT NULL UNIQUE,  -- 'skey' замість 'key' (key — зарезервоване слово)
        [svalue]      NVARCHAR(1000) NULL,
        [label]       NVARCHAR(200) NULL,
        [description] NVARCHAR(500) NULL,
        [updated_at]  DATETIME NOT NULL DEFAULT GETDATE()
    );
END
GO

-- 3. Значення налаштувань за замовчуванням
IF NOT EXISTS (SELECT 1 FROM settings WHERE skey='accounting_period_start')
    INSERT INTO settings (skey, svalue, label, description)
    VALUES ('accounting_period_start', '2025-01-01',
            N'Початок облікового періоду',
            N'Дата, з якої починається поточний обліковий період. Впливає на звіти та агрегати.');
GO
IF NOT EXISTS (SELECT 1 FROM settings WHERE skey='accounting_period_end')
    INSERT INTO settings (skey, svalue, label, description)
    VALUES ('accounting_period_end', '2025-12-31',
            N'Кінець облікового періоду',
            N'Останній день поточного облікового періоду.');
GO
IF NOT EXISTS (SELECT 1 FROM settings WHERE skey='backup_path')
    INSERT INTO settings (skey, svalue, label, description)
    VALUES ('backup_path', '/var/opt/mssql/backup',
            N'Каталог резервних копій',
            N'Абсолютний шлях на сервері БД, куди зберігаються файли .bak.');
GO
IF NOT EXISTS (SELECT 1 FROM settings WHERE skey='modules_path')
    INSERT INTO settings (skey, svalue, label, description)
    VALUES ('modules_path', '/var/www/cowork',
            N'Каталог модулів застосунку',
            N'Шлях до встановленого застосунку (site/ та admin/) на робочому сервері.');
GO
IF NOT EXISTS (SELECT 1 FROM settings WHERE skey='data_retention_days')
    INSERT INTO settings (skey, svalue, label, description)
    VALUES ('data_retention_days', '365',
            N'Термін зберігання журналу (дні)',
            N'Записи audit_log старші за цю кількість днів можуть бути архівовані.');
GO
IF NOT EXISTS (SELECT 1 FROM settings WHERE skey='current_period_name')
    INSERT INTO settings (skey, svalue, label, description)
    VALUES ('current_period_name', '2025',
            N'Назва поточного облікового періоду',
            N'Відображається у звітах (наприклад «2025» або «Q1-2025»).');
GO
IF NOT EXISTS (SELECT 1 FROM settings WHERE skey='archive_path')
    INSERT INTO settings (skey, svalue, label, description)
    VALUES ('archive_path', '/tmp/cowork_archives',
            N'Каталог CSV-архівів',
            N'Каталог на сервері застосунку (не СКБД) для вивантажень таблиць у CSV.');
GO
