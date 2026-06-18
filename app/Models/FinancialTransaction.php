<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_type',
        'related_type',
        'related_id',
        'amount',
        'currency',
        'description',
        'performed_by_id',
        'performed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'performed_at' => 'datetime',
    ];

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }

    public function related()
    {
        return $this->morphTo();
    }
}
