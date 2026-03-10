<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // إصلاح أي بيانات غير صالحة أولاً
        \Illuminate\Support\Facades\DB::statement("UPDATE requests SET type = 'lab' WHERE type NOT IN ('lab', 'radiology', 'pharmacy')");
        
        // في MySQL لتغيير ENUM نحتاج لتدرج الحقل بالكامل
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE requests MODIFY type ENUM('lab', 'radiology', 'pharmacy', 'emergency') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // العكس - إزالة emergency من ENUM
        Illuminate\Support\Facades\DB::statement("ALTER TABLE requests MODIFY type ENUM('lab', 'radiology', 'pharmacy') NOT NULL");
    }
};
