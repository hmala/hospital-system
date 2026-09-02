<?php

namespace App\Models;

use App\Models\Payment;
use App\Models\ResidentStationFollowUp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Surgery extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'surgeon_name',
        'department_id',
        'room_id',
        'expected_stay_days',
        'room_fee',
        'visit_id',
        'surgery_type',
        'previous_surgery_type',
        'description',
        'scheduled_date',
        'scheduled_time',
        'started_at',
        'status',
        'payment_status',
        'surgery_fee_paid',
        'payment_id',
        'surgery_fee',
        'surgery_fee_paid_amount',
        'room_fee_paid_amount',
        'referring_doctor_type',
        'referring_doctor_name',
        'notes',
        'post_op_notes',
        'diagnosis',
        'pre_op_medications',
        'estimated_duration',
        'required_tests',
        'anesthesia_type',
        'anesthesiologist_id',
        'anesthesiologist_2_id',
        'surgical_assistant_name',
        'start_time',
        'end_time',
        'referring_physician',
        'surgery_classification',
        'supplies',
        'surgery_category',
        'surgery_type_detail',
        'surgical_operation_id',
        'anesthesia_position',
        'asa_classification',
        'surgical_complexity',
        'surgical_notes',
        'treatment_plan',
        'follow_up_date',
        'discharged_at',
        'discharge_notes',
        'cancellation_reason',
        'referral_letter_path',
        'location_id',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime',
        'started_at' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'follow_up_date' => 'date',
        'discharged_at' => 'datetime',
        'status' => 'string',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function surgicalOperation()
    {
        return $this->belongsTo(SurgicalOperation::class)->withTrashed();
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function surgeryTypeChanges()
    {
        return $this->hasMany(SurgeryTypeChange::class);
    }

    public function additionalOperations()
    {
        return $this->hasMany(SurgeryAdditionalOperation::class);
    }

    public function labTests()
    {
        return $this->hasMany(SurgeryLabTest::class);
    }

    public function radiologyTests()
    {
        return $this->hasMany(SurgeryRadiologyTest::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function surgeryTreatments()
    {
        return $this->hasMany(SurgeryTreatment::class)->orderBy('sort_order');
    }

    public function getSurgeryNameAttribute()
    {
        return $this->surgery_type;
    }

    public function anesthesiologist()
    {
        return $this->belongsTo(Doctor::class, 'anesthesiologist_id');
    }

    public function anesthesiologist2()
    {
        return $this->belongsTo(Doctor::class, 'anesthesiologist_2_id');
    }

    // علاقات المحطات
    public function surgeonStation()
    {
        return $this->hasOne(SurgeonStation::class);
    }

    public function anesthesiaStation()
    {
        return $this->hasOne(AnesthesiaStation::class);
    }

    public function residentStations()
    {
        return $this->hasMany(ResidentStation::class);
    }

    public function residentStationFollowUps()
    {
        return $this->hasMany(ResidentStationFollowUp::class);
    }

    public function residentStation()
    {
        return $this->hasOne(ResidentStation::class);
    }

    public function preOpResidentStation()
    {
        return $this->hasOne(ResidentStation::class)->where('phase', 'pre_op');
    }

    public function postOpResidentStation()
    {
        return $this->hasOne(ResidentStation::class)->where('phase', 'post_op');
    }

    public function operationTheaterStation()
    {
        return $this->hasOne(OperationTheaterStation::class);
    }

    public function medicalDevices()
    {
        return $this->belongsToMany(MedicalDevice::class, 'surgery_medical_device')
                    ->withPivot('assigned_by', 'price')
                    ->withTimestamps();
    }

    public function nursingStation()
    {
        return $this->hasOne(NursingStation::class);
    }

    // Helper methods for new workflow
    public function getCurrentStation()
    {
        // 1. Resident (Pre-op) - التهيئة قبل العملية
        $preOpResident = $this->preOpResidentStation;
        if (!$preOpResident || $preOpResident->status !== 'completed') {
            return 'resident_pre_op';
        }

        // 2. Operation Theater - صالة العمليات
        if (!$this->operationTheaterStation || $this->operationTheaterStation->status !== 'completed') {
            return 'operation_theater';
        }

        // 3. Surgeon - تسجيل تفاصيل العملية
        if (!$this->surgeonStation || $this->surgeonStation->status !== 'completed') {
            return 'surgeon';
        }

        // 4. Anesthesia - توثيق التخدير
        if (!$this->anesthesiaStation || $this->anesthesiaStation->status !== 'completed') {
            return 'anesthesia';
        }

        // 5. Resident (Post-op) - متابعة ما بعد العملية
        $postOpResident = $this->postOpResidentStation;
        if (!$postOpResident || $postOpResident->status !== 'completed') {
            return 'resident_post_op';
        }

        // 6. Nursing - التمريض
        if (!$this->nursingStation || $this->nursingStation->status !== 'completed') {
            return 'nursing';
        }

        return 'completed';
    }

    public function canProceedToNextStation()
    {
        $currentStation = $this->getCurrentStation();
        
        if ($currentStation === 'surgeon') {
            return $this->surgeonStation && $this->surgeonStation->status === 'completed';
        }
        if ($currentStation === 'resident') {
            return $this->residentStation && $this->residentStation->status === 'completed';
        }
        if ($currentStation === 'anesthesia') {
            return $this->anesthesiaStation && $this->anesthesiaStation->status === 'completed';
        }
        
        return false;
    }

    /**
     * احتساب أجرة الليلة الأولى حسب نوع الغرفة
     * (عادية: 0 د.ع | VIP: 100,000 د.ع | VVIP: 200,000 د.ع)
     */
    public static function getInitialRoomFeeForType(?string $roomType): float
    {
        return match($roomType) {
            'vvip' => 200000.0,
            'vip' => 100000.0,
            default => 0.0,
        };
    }

    /**
     * احتساب تفاصيل الإقامة الفندقية بناءً على توقيت 12:00 ظهراً (توقيت بغداد)
     */
    public function calculateStayDetails($asOfTime = null): array
    {
        $room = $this->room;
        if (!$room) {
            return [
                'has_room' => false,
                'room_type' => null,
                'room_type_name' => 'بدون غرفة',
                'daily_fee' => 0,
                'initial_fee' => 0,
                'extra_nights' => 0,
                'extra_nights_fee' => 0,
                'total_fee' => 0,
                'paid_amount' => (float)($this->room_fee_paid_amount ?? 0),
                'remaining_amount' => 0,
                'excess_amount' => 0,
                'checkin_at' => null,
                'checkout_at' => null,
                'first_night_cutoff' => null,
            ];
        }

        $tz = 'Asia/Baghdad';
        
        // وقت الدخول (Checkin): إما started_at أو وقت الحجز أو وقت الإنشاء
        $checkin = $this->started_at 
            ? \Carbon\Carbon::parse($this->started_at)->setTimezone($tz)
            : ($this->scheduled_date 
                ? \Carbon\Carbon::parse($this->scheduled_date->format('Y-m-d') . ' ' . ($this->scheduled_time ? (is_string($this->scheduled_time) ? $this->scheduled_time : $this->scheduled_time->format('H:i:s')) : '08:00:00'))->setTimezone($tz)
                : ($this->created_at ? \Carbon\Carbon::parse($this->created_at)->setTimezone($tz) : now($tz)));

        // وقت الخروج (Checkout): إما وقت الخروج الفعلي أو الوقت الحالي
        $checkout = $asOfTime 
            ? \Carbon\Carbon::parse($asOfTime)->setTimezone($tz)
            : ($this->discharged_at ? \Carbon\Carbon::parse($this->discharged_at)->setTimezone($tz) : now($tz));

        // نهاية الليلة الأولى: الساعة 12:00 ظهراً من اليوم التالي لتاريخ الدخول
        $firstNightCutoff = (clone $checkin)->addDay()->setTime(12, 0, 0);

        // احتساب الليالي الإضافية: كم 12:00 ظهراً إضافية مرت بعد نهاية الليلة الأولى
        $extraNights = 0;
        if ($checkout->greaterThan($firstNightCutoff)) {
            $cursor = clone $firstNightCutoff;
            while ($checkout->greaterThan($cursor)) {
                $extraNights++;
                $cursor->addDay();
            }
        }

        $initialFee = self::getInitialRoomFeeForType($room->room_type);
        $dailyFee = (float)$room->daily_fee;
        $extraNightsFee = $extraNights * $dailyFee;
        $totalFee = $initialFee + $extraNightsFee;
        $paidAmount = (float)($this->room_fee_paid_amount ?? 0);
        $remainingAmount = max(0, $totalFee - $paidAmount);
        $excessAmount = max(0, $paidAmount - $totalFee);

        return [
            'has_room' => true,
            'room' => $room,
            'room_type' => $room->room_type,
            'room_type_name' => $room->room_type_name,
            'daily_fee' => $dailyFee,
            'initial_fee' => $initialFee,
            'extra_nights' => $extraNights,
            'extra_nights_fee' => $extraNightsFee,
            'total_fee' => $totalFee,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'excess_amount' => $excessAmount,
            'checkin_at' => $checkin,
            'checkout_at' => $checkout,
            'first_night_cutoff' => $firstNightCutoff,
        ];
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'scheduled' => 'مجدولة',
            'in_progress' => 'جارية',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغاة',
            default => $this->status
        };
    }
}
