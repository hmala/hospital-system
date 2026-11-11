<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuCareSchedule extends Model
{
    use HasFactory;

    protected $table = 'icu_care_schedules';

    protected $fillable = [
        'icu_admission_id',
        'care_type',
        'scheduled_time',
        'is_done',
        'done_at',
        'done_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'done_at' => 'datetime',
        ];
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IcuAdmission::class, 'icu_admission_id');
    }

    public function doneByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }
}
