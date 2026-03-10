<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = [
        'visit_id',
        'type',
        'description',
        'details',
        'status',
        'payment_status',
        'payment_id',
        'result'
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
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
        return match($this->type) {
            'lab' => 'مختبر',
            'radiology' => 'أشعة',
            'pharmacy' => 'صيدلية',
            'emergency' => 'طوارئ',
            default => $this->type
        };
    }

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

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Ensure visit status updates when all related requests complete.
     */
    protected static function booted()
    {
        static::saved(function ($request) {
            if ($request->isCompleted() && $request->visit) {
                $remaining = $request->visit->requests()
                    ->where('status', '!=', 'completed')
                    ->count();

                if ($remaining === 0 && $request->visit->status !== 'completed') {
                    $request->visit->status = 'completed';
                    $request->visit->save();
                }
            }
        });
    }
}
