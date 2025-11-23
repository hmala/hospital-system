<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GovernorateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $iraq = \App\Models\Country::where('name', 'العراق')->first();
        if ($iraq) {
            $governorates = [
                'بغداد', 'البصرة', 'نينوى', 'أربيل', 'الأنبار', 'بابل', 'النجف', 'كربلاء', 'القادسية', 'ميسان',
                'واسط', 'الديوانية', 'المثنى', 'ذي قار', 'صلاح الدين', 'كركوك', 'ديالى', 'السليمانية', 'دهوك'
            ];

            foreach ($governorates as $governorate) {
                \App\Models\Governorate::create([
                    'name' => $governorate,
                    'country_id' => $iraq->id
                ]);
            }
        }

    }
}
