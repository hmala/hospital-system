<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalPeriod extends Model
{
    use HasFactory;

    protected $table = 'acc_fiscal_periods';

    protected $fillable = [
        'fiscal_year_id',
        'period_number',
        'name',
        'start_date',
        'end_date',
        'is_closed',
        'closed_at',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'period_number' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_closed' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'fiscal_period_id');
    }

    public static function forDate(string $date): ?self
    {
        return static::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }
}
