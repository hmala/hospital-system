<?php

namespace App\Console\Commands;

use App\Services\DatabaseSyncService;
use Illuminate\Console\Command;

class PullHostingerDatabaseCommand extends Command
{
    protected $signature = 'db:pull-hostinger';
    protected $description = 'سحب وتحديث قاعدة البيانات بالكامل من سيرفر هوستنجر إلى جهازك اللوكل';

    public function handle(DatabaseSyncService $syncService): int
    {
        if (!app()->environment(['local', 'testing'])) {
            $this->error("❌ أمر السحب محظور تماماً على سيرفر الإنتاج (Online) لحماية البيانات الحية.");
            return Command::FAILURE;
        }

        $this->info("🔄 جارٍ فحص الاتصال بسيرفر هوستنجر (srv1687.hstgr.io)...");

        $test = $syncService->testRemoteConnection();
        if (!$test['success']) {
            $this->error("❌ " . $test['message']);
            return Command::FAILURE;
        }

        $this->info("✅ تم الاتصال بنجاح! (" . $test['latency_ms'] . "ms)");
        $this->line("قاعدة البيانات البعيدة: " . $test['database']);
        $this->line("إجمالي الجداول المتاحة: " . $test['tables_count']);
        if ($test['patients_count']) {
            $this->line("عدد المرضى بالسيرفر: " . $test['patients_count']);
        }

        if (!$this->confirm('هل أنت متأكد من سحب وتحديث قاعدة البيانات المحلية الآن؟', true)) {
            $this->warn('تم الإلغاء.');
            return Command::SUCCESS;
        }

        $this->info("💾 جارٍ أخذ نسخة احتياطية من نسختك اللوكل الحالية أولاً...");
        $bar = $this->output->createProgressBar($test['tables_count']);
        $bar->start();

        $result = $syncService->pullFromRemote(function ($table, $current, $total, $rows) use ($bar) {
            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("🎉 " . $result['message']);
        $this->line("الجداول المسحوبة: {$result['synced_tables']} / {$result['total_tables']}");
        $this->line("إجمالي السجلات: " . number_format($result['total_rows']));
        $this->line("الوقت المستغرق: {$result['elapsed_seconds']} ثانية");
        if ($result['backup_file']) {
            $this->line("نسخة احتياطية محلية محفوظة في: storage/app/backups/{$result['backup_file']}");
        }

        return Command::SUCCESS;
    }
}
