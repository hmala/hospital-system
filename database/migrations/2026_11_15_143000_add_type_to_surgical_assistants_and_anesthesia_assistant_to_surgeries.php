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
        if (Schema::hasTable('surgical_assistants')) {
            Schema::table('surgical_assistants', function (Blueprint $table) {
                if (! Schema::hasColumn('surgical_assistants', 'type')) {
                    $table->string('type', 30)->default('surgical')->after('name')->index();
                }
            });
        }

        if (Schema::hasTable('surgeries')) {
            Schema::table('surgeries', function (Blueprint $table) {
                if (! Schema::hasColumn('surgeries', 'anesthesia_assistant_name')) {
                    $table->string('anesthesia_assistant_name')->nullable()->after('surgical_assistant_name');
                }
            });
        }

        if (Schema::hasTable('anesthesia_stations')) {
            Schema::table('anesthesia_stations', function (Blueprint $table) {
                if (! Schema::hasColumn('anesthesia_stations', 'anesthesia_assistant_name')) {
                    $table->string('anesthesia_assistant_name')->nullable()->after('surgical_assistant_name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('surgical_assistants')) {
            Schema::table('surgical_assistants', function (Blueprint $table) {
                if (Schema::hasColumn('surgical_assistants', 'type')) {
                    $table->dropIndex(['type']);
                    $table->dropColumn('type');
                }
            });
        }

        if (Schema::hasTable('surgeries')) {
            Schema::table('surgeries', function (Blueprint $table) {
                if (Schema::hasColumn('surgeries', 'anesthesia_assistant_name')) {
                    $table->dropColumn('anesthesia_assistant_name');
                }
            });
        }

        if (Schema::hasTable('anesthesia_stations')) {
            Schema::table('anesthesia_stations', function (Blueprint $table) {
                if (Schema::hasColumn('anesthesia_stations', 'anesthesia_assistant_name')) {
                    $table->dropColumn('anesthesia_assistant_name');
                }
            });
        }
    }
};
