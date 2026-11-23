<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class JournalEntry extends Model
{
    use HasFactory;

    protected $table = 'acc_journal_entries';

    protected $fillable = [
        'entry_number',
        'date',
        'fiscal_year_id',
        'fiscal_period_id',
        'reference_type',
        'reference_id',
        'description',
        'status',
        'posted_at',
        'posted_by',
        'reversed_by_entry_id',
        'company_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'posted_at' => 'datetime',
            'company_id' => 'integer',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id');
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class, 'fiscal_period_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_by_entry_id');
    }

    public function getTotalDebitAttribute(): float
    {
        return (float) $this->lines()->sum('debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->lines()->sum('credit');
    }

    public function isBalanced(): bool
    {
        return round($this->total_debit, 2) === round($this->total_credit, 2);
    }

    public static function generateEntryNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "JV-{$year}-";

        $last = static::where('entry_number', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($last) {
            $parts = explode('-', $last->entry_number);
            $nextSeq = ((int) end($parts)) + 1;
        }

        return $prefix.str_pad((string) $nextSeq, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Reverse this journal entry with a new reversal entry (Audit-compliant)
     */
    public function reverse(int $userId, string $reason): self
    {
        if ($this->status !== 'posted') {
            throw new \Exception('لا يمكن عكس قيد غير معتمد ومرحل.');
        }

        return DB::transaction(function () use ($userId, $reason) {
            $reversalNumber = static::generateEntryNumber();

            $reversal = static::create([
                'entry_number' => $reversalNumber,
                'date' => now()->toDateString(),
                'fiscal_year_id' => $this->fiscal_year_id,
                'fiscal_period_id' => $this->fiscal_period_id,
                'reference_type' => static::class,
                'reference_id' => $this->id,
                'description' => "قيد عكسي للقيد رقم ({$this->entry_number}) - السبب: {$reason}",
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $userId,
                'company_id' => $this->company_id,
            ]);

            foreach ($this->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $reversal->id,
                    'account_id' => $line->account_id,
                    'cost_center_id' => $line->cost_center_id,
                    'debit' => $line->credit,   // Inverted
                    'credit' => $line->debit,   // Inverted
                    'currency_id' => $line->currency_id,
                    'exchange_rate' => $line->exchange_rate,
                    'currency_amount' => $line->currency_amount,
                    'description' => "عكس: {$line->description}",
                    'patient_id' => $line->patient_id,
                    'doctor_id' => $line->doctor_id,
                ]);
            }

            $this->update([
                'status' => 'reversed',
                'reversed_by_entry_id' => $reversal->id,
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'reverse',
                'auditable_type' => static::class,
                'auditable_id' => $this->id,
                'old_values' => ['status' => 'posted'],
                'new_values' => ['status' => 'reversed', 'reversed_by_entry_id' => $reversal->id],
                'reason' => $reason,
                'ip_address' => request()->ip(),
            ]);

            return $reversal;
        });
    }
}
