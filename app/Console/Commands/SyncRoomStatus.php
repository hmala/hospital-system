<?php

namespace App\Console\Commands;

use App\Observers\BedReservationObserver;
use Illuminate\Console\Command;

class SyncRoomStatus extends Command
{
    /**
     * اسم الأمر
     */
    protected $signature = 'rooms:sync';

    /**
     * وصف الأمر
     */
    protected $description = 'مزامنة حالة الغرف مع الحجوزات النشطة';

    /**
     * تنفيذ الأمر
     */
    public function handle(): int
    {
        $this->info('جاري مزامنة حالة الغرف...');

        $result = BedReservationObserver::syncAllRooms();

        $this->info("تم فحص {$result['total']} غرفة.");
        $this->info("تم تحديث {$result['updated']} غرفة.");

        if ($result['updated'] > 0) {
            $this->newLine();
            $this->info('✓ تمت المزامنة بنجاح!');
        } else {
            $this->newLine();
            $this->info('✓ جميع الغرف متزامنة بالفعل.');
        }

        return Command::SUCCESS;
    }
}
