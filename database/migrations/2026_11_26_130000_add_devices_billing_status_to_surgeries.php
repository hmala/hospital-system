<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('surgeries', 'devices_billing_status')) {
            Schema::table('surgeries', function (Blueprint $table) {
                $table->string('devices_billing_status')->default('none')->after('billing_status');
            });
        }

        // نسخ السعر الافتراضي للجهاز على جدول الربط إن كان فارغاً (متوافق مع MySQL و SQLite)
        if (Schema::hasTable('surgery_medical_device') && Schema::hasTable('medical_devices')) {
            DB::statement('
                UPDATE surgery_medical_device
                SET price = COALESCE(
                    (SELECT medical_devices.usage_price FROM medical_devices WHERE medical_devices.id = surgery_medical_device.medical_device_id),
                    0
                )
                WHERE price IS NULL
            ');
        }

        // فصل الأجهزة الطبية عن تسعير المريض (تصحيح العمليات المعلقة بسبب الأجهزة فقط مثل العملية #59)
        $deviceOnlyReviewIds = DB::table('surgeries as s')
            ->where('s.billing_status', 'pending_review')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('surgery_medical_device as smd')
                    ->whereColumn('smd.surgery_id', 's.id');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('surgery_type_changes as stc')
                    ->whereColumn('stc.surgery_id', 's.id');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('surgery_additional_operations as sao')
                    ->whereColumn('sao.surgery_id', 's.id');
            })
            ->pluck('s.id');

        if ($deviceOnlyReviewIds->isNotEmpty()) {
            DB::table('surgeries')
                ->whereIn('id', $deviceOnlyReviewIds)
                ->update([
                    'billing_status' => 'ready',
                    'devices_billing_status' => 'pending_review',
                ]);
        }

        // وضع أي عملية تحتوي على أجهزة ولم تتم مراجعة أجهزتها بعد في طابور أجهزة الحسابات
        DB::table('surgeries as s')
            ->where('s.devices_billing_status', 'none')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('surgery_medical_device as smd')
                    ->whereColumn('smd.surgery_id', 's.id');
            })
            ->update(['devices_billing_status' => 'pending_review']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('surgeries', 'devices_billing_status')) {
            Schema::table('surgeries', function (Blueprint $table) {
                $table->dropColumn('devices_billing_status');
            });
        }
    }
};
