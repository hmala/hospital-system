<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'request_id',
        'emergency_id',
        'surgery_id',
        'patient_id',
        'cashier_id',
        'receipt_number',
        'amount',
        'total_amount',
        'patient_share',
        'insurance_share',
        'insurance_type',
        'copay_percentage',
        'insurance_card_no',
        'claim_status',
        'payment_method',
        'payment_type',
        'description',
        'notes',
        'is_inclusive',
        'paid_at',
        'voided_at',
        'voided_by',
        'void_type',
        'void_reason',
        'original_receipt_number',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'patient_share' => 'decimal:2',
        'insurance_share' => 'decimal:2',
        'copay_percentage' => 'decimal:2',
        'is_inclusive' => 'boolean',
    ];

    // طرق الدفع المتاحة
    const PAYMENT_METHODS = [
        'pending' => 'معلق',
        'cash' => 'نقدي',
        'card' => 'بطاقة',
        'insurance' => 'تأمين',
    ];

    // أنواع الدفع
    const PAYMENT_TYPES = [
        'appointment' => 'العيادات الاستشارية (كشفية)',
        'lab' => 'المختبر والتحاليل',
        'radiology' => 'الأشعة والتصوير',
        'pharmacy' => 'الصيدلية والعلاج',
        'surgery' => 'العمليات الجراحية',
        'emergency' => 'طوارئ وإسعاف',
        'incubator' => 'الخدج والحاضنات (NICU)',
        'other' => 'أخرى',
    ];

    /**
     * العلاقة مع المريض
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * العلاقة مع الموعد
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * العلاقة مع الطلب الطبي الرئيسي
     */
    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    /**
     * كافة الطلبات الطبية المرتبطة بهذا الإيصال (في حالة الدفع الموحد للزيارة)
     */
    public function requests()
    {
        return $this->hasMany(Request::class, 'payment_id');
    }

    /**
     * العلاقة مع حالة الطوارئ
     */
    public function emergency()
    {
        return $this->belongsTo(Emergency::class);
    }

    /**
     * العلاقة مع العملية الجراحية
     */
    public function surgery()
    {
        return $this->belongsTo(Surgery::class);
    }

    /**
     * العلاقة مع الكاشير (المستخدم)
     */
    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * العلاقة مع إيراد الاستشارة
     */
    public function consultationRevenue()
    {
        return $this->hasOne(ConsultationRevenue::class);
    }

    /**
     * توليد رقم إيصال فريد
     */
    public static function generateReceiptNumber()
    {
        $date = Carbon::now()->format('Ymd');

        // البحث عن آخر رقم إيصال بنفس التاريخ
        $lastPayment = self::where('receipt_number', 'like', 'REC-'.$date.'-%')
            ->orderBy('receipt_number', 'desc')
            ->first();

        if ($lastPayment && $lastPayment->receipt_number) {
            $sequence = (int) substr($lastPayment->receipt_number, -4) + 1;
        } else {
            $sequence = 1;
        }

        // التحقق من عدم وجود الرقم مسبقاً (حماية إضافية)
        do {
            $receiptNumber = 'REC-'.$date.'-'.str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $exists = self::where('receipt_number', $receiptNumber)->exists();
            if ($exists) {
                $sequence++;
            }
        } while ($exists);

        return $receiptNumber;
    }

    /**
     * الحصول على اسم طريقة الدفع بالعربية
     */
    public function getPaymentMethodNameAttribute()
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * الحصول على اسم نوع الدفع بالعربية
     */
    public function getPaymentTypeNameAttribute()
    {
        return self::PAYMENT_TYPES[$this->payment_type] ?? $this->payment_type;
    }

    /**
     * الحصول على اسم نوع الضمان / التأمين بالعربية
     */
    public function getInsuranceTypeLabelAttribute(): string
    {
        $type = $this->insurance_type ?: ($this->patient?->insurance_type ?? 'hi');

        return match ($type) {
            'hi' => 'الضمان الصحي',
            'moi' => 'قوى الأمن الداخلي',
            'syndicate' => 'نقابات وتعاقدات',
            'private' => 'تأمين خاص',
            'none' => 'بدون ضمان',
            default => $type ?: 'الضمان الصحي',
        };
    }

    /**
     * اسم الطبيب المعالج / الاستشاري المرتبط بالإيصال
     */
    public function getAttendingDoctorNameAttribute(): ?string
    {
        $name = null;

        // 1. من خلال الموعد / الكشفية الاستشارية
        if ($this->appointment && $this->appointment->doctor && $this->appointment->doctor->user) {
            $name = $this->appointment->doctor->user->name;
        }
        // 2. من خلال الطلب المباشر والزيارة
        elseif ($this->request && $this->request->visit && $this->request->visit->doctor && $this->request->visit->doctor->user) {
            $name = $this->request->visit->doctor->user->name;
        }
        // 3. من خلال الطلبات المتعددة المرتبطة بالإيصال
        elseif ($this->relationLoaded('requests') && $this->requests->isNotEmpty()) {
            foreach ($this->requests as $req) {
                if ($req->visit && $req->visit->doctor && $req->visit->doctor->user) {
                    $name = $req->visit->doctor->user->name;
                    break;
                }
            }
        }
        // 4. من خلال الطوارئ
        elseif ($this->emergency && $this->emergency->doctor && $this->emergency->doctor->user) {
            $name = $this->emergency->doctor->user->name;
        }
        // 5. من خلال العمليات الجراحية
        elseif ($this->surgery && $this->surgery->doctor && $this->surgery->doctor->user) {
            $name = $this->surgery->doctor->user->name;
        }

        if ($name) {
            $cleaned = preg_replace('/^(د\.\s*)+/u', '', trim($name));

            return 'د. '.$cleaned;
        }

        return null;
    }

    /**
     * الوصف التفصيلي للخدمات المقدمة (كشوفات، تحاليل، أشعة، طوارئ، عمليات)
     */
    public function getServiceDetailsTextAttribute(): string
    {
        $items = [];

        // 1. كشفية استشارية
        if ($this->payment_type === 'appointment') {
            $dept = $this->appointment?->department?->name ?? 'عيادة استشارية';

            return "كشفية: {$dept}";
        }

        // 2. الطلبات الطبية (مختبر، أشعة، مصرف دم)
        $requests = collect([$this->request])->filter();
        if ($this->relationLoaded('requests') && $this->requests->isNotEmpty()) {
            $requests = $requests->concat($this->requests)->unique('id');
        }

        if ($requests->isNotEmpty()) {
            foreach ($requests as $req) {
                if ($req->type === 'lab') {
                    $details = is_string($req->details) ? json_decode($req->details, true) : $req->details;
                    if (! empty($details['tests']) && is_array($details['tests'])) {
                        $count = count($details['tests']);
                        $slice = array_slice($details['tests'], 0, 4);
                        $items[] = 'تحاليل: '.implode(', ', $slice).($count > 4 ? ' (+ '.($count - 4).' أخرى)' : '');
                    } elseif (! empty($req->description) && ! str_starts_with($req->description, 'دفع رسوم')) {
                        $items[] = $req->description;
                    }
                } elseif ($req->type === 'radiology') {
                    $radNames = $req->radiology_names;
                    if (! empty($radNames)) {
                        $items[] = 'أشعة: '.implode(', ', $radNames);
                    } elseif (! empty($req->description)) {
                        $items[] = $req->description;
                    }
                }
            }
        }

        // 3. الطوارئ
        if ($this->payment_type === 'emergency' && $this->emergency) {
            $prio = $this->emergency->priority_level ? "أولوية {$this->emergency->priority_level}" : '';
            $comp = $this->emergency->chief_complaint ?: 'خدمات طوارئ';
            $items[] = trim("طوارئ: {$comp} ".($prio ? "({$prio})" : ''));
        }

        // 4. العمليات الجراحية
        if ($this->payment_type === 'surgery' && $this->surgery) {
            $proc = $this->surgery->procedure_name ?: 'عملية جراحية';
            $items[] = "عملية: {$proc}";
        }

        if (empty($items)) {
            return $this->description ?: ($this->payment_type_name ?? 'خدمة طبية');
        }

        return implode(' | ', $items);
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function voidLogs()
    {
        return $this->hasMany(PaymentVoidLog::class, 'payment_id');
    }

    public function isVoided(): bool
    {
        return ! is_null($this->voided_at);
    }
}
