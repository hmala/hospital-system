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
        Schema::table('emergencies', function (Blueprint $table) {
            // drop old foreign key if exists
            $table->dropForeign(['patient_id']);
        });

        Schema::table('emergencies', function (Blueprint $table) {
            // modify column to be nullable without recreating it
            $table->unsignedBigInteger('patient_id')->nullable()->change();
            // re-add foreign constraint
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergencies', function (Blueprint $table) {
            // drop foreign key again
            $table->dropForeign(['patient_id']);
        });

        Schema::table('emergencies', function (Blueprint $table) {
            // make column non-nullable again
            $table->unsignedBigInteger('patient_id')->nullable(false)->change();
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
        });
    }
};
