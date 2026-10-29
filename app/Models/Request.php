<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = [
        'visit_id',
        'type',
        'subtype',
        'description',
        'details',
        'status',
        'payment_status',
        'insurance_type',
        'payment_id',
        'result',
    ];

    protected $casts = [
        'details' => 'array',
        'result' => 'array',
    ];

    public function getTotalAmountAttribute()
    {
        $details = $this->details;
        if (is_string($details)) {
            $details = json_decode($details, true) ?: [];
        }

        if (! is_array($details)) {
            return null;
        }

        if (isset($details['total_amount'])) {
            return (float) $details['total_amount'];
        }

        if (isset($details['amount'])) {
            return (float) $details['amount'];
        }

        switch ($this->type) {
            case 'lab':
                if (! empty($details['lab_test_ids']) && is_array($details['lab_test_ids'])) {
                    return LabTest::whereIn('id', $details['lab_test_ids'])->where('is_active', true)->sum('price');
                }

                if (! empty($details['tests']) && is_array($details['tests'])) {
                    $total = 0;
                    $seenTests = [];
                    foreach ($details['tests'] as $testName) {
                        $normName = mb_strtolower(trim((string) $testName));
                        if (in_array($normName, $seenTests)) {
                            continue;
                        }
                        $seenTests[] = $normName;

                        $test = LabTest::where('is_active', true)
                            ->where(function ($q) use ($testName) {
                                $q->where('name', $testName)->orWhere('code', $testName);
                            })
                            ->first();
                        if ($test) {
                            $total += $test->price ?? 0;
                        }
                    }

                    return $total;
                }

                if (! empty($details['package_id'])) {
                    $package = Package::find($details['package_id']);
                    if ($package) {
                        return (float) $package->price;
                    }
                }

                return null;

            case 'radiology':
                $radIds = [];
                if (! empty($details['radiology_type_ids'])) {
                    $radIds = (array) $details['radiology_type_ids'];
                } elseif (! empty($details['radiology_types'])) {
                    $radIds = (array) $details['radiology_types'];
                } elseif (! empty($details['radiology_type_id'])) {
                    $radIds = (array) $details['radiology_type_id'];
                } elseif (! empty($details['ultrasound_type_id'])) {
                    $radIds = (array) $details['ultrasound_type_id'];
                }

                if (! empty($radIds)) {
                    return (float) RadiologyType::whereIn('id', $radIds)->sum('base_price');
                }

                return null;

            case 'blood_bank':
                return $this->bloodBankRequest ? (float) $this->bloodBankRequest->total_amount : null;

            case 'emergency':
                if (! empty($details['emergency_priority'])) {
                    $fees = [
                        'critical' => 50000,
                        'urgent' => 35000,
                        'semi_urgent' => 25000,
                        'non_urgent' => 15000,
                    ];

                    return $fees[$details['emergency_priority']] ?? 25000;
                }

                return null;

            case 'pharmacy':
                if (! empty($details['tests']) && is_array($details['tests'])) {
                    $total = 0;
                    foreach ($details['tests'] as $itemName) {
                        $test = LabTest::where('name', $itemName)
                            ->orWhere('code', $itemName)
                            ->first();
                        if ($test) {
                            $total += $test->price ?? 0;
                        }
                    }

                    return $total;
                }

                return null;
        }

        return null;
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function bloodBankRequest()
    {
        return $this->hasOne(\App\Models\BloodBankRequest::class);
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\Payment::class, 'request_id');
    }

    public function payment()
    {
        return $this->belongsTo(\App\Models\Payment::class, 'payment_id');
    }

    public function labResults()
    {
        return $this->hasMany(\App\Models\LabResult::class, 'request_id');
    }

    /**
     * جلب خريطة آخر 3 نتائج سابقة لكل فحص مخبري لنفس المريض (Cumulative Previous Results)
     *
     * @return array<string, array<int, array{request_id: int, value: string, unit: string, status: string, date: string, date_short: string}>>
     */
    public function getPreviousLabResultsMap(): array
    {
        $patientId = $this->visit?->patient_id ?? $this->patient_id;
        if (! $patientId) {
            return [];
        }

        $resultsMap = [];

        // 1. استخراج النتائج السابقة من جدول lab_results لزيارات نفس المريض قبل الطلب الحالي
        $prevResults = \App\Models\LabResult::whereHas('request.visit', function ($q) use ($patientId) {
            $q->where('patient_id', $patientId);
        })
            ->where('request_id', '!=', $this->id)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->where('value', '!=', '—')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get(['id', 'request_id', 'test_name', 'value', 'unit', 'status', 'created_at']);

        foreach ($prevResults as $row) {
            $key = mb_strtolower(trim($row->test_name));
            if (! isset($resultsMap[$key])) {
                $resultsMap[$key] = [];
            }
            if (count($resultsMap[$key]) < 3) {
                $existingReqIds = array_column($resultsMap[$key], 'request_id');
                if (! in_array($row->request_id, $existingReqIds)) {
                    $resultsMap[$key][] = [
                        'request_id' => $row->request_id,
                        'value' => (string) $row->value,
                        'unit' => (string) ($row->unit ?? ''),
                        'status' => (string) ($row->status ?? 'normal'),
                        'date' => $row->created_at ? $row->created_at->format('d-M-Y') : '',
                        'date_short' => $row->created_at ? $row->created_at->format('d/m/y') : '',
                    ];
                }
            }
        }

        // 2. فحص الطلبات السابقة المخزنة بصيغة JSON القديمة في requests.result (إن وجدت)
        $prevJsonRequests = static::whereHas('visit', function ($q) use ($patientId) {
            $q->where('patient_id', $patientId);
        })
            ->where('id', '!=', $this->id)
            ->where('type', 'lab')
            ->whereNotNull('result')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get(['id', 'result', 'created_at']);

        foreach ($prevJsonRequests as $pReq) {
            $pResData = is_string($pReq->result) ? json_decode($pReq->result, true) : $pReq->result;
            $pTestResults = is_array($pResData) ? ($pResData['test_results'] ?? []) : [];
            foreach ($pTestResults as $tName => $tVal) {
                $tKey = mb_strtolower(trim((string) $tName));
                if (! isset($resultsMap[$tKey])) {
                    $resultsMap[$tKey] = [];
                }
                if (count($resultsMap[$tKey]) < 3) {
                    $valStr = is_array($tVal) ? ($tVal['value'] ?? '') : (string) $tVal;
                    if ($valStr !== '' && $valStr !== '—') {
                        $existingReqIds = array_column($resultsMap[$tKey], 'request_id');
                        if (! in_array($pReq->id, $existingReqIds)) {
                            $resultsMap[$tKey][] = [
                                'request_id' => $pReq->id,
                                'value' => $valStr,
                                'unit' => is_array($tVal) ? ($tVal['unit'] ?? '') : '',
                                'status' => is_array($tVal) ? ($tVal['status'] ?? 'normal') : 'normal',
                                'date' => $pReq->created_at ? $pReq->created_at->format('d-M-Y') : '',
                                'date_short' => $pReq->created_at ? $pReq->created_at->format('d/m/y') : '',
                            ];
                        }
                    }
                }
            }
        }

        return $resultsMap;
    }

    /**
     * التحقق مما إذا كان الطلب طلباً خارجياً / مباشراً من الاستعلامات أو الصندوق
     */
    public function isDirectExternal(): bool
    {
        $details = $this->details;
        if (is_string($details)) {
            $details = json_decode($details, true) ?: [];
        }

        // 1. إذا كان معلماً صراحة أنه مباشر أو صادر من الاستعلامات
        if (! empty($details['created_at_inquiry']) || ! empty($details['direct_lab_visit']) || ! empty($details['direct_radiology_visit'])) {
            return true;
        }

        // 2. إذا كانت نوع الزيارة مخصصة للفحوصات المباشرة الخارجية
        if ($this->visit && in_array($this->visit->visit_type, ['lab', 'radiology', 'external', 'blood_bank'], true)) {
            return true;
        }

        // 3. إذا لم تكن هناك زيارة أو لا يوجد طبيب استشاري مرتبط بالزيارة والموعد
        if (! $this->visit || (empty($this->visit->doctor_id) && empty($this->visit->appointment_id) && empty($this->visit->appointment?->doctor_id))) {
            return true;
        }

        return false;
    }

    public function getIsDirectExternalAttribute(): bool
    {
        return $this->isDirectExternal();
    }

    // Helper methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function getTypeTextAttribute()
    {
        if ($this->type === 'lab' && isset($this->details['blood_bank']) && $this->details['blood_bank']) {
            return 'مصرف الدم';
        }

        return match ($this->type) {
            'lab' => 'مختبر',
            'radiology' => 'أشعة',
            'pharmacy' => 'صيدلية',
            'nursing' => 'خدمات تمريضية',
            'emergency' => 'طوارئ',
            'blood_bank' => 'مصرف الدم',
            default => $this->type
        };
    }

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            'pending' => 'في الانتظار',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            default => $this->status
        };
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary'
        };
    }

    public function getRadiologyNamesAttribute()
    {
        if ($this->type !== 'radiology') {
            return [];
        }
        $details = $this->details;
        if (is_string($details)) {
            $details = json_decode($details, true) ?: [];
        }
        $typeIds = $details['radiology_types'] ?? $details['radiology_type_ids'] ?? [];
        if (! empty($typeIds) && is_array($typeIds)) {
            return \App\Models\RadiologyType::whereIn('id', $typeIds)->pluck('name')->toArray();
        }
        if (! empty($this->description)) {
            return [str_replace('طلب أشعة: ', '', $this->description)];
        }

        return [];
    }

    public function getDoctorNotesAttribute()
    {
        $details = $this->details;
        if (is_string($details)) {
            $details = json_decode($details, true) ?: [];
        }

        return $details['doctor_notes'] ?? $details['notes'] ?? (! empty($details['description']) && ! str_starts_with($details['description'], 'طلب ') ? $details['description'] : null);
    }

    /**
     * استخراج وتنسيق رقم هاتف المريض بالصيغة الدولية المعتمدة للواتساب
     */
    public function getPatientPhone(): ?string
    {
        $patient = $this->visit?->patient;
        $patientUser = $patient?->user;
        $details = is_string($this->details) ? json_decode($this->details, true) : $this->details;

        $phone = $patientUser?->phone
            ?: ($patient?->phone
            ?? ($details['patient_phone']
            ?? ($this->visit?->emergency?->emergencyPatient?->phone ?? null)));

        if (! $phone) {
            return null;
        }

        // تنظيف الرقم من أي مسافات أو رموز
        $clean = preg_replace('/[^0-9]/', '', (string) $phone);
        if (empty($clean)) {
            return null;
        }

        // إذا كان بالصيغة العراقية المحلية: 07XXXXXXXXX (11 رقماً تبدأ بـ 07)
        if (str_starts_with($clean, '07') && strlen($clean) === 11) {
            return '964'.substr($clean, 1);
        }

        // إذا كان يبدأ بـ 7 ومكون من 10 أرقام (بدون الصفر الأولي)
        if (str_starts_with($clean, '7') && strlen($clean) === 10) {
            return '964'.$clean;
        }

        // إذا كان يبدأ بـ 00964
        if (str_starts_with($clean, '00964')) {
            return substr($clean, 2);
        }

        return $clean;
    }

    /**
     * توليد توكن تشفيري آمن للرابط العام المخصص للمريض
     */
    public function getPublicReportToken(): string
    {
        return hash_hmac('sha256', $this->id.'|'.($this->created_at?->timestamp ?? 0), config('app.key') ?: 'kafaat_hospital_secret_key');
    }

    /**
     * رابط التقرير الطبي العام الآمن للمريض دون الحاجة لتسجيل دخول
     */
    public function getPublicReportUrl(): string
    {
        return route('lab.public-report', [
            'request' => $this->id,
            'token' => $this->getPublicReportToken(),
        ]);
    }

    /**
     * توليد رابط مشاركة النتيجة عبر واتساب برسالة رسمية متكاملة
     */
    public function getWhatsAppShareUrl(): ?string
    {
        $phone = $this->getPatientPhone();
        $patientName = $this->visit?->patient?->user?->name
            ?? ($this->visit?->patient?->name ?? 'المريض');
        $reportUrl = $this->getPublicReportUrl();
        $details = is_string($this->details) ? json_decode($this->details, true) : ($this->details ?? []);
        $hasAttachments = ! empty($details['attachment']) || ! empty($details['test_attachments']);

        $attachmentNote = $hasAttachments
            ? "📎 ملاحظة: يحتوي تقريركم على مرفقات وتقارير أجهزة طبية يمكنك استعراضها وتحميلها مباشرة من الرابط أعلاه.\n\n"
            : '';

        $text = "السلام عليكم ورحمة الله وبركاته\n"
              ."الأخ/الأخت: {$patientName} المحترم،\n\n"
              ."نود إعلامكم بأن نتائج الفحوصات المخبرية الخاصة بكم لدى مستشفى الكفاءات الأهلي للطلب رقم (#{$this->id}) جاهزة ومكتملة.\n\n"
              ."📄 يمكنكم الاطلاع على التقرير الطبي والنتائج المعتمدة مباشرة عبر الرابط التالي:\n"
              ."{$reportUrl}\n\n"
              .$attachmentNote
              ."مع تمنياتنا لكم بالصحة والعافية.\n"
              .'مختبر مستشفى الكفاءات الأهلي 🏥';

        $encodedText = rawurlencode($text);

        if ($phone) {
            return "https://wa.me/{$phone}?text={$encodedText}";
        }

        return "https://api.whatsapp.com/send?text={$encodedText}";
    }

    /**
     * Ensure visit status updates when all related requests complete (only for direct/standalone requests without a doctor consultation).
     */
    protected static function booted()
    {
        static::saved(function ($request) {
            if ($request->isCompleted() && $request->visit) {
                // زيارات الطبيب الاستشارية لا تكتمل تلقائياً من قبل المختبر أو الأشعة
                // لأن الطبيب هو صاحب القرار في مراجعة النتائج مع المريض وإنهاء الزيارة
                $isDoctorVisit = ! empty($request->visit->doctor_id) || ! empty($request->visit->appointment_id) || $request->visit->visit_type === 'checkup';

                if (! $isDoctorVisit) {
                    $remaining = $request->visit->requests()
                        ->where('status', '!=', 'completed')
                        ->count();

                    if ($remaining === 0 && $request->visit->status !== 'completed') {
                        $request->visit->status = 'completed';
                        $request->visit->save();
                    }
                }
            }
        });
    }
}
