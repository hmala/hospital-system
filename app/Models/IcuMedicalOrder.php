<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuMedicalOrder extends Model
{
    use HasFactory;

    protected $table = 'icu_medical_orders';

    protected $fillable = [
        'icu_admission_id',
        'doctor_id',
        'order_type',
        'item_name',
        'lab_test_id',
        'radiology_type_id',
        'price',
        'original_price',
        'billing_mode',
        'order_group_id',
        'order_text',
        'instructions',
        'priority',
        'status',
        'result',
        'unit',
        'reference_range',
        'attachment_path',
        'attachment_name',
        'ordered_by',
        'ordered_at',
        'completed_at',
        'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'ordered_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    const PRIORITY_ROUTINE = 'routine';

    const PRIORITY_URGENT = 'urgent';

    const PRIORITY_STAT = 'stat';

    const STATUS_PENDING = 'pending';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IcuAdmission::class, 'icu_admission_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'lab_test_id');
    }

    public function radiologyType(): BelongsTo
    {
        return $this->belongsTo(RadiologyType::class, 'radiology_type_id');
    }

    public function orderedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_STAT => 'bg-danger text-white pulse-animation',
            self::PRIORITY_URGENT => 'bg-warning text-dark',
            default => 'bg-info text-dark',
        };
    }

    public function getPriorityNameAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_STAT => '🚨 STAT (فوري وحرج)',
            self::PRIORITY_URGENT => '⚡ عاجل (Urgent)',
            default => 'اعتيادي (Routine)',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'bg-success text-white',
            self::STATUS_IN_PROGRESS => 'bg-primary text-white',
            self::STATUS_CANCELLED => 'bg-secondary text-white',
            default => 'bg-warning text-dark',
        };
    }

    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'تم الإنجاز',
            self::STATUS_IN_PROGRESS => 'قيد التنفيذ',
            self::STATUS_CANCELLED => 'ملغي',
            default => 'بانتظار التنفيذ',
        };
    }
}
