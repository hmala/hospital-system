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
        $dataFile = database_path('seeders/approved_emergency_services_data.php');
        if (!file_exists($dataFile)) {
            return;
        }

        $services = require $dataFile;
        $approvedIds = [];

        foreach ($services as $service) {
            $id = (int)$service['id'];
            $approvedIds[] = $id;

            $exists = DB::table('emergency_services')->where('id', $id)->first();
            if ($exists) {
                DB::table('emergency_services')->where('id', $id)->update([
                    'name' => $service['name'],
                    'category' => $service['category'] ?? 'طوارئ عامة',
                    'price' => $service['price'],
                    'hi_price' => $service['hi_price'],
                    'is_hi_active' => $service['is_hi_active'],
                    'is_active' => $service['is_active'],
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('emergency_services')->insert([
                    'id' => $id,
                    'name' => $service['name'],
                    'category' => $service['category'] ?? 'طوارئ عامة',
                    'price' => $service['price'],
                    'hi_price' => $service['hi_price'],
                    'is_hi_active' => $service['is_hi_active'],
                    'is_active' => $service['is_active'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Safely deactivate legacy services not present in the approved Excel list
        if (!empty($approvedIds)) {
            DB::table('emergency_services')
                ->whereNotIn('id', $approvedIds)
                ->update([
                    'is_active' => 0,
                    'is_hi_active' => 0,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep existing records intact
    }
};
