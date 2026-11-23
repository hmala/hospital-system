<?php

use App\Models\SurgeryTabPermission;
use App\Models\SurgeryTreatment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surgery_treatments', function (Blueprint $table) {
            if (! Schema::hasColumn('surgery_treatments', 'item_type')) {
                $table->string('item_type', 30)->default(SurgeryTreatment::TYPE_MEDICATION)->index()->after('surgery_id');
            }
        });

        SurgeryTreatment::with('batch.product')
            ->whereNotNull('batch_id')
            ->chunkById(100, function ($treatments) {
                foreach ($treatments as $treatment) {
                    $resolvedType = $treatment->batch?->product?->resolveItemType();
                    if ($resolvedType === SurgeryTreatment::TYPE_SUPPLY) {
                        $treatment->update(['item_type' => SurgeryTreatment::TYPE_SUPPLY]);
                    }
                }
            });

        if (Schema::hasTable('surgery_tab_permissions') && SurgeryTabPermission::count() > 0) {
            foreach (SurgeryTabPermission::defaultMatrix() as $roleName => $tabs) {
                if (isset($tabs['supplies'])) {
                    SurgeryTabPermission::firstOrCreate(
                        ['role_name' => $roleName, 'tab_key' => 'supplies'],
                        ['access_level' => $tabs['supplies']]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('surgery_tab_permissions')) {
            SurgeryTabPermission::where('tab_key', 'supplies')->delete();
        }

        Schema::table('surgery_treatments', function (Blueprint $table) {
            if (Schema::hasColumn('surgery_treatments', 'item_type')) {
                $table->dropIndex(['item_type']);
                $table->dropColumn('item_type');
            }
        });
    }
};
