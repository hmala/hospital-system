<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PDO;

class DatabaseSyncService
{
    /**
     * التحقق الصارم من أن البيئة محلية وآمنة بنسبة 100% للسحب (ليست هوستنجر ولا بيئة إنتاج)
     */
    public static function isSafeLocalEnvironment(): bool
    {
        // 1. إذا كان اسم قاعدة البيانات يطابق قاعدة بيانات هوستنجر الحية
        $localDb = (string) config('database.connections.mysql.database');
        $remoteDb = (string) config('database.connections.remote_hostinger.database');
        if (!empty($localDb) && !empty($remoteDb) && strtolower($localDb) === strtolower($remoteDb)) {
            return false;
        }

        if (strtolower($localDb) === 'u175868711_her2020') {
            return false;
        }

        // 2. إذا كان نظام التشغيل لينكس (سيرفر هوستنجر) وليس بيئة WAMP ويندوز المحلية
        if (DIRECTORY_SEPARATOR === '/') {
            return false;
        }

        // 3. إذا كان الدومين أونلاين
        $host = request()?->getHost() ?? '';
        if (!empty($host) && !in_array($host, ['localhost', '127.0.0.1', 'hospital-system.test'])) {
            return false;
        }

        // 4. إذا كانت البيئة إنتاج
        if (app()->environment('production')) {
            return false;
        }

        return true;
    }

    /**
     * اختبار الاتصال بقاعدة بيانات هوستنجر وجلب إحصائيات سريعة
     */
    public function testRemoteConnection(): array
    {
        $startTime = microtime(true);

        try {
            $remote = DB::connection('remote_hostinger');
            $tables = $remote->select('SHOW TABLES');
            $latency = round((microtime(true) - $startTime) * 1000, 1);

            $tableCount = count($tables);

            // إحصائيات سريعة من السيرفر
            $patientsCount = 0;
            $appointmentsCount = 0;
            $paymentsCount = 0;

            try {
                if ($remote->getSchemaBuilder()->hasTable('patients')) {
                    $patientsCount = $remote->table('patients')->count();
                }
                if ($remote->getSchemaBuilder()->hasTable('appointments')) {
                    $appointmentsCount = $remote->table('appointments')->count();
                }
                if ($remote->getSchemaBuilder()->hasTable('payments')) {
                    $paymentsCount = $remote->table('payments')->count();
                }
            } catch (\Throwable $e) {
                // تجاهل إذا كانت الجداول بمسميات أخرى
            }

            return [
                'success' => true,
                'message' => 'الاتصال بسيرفر هوستنجر ناجح 100%',
                'host' => config('database.connections.remote_hostinger.host'),
                'database' => config('database.connections.remote_hostinger.database'),
                'latency_ms' => $latency,
                'tables_count' => $tableCount,
                'patients_count' => $patientsCount,
                'appointments_count' => $appointmentsCount,
                'payments_count' => $paymentsCount,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'فشل الاتصال بسيرفر هوستنجر: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * إنشاء نسخة احتياطية من قاعدة البيانات المحلية الحالية قبل السحب
     */
    public function backupLocalDatabase(): ?string
    {
        try {
            $backupDir = storage_path('app/backups');
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $filename = 'local_backup_' . date('Y_m_d_His') . '.sql';
            $filepath = $backupDir . '/' . $filename;

            $local = DB::connection('mysql')->getPdo();
            $tables = DB::connection('mysql')->select('SHOW TABLES');

            $handle = fopen($filepath, 'w+');
            fwrite($handle, "-- Local Backup Created at " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($tables as $tRow) {
                $tableName = reset($tRow);
                if (empty($tableName)) continue;

                // Create Table
                $stmt = $local->query("SHOW CREATE TABLE `{$tableName}`");
                $createRow = $stmt->fetch(PDO::FETCH_ASSOC);
                $createSql = $createRow['Create Table'] ?? '';

                fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                fwrite($handle, $createSql . ";\n\n");

                // Rows
                $rowsStmt = $local->query("SELECT * FROM `{$tableName}`");
                while ($row = $rowsStmt->fetch(PDO::FETCH_ASSOC)) {
                    $values = array_map(function ($val) use ($local) {
                        return is_null($val) ? 'NULL' : $local->quote($val);
                    }, array_values($row));

                    $keys = array_map(fn($k) => "`{$k}`", array_keys($row));
                    $insertSql = "INSERT INTO `{$tableName}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
                    fwrite($handle, $insertSql);
                }
                fwrite($handle, "\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);

            return $filename;
        } catch (\Throwable $e) {
            Log::warning("Backup local DB failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * سحب كافة الجداول والبيانات من هوستنجر وحقنها في قاعدة البيانات المحلية
     */
    public function pullFromRemote(callable $progressCallback = null): array
    {
        // حظر أمني حاسم: منع تشغيل السحب نهائياً خارج البيئة المحلية
        if (!self::isSafeLocalEnvironment() && !app()->environment('testing')) {
            throw new Exception('عملية السحب محظورة تماماً على سيرفر الإنتاج (Online) لحماية البيانات الحية من المسح.');
        }

        $localDbName = config('database.connections.mysql.database');
        $remoteDbName = config('database.connections.remote_hostinger.database');
        if (!empty($localDbName) && !empty($remoteDbName) && strtolower($localDbName) === strtolower($remoteDbName)) {
            throw new Exception("حظر أمني خطير: اسم قاعدة البيانات المحلية ({$localDbName}) يطابق قاعدة البيانات السحابية! تم إلغاء العملية فوراً لمنع تدمير البيانات.");
        }

        set_time_limit(600); // 10 دقائق كحد أقصى للعمليات الكبيرة
        ini_set('memory_limit', '1024M');

        $startTime = microtime(true);

        // 1. أخذ نسخة احتياطية من اللوكل الحالي أولاً
        $backupFile = $this->backupLocalDatabase();

        $remotePdo = DB::connection('remote_hostinger')->getPdo();
        $localPdo = DB::connection('mysql')->getPdo();

        // تعطيل قيود المفاتيح الأجنبية على اللوكل
        $localPdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $localPdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

        $remoteTablesStmt = $remotePdo->query("SHOW TABLES");
        $remoteTables = $remoteTablesStmt->fetchAll(PDO::FETCH_COLUMN);

        $totalTables = count($remoteTables);
        $syncedTables = 0;
        $totalRowsSynced = 0;

        foreach ($remoteTables as $index => $tableName) {
            try {
                // جلب جملة CREATE TABLE من هوستنجر
                $createStmt = $remotePdo->query("SHOW CREATE TABLE `{$tableName}`");
                $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
                $createSql = $createRow['Create Table'] ?? '';

                if (empty($createSql)) continue;

                // حذف وإنشاء الجدول على اللوكل
                $localPdo->exec("DROP TABLE IF EXISTS `{$tableName}`;");
                $localPdo->exec($createSql);

                // سحب البيانات على دفعات (Chunks)
                $selectStmt = $remotePdo->query("SELECT * FROM `{$tableName}`");
                $batch = [];
                $batchSize = 200;
                $tableRowsCount = 0;

                while ($row = $selectStmt->fetch(PDO::FETCH_ASSOC)) {
                    $batch[] = $row;
                    $tableRowsCount++;
                    $totalRowsSynced++;

                    if (count($batch) >= $batchSize) {
                        $this->insertBatch($localPdo, $tableName, $batch);
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    $this->insertBatch($localPdo, $tableName, $batch);
                }

                $syncedTables++;

                if ($progressCallback) {
                    $progressCallback($tableName, $index + 1, $totalTables, $tableRowsCount);
                }
            } catch (\Throwable $e) {
                Log::error("Failed syncing table {$tableName}: " . $e->getMessage());
            }
        }

        // إعادة تفعيل قيود المفاتيح الأجنبية
        $localPdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // تنظيف الكاش
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {}

        $elapsedSeconds = round(microtime(true) - $startTime, 2);

        return [
            'success' => true,
            'message' => "تم سحب وتحديث قاعدة البيانات بنجاح تام!",
            'total_tables' => $totalTables,
            'synced_tables' => $syncedTables,
            'total_rows' => $totalRowsSynced,
            'elapsed_seconds' => $elapsedSeconds,
            'backup_file' => $backupFile,
        ];
    }

    /**
     * إدخال دفعة من السجلات في اللوكل
     */
    protected function insertBatch(PDO $pdo, string $tableName, array $rows): void
    {
        if (empty($rows)) return;

        $firstRow = $rows[0];
        $columns = array_keys($firstRow);
        $escapedColumns = array_map(fn($col) => "`{$col}`", $columns);
        $columnList = implode(', ', $escapedColumns);

        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($rows), $placeholders));

        $sql = "INSERT INTO `{$tableName}` ({$columnList}) VALUES {$allPlaceholders}";
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
     * رفع وزرع كافة الجداول والبيانات من اللوكل إلى سيرفر هوستنجر (استعادة / Push)
     */
    public function pushToRemote(callable $progressCallback = null): array
    {
        set_time_limit(900); // 15 دقيقة
        ini_set('memory_limit', '1024M');

        $startTime = microtime(true);

        $remotePdo = DB::connection('remote_hostinger')->getPdo();
        $localPdo = DB::connection('mysql')->getPdo();

        // تعطيل قيود المفاتيح الأجنبية على السيرفر البعيد
        $remotePdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $remotePdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

        $localTablesStmt = $localPdo->query("SHOW TABLES");
        $localTables = $localTablesStmt->fetchAll(PDO::FETCH_COLUMN);

        $totalTables = count($localTables);
        $syncedTables = 0;
        $totalRowsSynced = 0;

        foreach ($localTables as $index => $tableName) {
            try {
                // جلب جملة CREATE TABLE من اللوكل
                $createStmt = $localPdo->query("SHOW CREATE TABLE `{$tableName}`");
                $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
                $createSql = $createRow['Create Table'] ?? '';

                if (empty($createSql)) continue;

                // حذف وإنشاء الجدول على هوستنجر
                $remotePdo->exec("DROP TABLE IF EXISTS `{$tableName}`;");
                $remotePdo->exec($createSql);

                // نقل البيانات على دفعات
                $selectStmt = $localPdo->query("SELECT * FROM `{$tableName}`");
                $batch = [];
                $batchSize = 200;
                $tableRowsCount = 0;

                while ($row = $selectStmt->fetch(PDO::FETCH_ASSOC)) {
                    $batch[] = $row;
                    $tableRowsCount++;
                    $totalRowsSynced++;

                    if (count($batch) >= $batchSize) {
                        $this->insertBatch($remotePdo, $tableName, $batch);
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    $this->insertBatch($remotePdo, $tableName, $batch);
                }

                $syncedTables++;

                if ($progressCallback) {
                    $progressCallback($tableName, $index + 1, $totalTables, $tableRowsCount);
                }
            } catch (\Throwable $e) {
                Log::error("Failed pushing table {$tableName} to remote: " . $e->getMessage());
            }
        }

        // إعادة تفعيل قيود المفاتيح الأجنبية
        $remotePdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        $elapsedSeconds = round(microtime(true) - $startTime, 2);

        return [
            'success' => true,
            'message' => "تم رفع واستعادة قاعدة البيانات بالكامل إلى سيرفر هوستنجر بنجاح!",
            'total_tables' => $totalTables,
            'synced_tables' => $syncedTables,
            'total_rows' => $totalRowsSynced,
            'elapsed_seconds' => $elapsedSeconds,
        ];
    }
}

