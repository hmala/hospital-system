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
        // 1. العملات وسعر الصرف الرسمي
        Schema::create('acc_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // IQD, USD
            $table->string('name', 50);          // دينار عراقي, دولار أمريكي
            $table->string('symbol', 10);        // د.ع, $
            $table->decimal('exchange_rate', 12, 4)->default(1.0000); // سعر الصرف مقابل الدينار
            $table->boolean('is_default')->default(false);            // العملة الأساسية (IQD)
            $table->boolean('is_official')->default(true);            // هل السعر رسمي
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. مراكز التكلفة
        Schema::create('acc_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->foreignId('parent_id')->nullable()->constrained('acc_cost_centers')->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->timestamps();

            $table->unique(['code', 'company_id']);
        });

        // 3. شجرة الحسابات (الدليل المحاسبي الموحد العراقي)
        Schema::create('acc_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30);
            $table->string('name', 255);
            $table->foreignId('parent_id')->nullable()->constrained('acc_accounts')->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(1); // 1 إلى 6
            $table->enum('account_type', ['asset', 'liability', 'equity', 'expense', 'revenue']);
            $table->enum('nature', ['debit', 'credit']);
            $table->boolean('is_postable')->default(false);   // true للمستوى 5 أو 6 النهائي فقط
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->timestamps();

            $table->unique(['code', 'company_id']);
            $table->index(['account_type', 'is_postable', 'level']);
        });

        // 4. السنوات المالية
        Schema::create('acc_fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // e.g. السنة المالية 2026
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_closed')->default(false);
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->timestamps();
        });

        // 5. الفترات والشهور المالية
        Schema::create('acc_fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained('acc_fiscal_years')->cascadeOnDelete();
            $table->unsignedTinyInteger('period_number'); // 1 إلى 12
            $table->string('name', 50);                  // كانون الثاني 2026
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'period_number']);
        });

        // 6. القيود المحاسبية اليومية
        Schema::create('acc_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 50); // e.g. JV-2026-00001
            $table->date('date');
            $table->foreignId('fiscal_year_id')->nullable()->constrained('acc_fiscal_years')->nullOnDelete();
            $table->foreignId('fiscal_period_id')->nullable()->constrained('acc_fiscal_periods')->nullOnDelete();
            $table->string('reference_type', 150)->nullable(); // Polymorphic source
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('posted');
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->foreignId('reversed_by_entry_id')->nullable()->constrained('acc_journal_entries')->nullOnDelete();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->timestamps();

            $table->unique(['entry_number', 'company_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['date', 'status']);
        });

        // 7. أسطر القيود المحاسبية
        Schema::create('acc_journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('acc_journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('acc_accounts');
            $table->foreignId('cost_center_id')->nullable()->constrained('acc_cost_centers')->nullOnDelete();
            $table->decimal('debit', 15, 2)->default(0.00);
            $table->decimal('credit', 15, 2)->default(0.00);
            $table->foreignId('currency_id')->nullable()->constrained('acc_currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 12, 4)->default(1.0000);
            $table->decimal('currency_amount', 15, 2)->nullable();
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('patient_id')->nullable()->index();
            $table->unsignedBigInteger('doctor_id')->nullable()->index();
            $table->timestamps();

            $table->index(['account_id', 'cost_center_id']);
        });

        // 8. سجل التدقيق والرقابة المالية الصارم
        Schema::create('acc_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action', 50); // post, reverse, override, close_period
            $table->string('auditable_type', 100);
            $table->unsignedBigInteger('auditable_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_audit_logs');
        Schema::dropIfExists('acc_journal_entry_lines');
        Schema::dropIfExists('acc_journal_entries');
        Schema::dropIfExists('acc_fiscal_periods');
        Schema::dropIfExists('acc_fiscal_years');
        Schema::dropIfExists('acc_accounts');
        Schema::dropIfExists('acc_cost_centers');
        Schema::dropIfExists('acc_currencies');
    }
};
