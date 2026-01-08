<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // تعديل enum لإضافة pending_payment
        DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'pending_payment') DEFAULT 'scheduled'");
    }

    public function down(): void
    {
        // إرجاع enum إلى حالته الأصلية
        DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled'");
    }
};
