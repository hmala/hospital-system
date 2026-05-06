<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE `users` MODIFY `role` VARCHAR(50) NOT NULL DEFAULT 'patient'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin','doctor','receptionist','patient') NOT NULL DEFAULT 'patient'");
    }
};
