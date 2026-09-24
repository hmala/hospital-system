<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'prescription_number',
        'patient_id',
        'doctor_id',
        'visit_id',
        'emergency_id',
        'status',
        'diagnosis',
        'notes',
        'dispensed_at',
        'dispensed_by',
        'sale_id',
    ];

    protected $casts = [
        'dispensed_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->prescription_number)) {
                $dateStr = date('Ymd');
                $prefix = 'RX-' . $dateStr . '-';
                
                $latest = self::withTrashed()
                    ->where('prescription_number', 'like', $prefix . '%')
                    ->orderBy('id', 'desc')
                    ->first();

                $nextNum = 1;
                if ($latest && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $latest->prescription_number, $matches)) {
                    $nextNum = (int)$matches[1] + 1;
                }

                do {
                    $candidate = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
                    $exists = self::withTrashed()->where('prescription_number', $candidate)->exists();
                    if ($exists) {
                        $nextNum++;
                    }
                } while ($exists);

                $model->prescription_number = $candidate;
            }
        });
    }

    // ────────────── العلاقات ──────────────

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function emergency()
    {
        return $this->belongsTo(Emergency::class);
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function dispenser()
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    public function sale()
    {
        return $this->belongsTo(PharmacySale::class, 'sale_id');
    }

    // ────────────── Scopes ──────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDispensed($query)
    {
        return $query->where('status', 'dispensed');
    }

    // ────────────── Accessors ──────────────

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            'pending' => 'قيد الصرف',
            'in_progress' => 'جاري التجهيز',
            'dispensed' => 'تم الصرف بالكامل',
            'partially_dispensed' => 'تم الصرف جزئياً',
            'cancelled' => 'ملغاة',
            default => $this->status,
        };
    }

    public function getStatusBadgeAttribute()
    {
        return match ($this->status) {
            'pending' => 'badge bg-warning text-dark',
            'in_progress' => 'badge bg-info text-white',
            'dispensed' => 'badge bg-success',
            'partially_dispensed' => 'badge bg-primary',
            'cancelled' => 'badge bg-danger',
            default => 'badge bg-secondary',
        };
    }
}
