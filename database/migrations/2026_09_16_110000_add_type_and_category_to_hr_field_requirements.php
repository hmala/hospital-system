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
        Schema::table('hr_field_requirements', function (Blueprint $table) {
            $table->string('field_type', 30)->default('text')->after('field_name_ar')->comment('text, select, date, number, file, textarea');
            $table->string('lookup_category', 50)->nullable()->after('field_type')->comment('category in hr_lookup_options if field_type is select');
            $table->integer('sort_order')->default(0)->after('is_locked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_field_requirements', function (Blueprint $table) {
            $table->dropColumn(['field_type', 'lookup_category', 'sort_order']);
        });
    }
};
