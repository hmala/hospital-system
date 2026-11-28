<?php

namespace App\Models;

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
        'room_checkin_at',
        'room_checkin_by',
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
        'billing_status',
        'devices_billing_status',
        'insurance_type',
        'insurance_card_no',
        'copay_percentage',
        'original_surgery_fee',
        'surgery_fee_paid',
        'payment_id',
        'surgery_fee',
        'surgery_fee_paid_amount',
        'room_fee_paid_amount',
        'is_room_package_included',
        'room_deduction_bearer',
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
        'anesthesia_assistant_name',
        'theater_attendant_name',
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
        'operation_theater_id',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime',
        'started_at' => 'datetime',
        'room_checkin_at' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'follow_up_date' => 'date',
        'discharged_at' => 'datetime',
        'is_room_package_included' => 'boolean',
        'status' => 'string',
        'copay_percentage' => 'decimal:2',
        'original_surgery_fee' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::created(function (Surgery $surgery) {
            // Clean up any stale ghost records in station tables from previous deleted surgeries with the same ID
            OperationTheaterStation::where('surgery_id', $surgery->id)
                ->where('created_at', '<', $surgery->created_at ?? now())
                ->delete();
            NursingStation::where('surgery_id', $surgery->id)
                ->where('created_at', '<', $surgery->created_at ?? now())
                ->delete();
            ResidentStation::where('surgery_id', $surgery->id)
                ->where('created_at', '<', $surgery->created_at ?? now())
                ->delete();
            ResidentStationFollowUp::where('surgery_id', $surgery->id)
                ->where('created_at', '<', $surgery->created_at ?? now())
                ->delete();
            AnesthesiaStation::where('surgery_id', $surgery->id)
                ->where('created_at', '<', $surgery->created_at ?? now())
                ->delete();
            SurgeonStation::where('surgery_id', $surgery->id)
                ->where('created_at', '<', $surgery->created_at ?? now())
                ->delete();
        });

        static::deleting(function (Surgery $surgery) {
            // Cascade delete child station and related records when a surgery is deleted to prevent orphan ghosts
            $surgery->operationTheaterStation()?->delete();
            $surgery->nursingStation()?->delete();
            $surgery->residentStations()->delete();
            $surgery->residentStationFollowUps()->delete();
            $surgery->anesthesiaStation()?->delete();
            $surgery->surgeonStation()?->delete();
            $surgery->additionalOperations()->delete();
            $surgery->extraServices()->delete();
            $surgery->surgeryTreatments()->delete();
        });
    }

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

    public function roomCheckinBy()
    {
        return $this->belongsTo(User::class, 'room_checkin_by');
    }

    public function inpatientSurvey()
    {
        return $this->hasOne(InpatientRoomSurvey::class);
    }

    public function operationTheater()
    {
        return $this->belongsTo(OperationTheater::class);
    }

    public function surgicalOperation()
    {
        return $this->belongsTo(SurgicalOperation::class)->withTrashed();
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function nicuDeliveryObservation()
    {
        return $this->hasOne(NicuDeliveryObservation::class, 'surgery_id');
    }

    public function surgeryTypeChanges()
    {
        return $this->hasMany(SurgeryTypeChange::class);
    }

    public function typeChanges()
    {
        return $this->surgeryTypeChanges();
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

    public function documents()
    {
        return $this->hasMany(SurgeryDocument::class);
    }

    public function consultations()
    {
        return $this->hasMany(SurgeryConsultation::class);
    }

    public function extraServices()
    {
        return $this->hasMany(SurgeryExtraService::class);
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

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function pharmacySales()
    {
        return $this->hasMany(PharmacySale::class);
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
        if (! $preOpResident || $preOpResident->status !== 'completed') {
            return 'resident_pre_op';
        }

        // 2. Operation Theater - صالة العمليات
        if (! $this->operationTheaterStation || $this->operationTheaterStation->status !== 'completed') {
            return 'operation_theater';
        }

        // 3. Surgeon - تسجيل تفاصيل العملية
        if (! $this->surgeonStation || $this->surgeonStation->status !== 'completed') {
            return 'surgeon';
        }

        // 4. Anesthesia - توثيق التخدير
        if (! $this->anesthesiaStation || $this->anesthesiaStation->status !== 'completed') {
            return 'anesthesia';
        }

        // 5. Resident (Post-op) - متابعة ما بعد العملية
        $postOpResident = $this->postOpResidentStation;
        if (! $postOpResident || $postOpResident->status !== 'completed') {
            return 'resident_post_op';
        }

        // 6. Nursing - التمريض
        if (! $this->nursingStation || $this->nursingStation->status !== 'completed') {
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
        return match ($roomType) {
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
        if (! $room) {
            return [
                'has_room' => false,
                'room_type' => null,
                'room_type_name' => 'بدون غرفة',
                'daily_fee' => 0,
                'initial_fee' => 0,
                'extra_nights' => 0,
                'extra_nights_fee' => 0,
                'total_fee' => 0,
                'paid_amount' => (float) ($this->room_fee_paid_amount ?? 0),
                'remaining_amount' => 0,
                'excess_amount' => 0,
                'checkin_at' => null,
                'checkout_at' => null,
                'first_night_cutoff' => null,
            ];
        }

        $tz = 'Asia/Baghdad';

        // وقت الدخول (Checkin): الأولوية لتسجيل دخول الغرفة الفعلي room_checkin_at، ثم started_at، ثم وقت الجدولة
        $checkin = $this->room_checkin_at
            ? \Carbon\Carbon::parse($this->room_checkin_at)->setTimezone($tz)
            : ($this->started_at
                ? \Carbon\Carbon::parse($this->started_at)->setTimezone($tz)
                : ($this->scheduled_date
                    ? \Carbon\Carbon::parse($this->scheduled_date->format('Y-m-d').' '.($this->scheduled_time ? (is_string($this->scheduled_time) ? $this->scheduled_time : $this->scheduled_time->format('H:i:s')) : '08:00:00'))->setTimezone($tz)
                    : ($this->created_at ? \Carbon\Carbon::parse($this->created_at)->setTimezone($tz) : now($tz))));

        // وقت الخروج (Checkout): إما وقت الخروج الفعلي أو الوقت المحدد أو الوقت الحالي
        $checkout = $asOfTime
            ? \Carbon\Carbon::parse($asOfTime)->setTimezone($tz)
            : ($this->discharged_at ? \Carbon\Carbon::parse($this->discharged_at)->setTimezone($tz) : now($tz));

        // نهاية الليلة الأولى: الساعة 12:00 ظهراً من اليوم التالي لتاريخ الدخول
        $firstNightCutoff = (clone $checkin)->addDay()->setTime(12, 0, 0);

        // احتساب الليالي الإضافية: كم 12:00 ظهراً إضافية مرت بعد نهاية الليلة الأولى
        // لا يتم احتساب ليالي إضافية إلا إذا تم تسجيل الدخول الفعلي للغرفة أو بدأت العملية
        $hasActuallyStarted = ! empty($this->room_checkin_at) || ! empty($this->started_at) || in_array($this->status, ['in_progress', 'completed']);
        $extraNights = 0;
        if ($hasActuallyStarted && $checkout->greaterThan($firstNightCutoff)) {
            $cursor = clone $firstNightCutoff;
            while ($checkout->greaterThan($cursor)) {
                $extraNights++;
                $cursor->addDay();
            }
        }

        $isInsurance = in_array($this->insurance_type, ['hi', 'moi'])
            || in_array($this->patient?->insurance_type, ['hi', 'moi']);

        $standardRoomPrice = HealthInsuranceStayTier::getStandardRoomPrice();
        $dailyFee = (float) $room->daily_fee;
        $roomType = strtolower($room->room_type ?? 'regular');
        $isUpgrade = in_array($roomType, ['vip', 'vvip']);
        $upgradeDiffDaily = $isUpgrade ? max(0.0, round($dailyFee - $standardRoomPrice, 2)) : 0.0;

        $totalNights = 1 + $extraNights;
        $nightsBreakdown = [];
        $patientStayShare = 0.0;
        $insuranceStayShare = 0.0;

        if ($isInsurance) {
            for ($night = 1; $night <= $totalNights; $night++) {
                $nightFig = HealthInsuranceStayTier::calculateNightFigures($night, $room);
                $tier = HealthInsuranceStayTier::getTierForDay($night);

                // الليلة الأولى مشمولة بالعملية للغرفة العادية، وفرق الترقية يُدفع عن كل الليالي
                $isFirstNight = ($night === 1);
                $nightCopayPct = $isFirstNight ? 0.0 : (float) $nightFig['copay_percentage'];
                $regPat = $isFirstNight ? 0.0 : (float) $nightFig['regular_patient_share'];
                $regIns = $isFirstNight ? 0.0 : (float) $nightFig['regular_insurance_share'];
                $upDiff = (float) $nightFig['upgrade_diff'];

                $pShare = $regPat + $upDiff;
                $iShare = $regIns;

                $patientStayShare += $pShare;
                $insuranceStayShare += $iShare;

                $nightsBreakdown[] = [
                    'night_number' => $night,
                    'tier_name' => $isFirstNight ? 'الليلة الأولى (مشمولة بالعملية)' : ($tier?->name ?? 'إقامة'),
                    'copay_percentage' => $nightCopayPct,
                    'standard_price' => $standardRoomPrice,
                    'regular_patient_share' => $regPat,
                    'regular_insurance_share' => $regIns,
                    'upgrade_diff' => $upDiff,
                    'patient_share' => $pShare,
                    'insurance_share' => $iShare,
                    'total_night_fee' => $pShare + $iShare,
                ];
            }

            $initialFee = $nightsBreakdown[0]['patient_share'] ?? 0;
            $extraNightsFee = max(0.0, ($patientStayShare + $insuranceStayShare) - ($nightsBreakdown[0]['total_night_fee'] ?? 0));
            $totalFee = $patientStayShare + $insuranceStayShare;
        } else {
            $initialFee = self::getInitialRoomFeeForType($room->room_type);
            $extraNightsFee = $extraNights * $dailyFee;
            $totalFee = $initialFee + $extraNightsFee;
            $patientStayShare = $totalFee;
            $insuranceStayShare = 0.0;
        }

        $paidAmount = (float) ($this->room_fee_paid_amount ?? 0);
        $remainingAmount = max(0, $patientStayShare - $paidAmount);
        $excessAmount = max(0, $paidAmount - $patientStayShare);

        return [
            'has_room' => true,
            'room' => $room,
            'room_type' => $room->room_type,
            'room_type_name' => $room->room_type_name,
            'daily_fee' => $dailyFee,
            'initial_fee' => $initialFee,
            'extra_nights' => $extraNights,
            'total_nights' => $totalNights,
            'extra_nights_fee' => $extraNightsFee,
            'total_fee' => $totalFee,
            'patient_stay_share' => $patientStayShare,
            'insurance_stay_share' => $insuranceStayShare,
            'standard_room_price' => $standardRoomPrice,
            'upgrade_diff_daily' => $upgradeDiffDaily,
            'nights_breakdown' => $nightsBreakdown,
            'is_insurance_calculated' => $isInsurance,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'excess_amount' => $excessAmount,
            'checkin_at' => $checkin,
            'checkout_at' => $checkout,
            'first_night_cutoff' => $firstNightCutoff,
            'room_checkin_at' => $this->room_checkin_at,
            'is_room_checked_in' => ! empty($this->room_checkin_at),
        ];
    }

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            'scheduled' => 'مجدولة',
            'in_progress' => 'جارية',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغاة',
            default => $this->status
        };
    }

    public static function isOperationLocationName(?string $name): bool
    {
        if (! $name) {
            return false;
        }

        $normalized = str_replace(' ', '', mb_strtolower($name));

        return str_contains($normalized, 'عمليات')
            || str_contains($normalized, 'صالة')
            || str_contains($normalized, 'operation')
            || str_contains($normalized, 'theater');
    }

    /**
     * جلب جميع المخازن الفرعية التابعة للعمليات
     */
    public static function resolveOperationSubStores()
    {
        return Location::where('type', 'sub')
            ->orderBy('name')
            ->get()
            ->filter(fn (Location $loc) => self::isOperationLocationName($loc->name))
            ->values();
    }

    /**
     * استنتاج مخزن وعُهدة العمليات الفرعي (الأولوية لعهدة المستخدم المعينة ثم مخزن عمليات رئيسي ثم صالات العمليات)
     */
    public function getOperationSubStoreLocation(?User $user = null): ?Location
    {
        if ($user?->location_id) {
            $userLoc = Location::find($user->location_id);
            if ($userLoc && $userLoc->type === 'sub' && self::isOperationLocationName($userLoc->name)) {
                return $userLoc;
            }
        }

        $subLocations = Location::where('type', 'sub')->orderBy('id')->get();

        foreach (['مخزنعملياترئيسي', 'مخزنالعملياتالرئيسي', 'مخزنعمليات', 'مخزنالعمليات', 'عمليات'] as $priorityName) {
            $matched = $subLocations->first(function (Location $loc) use ($priorityName) {
                return str_replace(' ', '', mb_strtolower($loc->name)) === $priorityName;
            });
            if ($matched) {
                return $matched;
            }
        }

        $explicitOrStore = $subLocations->first(function (Location $loc) {
            $lower = mb_strtolower($loc->name);

            return str_contains($lower, 'مخزن') && str_contains($lower, 'عمليات');
        });
        if ($explicitOrStore) {
            return $explicitOrStore;
        }

        if ($this->location_id) {
            $loc = Location::find($this->location_id);
            if ($loc && $loc->type === 'sub' && self::isOperationLocationName($loc->name)) {
                return $loc;
            }
        }

        foreach ($subLocations as $loc) {
            if (str_contains(mb_strtolower($loc->name), 'عمليات')) {
                return $loc;
            }
        }

        return $subLocations->first(fn (Location $loc) => self::isOperationLocationName($loc->name));
    }

    /**
     * استنتاج مخزن عهدة الطابق المرتبط بغرفة المريض تلقائياً
     */
    public function getFloorSubStoreLocation(): ?Location
    {
        if ($this->location_id) {
            $loc = Location::find($this->location_id);
            if ($loc && ! self::isOperationLocationName($loc->name)) {
                return $loc;
            }
        }

        $floor = $this->room?->floor;
        if (! $floor) {
            return null;
        }

        $normalizedFloor = str_replace(' ', '', $floor);

        return Location::where('type', 'sub')
            ->where(function ($q) use ($floor, $normalizedFloor) {
                $q->where('name', 'like', "%{$floor}%")
                    ->orWhereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$normalizedFloor}%"]);
            })
            ->first();
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * إلحاق الخدمات الإضافية الافتراضية للعملية تلقائياً (تخطيط قلب، فتح طبلة، خدمات فندقية)
     */
    public function attachDefaultExtraServices(): void
    {
        $servicesConfig = [
            [
                'match' => fn ($q) => $q->where('category', 'medical')->where(fn ($sub) => $sub->where('name', 'like', '%تخطيط%')->orWhere('name', 'like', '%ECG%')),
                'fallback_id' => 1,
                'name' => 'تخطيط قلب',
                'default_price' => 15000,
            ],
            [
                'match' => fn ($q) => $q->where(fn ($sub) => $sub->where('name', 'like', '%طبلة%')->orWhere('name', 'like', '%طبله%')),
                'fallback_id' => 7,
                'name' => 'اجور فتح طبلة',
                'default_price' => 10000,
            ],
            [
                'match' => fn ($q) => $q->where(fn ($sub) => $sub->where('name', 'like', '%فندق%')->orWhere('name', 'like', '%إقامة%')),
                'fallback_id' => 5,
                'name' => 'خدمات فندقية',
                'default_price' => 30000,
            ],
        ];

        foreach ($servicesConfig as $cfg) {
            $matchedService = HospitalExtraService::where($cfg['match'])->first();
            $serviceId = $matchedService?->id ?? $cfg['fallback_id'];
            $serviceName = $matchedService ? ($matchedService->id == 5 ? 'خدمات فندقية' : $matchedService->name) : $cfg['name'];
            $servicePrice = $matchedService ? (float) $matchedService->default_price : $cfg['default_price'];

            $alreadyExists = $this->extraServices()
                ->where(function ($q) use ($serviceId, $serviceName) {
                    if ($serviceId) {
                        $q->where('service_id', $serviceId);
                    }
                    $q->orWhere('name', 'like', '%'.$serviceName.'%');
                })
                ->exists();

            if (! $alreadyExists) {
                $this->extraServices()->create([
                    'service_id' => $serviceId,
                    'name' => $serviceName,
                    'price' => $servicePrice,
                    'quantity' => 1,
                    'total_amount' => $servicePrice,
                    'notes' => 'خدمة تلقائية مرافقة لحجز العملية',
                    'payment_status' => 'pending',
                    'created_by' => auth()->id() ?? 1,
                ]);
            }
        }
    }

    /**
     * إجمالي تكلفة الأجهزة الطبية المستخدمة في العملية (تُحاسب مع الجراح في الحسابات فقط ولا تُحمّل على المريض)
     */
    public function getDevicesTotalFee(): float
    {
        $devices = $this->relationLoaded('medicalDevices')
            ? $this->medicalDevices
            : $this->medicalDevices()->get();

        return (float) $devices->sum(function ($device) {
            return (float) ($device->pivot->price ?? $device->usage_price ?? 0);
        });
    }

    /**
     * إجمالي أجور العملية الجراحية للمريض (العملية الأساسية + العمليات المرافقة فقط، دون الأجهزة الطبية)
     */
    public function getTotalSurgeryPatientFee(): float
    {
        $additionalOps = $this->relationLoaded('additionalOperations')
            ? $this->additionalOperations
            : $this->additionalOperations()->get();

        return (float) ($this->surgery_fee ?? 0) + (float) $additionalOps->sum('fee');
    }

    /**
     * حساب المستحقات والذمم المالية المعلقة للمريض وبراءة الذمة
     */
    public function calculatePendingItemsBreakdown($asOfTime = null): array
    {
        $this->loadMissing([
            'patient',
            'room',
            'additionalOperations',
            'labTests.labTest',
            'radiologyTests.radiologyType',
            'consultations',
            'extraServices',
        ]);

        $isCancelled = $this->status === 'cancelled';
        $isPricePendingReview = ($this->billing_status === 'pending_review');

        $totalSurgeryFee = $isCancelled ? 0.0 : $this->getTotalSurgeryPatientFee();
        $surgeryPaidAmount = (float) ($this->surgery_fee_paid_amount ?? 0);
        $remainingSurgeryFee = $isCancelled ? 0.0 : max(0.0, $totalSurgeryFee - $surgeryPaidAmount);
        $excessSurgeryFee = $isCancelled
            ? max(0.0, (float) $this->payments()->whereNull('voided_at')->sum('amount'))
            : max(0.0, $surgeryPaidAmount - $totalSurgeryFee);

        $stayDetails = $this->calculateStayDetails($asOfTime);
        $insuranceType = $this->insurance_type ?: ($this->patient?->insurance_type ?: 'none');
        $isInsurance = in_array($insuranceType, ['hi', 'moi'], true);
        $isStayIns = $isInsurance && ($stayDetails['is_insurance_calculated'] ?? false);

        $targetRoomFee = $isCancelled
            ? 0.0
            : (float) ($isStayIns ? ($stayDetails['patient_stay_share'] ?? 0) : ($stayDetails['total_fee'] ?? ($this->room_fee ?? 0)));
        $roomPaidAmount = (float) ($this->room_fee_paid_amount ?? 0);
        $remainingRoomFee = $isCancelled ? 0.0 : max(0.0, $targetRoomFee - $roomPaidAmount);
        $excessRoomFee = $isCancelled ? 0.0 : max(0.0, $roomPaidAmount - $targetRoomFee);

        $pendingLabTests = $isCancelled
            ? collect()
            : $this->labTests->where('payment_status', '!=', 'paid')->where('status', '!=', 'cancelled');
        $pendingRadiologyTests = $isCancelled
            ? collect()
            : $this->radiologyTests->where('payment_status', '!=', 'paid')->where('status', '!=', 'cancelled');
        $pendingConsultations = $isCancelled
            ? collect()
            : $this->consultations->where('payment_status', '!=', 'paid')->where('status', '!=', 'cancelled');
        $pendingExtraServices = $isCancelled
            ? collect()
            : $this->extraServices->where('payment_status', '!=', 'paid');

        $hasUnpaidItems = ! $isCancelled && (
            $remainingSurgeryFee > 0
            || $remainingRoomFee > 0
            || $pendingLabTests->isNotEmpty()
            || $pendingRadiologyTests->isNotEmpty()
            || $pendingConsultations->isNotEmpty()
            || $pendingExtraServices->isNotEmpty()
        );

        $hasRefundDue = ($isCancelled && (($surgeryPaidAmount + $roomPaidAmount) > 0 || $this->payments()->whereNull('voided_at')->where('amount', '>', 0)->exists()))
            || (! $isCancelled && ($excessSurgeryFee > 0 || $excessRoomFee > 0));

        return [
            'is_cancelled' => $isCancelled,
            'is_price_pending_review' => $isPricePendingReview,
            'total_surgery_fee' => $totalSurgeryFee,
            'surgery_paid_amount' => $surgeryPaidAmount,
            'remaining_surgery_fee' => $remainingSurgeryFee,
            'excess_surgery_fee' => $excessSurgeryFee,
            'stay_details' => $stayDetails,
            'target_room_fee' => $targetRoomFee,
            'room_paid_amount' => $roomPaidAmount,
            'remaining_room_fee' => $remainingRoomFee,
            'excess_room_fee' => $excessRoomFee,
            'pending_lab_count' => $pendingLabTests->count(),
            'pending_radiology_count' => $pendingRadiologyTests->count(),
            'pending_consultations_count' => $pendingConsultations->count(),
            'pending_extra_services_count' => $pendingExtraServices->count(),
            'pending_extra_services_fee' => (float) $pendingExtraServices->sum('total_amount'),
            'pending_consultations_fee' => (float) $pendingConsultations->sum('fee'),
            'has_unpaid_items' => $hasUnpaidItems,
            'has_refund_due' => $hasRefundDue,
            'has_any_pending_reason' => $hasUnpaidItems || $hasRefundDue || $isPricePendingReview,
        ];
    }

    /**
     * التحقق من براءة الذمة المالية الكاملة للمريض قبل السماح بالتخريج
     */
    public function getFinancialClearanceSummary($asOfTime = null): array
    {
        $breakdown = $this->calculatePendingItemsBreakdown($asOfTime);
        $reasons = [];

        if ($breakdown['is_price_pending_review']) {
            $reasons[] = 'تعديل العملية بانتظار اعتماد التسعيرة في قسم الحسابات';
        }
        if ($breakdown['remaining_surgery_fee'] > 0) {
            $reasons[] = 'متبقي رسوم العملية الجراحية: '.number_format($breakdown['remaining_surgery_fee'], 0).' د.ع';
        }
        if ($breakdown['remaining_room_fee'] > 0) {
            $reasons[] = 'متبقي أجور الغرفة والإقامة الفندقية: '.number_format($breakdown['remaining_room_fee'], 0).' د.ع';
        }
        if ($breakdown['pending_lab_count'] > 0) {
            $reasons[] = 'تحاليل مختبر غير مسددة ('.$breakdown['pending_lab_count'].')';
        }
        if ($breakdown['pending_radiology_count'] > 0) {
            $reasons[] = 'فحوصات أشعة غير مسددة ('.$breakdown['pending_radiology_count'].')';
        }
        if ($breakdown['pending_consultations_count'] > 0) {
            $reasons[] = 'استشارات طبية غير مسددة ('.$breakdown['pending_consultations_count'].')';
        }
        if ($breakdown['pending_extra_services_count'] > 0) {
            $reasons[] = 'خدمات إضافية/علاج فيزيائي غير مسددة ('.$breakdown['pending_extra_services_count'].')';
        }

        return [
            'cleared' => empty($reasons),
            'reasons' => $reasons,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * مزامنة حالة الدفع الإجمالية للعملية وحالة رسم العملية عند إضافة بنود أو تعديل الأسعار أو الغرف
     */
    public function syncPaymentStatus(): string
    {
        if ($this->status === 'cancelled') {
            return $this->payment_status ?? 'cancelled';
        }

        $breakdown = $this->calculatePendingItemsBreakdown();

        $surgeryFeePaidStatus = 'pending';
        if ($breakdown['remaining_surgery_fee'] <= 0 && ($breakdown['total_surgery_fee'] > 0 || $breakdown['surgery_paid_amount'] > 0 || $this->surgery_fee_paid === 'paid')) {
            $surgeryFeePaidStatus = 'paid';
        } elseif ($breakdown['surgery_paid_amount'] > 0) {
            $surgeryFeePaidStatus = 'partial';
        }

        $hasAnyPaidItem = $breakdown['surgery_paid_amount'] > 0
            || $breakdown['room_paid_amount'] > 0
            || $this->payments()->whereNull('voided_at')->where('amount', '>', 0)->exists()
            || $this->labTests()->where('payment_status', 'paid')->exists()
            || $this->radiologyTests()->where('payment_status', 'paid')->exists()
            || $this->extraServices()->where('payment_status', 'paid')->exists()
            || $this->consultations()->where('payment_status', 'paid')->exists();

        if (! $breakdown['has_unpaid_items'] && ($hasAnyPaidItem || $breakdown['total_surgery_fee'] <= 0)) {
            $paymentStatus = 'paid';
        } elseif ($hasAnyPaidItem) {
            $paymentStatus = 'partial';
        } else {
            $paymentStatus = 'pending';
        }

        $this->forceFill([
            'surgery_fee_paid' => $surgeryFeePaidStatus,
            'payment_status' => $paymentStatus,
        ])->save();

        return $paymentStatus;
    }

    /**
     * احتساب تفاصيل مستحقات الطبيب الجراح (شاملة استقطاعات الباقة واستقطاعات الأجهزة الطبية)
     */
    public function calculateSurgeonFinancials(): array
    {
        $this->loadMissing(['additionalOperations', 'medicalDevices', 'payments']);

        $grossFees = $this->getTotalSurgeryPatientFee();
        $packageDeductions = (float) $this->payments->where('payment_type', 'surgery')->sum('doctor_deductions_amount');
        $devicesDeductions = $this->getDevicesTotalFee();
        $isIns = in_array($this->insurance_type, ['hi', 'moi', 'health_insurance'], true);

        // استقطاع الباقة يخصم من النقدي فقط، بينما الأجهزة الطبية المستخدمة في الصالة تُحاسب مع الجراح في جميع الحالات
        $afterPackage = $isIns ? $grossFees : max(0.0, $grossFees - $packageDeductions);
        $netEarned = max(0.0, $afterPackage - $devicesDeductions);

        return [
            'gross_fees' => $grossFees,
            'package_deductions' => $packageDeductions,
            'devices_deductions' => $devicesDeductions,
            'total_deductions' => ($isIns ? 0.0 : $packageDeductions) + $devicesDeductions,
            'net_earned' => $netEarned,
            'is_insurance' => $isIns,
        ];
    }

    /**
     * مزامنة قيد مستحق الجراح غير المصروف في جدول DoctorDue عند مراجعة الأسعار أو الأجهزة
     */
    public function syncSurgeonDoctorDue(): void
    {
        if (! $this->doctor_id || $this->status === 'cancelled') {
            return;
        }

        $payment = $this->payments()->whereNull('voided_at')->where('payment_type', 'surgery')->latest()->first();
        if (! $payment) {
            return;
        }

        $due = DoctorDue::where('doctor_id', $this->doctor_id)
            ->where('payment_id', $payment->id)
            ->first();

        if (! $due || $due->status === 'paid') {
            return;
        }

        $fin = $this->calculateSurgeonFinancials();
        $noteParts = ['أتعاب جراحة العملية #'.$this->id.' ('.number_format($fin['gross_fees'], 0).' د.ع)'];
        if (! $fin['is_insurance'] && $fin['package_deductions'] > 0) {
            $noteParts[] = 'استقطاعات باقة: '.number_format($fin['package_deductions'], 0).' د.ع';
        }
        if ($fin['devices_deductions'] > 0) {
            $noteParts[] = 'أجهزة طبية: '.number_format($fin['devices_deductions'], 0).' د.ع';
        }

        $due->update([
            'amount' => $fin['net_earned'],
            'notes' => implode(' | ', $noteParts),
        ]);
    }
}
