<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrRosterApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'period_type',
        'start_date',
        'end_date',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function schedules()
    {
        return $this->hasMany(HrSchedule::class, 'hr_roster_approval_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة قيد الإعداد',
            'submitted_by_dept' => 'معتمد من القسم (بانتظار المراجعة النهائية)',
            'approved' => 'معتمد نهائياً للصرف',
            default => $this->status,
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'bg-secondary',
            'submitted_by_dept' => 'bg-warning text-dark',
            'approved' => 'bg-success',
            default => 'bg-secondary',
        };
    }
}
