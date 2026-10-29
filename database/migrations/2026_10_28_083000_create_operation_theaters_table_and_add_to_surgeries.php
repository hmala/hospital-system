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
        if (! Schema::hasTable('operation_theaters')) {
            Schema::create('operation_theaters', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('code')->nullable();
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('surgeries') && ! Schema::hasColumn('surgeries', 'operation_theater_id')) {
            Schema::table('surgeries', function (Blueprint $table) {
                $table->unsignedBigInteger('operation_theater_id')
                    ->nullable()
                    ->after('room_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('surgeries') && Schema::hasColumn('surgeries', 'operation_theater_id')) {
            Schema::table('surgeries', function (Blueprint $table) {
                $table->dropColumn('operation_theater_id');
            });
        }

        Schema::dropIfExists('operation_theaters');
    }
};
