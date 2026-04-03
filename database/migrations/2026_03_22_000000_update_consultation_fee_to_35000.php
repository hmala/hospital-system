<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // تأكد أن القيم القديمة تصبح 35000 وصرّف القيمة الافتراضية من الآن فصاعداً
        DB::table('doctors')->where('type', 'consultant')->update(['consultation_fee' => 35000.00]);

        // تغير عمود الاستشارة للافتراضي
        Schema::table('doctors', function (Blueprint $table) {
            $table->decimal('consultation_fee', 10, 2)->default(35000.00)->change();
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->decimal('consultation_fee', 10, 2)->default(0)->change();
        });
    }
};