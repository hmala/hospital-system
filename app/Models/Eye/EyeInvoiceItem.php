<?php

namespace App\Models\Eye;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EyeInvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'eye_invoice_items';

    protected $fillable = [
        'eye_invoice_id',
        'service_type',
        'service_id',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(EyeInvoice::class, 'eye_invoice_id');
    }
}
