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
        if (!Schema::hasColumn('surgeries', 'surgery_fee')) {
            Schema::table('surgeries', function (Blueprint $table) {
                $table->decimal('surgery_fee', 10, 2)
                      ->default(0)
                      ->after('payment_id')
                      ->comment('رسوم العملية الجراحية (أعيدت بعد حذف غير مقصود)');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('surgeries', 'surgery_fee')) {
            Schema::table('surgeries', function (Blueprint $table) {
                $table->dropColumn('surgery_fee');
            });
        }
    }
};