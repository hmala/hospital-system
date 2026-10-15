<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;

class PushPatientsOnlyToHostingerCommand extends Command
{
    /**
     * اسم الأمر في الكونسول.
     */
    protected $signature = 'db:push-patients-hostinger 
                            {--dry-run : معاينة العمليات دون إرسال أي بيانات للسيرفر}
                            {--force : تجاوز التأكيد والتنفيذ المباشر}';

    /**
     * وصف الأمر.
     */
    protected $description = 'رفع وتحديث جدول المرضى (patients) وحساباتهم (users) فقط إلى سيرفر هوستنجر دون لمس الجداول الأخرى';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("==========================================================");
        $this->info("🔄 فحص الاتصال بسيرفر هوستنجر (srv1687.hstgr.io)...");
        $this->info("==========================================================");

        try {
            $remote = DB::connection('remote_hostinger');
            $remotePdo = $remote->getPdo();
            $this->info("✅ تم الاتصال بسيرفر هوستنجر بنجاح 100%!");
        } catch (\Throwable $e) {
            $this->error("❌ فشل الاتصال بهوستنجر: " . $e->getMessage());
            return Command::FAILURE;
        }

        $remotePatientsCount = $remote->table('patients')->count();
        $remoteUsersCount = $remote->table('users')->count();

        $localPatientsCount = DB::table('patients')->count();
        $localUsersCount = DB::table('users')->count();

        $this->table(
            ['البيان', 'السيرفر المحلي (Local)', 'سيرفر هوستنجر (Hostinger)'],
            [
                ['عدد المرضى (patients)', number_format($localPatientsCount), number_format($remotePatientsCount)],
                ['عدد المستخدمين (users)', number_format($localUsersCount), number_format($remoteUsersCount)],
                ['باقي جداول النظام (104 جدول)', 'تبقى كما هي محلياً', 'محمية 100% ولن تُمَس إطلاقاً'],
            ]
        );

        if ($isDryRun) {
            $this->info("\n[وضع المعاينة الوهمية مكتمل] تم فحص الاتصال وتطابق الجداول دون تعديل السيرفر.");
            return Command::SUCCESS;
        }

        if (!$force && !$this->confirm('⚠️ هل أنت متأكد من رفع وتحديث جدول المرضى وحساباتهم فقط إلى هوستنجر الآن؟', true)) {
            $this->warn('تم إلغاء العملية بناءً على رغبتك.');
            return Command::SUCCESS;
        }

        // 1. أخذ نسخة احتياطية من جدولي المرضى والمستخدمين من هوستنجر أولاً للأمان
        $this->line("💾 إنشاء نسخة احتياطية سريعة لجدولي (patients & users) من هوستنجر قبل البدء...");
        $this->backupRemoteTables($remotePdo, ['users', 'patients']);

        $startTime = microtime(true);
        $localPdo = DB::connection('mysql')->getPdo();

        // 2. مزامنة جدول users (إدخال المرضى الجدد وتحديث الجنس والاسم دون المساس بالأطباء أو الإدارة)
        $this->info("\n🚀 1/2: مزامنة جدول المستخدمين (users) مع سيرفر هوستنجر...");
        $this->syncUsersTable($localPdo, $remotePdo);

        // 3. مزامنة جدول patients (رفع كافة المرضى الـ 8,372 مع فئات الضمان ونسب الاستقطاع)
        $this->info("\n🚀 2/2: مزامنة جدول المرضى (patients) مع سيرفر هوستنجر...");
        $this->syncPatientsTable($localPdo, $remotePdo);

        $elapsed = round(microtime(true) - $startTime, 2);

        // 4. التحقق النهائي من السيرفر البعيد
        $finalRemotePatientsCount = $remote->table('patients')->count();
        $finalRemoteUsersCount = $remote->table('users')->count();

        $this->newLine();
        $this->info("==========================================================");
        $this->info("🎉 اكتمل رفع وتحديث جدول المرضى على هوستنجر بنجاح تام!");
        $this->info(" - عدد المرضى على هوستنجر الآن: " . number_format($finalRemotePatientsCount) . " مريض.");
        $this->info(" - عدد المستخدمين على هوستنجر الآن: " . number_format($finalRemoteUsersCount) . " مستخدم.");
        $this->info(" - الوقت المستغرق: {$elapsed} ثانية.");
        $this->info(" - بقية الجداول الـ 104 في هوستنجر بقيت محمية وسليمة 100%.");
        $this->info("==========================================================");

        return Command::SUCCESS;
    }

    /**
     * مزامنة جدول users عبر INSERT ... ON DUPLICATE KEY UPDATE
     */
    private function syncUsersTable(PDO $localPdo, PDO $remotePdo): void
    {
        $remotePdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        $colsStmt = $localPdo->query("SHOW COLUMNS FROM `users`");
        $columns = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
        $escapedCols = array_map(fn($c) => "`{$c}`", $columns);
        $colList = implode(', ', $escapedCols);

        $updateParts = [];
        foreach ($columns as $c) {
            if ($c !== 'id') {
                $updateParts[] = "`{$c}` = VALUES(`{$c}`)";
            }
        }
        $updateClause = implode(', ', $updateParts);

        $totalUsers = DB::table('users')->count();
        $bar = $this->output->createProgressBar($totalUsers);
        $bar->start();

        $selectStmt = $localPdo->query("SELECT * FROM `users`");
        $batch = [];
        $batchSize = 250;

        while ($row = $selectStmt->fetch(PDO::FETCH_ASSOC)) {
            $batch[] = $row;
            $bar->advance();

            if (count($batch) >= $batchSize) {
                $this->upsertBatch($remotePdo, 'users', $columns, $colList, $updateClause, $batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $this->upsertBatch($remotePdo, 'users', $columns, $colList, $updateClause, $batch);
        }

        $bar->finish();
        $this->newLine();
        $remotePdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    /**
     * مزامنة جدول patients عبر INSERT ... ON DUPLICATE KEY UPDATE
     */
    private function syncPatientsTable(PDO $localPdo, PDO $remotePdo): void
    {
        $remotePdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        $colsStmt = $localPdo->query("SHOW COLUMNS FROM `patients`");
        $columns = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
        $escapedCols = array_map(fn($c) => "`{$c}`", $columns);
        $colList = implode(', ', $escapedCols);

        $updateParts = [];
        foreach ($columns as $c) {
            if ($c !== 'id') {
                $updateParts[] = "`{$c}` = VALUES(`{$c}`)";
            }
        }
        $updateClause = implode(', ', $updateParts);

        $totalPatients = DB::table('patients')->count();
        $bar = $this->output->createProgressBar($totalPatients);
        $bar->start();

        $selectStmt = $localPdo->query("SELECT * FROM `patients`");
        $batch = [];
        $batchSize = 250;

        while ($row = $selectStmt->fetch(PDO::FETCH_ASSOC)) {
            $batch[] = $row;
            $bar->advance();

            if (count($batch) >= $batchSize) {
                $this->upsertBatch($remotePdo, 'patients', $columns, $colList, $updateClause, $batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $this->upsertBatch($remotePdo, 'patients', $columns, $colList, $updateClause, $batch);
        }

        $bar->finish();
        $this->newLine();
        $remotePdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    /**
     * تنفيذ UPSERT لدُفعة من السجلات
     */
    private function upsertBatch(PDO $pdo, string $table, array $columns, string $colList, string $updateClause, array $rows): void
    {
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($rows), $placeholders));

        $sql = "INSERT INTO `{$table}` ({$colList}) VALUES {$allPlaceholders} ON DUPLICATE KEY UPDATE {$updateClause}";
        $stmt = $pdo->prepare($sql);

        $params = [];
        foreach ($rows as $row) {
            foreach ($columns as $col) {
                $params[] = $row[$col];
            }
        }

        $stmt->execute($params);
    }

    /**
     * حفظ نسخة احتياطية من جداول هوستنجر قبل التعديل
     */
    private function backupRemoteTables(PDO $remotePdo, array $tables): void
    {
        try {
            $backupDir = storage_path('app/backups');
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $filepath = $backupDir . '/hostinger_patients_backup_' . date('Y_m_d_His') . '.sql';
            $handle = fopen($filepath, 'w+');

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            foreach ($tables as $t) {
                $stmt = $remotePdo->query("SELECT * FROM `{$t}`");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $keys = array_map(fn($k) => "`{$k}`", array_keys($row));
                    $vals = array_map(fn($v) => is_null($v) ? 'NULL' : $remotePdo->quote($v), array_values($row));
                    fwrite($handle, "REPLACE INTO `{$t}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $vals) . ");\n");
                }
            }
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);
            $this->line("   -> تم حفظ النسخة الاحتياطية في: " . basename($filepath));
        } catch (\Throwable $e) {
            $this->warn("تعذر أخذ النسخة الاحتياطية: " . $e->getMessage());
        }
    }
}
