<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->consolidateOnConnection(null);

        // إذا كان هناك اتصال بهوستنجر متوفر، نطبق عليه أيضاً
        try {
            if (config('database.connections.remote_hostinger')) {
                $this->consolidateOnConnection('remote_hostinger');
            }
        } catch (\Throwable $e) {
            // تجاهل إن لم يكن متاحاً أثناء المايجريشن المحلي
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('lab_tests')->where('id', 44)->update([
            'name' => 'CBC (5-Part Diff)',
            'is_active' => 1,
        ]);
    }

    protected function consolidateOnConnection(?string $connection): void
    {
        $db = $connection ? DB::connection($connection) : DB::connection();

        // 1. اعتماد السجل #43 كفحص رسمي وحيد نشط باسم CBC (5-Part Diff)
        $db->table('lab_tests')->where('id', 43)->update([
            'name' => 'CBC (5-Part Diff)',
            'is_active' => 1,
            'price' => 20000.00,
        ]);

        // 2. تمييز وتعطيل السجل #44 كأرشيف مدمج
        $db->table('lab_tests')->where('id', 44)->update([
            'name' => 'CBC (5-Part Diff) [أرشيف مدمج]',
            'is_active' => 0,
        ]);

        // 3. تمييز وتعطيل السجل #1440 كمكرر ملغي
        $db->table('lab_tests')->where('id', 1440)->update([
            'name' => 'CBC [مكرر ملغي]',
            'is_active' => 0,
        ]);

        // 4. ترحيل باقات الأطباء (user_lab_test_group_lab_test) من 44 و 1440 إلى 43
        $groupRows = $db->table('user_lab_test_group_lab_test')
            ->whereIn('lab_test_id', [44, 1440])
            ->get();

        foreach ($groupRows as $row) {
            $groupId = $row->user_lab_test_group_id ?? $row->group_id ?? null;
            if ($groupId) {
                $alreadyHas43 = $db->table('user_lab_test_group_lab_test')
                    ->where(isset($row->user_lab_test_group_id) ? 'user_lab_test_group_id' : 'group_id', $groupId)
                    ->where('lab_test_id', 43)
                    ->exists();

                if ($alreadyHas43) {
                    $db->table('user_lab_test_group_lab_test')->where('id', $row->id)->delete();
                } else {
                    $db->table('user_lab_test_group_lab_test')->where('id', $row->id)->update(['lab_test_id' => 43]);
                }
            }
        }

        // 5. ترحيل الباقات العامة (package_lab_test) إن وجدت
        if (\Illuminate\Support\Facades\Schema::connection($connection)->hasTable('package_lab_test')) {
            $pkgRows = $db->table('package_lab_test')
                ->whereIn('lab_test_id', [44, 1440])
                ->get();

            foreach ($pkgRows as $row) {
                $pkgId = $row->package_id;
                $alreadyHas43 = $db->table('package_lab_test')
                    ->where('package_id', $pkgId)
                    ->where('lab_test_id', 43)
                    ->exists();

                if ($alreadyHas43) {
                    $db->table('package_lab_test')->where('id', $row->id)->delete();
                } else {
                    $db->table('package_lab_test')->where('id', $row->id)->update(['lab_test_id' => 43]);
                }
            }
        }
    }
};
