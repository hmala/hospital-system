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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('cashier_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('receipt_number')->unique(); // رقم الإيصال
            $table->decimal('amount', 10, 2); // المبلغ المدفوع
            $table->enum('payment_method', ['cash', 'card', 'insurance'])->default('cash'); // طريقة الدفع
            $table->enum('payment_type', ['appointment', 'lab', 'radiology', 'pharmacy', 'surgery', 'other'])->default('appointment');
            $table->text('description')->nullable(); // وصف الدفعة
            $table->text('notes')->nullable(); // ملاحظات
            $table->timestamp('paid_at'); // تاريخ ووقت الدفع
            $table->timestamps();
            
            // فهرس للبحث السريع
            $table->index('receipt_number');
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
