<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyRadiologyRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_id',
        'patient_id',
        'status',
        'priority',
        'notes',
        'requested_at',
        'completed_at'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // العلاقات
    public function emergency()
    {
        return $this->belongsTo(Emergency::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function radiologyTypes()
    {
        return $this->belongsToMany(RadiologyType::class, 'emergency_radiology_request_types', 'emergency_radiology_request_id', 'radiology_type_id')
            ->withPivot('result', 'image_path')
            ->withTimestamps();
    }

    public function requestTypes()
    {
        return $this->hasMany(EmergencyRadiologyRequestType::class);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'pending' => 'في الانتظار',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            default => $this->status
        };
    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'pending' => 'bg-warning',
            'in_progress' => 'bg-info',
            'completed' => 'bg-success',
            'cancelled' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    public function getPriorityTextAttribute()
    {
        return match($this->priority) {
            'urgent' => 'عاجل',
            'critical' => 'حرج',
            default => $this->priority
        };
    }

    public function getPriorityBadgeClassAttribute()
    {
        return match($this->priority) {
            'urgent' => 'bg-warning',
            'critical' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    public function getTotalAmountAttribute()
    {
        return $this->radiologyTypes->sum('price');
    }
}
