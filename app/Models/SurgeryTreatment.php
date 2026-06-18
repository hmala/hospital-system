<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurgeryTreatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'surgery_id',
        'description',
        'dosage',
        'timing',
        'duration_value',
        'duration_unit',
        'sort_order',
        'status',
        'administered_by',
        'administered_at',
        'admin_notes',
        'administrations',
    ];

    protected $casts = [
        'administered_at' => 'datetime',
        'administrations' => 'array',
    ];

    public function surgery()
    {
        return $this->belongsTo(Surgery::class);
    }

    public function administeredBy()
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    public function logAdministration($userId, $userName, $notes = null)
    {
        $current = $this->administrations ?? [];
        $current[] = [
            'administered_at' => now()->format('Y-m-d H:i'),
            'administered_by' => $userId,
            'administered_by_name' => $userName,
            'notes' => $notes,
        ];
        $this->update([
            'administrations' => $current,
            'administered_by' => $userId,
            'administered_at' => now(),
            'admin_notes' => $notes,
        ]);
    }
}
