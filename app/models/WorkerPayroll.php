<?php

namespace App\Models;

use App\Core\Database;

class WorkerPayroll
{
    public static function forWorker(int $workerId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM worker_payroll WHERE worker_id = :id ORDER BY period_start DESC, id DESC');
        $stmt->execute(['id' => $workerId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM worker_payroll WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $workerId, int $generatedBy, array $data): int
    {
        $pdo = Database::connection();
        $basePay = (float) $data['base_pay'];
        $bonuses = (float) ($data['bonuses'] !== '' ? $data['bonuses'] : 0);
        $deductions = (float) ($data['deductions'] !== '' ? $data['deductions'] : 0);
        $netPay = $basePay + $bonuses - $deductions;

        $stmt = $pdo->prepare('INSERT INTO worker_payroll (worker_id, period_start, period_end, base_pay, bonuses, deductions, net_pay, status, generated_by)
            VALUES (:worker_id, :period_start, :period_end, :base_pay, :bonuses, :deductions, :net_pay, :status, :generated_by)');
        $stmt->execute([
            'worker_id' => $workerId,
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'base_pay' => $basePay,
            'bonuses' => $bonuses,
            'deductions' => $deductions,
            'net_pay' => $netPay,
            'status' => 'draft',
            'generated_by' => $generatedBy,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function updateStatus(int $id, string $status): void
    {
        $paidAt = $status === 'paid' ? 'NOW()' : 'NULL';
        Database::connection()->prepare("UPDATE worker_payroll SET status = :status, paid_at = {$paidAt} WHERE id = :id")
            ->execute(['id' => $id, 'status' => $status]);
    }
}
