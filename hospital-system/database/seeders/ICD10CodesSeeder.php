<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ICD10Import;

class ICD10CodesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = storage_path('app/icd10.xlsx');

        if (!file_exists($filePath)) {
            $this->command->info('ICD10 Excel file not found at: ' . $filePath);
            return;
        }

        Excel::import(new ICD10Import, $filePath);

        $this->command->info('ICD10 codes imported successfully.');
    }
}
