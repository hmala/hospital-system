<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalYear extends Model
{
    use HasFactory;

    protected $table = 'acc_fiscal_years';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_closed',
        'company_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_closed' => 'boolean',
            'company_id' => 'integer',
        ];
    }

    public function periods(): HasMany
    {
        return $this->hasMany(FiscalPeriod::class, 'fiscal_year_id')->orderBy('period_number');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'fiscal_year_id');
    }

    public static function current(): ?self
    {
        $today = now()->toDateString();

        return static::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('is_closed', false)
            ->first();
    }
}
