<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تعديل العمود type لإضافة 'checkup'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE requests MODIFY COLUMN type ENUM('lab', 'radiology', 'pharmacy', 'checkup') NOT NULL");
        } else {
            // للـ SQLite والقواعد الأخرى
            DB::statement("ALTER TABLE requests MODIFY type TEXT CHECK(type IN ('lab', 'radiology', 'pharmacy', 'checkup'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE requests MODIFY COLUMN type ENUM('lab', 'radiology', 'pharmacy') NOT NULL");
        }
    }
};
