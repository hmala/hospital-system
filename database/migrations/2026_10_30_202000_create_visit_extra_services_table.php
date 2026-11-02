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
        if (! Schema::hasTable('visit_extra_services')) {
            Schema::create('visit_extra_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('visit_id')->nullable()->constrained('visits')->cascadeOnDelete();
                $table->foreignId('request_id')->nullable()->constrained('requests')->cascadeOnDelete();
                $table->foreignId('service_id')->nullable()->constrained('hospital_extra_services')->nullOnDelete();
                $table->string('name');
                $table->decimal('price', 12, 2)->default(0.00);
                $table->integer('quantity')->default(1);
                $table->decimal('total_amount', 12, 2)->default(0.00);
                $table->string('notes', 500)->nullable();
                $table->string('payment_status', 30)->default('pending'); // pending, paid, cancelled
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_extra_services');
    }
};
