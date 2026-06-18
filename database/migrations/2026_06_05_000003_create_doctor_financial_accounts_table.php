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
        Schema::create('doctor_financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 14, 2)->default(0);
            $table->decimal('total_earned', 14, 2)->default(0);
            $table->decimal('total_paid', 14, 2)->default(0);
            $table->timestamp('last_paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('doctor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_financial_accounts');
    }
};
