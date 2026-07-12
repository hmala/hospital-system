<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') { DB::statement("ALTER TABLE requests MODIFY COLUMN type ENUM('lab','radiology','pharmacy','emergency','blood_bank') NOT NULL"); }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') { DB::statement("ALTER TABLE requests MODIFY COLUMN type ENUM('lab','radiology','pharmacy','emergency') NOT NULL"); }
    }
};
