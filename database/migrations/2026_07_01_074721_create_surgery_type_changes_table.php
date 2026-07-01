<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgery_type_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained()->cascadeOnDelete();
            $table->string('old_type')->nullable();
            $table->string('new_type');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgery_type_changes');
    }
};
