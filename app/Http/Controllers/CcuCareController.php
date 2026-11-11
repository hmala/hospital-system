<?php

namespace App\Http\Controllers;

use App\Models\CcuAttachment;
use App\Models\CcuConsultation;
use App\Models\CcuDoctorNote;
use App\Models\CcuMedicationSchedule;
use App\Models\CcuService;
use App\Models\IcuAdmission;
use App\Models\IcuVitalSign;
use App\Services\CcuBillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CcuCareController extends Controller
{
    /**
     * إعادة التوجيه مع الحفاظ على التبويب النشط وموضع التمرير
     */
    protected function redirectBackWithActiveState(?Request $request = null, ?string $defaultTab = null, ?string $fallbackUrl = null)
    {
        $req = $request ?: request();
        $tab = $req->input('_active_tab', $defaultTab);

        $redirect = $fallbackUrl ? redirect()->back(fallback: $fallbackUrl) : redirect()->back();
        if ($tab) {
            $redirect->with('active_tab', $tab);
        }

        return $redirect;
    }

    /**
     * 1. تدوين توجيه أو ملاحظة سريرية للطبيب الاستشاري
     */
    public function storeDoctorNote(Request $request, IcuAdmission $admission)
    {
        $validated = $request->validate([
            'note_text' => 'required|string|max:3000',
            'priority' => 'required|in:normal,urgent,stat',
            'is_pinned' => 'nullable|boolean',
            'doctor_id' => 'nullable|exists:doctors,id',
        ]);

        $user = Auth::user();
        $doctorId = $validated['doctor_id'] ?? ($user->doctor?->id ?? $admission->doctor_id);
        $isPinned = (bool) ($request->boolean('is_pinned') ?? false);

        CcuDoctorNote::create([
            'icu_admission_id' => $admission->id,
            'doctor_id' => $doctorId,
            'note_text' => $validated['note_text'],
            'priority' => $validated['priority'],
            'is_pinned' => $isPinned,
            'pinned_at' => $isPinned ? now() : null,
            'created_by' => Auth::id(),
        ]);

        return $this->redirectBackWithActiveState($request, 'doctorNotesTabPane', route('icu.show', $admission->id))
            ->with('success', 'تم تدوين وحفظ توجيه الطبيب السريري بنجاح.');
    }

    /**
     * تثبيت أو إلغاء تثبيت توجيه الطبيب في رأس الإضبارة
     */
    public function toggleDoctorNotePin(Request $request, CcuDoctorNote $note)
    {
        $newPinned = ! $note->is_pinned;
        $note->update([
            'is_pinned' => $newPinned,
            'pinned_at' => $newPinned ? now() : null,
        ]);

        $msg = $newPinned
            ? '📌 تم تثبيت التوجيه السريري بنجاح في رأس إضبارة المريض.'
            : 'تم إلغاء تثبيت التوجيه من رأس الملف.';

        return $this->redirectBackWithActiveState($request, 'doctorNotesTabPane', route('icu.show', $note->icu_admission_id))
            ->with('success', $msg);
    }

    /**
     * حذف توجيه الطبيب
     */
    public function destroyDoctorNote(Request $request, CcuDoctorNote $note)
    {
        $admissionId = $note->icu_admission_id;
        $note->delete();

        return $this->redirectBackWithActiveState($request, 'doctorNotesTabPane', route('icu.show', $admissionId))
            ->with('success', 'تم حذف التوجيه السريري بنجاح.');
    }

    /**
     * 2. اعتماد جدول إعطاء وجرعات الأدوية ومضخات التسريب الوريدي (MAR)
     */
    public function storeMedicationSchedule(Request $request, IcuAdmission $admission)
    {
        $validated = $request->validate([
            'type' => 'required|in:medication,infusion,feeding,other',
            'title' => 'required|string|max:255',
            'dose_or_amount' => 'nullable|string|max:100',
            'route' => 'required|string|max:100',
            'interval_hours' => 'required|string|max:50',
            'start_time' => 'nullable|date',
            'infusion_rate' => 'nullable|string|max:100',
            'instructions' => 'nullable|string|max:1000',
            'doctor_id' => 'nullable|exists:doctors,id',
        ]);

        $user = Auth::user();
        $doctorId = $validated['doctor_id'] ?? ($user->doctor?->id ?? $admission->doctor_id);
        $startTime = ! empty($validated['start_time']) ? Carbon::parse($validated['start_time']) : now();

        $nextDue = $startTime;
        if (is_numeric($validated['interval_hours']) && (int) $validated['interval_hours'] > 0) {
            $hours = (int) $validated['interval_hours'];
            $nextDue = $startTime->lte(now()) ? $startTime->copy()->addHours($hours) : $startTime;
        }

        CcuMedicationSchedule::create([
            'icu_admission_id' => $admission->id,
            'doctor_id' => $doctorId,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'dose_or_amount' => $validated['dose_or_amount'] ?? null,
            'route' => $validated['route'],
            'interval_hours' => $validated['interval_hours'],
            'start_time' => $startTime,
            'next_due_at' => $nextDue,
            'infusion_rate' => $validated['infusion_rate'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return $this->redirectBackWithActiveState($request, 'medicationTabPane', route('icu.show', $admission->id))
            ->with('success', 'تم اعتماد وإدراج جدول الدواء ومضخة التسريب بنجاح.');
    }

    /**
     * توثيق إعطاء الجرعة وتحديث الموعد القادم تلقائياً
     */
    public function administerMedicationQuick(Request $request, CcuMedicationSchedule $schedule)
    {
        $now = now();
        $nextDue = null;

        if (is_numeric($schedule->interval_hours) && (int) $schedule->interval_hours > 0) {
            $nextDue = $now->copy()->addHours((int) $schedule->interval_hours);
        } else {
            $nextDue = $schedule->next_due_at;
        }

        $schedule->update([
            'last_administered_at' => $now,
            'last_administered_by' => Auth::id(),
            'next_due_at' => $nextDue,
        ]);

        return $this->redirectBackWithActiveState($request, 'medicationTabPane', route('icu.show', $schedule->icu_admission_id))
            ->with('success', '✅ تم توثيق إعطاء الجرعة بنجاح وتحديث موعد الجرعة القادمة تلقائياً.');
    }

    /**
     * تفعيل أو إيقاف مؤقت لجدول الدواء
     */
    public function toggleMedicationSchedule(Request $request, CcuMedicationSchedule $schedule)
    {
        $newActive = ! $schedule->is_active;
        $schedule->update(['is_active' => $newActive]);

        $msg = $newActive ? 'تم تفعيل التوصية الدوائية.' : 'تم إيقاف التوصية الدوائية مؤقتاً.';

        return $this->redirectBackWithActiveState($request, 'medicationTabPane', route('icu.show', $schedule->icu_admission_id))
            ->with('success', $msg);
    }

    /**
     * حذف جدول الدواء
     */
    public function destroyMedicationSchedule(Request $request, CcuMedicationSchedule $schedule)
    {
        $admissionId = $schedule->icu_admission_id;
        $schedule->delete();

        return $this->redirectBackWithActiveState($request, 'medicationTabPane', route('icu.show', $admissionId))
            ->with('success', 'تم حذف جدول الدواء بنجاح.');
    }

    /**
     * 3. طلب استشارة طبية تخصصية (داخلي / خارجي)
     */
    public function storeConsultation(Request $request, IcuAdmission $admission)
    {
        $validated = $request->validate([
            'consultation_type' => 'required|in:internal,external',
            'specialty' => 'required|string|max:255',
            'doctor_id' => 'nullable|exists:doctors,id',
            'external_doctor_name' => 'nullable|string|max:255',
            'reason' => 'required|string|max:3000',
            'priority' => 'required|in:normal,urgent,stat',
            'fee' => 'nullable|numeric|min:0',
        ]);

        if ($validated['consultation_type'] === 'internal' && empty($validated['doctor_id'])) {
            return $this->redirectBackWithActiveState($request, 'consultationsTabPane', route('icu.show', $admission->id))
                ->with('error', 'يرجى اختيار الطبيب الاستشاري الداخلي من القائمة.');
        }

        if ($validated['consultation_type'] === 'external' && empty($validated['external_doctor_name'])) {
            return $this->redirectBackWithActiveState($request, 'consultationsTabPane', route('icu.show', $admission->id))
                ->with('error', 'يرجى كتابة اسم الطبيب الاستشاري الخارجي.');
        }

        $fee = (float) ($validated['fee'] ?? 0);

        CcuConsultation::create([
            'icu_admission_id' => $admission->id,
            'consultation_type' => $validated['consultation_type'],
            'specialty' => $validated['specialty'],
            'doctor_id' => $validated['consultation_type'] === 'internal' ? $validated['doctor_id'] : null,
            'external_doctor_name' => $validated['consultation_type'] === 'external' ? $validated['external_doctor_name'] : null,
            'reason' => $validated['reason'],
            'priority' => $validated['priority'],
            'fee' => $fee,
            'original_fee' => $fee,
            'billing_mode' => 'auto',
            'status' => 'pending',
            'requested_by' => Auth::id(),
        ]);

        app(CcuBillingService::class)->calculateStayDetails($admission);

        return $this->redirectBackWithActiveState($request, 'consultationsTabPane', route('icu.show', $admission->id))
            ->with('success', 'تم إصدار طلب الاستشارة الطبية بنجاح.');
    }

    /**
     * توثيق رد وتوصيات الطبيب الاستشاري
     */
    public function respondConsultation(Request $request, CcuConsultation $consultation)
    {
        $validated = $request->validate([
            'response' => 'required|string|max:3000',
        ]);

        $consultation->update([
            'response' => $validated['response'],
            'status' => 'completed',
            'responded_by' => Auth::id(),
            'responded_at' => now(),
        ]);

        return $this->redirectBackWithActiveState($request, 'consultationsTabPane', route('icu.show', $consultation->icu_admission_id))
            ->with('success', 'تم اعتماد وتوثيق رد وتوصيات الاستشاري بنجاح.');
    }

    /**
     * 4. رفع وتخزين المرفقات والتقارير الطبية وتخطيط القلب (ECG)
     */
    public function storeAttachment(Request $request, IcuAdmission $admission)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'attachment_type' => 'required|in:ecg,report,imaging,lab_external,referral,other',
            'file' => 'required|file|max:25600', // max 25MB
            'notes' => 'nullable|string|max:1000',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        $path = $file->store('ccu_attachments/'.$admission->id, 'public');

        CcuAttachment::create([
            'icu_admission_id' => $admission->id,
            'title' => $validated['title'],
            'attachment_type' => $validated['attachment_type'],
            'file_path' => $path,
            'file_name' => $originalName,
            'file_size' => $fileSize,
            'notes' => $validated['notes'] ?? null,
            'uploaded_by' => Auth::id(),
        ]);

        return $this->redirectBackWithActiveState($request, 'attachmentsTabPane', route('icu.show', $admission->id))
            ->with('success', 'تم رفع المرفق الطبي وحفظه في أرشيف المريض بنجاح.');
    }

    /**
     * حذف مرفق طبي
     */
    public function destroyAttachment(Request $request, CcuAttachment $attachment)
    {
        $admissionId = $attachment->icu_admission_id;

        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return $this->redirectBackWithActiveState($request, 'attachmentsTabPane', route('icu.show', $admissionId))
            ->with('success', 'تم حذف المرفق الطبي من الأرشيف.');
    }

    /**
     * 5. تحديد أو تحديث التشخيص السريري الأولي ورمز ICD-10 ومقياس الوعي GCS داخل طبلة الطبيب
     */
    public function updateInitialDiagnosis(Request $request, IcuAdmission $admission)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'doctor', 'icu_doctor']) && ! $user->can('edit ccu diagnosis')) {
            abort(403, 'تحديد التشخيص الأولي ومقياس الوعي من صلاحيات الطبيب المختص فقط.');
        }

        $validated = $request->validate([
            'primary_diagnosis' => 'required|string|max:500',
            'icd10_code' => 'nullable|string|max:50',
            'initial_gcs' => 'nullable|integer|min:3|max:15',
        ]);

        $admission->update([
            'primary_diagnosis' => $validated['primary_diagnosis'],
            'icd10_code' => $validated['icd10_code'] ?? null,
            'initial_gcs' => $validated['initial_gcs'] ?? null,
        ]);

        // تدوين / تحديث التقييم الأولي لمقياس الوعي GCS إن تم إدخاله
        if (! empty($validated['initial_gcs'])) {
            $initialVital = IcuVitalSign::where('icu_admission_id', $admission->id)
                ->where('notes', 'التقييم الأولي لمستوى الوعي (تحديد الطبيب بالطبلة)')
                ->first();

            if ($initialVital) {
                $initialVital->update([
                    'gcs' => $validated['initial_gcs'],
                    'recorded_by' => Auth::id(),
                ]);
            } else {
                IcuVitalSign::create([
                    'icu_admission_id' => $admission->id,
                    'recorded_at' => now(),
                    'recorded_by' => Auth::id(),
                    'gcs' => $validated['initial_gcs'],
                    'notes' => 'التقييم الأولي لمستوى الوعي (تحديد الطبيب بالطبلة)',
                ]);
            }
        }

        return $this->redirectBackWithActiveState($request, $request->input('_active_tab'), route('icu.show', $admission->id))
            ->with('success', 'تم اعتماد وتوثيق التشخيص الأولي والتصنيف الدولي (ICD-10) في طبلة المريض.');
    }

    /**
     * 6. تبديل باقة الرعاية اليومية في طبلة المريض (فنتليتر <-> مراقبة أكسجين <-> CCU <-> سرير منفصل)
     */
    public function switchService(Request $request, IcuAdmission $admission)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'doctor', 'icu_doctor', 'cashier', 'accountant']) && ! $user->can('switch ccu services')) {
            abort(403, 'غير مصرح لك بتبديل باقة الرعاية.');
        }

        $validated = $request->validate([
            'ccu_service_id' => 'required|exists:ccu_services,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $newService = CcuService::findOrFail($validated['ccu_service_id']);
        app(CcuBillingService::class)->switchService(
            $admission,
            $newService,
            $validated['reason'] ?? 'تبديل الباقة من طبلة العناية المركزة',
            Auth::id()
        );

        return $this->redirectBackWithActiveState($request, $request->input('_active_tab'), route('icu.show', $admission->id))
            ->with('success', "تم تحويل باقة المريض إلى ({$newService->name}) مع منع تكرار أجرة اليوم الفندقي.");
    }

    /**
     * 7. إضافة خدمة مخصصة منفصلة لحساب المريض (مثلاً لنظام الكاش المخصص أو أجهزة إضافية)
     */
    public function addCustomService(Request $request, IcuAdmission $admission)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'doctor', 'icu_doctor', 'cashier', 'accountant']) && ! $user->can('switch ccu services')) {
            abort(403, 'غير مصرح لك بإضافة خدمات مالية إضافية.');
        }

        $validated = $request->validate([
            'ccu_service_id' => 'nullable|exists:ccu_services,id',
            'custom_name' => 'required_without:ccu_service_id|nullable|string|max:255',
            'unit_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:1|max:365',
            'notes' => 'nullable|string|max:1000',
        ]);

        $service = ! empty($validated['ccu_service_id']) ? CcuService::find($validated['ccu_service_id']) : null;
        $isInsured = (bool) $admission->is_insured;
        $insType = $isInsured ? ($admission->insurance_type ?: 'hi') : 'none';

        $unitPrice = array_key_exists('unit_price', $validated) && $validated['unit_price'] !== null
            ? (float) $validated['unit_price']
            : ($service ? $service->getServiceOnlyFee($insType) : 0.0);

        $serviceName = ! empty($validated['custom_name'])
            ? $validated['custom_name']
            : ($service?->name ?? 'خدمة عناية مركزة مخصصة');

        app(CcuBillingService::class)->addAddonService(
            $admission,
            $service,
            $serviceName,
            $unitPrice,
            (int) $validated['quantity'],
            $validated['notes'] ?? null,
            Auth::id()
        );

        return $this->redirectBackWithActiveState($request, $request->input('_active_tab'), route('icu.show', $admission->id))
            ->with('success', "تمت إضافة الخدمة المخصصة ({$serviceName}) لحساب المريض بنجاح.");
    }
}
