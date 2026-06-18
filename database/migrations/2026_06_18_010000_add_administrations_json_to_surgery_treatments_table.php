<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surgery_treatments', function (Blueprint $table) {
            $table->json('administrations')->nullable()->after('admin_notes');
        });
    }

    public function down(): void
    {
        Schema::table('surgery_treatments', function (Blueprint $table) {
            $table->dropColumn('administrations');
        });
    }
};
