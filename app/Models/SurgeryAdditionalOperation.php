<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurgeryAdditionalOperation extends Model
{
    protected $fillable = [
        'surgery_id',
        'surgical_operation_id',
        'notes',
        'added_by',
    ];

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(Surgery::class);
    }

    public function surgicalOperation(): BelongsTo
    {
        return $this->belongsTo(SurgicalOperation::class)->withTrashed();
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
