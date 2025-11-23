<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RadiologyRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'radiology_type_id',
        'visit_id',
        'requested_date',
        'scheduled_date',
        'priority',
        'status',
        'clinical_indication',
        'specific_instructions',
        'notes',
        'total_cost',
        'performed_by',
        'performed_at'
    ];

    protected $casts = [
        'requested_date' => 'datetime',
        'scheduled_date' => 'datetime',
        'performed_at' => 'datetime',
        'total_cost' => 'decimal:2'
    ];

    // العلاقات
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function radiologyType()
    {
        return $this->belongsTo(RadiologyType::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function result()
    {
        return $this->hasOne(RadiologyResult::class);
    }

    // النطاقات
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    // الخصائص المساعدة
    public function getStatusTextAttribute()
    {
        $statuses = [
            'pending' => 'في الانتظار',
            'scheduled' => 'مجدول',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغى'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'scheduled' => 'info',
            'in_progress' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getPriorityTextAttribute()
    {
        $priorities = [
            'normal' => 'عادي',
            'urgent' => 'عاجل',
            'emergency' => 'طوارئ'
        ];

        return $priorities[$this->priority] ?? $this->priority;
    }

    public function getPriorityColorAttribute()
    {
        $colors = [
            'normal' => 'secondary',
            'urgent' => 'warning',
            'emergency' => 'danger'
        ];

        return $colors[$this->priority] ?? 'secondary';
    }

    // الدوال المساعدة
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isScheduled()
    {
        return $this->status === 'scheduled';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function canBeScheduled()
    {
        return in_array($this->status, ['pending']);
    }

    public function canBePerformed()
    {
        // يمكن البدء إذا كان معلقاً أو مجدولاً (وحان وقته أو لم يحدد وقت)
        return $this->status === 'pending' || 
               ($this->status === 'scheduled' && (!$this->scheduled_date || $this->scheduled_date->isPast()));
    }

    // تغيير الحالة
    public function schedule($date)
    {
        $this->update([
            'status' => 'scheduled',
            'scheduled_date' => $date
        ]);
    }

    public function startProcedure($performerId)
    {
        $this->update([
            'status' => 'in_progress',
            'performed_by' => $performerId,
            'performed_at' => now()
        ]);
    }

    public function complete()
    {
        $this->update(['status' => 'completed']);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }
}
