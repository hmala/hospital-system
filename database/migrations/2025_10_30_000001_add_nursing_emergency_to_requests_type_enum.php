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
        // في MySQL، لتعديل ENUM نحتاج للتعديل على العمود مباشرة
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') { DB::statement("ALTER TABLE `requests` 
            MODIFY COLUMN `type` ENUM(
                'lab', 
                'radiology', 
                'pharmacy', 
                'blood_bank',
                'nursing',
                'emergency'
            ) NOT NULL"); }

        // تحديث payment_status أيضاً إن وجد
        if (Schema::hasColumn('requests', 'payment_status')) {
            if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') { DB::statement("ALTER TABLE `requests` 
                MODIFY COLUMN `payment_status` ENUM(
                    'pending', 
                    'paid',
                    'not_applicable'
                ) DEFAULT 'pending'"); }
        } else {
            // إضافة العمود إذا لم يكن موجوداً
            Schema::table('requests', function (Blueprint $table) {
                $table->enum('payment_status', ['pending', 'paid', 'not_applicable'])
                    ->default('pending')
                    ->after('type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // استرجاع القيم الأصلية
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') { DB::statement("ALTER TABLE `requests` 
            MODIFY COLUMN `type` ENUM(
                'lab', 
                'radiology', 
                'pharmacy', 
                'blood_bank'
            ) NOT NULL"); }

        if (Schema::hasColumn('requests', 'payment_status')) {
            if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') { DB::statement("ALTER TABLE `requests` 
                MODIFY COLUMN `payment_status` ENUM(
                    'pending', 
                    'paid'
                ) DEFAULT 'pending'"); }
        }
    }
};
