<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabTestSubTest extends Model
{
    protected $fillable = [
        'lab_test_id',
        'name',
        'unit',
        'reference_range',
        'result_type',
        'sort_order',
        'notes',
    ];

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }
}
