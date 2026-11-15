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
        DB::table('hospitals')->where('id', 1)->update([
            'name' => 'مستشفى الكفاءات الأهلي',
            'email' => 'info@alkafaat-hospital.com',
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('hospitals')->where('id', 1)->update([
            'name' => 'مستشفى الأمل الأهلي',
            'email' => 'info@al-amel-hospital.com',
            'updated_at' => now(),
        ]);
    }
};
