<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $table = 'acc_currencies';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_default',
        'is_official',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:4',
            'is_default' => 'boolean',
            'is_official' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    public static function getRate(string $code): float
    {
        $currency = static::where('code', strtoupper($code))->first();

        return $currency ? (float) $currency->exchange_rate : 1.0;
    }
}
