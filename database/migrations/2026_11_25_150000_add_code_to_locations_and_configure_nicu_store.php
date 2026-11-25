<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('locations') && ! Schema::hasColumn('locations', 'code')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->string('code', 60)->nullable()->unique()->after('type');
            });
        }

        if (Schema::hasTable('locations') && Schema::hasColumn('locations', 'code')) {
            // وسم المخزن الرئيسي بكود ثابت إن لم يكن موسماً
            $mainLocation = DB::table('locations')
                ->where('type', 'main')
                ->orderBy('id')
                ->first();

            if ($mainLocation && empty($mainLocation->code)) {
                DB::table('locations')
                    ->where('id', $mainLocation->id)
                    ->update(['code' => 'main']);
            }

            // اكتشاف مخزن الخدج الحالي بأي قاعدة بيانات ووسمه بالكود الثابت nicu بدون الاعتماد على ID ثابت
            $nicuLocation = DB::table('locations')
                ->where('code', 'nicu')
                ->first();

            if (! $nicuLocation) {
                $nicuLocation = DB::table('locations')
                    ->where('type', 'sub')
                    ->where(function ($q) {
                        $q->where('name', 'like', '%خدج%')
                            ->orWhere('name', 'like', '%حاضن%')
                            ->orWhere('name', 'like', '%NICU%');
                    })
                    ->orderBy('id')
                    ->first();

                if ($nicuLocation && empty($nicuLocation->code)) {
                    DB::table('locations')
                        ->where('id', $nicuLocation->id)
                        ->update(['code' => 'nicu']);
                }
            }

            if ($nicuLocation && Schema::hasTable('nicu_settings')) {
                $existingSetting = DB::table('nicu_settings')
                    ->where('key', 'nicu_store_location_id')
                    ->first();

                if (! $existingSetting) {
                    DB::table('nicu_settings')->insert([
                        'key' => 'nicu_store_location_id',
                        'value' => (string) $nicuLocation->id,
                        'label' => 'مخزن وعُهدة قسم الخدج المعتمد',
                        'description' => 'معرف الموقع المخزني الفرعي المعتمد لقسم الخدج والحاضنات (ديناميكي لكل مستشفى وقاعدة بيانات)',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('nicu_settings')) {
            DB::table('nicu_settings')->where('key', 'nicu_store_location_id')->delete();
        }

        if (Schema::hasTable('locations') && Schema::hasColumn('locations', 'code')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropUnique(['code']);
                $table->dropColumn('code');
            });
        }
    }
};
