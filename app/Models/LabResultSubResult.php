<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResultSubResult extends Model
{
    protected $fillable = [
        'lab_result_id',
        'sub_test_name',
        'value',
        'value_text',
        'unit',
        'status',
        'reference_range',
        'notes',
    ];

    public function labResult(): BelongsTo
    {
        return $this->belongsTo(LabResult::class);
    }
}
