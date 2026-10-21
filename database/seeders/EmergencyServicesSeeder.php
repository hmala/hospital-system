<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EmergencyService;

class EmergencyServicesSeeder extends Seeder
{
    public function run(): void
    {
        $dataFile = database_path('seeders/approved_emergency_services_data.php');
        if (!file_exists($dataFile)) {
            return;
        }

        $services = require $dataFile;
        foreach ($services as $service) {
            EmergencyService::updateOrCreate(
                ['id' => $service['id']],
                [
                    'name' => $service['name'],
                    'category' => $service['category'] ?? 'طوارئ عامة',
                    'price' => $service['price'],
                    'hi_price' => $service['hi_price'],
                    'is_hi_active' => $service['is_hi_active'],
                    'is_active' => $service['is_active'],
                ]
            );
        }
    }
}