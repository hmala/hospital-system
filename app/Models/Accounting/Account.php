<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $table = 'acc_accounts';

    protected $fillable = [
        'code',
        'name',
        'parent_id',
        'level',
        'account_type',
        'nature',
        'is_postable',
        'is_active',
        'company_id',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_postable' => 'boolean',
            'is_active' => 'boolean',
            'company_id' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    public function scopePostable($query)
    {
        return $query->where('is_postable', true)->doesntHave('children');
    }

    public function hasJournalLines(): bool
    {
        return $this->lines()->exists();
    }

    public function canReceiveEntries(): bool
    {
        return $this->is_postable && ! $this->children()->exists();
    }

    public function scopeHeaders($query)
    {
        return $query->where('is_postable', false);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    /**
     * Get the current balance of the account
     */
    public function getBalance(?string $startDate = null, ?string $endDate = null): float
    {
        $query = $this->lines()
            ->whereHas('entry', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'posted');
                if ($startDate) {
                    $q->where('date', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('date', '<=', $endDate);
                }
            });

        $totalDebit = (float) (clone $query)->sum('debit');
        $totalCredit = (float) (clone $query)->sum('credit');

        if ($this->nature === 'debit') {
            return $totalDebit - $totalCredit;
        }

        return $totalCredit - $totalDebit;
    }
}
