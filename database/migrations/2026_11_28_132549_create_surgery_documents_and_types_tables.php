<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('surgery_document_types')) {
            Schema::create('surgery_document_types', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('code')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });

            // Seed initial common surgical document types
            $defaultTypes = [
                ['name' => 'ورقة تحويل طبي', 'code' => 'referral_letter', 'sort_order' => 1],
                ['name' => 'استمارة موافقة وتعهد العملية', 'code' => 'surgical_consent', 'sort_order' => 2],
                ['name' => 'فحص واستشارة التخدير / الباطنية', 'code' => 'anesthesia_clearance', 'sort_order' => 3],
                ['name' => 'تقرير أشعة / سونار / رنين', 'code' => 'radiology_report', 'sort_order' => 4],
                ['name' => 'تقرير تخطيط القلب / إيكو', 'code' => 'ecg_echo', 'sort_order' => 5],
                ['name' => 'نتائج تحاليل ومطابقة دم', 'code' => 'lab_results', 'sort_order' => 6],
                ['name' => 'هوية / بطاقة الضمان أو التأمين', 'code' => 'id_insurance', 'sort_order' => 7],
                ['name' => 'مستندات وتعهدات أخرى', 'code' => 'other', 'sort_order' => 8],
            ];

            foreach ($defaultTypes as $type) {
                DB::table('surgery_document_types')->insert([
                    'name' => $type['name'],
                    'code' => $type['code'],
                    'is_active' => true,
                    'sort_order' => $type['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (! Schema::hasTable('surgery_documents')) {
            Schema::create('surgery_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('surgery_id')->constrained('surgeries')->cascadeOnDelete();
                $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
                $table->foreignId('surgery_document_type_id')->nullable()->constrained('surgery_document_types')->nullOnDelete();
                $table->string('document_type_name')->nullable();
                $table->string('title');
                $table->string('file_path');
                $table->string('file_name')->nullable();
                $table->string('file_type', 100)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['surgery_id', 'created_at']);
                $table->index(['patient_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surgery_documents');
        Schema::dropIfExists('surgery_document_types');
    }
};
