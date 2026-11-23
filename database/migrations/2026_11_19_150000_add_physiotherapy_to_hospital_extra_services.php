<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('hospital_extra_services')) {
            DB::table('hospital_extra_services')->updateOrInsert(
                ['name' => 'علاج فيزيائي'],
                [
                    'code' => 'PHYS-01',
                    'category' => 'medical',
                    'default_price' => 60000.00,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('hospital_extra_services')) {
            DB::table('hospital_extra_services')
                ->where('name', 'علاج فيزيائي')
                ->orWhere('code', 'PHYS-01')
                ->delete();
        }
    }
};
