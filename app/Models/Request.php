<?php

namespace App\Models;

use App\Models\LabTest;
use App\Models\Package;
use App\Models\RadiologyType;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = [
        'visit_id',
        'type',
        'subtype',
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

    public function getTotalAmountAttribute()
    {
        $details = $this->details;
        if (is_string($details)) {
            $details = json_decode($details, true) ?: [];
        }

        if (!is_array($details)) {
            return null;
        }

        if (isset($details['total_amount'])) {
            return (float) $details['total_amount'];
        }

        if (isset($details['amount'])) {
            return (float) $details['amount'];
        }

        switch ($this->type) {
            case 'lab':
                if (!empty($details['lab_test_ids']) && is_array($details['lab_test_ids'])) {
                    return LabTest::whereIn('id', $details['lab_test_ids'])->sum('price');
                }

                if (!empty($details['tests']) && is_array($details['tests'])) {
                    $total = 0;
                    foreach ($details['tests'] as $testName) {
                        $test = LabTest::where('name', $testName)
                            ->orWhere('code', $testName)
                            ->first();
                        if ($test) {
                            $total += $test->price ?? 0;
                        }
                    }
                    return $total;
                }

                if (!empty($details['package_id'])) {
                    $package = Package::find($details['package_id']);
                    if ($package) {
                        return (float) $package->price;
                    }
                }

                return null;

            case 'radiology':
                if (!empty($details['radiology_type_ids']) && is_array($details['radiology_type_ids'])) {
                    return RadiologyType::whereIn('id', $details['radiology_type_ids'])->sum('base_price');
                }

                if (!empty($details['radiology_types']) && is_array($details['radiology_types'])) {
                    return RadiologyType::whereIn('id', $details['radiology_types'])->sum('base_price');
                }

                return null;

            case 'blood_bank':
                return $this->bloodBankRequest ? (float) $this->bloodBankRequest->total_amount : null;

            case 'emergency':
                if (!empty($details['emergency_priority'])) {
                    $fees = [
                        'critical' => 50000,
                        'urgent' => 35000,
                        'semi_urgent' => 25000,
                        'non_urgent' => 15000,
                    ];
                    return $fees[$details['emergency_priority']] ?? 25000;
                }
                return null;

            case 'pharmacy':
                if (!empty($details['tests']) && is_array($details['tests'])) {
                    $total = 0;
                    foreach ($details['tests'] as $itemName) {
                        $test = LabTest::where('name', $itemName)
                            ->orWhere('code', $itemName)
                            ->first();
                        if ($test) {
                            $total += $test->price ?? 0;
                        }
                    }
                    return $total;
                }
                return null;
        }

        return null;
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function bloodBankRequest()
    {
        return $this->hasOne(\App\Models\BloodBankRequest::class);
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
        if ($this->type === 'lab' && isset($this->details['blood_bank']) && $this->details['blood_bank']) {
            return 'مصرف الدم';
        }

        return match($this->type) {
            'lab' => 'مختبر',
            'radiology' => 'أشعة',
            'pharmacy' => 'صيدلية',
            'nursing' => 'خدمات تمريضية',
            'emergency' => 'طوارئ',
            'blood_bank' => 'مصرف الدم',
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
