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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('transaction_type', ['doctor_payment', 'hospital_revenue', 'expense', 'receivable', 'payable', 'other']);
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 10)->default('IQD');
            $table->text('description')->nullable();
            $table->foreignId('performed_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('performed_at')->nullable();
            $table->timestamps();

            $table->index(['related_type', 'related_id']);
            $table->index('transaction_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
