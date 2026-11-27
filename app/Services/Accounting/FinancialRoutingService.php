<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\FiscalPeriod;
use App\Models\Accounting\FiscalYear;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Models\Location;
use App\Models\Payment;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialRoutingService
{
    /**
     * تحديد صندوق المقبوضات النقدية بالكاشير بحسب نوع تغطية المريض
     */
    public function resolveCashDrawerAccount(?string $insuranceType): Account
    {
        $insuranceType = strtolower((string) $insuranceType);

        if (in_array($insuranceType, ['health_insurance', 'hi'])) {
            return Account::where('code', '134112')->firstOrFail();
        }

        if (in_array($insuranceType, ['moi', 'police'])) {
            return Account::where('code', '134113')->firstOrFail();
        }

        return Account::where('code', '134111')->firstOrFail();
    }

    /**
     * تحديد حساب ذمة المطالبة الآجلة على الجهة الضامنة الحكومية
     */
    public function resolveReceivableAccount(?string $insuranceType): ?Account
    {
        $insuranceType = strtolower((string) $insuranceType);

        if (in_array($insuranceType, ['health_insurance', 'hi'])) {
            return Account::where('code', '132111')->first();
        }

        if (in_array($insuranceType, ['moi', 'police'])) {
            return Account::where('code', '132112')->first();
        }

        return null;
    }

    /**
     * جلب الحساب الوسيط لأمانات ومقبوضات المرضى (2326111)
     */
    public function resolveClearingAccount(): Account
    {
        return Account::where('code', '2326111')->firstOrFail();
    }

    /**
     * توجيه إيراد الخدمة الطبية حسب نوع النشاط والتبويب الداخلي
     */
    public function resolveRevenueAccount(string $module, ?string $category = null): ?Account
    {
        $module = strtolower($module);

        if ($module === 'radiology') {
            if ($category) {
                if (str_contains($category, 'سونار')) {
                    return Account::where('code', '4312132')->first();
                }
                if (str_contains($category, 'رنين') || str_contains($category, 'الرنين')) {
                    return Account::where('code', '4312133')->first();
                }
                if (str_contains($category, 'مفراس')) {
                    return Account::where('code', '4312134')->first();
                }
                if (str_contains($category, 'إيكو') || str_contains($category, 'ايكو')) {
                    return Account::where('code', '4312135')->first();
                }
                if (str_contains($category, 'أشعة') || str_contains($category, 'اشعة')) {
                    return Account::where('code', '4312131')->first();
                }
            }

            return Account::where('code', '4312131')->first() ?? Account::where('code', '431213')->first();
        }

        if ($module === 'lab') {
            return Account::where('code', '4312121')->first() ?? Account::where('code', '431212')->first();
        }

        if ($module === 'nicu') {
            if ($category && (str_contains($category, 'ventilator') || str_contains($category, 'cpap'))) {
                return Account::where('code', '4312152')->first();
            }

            return Account::where('code', '4312151')->first() ?? Account::where('code', '431215')->first();
        }

        if ($module === 'surgery') {
            if ($category && str_contains($category, 'device')) {
                return Account::where('code', '4312112')->first();
            }

            return Account::where('code', '4312111')->first();
        }

        if ($module === 'emergency') {
            return Account::where('code', '431216')->first();
        }

        if (in_array($module, ['clinic', 'consultation'])) {
            return Account::where('code', '431217')->first();
        }

        return Account::where('code', '431217')->first() ?? Account::where('code', '43121')->first();
    }

    /**
     * توليد قيد القبض النقدي المتوازن آلياً عند دفع الوصل في الكاشير
     */
    public function recordPaymentReceipt(Payment $payment, ?int $userId = null): JournalEntry
    {
        return DB::transaction(function () use ($payment, $userId) {
            $paymentDate = $payment->paid_at ?? now();
            $carbonDate = Carbon::parse($paymentDate);

            // جلب السنة والفترة المالية
            $fiscalYear = FiscalYear::where('start_date', '<=', $carbonDate->toDateString())
                ->where('end_date', '>=', $carbonDate->toDateString())
                ->first() ?? FiscalYear::first();

            $fiscalPeriod = FiscalPeriod::where('fiscal_year_id', $fiscalYear?->id)
                ->where('start_date', '<=', $carbonDate->toDateString())
                ->where('end_date', '>=', $carbonDate->toDateString())
                ->first() ?? FiscalPeriod::first();

            // حساب الحصص
            $patientShare = (float) ($payment->patient_share > 0 ? $payment->patient_share : ($payment->cash_amount > 0 ? $payment->cash_amount : $payment->amount));
            $insuranceShare = (float) ($payment->insurance_share ?? 0);
            $totalAmount = $patientShare + $insuranceShare;

            if ($totalAmount <= 0) {
                $totalAmount = (float) ($payment->total_amount ?: $payment->amount);
                $patientShare = $totalAmount;
            }

            // الصندوق والذمة المدينة
            $cashDrawer = $this->resolveCashDrawerAccount($payment->insurance_type);
            $receivableAcc = $this->resolveReceivableAccount($payment->insurance_type);
            $clearingAcc = $this->resolveClearingAccount();

            $entryNumber = JournalEntry::generateEntryNumber();
            $patientName = $payment->patient?->name ?? 'غير محدد';
            $receiptNum = $payment->receipt_number ?? "REC-{$payment->id}";

            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'date' => $carbonDate->toDateString(),
                'fiscal_year_id' => $fiscalYear?->id,
                'fiscal_period_id' => $fiscalPeriod?->id,
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'description' => "قبض نقدي بالصندوق - وصل رقم ({$receiptNum}) - المريض: {$patientName}",
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $userId ?? auth()->id(),
                'company_id' => $fiscalYear?->company_id ?? 1,
            ]);

            // 1. السطر المدين: الكاش المستلم في صندوق الكاشير
            if ($patientShare > 0) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $cashDrawer->id,
                    'debit' => $patientShare,
                    'credit' => 0.00,
                    'description' => "مقبوضات كاش من المريض ({$patientName}) - وصل: {$receiptNum}",
                    'patient_id' => $payment->patient_id,
                ]);
            }

            // 2. السطر المدين: حصة المطالبة الآجلة على الضمان أو الداخلية
            if ($insuranceShare > 0 && $receivableAcc) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $receivableAcc->id,
                    'debit' => $insuranceShare,
                    'credit' => 0.00,
                    'description' => "مطالبة آجلة على الجهة الضامنة للمريض ({$patientName}) - وصل: {$receiptNum}",
                    'patient_id' => $payment->patient_id,
                ]);
            }

            // 3. السطر الدائن: إقفال في حساب مقبوضات المرضى الوسيط (2326111)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $clearingAcc->id,
                'debit' => 0.00,
                'credit' => $totalAmount,
                'description' => "قيد وسيط أمانات ومقبوضات المريض ({$patientName}) - وصل: {$receiptNum}",
                'patient_id' => $payment->patient_id,
            ]);

            return $entry;
        });
    }

    /**
     * توليد قيد استهلاك وصرف مواد طبية من المخزن الفرعي (3263)
     */
    public function recordMaterialConsumption(
        int $locationId,
        float $totalCost,
        ?int $surgeryId = null,
        ?int $patientId = null,
        string $description = ''
    ): JournalEntry {
        return DB::transaction(function () use ($locationId, $totalCost, $patientId, $description) {
            $location = Location::find($locationId);
            $costCenterId = $location?->cost_center_id;

            $medicalExpenseAcc = Account::where('code', '3263')->firstOrFail();
            $inventoryAcc = Account::where('code', '131')->first()
                ?? Account::where('code', 'LIKE', '131%')->where('is_postable', true)->firstOrFail();

            $carbonDate = now();
            $fiscalYear = FiscalYear::first();
            $fiscalPeriod = FiscalPeriod::first();

            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(),
                'date' => $carbonDate->toDateString(),
                'fiscal_year_id' => $fiscalYear?->id,
                'fiscal_period_id' => $fiscalPeriod?->id,
                'reference_type' => Location::class,
                'reference_id' => $locationId,
                'description' => $description ?: 'صرف مواد ومستلزمات طبية من: '.($location?->name ?? 'مخزن فرعي'),
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
                'company_id' => 1,
            ]);

            // المدين: حساب مصروفات المواد الطبية مع مركز الكلفة
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $medicalExpenseAcc->id,
                'cost_center_id' => $costCenterId,
                'debit' => $totalCost,
                'credit' => 0.00,
                'description' => 'مصروف مواد طبية مستهلكة - '.($location?->name ?? ''),
                'patient_id' => $patientId,
            ]);

            // الدائن: حساب المخزون (تخفيض الرصيد)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAcc->id,
                'debit' => 0.00,
                'credit' => $totalCost,
                'description' => 'صرف من رصيد مخزون الأدوية والمستلزمات الطبية',
                'patient_id' => $patientId,
            ]);

            return $entry;
        });
    }

    /**
     * توليد قيد فاتورة شراء وتوريد مستلزمات من مورد قطاع خاص (23214)
     */
    public function recordSupplierInvoice(
        int $supplierId,
        string $invoiceNumber,
        float $totalAmount,
        string $receivedDate,
        string $notes = ''
    ): JournalEntry {
        return DB::transaction(function () use ($supplierId, $invoiceNumber, $totalAmount, $receivedDate, $notes) {
            $supplier = Supplier::findOrFail($supplierId);
            $supplierAcc = Account::where('code', '23214')->firstOrFail();
            $inventoryAcc = Account::where('code', '131')->first()
                ?? Account::where('code', 'LIKE', '131%')->where('is_postable', true)->firstOrFail();

            $carbonDate = Carbon::parse($receivedDate);
            $fiscalYear = FiscalYear::first();
            $fiscalPeriod = FiscalPeriod::first();

            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(),
                'date' => $carbonDate->toDateString(),
                'fiscal_year_id' => $fiscalYear?->id,
                'fiscal_period_id' => $fiscalPeriod?->id,
                'reference_type' => Supplier::class,
                'reference_id' => $supplierId,
                'description' => "فاتورة توريد مواد طبية رقم ({$invoiceNumber}) - المورد: {$supplier->name}",
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
                'company_id' => 1,
            ]);

            // المدين: حساب المخزون (زيادة البضاعة بالمخزن)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAcc->id,
                'debit' => $totalAmount,
                'credit' => 0.00,
                'description' => "توريد مخزني بموجب فاتورة ({$invoiceNumber}) - {$notes}",
            ]);

            // الدائن: حساب المورد قطاع خاص (23214)
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $supplierAcc->id,
                'debit' => 0.00,
                'credit' => $totalAmount,
                'description' => "استحقاق المورد ({$supplier->name}) بموجب فاتورة ({$invoiceNumber})",
            ]);

            return $entry;
        });
    }
}
