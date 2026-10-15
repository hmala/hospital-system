<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Patient;
use App\Models\HealthInsuranceCategory;

class ImportInsurancePatientsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insurance:import-patients 
                            {--file=service_records.csv : المسار النسبي أو المطلق لملف الـ CSV}
                            {--dry-run : تجربة وهمية لفحص البيانات دون الحفظ في قاعدة البيانات}
                            {--update-categories : تحديث فئة الضمان للمرضى المسجلين مسبقاً في حال اختلافها}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'استيراد وفحص مرضى الضمان الصحي وفئاتهم من ملف service_records.csv';

    /**
     * تنفيذ الأمر.
     */
    public function handle()
    {
        $filePath = $this->option('file');
        if (!file_exists($filePath)) {
            $filePath = base_path($filePath);
        }

        if (!file_exists($filePath)) {
            $this->error("الملف غير موجود في المسار: {$filePath}");
            return 1;
        }

        $isDryRun = $this->option('dry-run');
        $updateCategories = $this->option('update-categories');

        $this->info("==========================================================");
        $this->info("بدء استيراد مرضى الضمان الصحي " . ($isDryRun ? "[وضع المعاينة الوهمية Dry-Run]" : "[تنفيذ فعلي]"));
        $this->info("الملف المستهدف: " . $filePath);
        $this->info("==========================================================");

        // 1. تحميل فئات الضمان المعتمدة في النظام
        $categories = HealthInsuranceCategory::pluck('id', 'name')->toArray();
        $this->line("تم تحميل " . count($categories) . " فئات ضمان صحي من النظام.");

        // 2. تحميل المرضى الحاليين من قاعدة البيانات للمطابقة
        $this->line("جلب ومطابقة بيانات المرضى المسجلين مسبقاً في النظام...");
        $dbPatients = DB::table('patients')
            ->join('users', 'users.id', '=', 'patients.user_id')
            ->leftJoin('health_insurance_categories', 'health_insurance_categories.id', '=', 'patients.health_insurance_category_id')
            ->select(
                'patients.id as patient_id',
                'users.id as user_id',
                'users.name',
                'patients.insurance_type',
                'patients.health_insurance_category_id',
                'health_insurance_categories.name as category_name'
            )
            ->get();

        $dbExactLookup = [];
        $dbNormLookup = [];
        foreach ($dbPatients as $p) {
            $raw = trim($p->name);
            $norm = $this->normalizeArabic($raw);
            $dbExactLookup[$raw] = $p;
            if (!isset($dbNormLookup[$norm])) {
                $dbNormLookup[$norm] = $p;
            }
        }
        $this->line("يوجد في النظام حالياً " . count($dbPatients) . " مريض.");

        // 3. قراءة متدفقة لملف الـ CSV واستخراج المرضى الفريدين
        $this->line("قراءة وتجميع المرضى الفريدين من ملف الـ CSV...");
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("تعذر فتح ملف الـ CSV.");
            return 1;
        }

        // إزالة BOM إن وجد
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        $headerMap = array_flip($header);

        $nameCol = $headerMap['INSURED NAME'] ?? null;
        $policyCol = $headerMap['POLICY NAME'] ?? null;
        $dateCol = $headerMap['DATE'] ?? null;

        if ($nameCol === null || $policyCol === null) {
            $this->error("أعمدة الملف غير مطابقة (INSURED NAME أو POLICY NAME غير موجود).");
            fclose($handle);
            return 1;
        }

        $csvPatients = [];
        $rowCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;
            if (!isset($row[$nameCol])) continue;

            $name = trim($row[$nameCol]);
            if (empty($name)) continue;

            $policy = trim($row[$policyCol] ?? '');
            $date = trim($row[$dateCol] ?? '');

            if (!isset($csvPatients[$name])) {
                $csvPatients[$name] = [
                    'name' => $name,
                    'norm' => $this->normalizeArabic($name),
                    'policy' => $policy,
                    'earliest_date' => $date,
                    'latest_date' => $date,
                    'encounters_count' => 1,
                ];
            } else {
                $csvPatients[$name]['encounters_count']++;
                // نعتمد أحدث فئة ظهرت للمريض
                if (!empty($policy)) {
                    $csvPatients[$name]['policy'] = $policy;
                }
            }
        }
        fclose($handle);

        $totalUniquePatients = count($csvPatients);
        $this->info("تمت قراءة {$rowCount} سجل، واستخراج {$totalUniquePatients} مريض فريد.");

        // 4. تصنيف المرضى إلى: متطابقين، ومرضى جدد
        $matchedList = [];
        $newList = [];
        $differingCategories = [];

        foreach ($csvPatients as $name => $cData) {
            $norm = $cData['norm'];
            $matchedDbPatient = $dbExactLookup[$name] ?? ($dbNormLookup[$norm] ?? null);

            if ($matchedDbPatient) {
                $cData['db_patient'] = $matchedDbPatient;
                $matchedList[] = $cData;

                $dbCatName = trim($matchedDbPatient->category_name ?? '');
                $csvCatName = trim($cData['policy']);

                if ($dbCatName !== $csvCatName) {
                    $differingCategories[] = [
                        'name' => $name,
                        'patient_id' => $matchedDbPatient->patient_id,
                        'db_category' => $dbCatName ?: 'غير محددة',
                        'csv_category' => $csvCatName,
                        'target_cat_id' => $categories[$csvCatName] ?? null,
                    ];
                }
            } else {
                $cData['target_cat_id'] = $categories[$cData['policy']] ?? null;
                $newList[] = $cData;
            }
        }

        $this->table(
            ['المؤشر', 'العدد'],
            [
                ['إجمالي السجلات المفحوصة في الملف', number_format($rowCount)],
                ['إجمالي المرضى الفريدين في الملف', number_format($totalUniquePatients)],
                ['مرضى متطابقون (مسجلون مسبقاً في النظام)', number_format(count($matchedList))],
                ['مرضى تختلف فئاتهم عن النظام', number_format(count($differingCategories))],
                ['مرضى جدد جاهزون للإدخال', number_format(count($newList))],
            ]
        );

        if (!empty($differingCategories)) {
            $this->warn("الحالات التي تختلف فئاتها بين النظام والملف:");
            foreach ($differingCategories as $dc) {
                $this->line(" - المريض: {$dc['name']} (#{$dc['patient_id']}): النظام [{$dc['db_category']}] <---> الملف [{$dc['csv_category']}]");
            }
        }

        if ($isDryRun) {
            $this->info("\n[وضع المعاينة الوهمية مكتمل] لم يتم تعديل أو كتابة أي بيانات في قاعدة البيانات.");
            return 0;
        }

        // 5. التنفيذ الفعلي في قاعدة البيانات
        $this->info("\nجاري تنفيذ التحديث والاستيراد الفعلي...");

        // أ) تحديث فئات المرضى المتطابقين إذا طلب ذلك
        $updatedCategoriesCount = 0;
        if ($updateCategories && !empty($differingCategories)) {
            $this->line("تحديث فئات المرضى الـ " . count($differingCategories) . " المتطابقين...");
            foreach ($differingCategories as $dc) {
                if ($dc['target_cat_id']) {
                    DB::table('patients')
                        ->where('id', $dc['patient_id'])
                        ->update([
                            'health_insurance_category_id' => $dc['target_cat_id'],
                            'insurance_type' => 'hi',
                            'covered_by_insurance' => 1,
                            'updated_at' => now(),
                        ]);
                    $updatedCategoriesCount++;
                }
            }
            $this->info("تم تحديث فئة {$updatedCategoriesCount} مريض متطابق بنجاح.");
        }

        // ب) استيراد المرضى الجدد على دفعات Chunk داخل Transactions
        $newPatientsCount = count($newList);
        $this->line("بدء إدخال {$newPatientsCount} مريض جديد في النظام...");

        $progressBar = $this->output->createProgressBar($newPatientsCount);
        $progressBar->start();

        // معرف أول مدير نظام لنسب السجل إليه
        $adminUserId = DB::table('users')->where('role', 'admin')->value('id') ?: 1;
        $batchSize = 250;
        $chunks = array_chunk($newList, $batchSize);
        $insertedCount = 0;
        $defaultPassword = Hash::make('patient@hospital123');

        foreach ($chunks as $chunkIndex => $chunk) {
            DB::transaction(function () use ($chunk, &$insertedCount, $adminUserId, $defaultPassword, $progressBar) {
                $now = now();
                foreach ($chunk as $pData) {
                    $uniqueToken = bin2hex(random_bytes(4));
                    $email = 'hi_p_' . time() . '_' . $uniqueToken . '@hospital.local';

                    // 1. إنشاء حساب المستخدم
                    $userId = DB::table('users')->insertGetId([
                        'name' => $pData['name'],
                        'email' => $email,
                        'password' => $defaultPassword,
                        'role' => 'patient',
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    // 2. تحليل تاريخ الزيارة الأولى
                    $firstVisitDate = null;
                    if (!empty($pData['earliest_date'])) {
                        try {
                            $firstVisitDate = Carbon::createFromFormat('d/m/Y H:i', $pData['earliest_date'])->toDateString();
                        } catch (\Exception $e) {
                            $firstVisitDate = null;
                        }
                    }

                    // 3. إنشاء سجل المريض
                    DB::table('patients')->insert([
                        'user_id' => $userId,
                        'created_by' => $adminUserId,
                        'insurance_type' => 'hi',
                        'covered_by_insurance' => 1,
                        'health_insurance_category_id' => $pData['target_cat_id'],
                        'copay_percentage' => 15.00,
                        'first_visit_date' => $firstVisitDate ?: $now->toDateString(),
                        'notes' => 'مستورد من سجلات الضمان الصحي (مستشفى الكفاءات)',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $insertedCount++;
                    $progressBar->advance();
                }
            });
        }

        $progressBar->finish();
        $this->newLine(2);

        // 6. ملخص النجاح النهائي
        $totalPatientsNow = DB::table('patients')->count();
        $this->info("==========================================================");
        $this->info("✅ اكتمل استيراد مرضى الضمان الصحي بنجاح تام!");
        $this->info(" - مرضى جدد تم إدخالهم: " . number_format($insertedCount));
        $this->info(" - مرضى تم تحديث فئاتهم: " . number_format($updatedCategoriesCount));
        $this->info(" - إجمالي مرضى النظام الآن: " . number_format($totalPatientsNow) . " مريض.");
        $this->info("==========================================================");

        return 0;
    }

    /**
     * تسوية وتوحيد الأحرف العربية للمطابقة الدقيقة.
     */
    private function normalizeArabic(?string $text): string
    {
        if (empty($text)) {
            return '';
        }
        $text = trim($text);
        // إزالة التطويل والتشكيل
        $text = preg_replace('/[ـ\x{0640}]/u', '', $text);
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text);
        // توحيد الألف
        $text = preg_replace('/[إأآا]/u', 'ا', $text);
        // توحيد التاء المربوطة والهاء
        $text = preg_replace('/ة/u', 'ه', $text);
        // توحيد الياء والألف المقصورة
        $text = preg_replace('/ى/u', 'ي', $text);
        // توحيد المسافات
        $text = preg_replace('/\s+/u', ' ', $text);

        return mb_strtolower($text, 'UTF-8');
    }
}
