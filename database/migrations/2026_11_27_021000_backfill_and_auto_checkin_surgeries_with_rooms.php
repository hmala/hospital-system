<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * تحديث جميع حالات العمليات التي تملك غرفة مخصصة ولم يسجل لها تاريخ دخول فعلي،
     * وتثبيت تاريخ الدخول تلقائياً بناءً على تاريخ الحجز لمنع تعليقها بحالة "بانتظار استلام الغرفة".
     */
    public function up(): void
    {
        if (Schema::hasTable('surgeries') && Schema::hasColumn('surgeries', 'room_checkin_at')) {
            $isSqlite = DB::getDriverName() === 'sqlite';
            $nowSql = $isSqlite ? "datetime('now')" : 'NOW()';
            DB::table('surgeries')
                ->whereNotNull('room_id')
                ->whereNull('room_checkin_at')
                ->update([
                    'room_checkin_at' => DB::raw("COALESCE(started_at, scheduled_date, created_at, {$nowSql})"),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا يتم عكس أوقات الدخول تجنباً لفقدان البيانات
    }
};
