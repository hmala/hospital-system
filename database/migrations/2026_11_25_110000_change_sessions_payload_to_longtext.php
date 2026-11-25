<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sessions')) {
            $driver = DB::getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'])) {
                DB::statement('ALTER TABLE sessions MODIFY payload LONGTEXT');
            } else {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->longText('payload')->change();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sessions')) {
            $driver = DB::getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'])) {
                DB::statement('ALTER TABLE sessions MODIFY payload TEXT');
            } else {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->text('payload')->change();
                });
            }
        }
    }
};
