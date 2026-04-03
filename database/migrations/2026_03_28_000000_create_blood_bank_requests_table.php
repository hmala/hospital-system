<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('blood_bank_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->onDelete('cascade');
            $table->foreignId('visit_id')->constrained('visits')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->string('room_no')->nullable();
            $table->string('donor_group')->nullable();
            $table->string('patient_group')->nullable();
            $table->string('at_room_temp')->nullable();
            $table->string('bovine_albumin')->nullable();
            $table->string('anti_human_globulin')->nullable();
            $table->string('compatibility')->nullable();
            $table->string('bottle_no')->nullable();
            $table->date('operative_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->string('doctor_in_charge')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('blood_bank_requests');
    }
};