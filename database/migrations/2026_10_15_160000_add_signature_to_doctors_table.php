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
        if (Schema::hasTable('doctors') && !Schema::hasColumn('doctors', 'signature')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->string('signature')->nullable()->after('specialization');
            });
        }

        // إسناد توقيع د. مهند إسماعيل (طبيب الطوارئ - المعرف: 244)
        if (Schema::hasTable('doctors')) {
            DB::table('doctors')
                ->where('id', 244)
                ->update(['signature' => 'images/signatures/doctor_244.png']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('doctors') && Schema::hasColumn('doctors', 'signature')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->dropColumn('signature');
            });
        }
    }
};
