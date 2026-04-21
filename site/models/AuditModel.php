<?php
// site/models/AuditModel.php
// Легка модель для запису дій користувачів публічного сайту в audit_log.

final class AuditModel extends Db
{
    public function log(
        ?int $userId,
        ?string $userName,
        string $action,
        ?string $tableName = null,
        ?int $recordId = null,
        ?string $details = null,
    ): void {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $this->exec(
                'INSERT INTO audit_log (user_id, user_name, action, table_name, record_id, details, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $userId ?? null,
                    $userName !== null ? mb_substr($userName, 0, 150) : null,
                    mb_substr($action, 0, 30),
                    $tableName !== null ? mb_substr($tableName, 0, 50) : null,
                    $recordId ?? null,
                    $details !== null ? mb_substr($details, 0, 1000) : null,
                    $ip !== null ? mb_substr($ip, 0, 45) : null,
                ],
            );
        } catch (\Throwable) {
            // тихо ігноруємо
        }
    }
}
