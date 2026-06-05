<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_result_sub_results', function (Blueprint $table) {
            $table->string('value_text')->nullable()->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('lab_result_sub_results', function (Blueprint $table) {
            $table->dropColumn('value_text');
        });
    }
};
