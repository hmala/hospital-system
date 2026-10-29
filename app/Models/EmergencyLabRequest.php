<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyLabRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_id',
        'patient_id',
        'payment_id',
        'payment_status',
        'status',
        'priority',
        'notes',
        'details',
        'performed_by',
        'verified_by',
        'requested_at',
        'completed_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
        'details' => 'array',
    ];

    // العلاقات
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function emergency()
    {
        return $this->belongsTo(Emergency::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function performedByUser()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function verifiedByUser()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function labTests()
    {
        return $this->belongsToMany(LabTest::class, 'emergency_lab_request_tests', 'emergency_lab_request_id', 'lab_test_id')
            ->withPivot(['result', 'image_path'])
            ->withTimestamps();
    }

    public function requestTests()
    {
        return $this->hasMany(EmergencyLabRequestTest::class);
    }

    public function getPatientPhone(): ?string
    {
        $phone = $this->patient?->phone ?? $this->patient?->user?->phone;
        if (! $phone) {
            return null;
        }

        $clean = preg_replace('/[^0-9]/', '', (string) $phone);
        if (str_starts_with($clean, '07')) {
            $clean = '964'.substr($clean, 1);
        } elseif (str_starts_with($clean, '7')) {
            $clean = '964'.$clean;
        }

        return $clean;
    }

    public function getWhatsAppShareUrl(): ?string
    {
        $phone = $this->getPatientPhone();
        $patientName = $this->patient?->user?->name ?? ($this->patient?->name ?? 'المريض');
        $reportUrl = route('staff.emergency-lab.print', $this);

        $text = "السلام عليكم ورحمة الله وبركاته\n"
              ."الأخ/الأخت: {$patientName} المحترم،\n\n"
              ."نود إعلامكم بأن نتائج تحاليل الطوارئ الخاصة بكم لدى مستشفى الكفاءات الأهلي لحالة الطوارئ (#{$this->emergency_id}) جاهزة ومكتملة.\n\n"
              ."📄 يمكنكم الاطلاع على التقرير الطبي والنتائج المعتمدة مباشرة عبر الرابط التالي:\n"
              ."{$reportUrl}\n\n"
              ."مع تمنياتنا لكم بالصحة والعافية والشفاء العاجل.\n"
              .'مختبر مستشفى الكفاءات الأهلي 🏥';

        $encodedText = rawurlencode($text);

        if ($phone) {
            return "https://wa.me/{$phone}?text={$encodedText}";
        }

        return "https://api.whatsapp.com/send?text={$encodedText}";
    }

    // Accessors
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

    public function getStatusBadgeClassAttribute()
    {
        return match ($this->status) {
            'pending' => 'bg-warning',
            'in_progress' => 'bg-info',
            'completed' => 'bg-success',
            'cancelled' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    public function getPriorityTextAttribute()
    {
        return match ($this->priority) {
            'urgent' => 'عاجل',
            'critical' => 'حرج',
            default => $this->priority
        };
    }

    public function getPriorityBadgeClassAttribute()
    {
        return match ($this->priority) {
            'urgent' => 'bg-warning',
            'critical' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    public function getTotalAmountAttribute()
    {
        return $this->labTests->sum('price');
    }
}
