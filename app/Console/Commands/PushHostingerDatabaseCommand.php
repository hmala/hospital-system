<?php

namespace App\Console\Commands;

use App\Services\DatabaseSyncService;
use Illuminate\Console\Command;

class PushHostingerDatabaseCommand extends Command
{
    protected $signature = 'db:push-hostinger {--force : تجاوز التأكيد والتنفيذ الفوري}';
    protected $description = 'رفع وزرع واستعادة كافة الجداول والبيانات من جهازك اللوكل إلى سيرفر هوستنجر';

    public function handle(DatabaseSyncService $syncService): int
    {
        $this->info("🔄 جارٍ فحص الاتصال بسيرفر هوستنجر (srv1687.hstgr.io)...");

        $test = $syncService->testRemoteConnection();
        if (!$test['success']) {
            $this->error("❌ " . $test['message']);
            return Command::FAILURE;
        }

        $this->info("✅ تم الاتصال بنجاح! (" . $test['latency_ms'] . "ms)");
        $this->line("قاعدة البيانات البعيدة: " . $test['database']);
        $this->line("الجداول الحالية بالسيرفر: " . $test['tables_count']);

        if (!$this->option('force') && !$this->confirm('⚠️ هل أنت متأكد من رفع وزرع قاعدة البيانات المحلية بالكامل إلى هوستنجر الآن؟', true)) {
            $this->warn('تم الإلغاء.');
            return Command::SUCCESS;
        }

        $this->info("🚀 جارٍ رفع الجداول والبيانات إلى هوستنجر...");

        $localTables = \Illuminate\Support\Facades\DB::connection('mysql')->select('SHOW TABLES');
        $totalTables = count($localTables);
        $bar = $this->output->createProgressBar($totalTables);
        $bar->start();

        $result = $syncService->pushToRemote(function ($table, $current, $total, $rows) use ($bar) {
            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);

        if ($result['success']) {
            $this->info("🎉 " . $result['message']);
            $this->line("الجداول المرفوعة: {$result['synced_tables']} / {$result['total_tables']}");
            $this->line("إجمالي السجلات: " . number_format($result['total_rows']));
            $this->line("الوقت المستغرق: {$result['elapsed_seconds']} ثانية");
            return Command::SUCCESS;
        }

        $this->error("❌ حدث خطأ أثناء الرفع.");
        return Command::FAILURE;
    }
}
