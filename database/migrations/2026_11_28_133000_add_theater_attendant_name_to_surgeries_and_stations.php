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
        if (Schema::hasTable('surgeries')) {
            Schema::table('surgeries', function (Blueprint $table) {
                if (! Schema::hasColumn('surgeries', 'theater_attendant_name')) {
                    $table->string('theater_attendant_name')->nullable()->after('anesthesia_assistant_name');
                }
            });
        }

        if (Schema::hasTable('anesthesia_stations')) {
            Schema::table('anesthesia_stations', function (Blueprint $table) {
                if (! Schema::hasColumn('anesthesia_stations', 'theater_attendant_name')) {
                    $table->string('theater_attendant_name')->nullable()->after('anesthesia_assistant_name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('surgeries')) {
            Schema::table('surgeries', function (Blueprint $table) {
                if (Schema::hasColumn('surgeries', 'theater_attendant_name')) {
                    $table->dropColumn('theater_attendant_name');
                }
            });
        }

        if (Schema::hasTable('anesthesia_stations')) {
            Schema::table('anesthesia_stations', function (Blueprint $table) {
                if (Schema::hasColumn('anesthesia_stations', 'theater_attendant_name')) {
                    $table->dropColumn('theater_attendant_name');
                }
            });
        }
    }
};
