<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurgeryTreatment extends Model
{
    use HasFactory;

    public const TYPE_MEDICATION = 'medication';

    public const TYPE_SUPPLY = 'supply';

    public const FREQ_SCHEDULED = 'scheduled';

    public const FREQ_STAT = 'stat';

    public const FREQ_PRN = 'prn';

    public const FREQ_CUSTOM = 'custom';

    public const FREQUENCY_PRESETS = [
        'q24h' => [
            'label' => 'مرة يومياً (كل 24 ساعة)',
            'short' => 'كل 24 ساعة',
            'mode' => self::FREQ_SCHEDULED,
            'interval_hours' => 24,
        ],
        'q12h' => [
            'label' => 'مرتين يومياً (كل 12 ساعة)',
            'short' => 'كل 12 ساعة',
            'mode' => self::FREQ_SCHEDULED,
            'interval_hours' => 12,
        ],
        'q8h' => [
            'label' => '3 مرات يومياً (كل 8 ساعات)',
            'short' => 'كل 8 ساعات',
            'mode' => self::FREQ_SCHEDULED,
            'interval_hours' => 8,
        ],
        'q6h' => [
            'label' => '4 مرات يومياً (كل 6 ساعات)',
            'short' => 'كل 6 ساعات',
            'mode' => self::FREQ_SCHEDULED,
            'interval_hours' => 6,
        ],
        'q4h' => [
            'label' => 'كل 4 ساعات (6 مرات)',
            'short' => 'كل 4 ساعات',
            'mode' => self::FREQ_SCHEDULED,
            'interval_hours' => 4,
        ],
        'stat' => [
            'label' => 'جرعة واحدة فورية (STAT)',
            'short' => 'جرعة فورية STAT',
            'mode' => self::FREQ_STAT,
            'interval_hours' => null,
        ],
        'prn' => [
            'label' => 'عند اللزوم (PRN)',
            'short' => 'عند اللزوم PRN',
            'mode' => self::FREQ_PRN,
            'interval_hours' => null,
        ],
        'custom' => [
            'label' => 'تكرار مخصص',
            'short' => 'حسب التوجيه',
            'mode' => self::FREQ_CUSTOM,
            'interval_hours' => null,
        ],
    ];

    protected $fillable = [
        'surgery_id',
        'item_type',
        'medicine_id',
        'prescribed_by',
        'description',
        'dosage',
        'route',
        'timing',
        'frequency_mode',
        'interval_hours',
        'duration_value',
        'duration_unit',
        'start_at',
        'total_doses',
        'sort_order',
        'status',
        'administered_by',
        'administered_at',
        'admin_notes',
        'administrations',
        'batch_id',
        'stock_movement_id',
        'quantity',
    ];

    protected $casts = [
        'administered_at' => 'datetime',
        'start_at' => 'datetime',
        'administrations' => 'array',
        'quantity' => 'integer',
        'interval_hours' => 'integer',
        'duration_value' => 'integer',
        'total_doses' => 'integer',
    ];

    public function surgery()
    {
        return $this->belongsTo(Surgery::class);
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function prescribedBy()
    {
        return $this->belongsTo(User::class, 'prescribed_by');
    }

    public function administeredBy()
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    public function stockMovement()
    {
        return $this->belongsTo(StockMovement::class, 'stock_movement_id');
    }

    public static function resolveFrequencyInput(?string $preset, ?string $timingText = null, ?int $customInterval = null): array
    {
        $preset = trim((string) $preset);
        $timingText = trim((string) $timingText);

        if ($preset !== '' && isset(self::FREQUENCY_PRESETS[$preset]) && $preset !== 'custom') {
            $meta = self::FREQUENCY_PRESETS[$preset];

            return [
                'frequency_mode' => $meta['mode'],
                'interval_hours' => $meta['interval_hours'],
                'timing' => $timingText !== '' ? $timingText : $meta['short'],
            ];
        }

        if ($customInterval && $customInterval > 0) {
            return [
                'frequency_mode' => self::FREQ_SCHEDULED,
                'interval_hours' => min(48, $customInterval),
                'timing' => $timingText !== '' ? $timingText : "كل {$customInterval} ساعات",
            ];
        }

        return self::inferFrequencyFromText($timingText);
    }

    public static function inferFrequencyFromText(?string $timing): array
    {
        $raw = trim((string) $timing);
        $lower = mb_strtolower($raw);

        if ($lower === '') {
            return [
                'frequency_mode' => self::FREQ_SCHEDULED,
                'interval_hours' => 24,
                'timing' => 'مرة يومياً',
            ];
        }

        if (str_contains($lower, 'prn') || str_contains($lower, 'عند اللزوم') || str_contains($lower, 'حسب الحاجة')) {
            return [
                'frequency_mode' => self::FREQ_PRN,
                'interval_hours' => null,
                'timing' => $raw,
            ];
        }

        if (str_contains($lower, 'stat') || str_contains($lower, 'فوري') || str_contains($lower, 'مرة واحدة') || str_contains($lower, 'جرعة واحدة')) {
            return [
                'frequency_mode' => self::FREQ_STAT,
                'interval_hours' => null,
                'timing' => $raw,
            ];
        }

        if (preg_match('/(?:every|q|كل)\s*(\d{1,2})\s*(?:h|hr|hrs|hour|hours|ساع|ساعات)/iu', $raw, $m)) {
            $hrs = max(1, min(48, (int) $m[1]));

            return [
                'frequency_mode' => self::FREQ_SCHEDULED,
                'interval_hours' => $hrs,
                'timing' => $raw,
            ];
        }

        if (preg_match('/\b1\s*[x×\*]\s*([1-6])\b/iu', $raw, $m)) {
            $perDay = (int) $m[1];
            $hrs = (int) max(1, round(24 / max(1, $perDay)));

            return [
                'frequency_mode' => self::FREQ_SCHEDULED,
                'interval_hours' => $hrs,
                'timing' => $raw,
            ];
        }

        if (str_contains($lower, '4 مرات') || str_contains($lower, 'أربع مرات') || str_contains($lower, 'qid')) {
            return ['frequency_mode' => self::FREQ_SCHEDULED, 'interval_hours' => 6, 'timing' => $raw];
        }
        if (str_contains($lower, '3 مرات') || str_contains($lower, 'ثلاث مرات') || str_contains($lower, 'tid')) {
            return ['frequency_mode' => self::FREQ_SCHEDULED, 'interval_hours' => 8, 'timing' => $raw];
        }
        if (str_contains($lower, 'مرتين') || str_contains($lower, 'مرتان') || str_contains($lower, 'bid')) {
            return ['frequency_mode' => self::FREQ_SCHEDULED, 'interval_hours' => 12, 'timing' => $raw];
        }
        if (str_contains($lower, 'يومياً') || str_contains($lower, 'يوميا') || str_contains($lower, 'مرة') || str_contains($lower, 'daily') || str_contains($lower, 'od')) {
            return ['frequency_mode' => self::FREQ_SCHEDULED, 'interval_hours' => 24, 'timing' => $raw];
        }

        return [
            'frequency_mode' => self::FREQ_CUSTOM,
            'interval_hours' => null,
            'timing' => $raw,
        ];
    }

    public static function calculateTotalDoses(string $mode, ?int $intervalHours, ?int $durationValue, ?string $durationUnit): ?int
    {
        if ($mode === self::FREQ_PRN) {
            return null;
        }

        if ($mode === self::FREQ_STAT) {
            return 1;
        }

        $unit = $durationUnit ?: 'days';
        $val = ($durationValue && $durationValue > 0) ? $durationValue : 1;

        if ($unit === 'doses') {
            return max(1, min(60, $val));
        }

        $interval = ($intervalHours && $intervalHours > 0) ? $intervalHours : 24;

        $totalHours = match ($unit) {
            'hours' => $val,
            'weeks' => $val * 7 * 24,
            'months' => $val * 30 * 24,
            default => $val * 24,
        };

        $doses = $unit === 'hours'
            ? (int) ceil($totalHours / $interval)
            : (int) floor($totalHours / $interval);

        return max(1, min(60, $doses));
    }

    public function resolveEffectiveFrequency(): array
    {
        if ($this->frequency_mode === self::FREQ_PRN || $this->frequency_mode === self::FREQ_STAT) {
            return [
                'frequency_mode' => $this->frequency_mode,
                'interval_hours' => $this->interval_hours,
                'timing' => $this->timing,
            ];
        }

        if ($this->interval_hours && $this->interval_hours > 0) {
            return [
                'frequency_mode' => $this->frequency_mode ?: self::FREQ_SCHEDULED,
                'interval_hours' => (int) $this->interval_hours,
                'timing' => $this->timing,
            ];
        }

        return self::inferFrequencyFromText($this->timing);
    }

    public function getExpectedDosesCountAttribute(): ?int
    {
        $freq = $this->resolveEffectiveFrequency();
        if ($freq['frequency_mode'] === self::FREQ_PRN) {
            return null;
        }

        if ($this->total_doses && $this->total_doses > 0) {
            return min(60, (int) $this->total_doses);
        }

        return self::calculateTotalDoses(
            $freq['frequency_mode'],
            $freq['interval_hours'],
            $this->duration_value,
            $this->duration_unit
        );
    }

    public function getNormalizedAdministrations(): array
    {
        $entries = is_array($this->administrations) ? $this->administrations : [];
        $normalized = [];

        foreach ($entries as $idx => $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $slotIndex = array_key_exists('slot_index', $entry) && $entry['slot_index'] !== null
                ? (int) $entry['slot_index']
                : (int) $idx;

            $entry['slot_index'] = $slotIndex;
            $normalized[] = $entry;
        }

        return $normalized;
    }

    public function getAdministeredCountAttribute(): int
    {
        return count($this->getNormalizedAdministrations());
    }

    public function getIsPrnAttribute(): bool
    {
        return $this->resolveEffectiveFrequency()['frequency_mode'] === self::FREQ_PRN;
    }

    public function getDurationLabelAttribute(): string
    {
        if (! $this->duration_value) {
            return $this->is_prn ? 'عند اللزوم' : ($this->resolveEffectiveFrequency()['frequency_mode'] === self::FREQ_STAT ? 'جرعة واحدة' : 'يوم واحد (افتراضي)');
        }

        $unitMap = [
            'days' => $this->duration_value == 1 ? 'يوم' : 'أيام',
            'weeks' => $this->duration_value == 1 ? 'أسبوع' : 'أسابيع',
            'months' => 'شهر',
            'hours' => $this->duration_value == 1 ? 'ساعة' : 'ساعات',
            'doses' => $this->duration_value == 1 ? 'جرعة' : 'جرعات',
        ];

        return $this->duration_value.' '.($unitMap[$this->duration_unit] ?? $this->duration_unit);
    }

    public function getDoseSlots(?User $viewer = null, bool $isAdmin = false): array
    {
        $freq = $this->resolveEffectiveFrequency();
        $entries = $this->getNormalizedAdministrations();
        $entriesBySlot = [];
        foreach ($entries as $entry) {
            $entriesBySlot[(int) $entry['slot_index']] = $entry;
        }

        $baseTime = ($this->start_at ?? $this->created_at ?? now())->copy();
        $interval = ($freq['interval_hours'] && $freq['interval_hours'] > 0) ? (int) $freq['interval_hours'] : 24;
        $now = now();

        if ($freq['frequency_mode'] === self::FREQ_PRN) {
            $slots = [];
            foreach ($entries as $idx => $entry) {
                $adminAt = ! empty($entry['administered_at']) ? Carbon::parse($entry['administered_at']) : null;
                $canUndo = $this->canUserUndoEntry($entry, $viewer, $isAdmin, $now);

                $slots[] = [
                    'slot_index' => (int) $entry['slot_index'],
                    'dose_number' => $idx + 1,
                    'day_number' => 1,
                    'scheduled_at' => $adminAt,
                    'scheduled_time_label' => $adminAt ? $this->formatArabicTime($adminAt) : '--',
                    'scheduled_date_label' => $adminAt ? $adminAt->format('m/d') : '',
                    'is_checked' => true,
                    'is_due' => false,
                    'is_overdue' => false,
                    'administered_at' => $adminAt,
                    'administered_at_label' => $adminAt ? $this->formatArabicTime($adminAt) : '',
                    'administered_by' => $entry['administered_by'] ?? null,
                    'administered_by_name' => $entry['administered_by_name'] ?? 'كادر التمريض',
                    'notes' => $entry['notes'] ?? null,
                    'can_undo' => $canUndo,
                ];
            }

            return $slots;
        }

        $expectedCount = max(1, (int) ($this->expected_doses_count ?? 1));
        $slots = [];

        for ($i = 0; $i < $expectedCount; $i++) {
            $scheduledAt = $freq['frequency_mode'] === self::FREQ_STAT
                ? $baseTime->copy()
                : $baseTime->copy()->addHours($i * $interval);

            $entry = $entriesBySlot[$i] ?? null;
            $isChecked = $entry !== null;
            $adminAt = ($entry && ! empty($entry['administered_at'])) ? Carbon::parse($entry['administered_at']) : null;
            $dayNum = $freq['frequency_mode'] === self::FREQ_STAT
                ? 1
                : max(1, (int) floor(($i * $interval) / 24) + 1);

            $slots[] = [
                'slot_index' => $i,
                'dose_number' => $i + 1,
                'day_number' => $dayNum,
                'scheduled_at' => $scheduledAt,
                'scheduled_time_label' => $this->formatArabicTime($scheduledAt),
                'scheduled_date_label' => $scheduledAt->format('m/d'),
                'is_checked' => $isChecked,
                'is_due' => ! $isChecked && $scheduledAt->lte($now),
                'is_overdue' => ! $isChecked && $scheduledAt->lte($now->copy()->subMinutes(30)),
                'administered_at' => $adminAt,
                'administered_at_label' => $adminAt ? $this->formatArabicTime($adminAt) : '',
                'administered_by' => $entry['administered_by'] ?? null,
                'administered_by_name' => $entry['administered_by_name'] ?? null,
                'notes' => $entry['notes'] ?? null,
                'can_undo' => $isChecked && $this->canUserUndoEntry($entry, $viewer, $isAdmin, $now),
            ];
        }

        return $slots;
    }

    private function canUserUndoEntry(array $entry, ?User $viewer, bool $isAdmin, Carbon $now): bool
    {
        if (! $viewer) {
            return false;
        }
        if ($isAdmin) {
            return true;
        }
        $sameOwner = isset($entry['administered_by']) && (int) $entry['administered_by'] === (int) $viewer->id;
        if (! $sameOwner || empty($entry['administered_at'])) {
            return false;
        }

        return $now->lte(Carbon::parse($entry['administered_at'])->addMinutes(30));
    }

    private function formatArabicTime(Carbon $dt): string
    {
        $suffix = $dt->format('A') === 'AM' ? 'ص' : 'م';

        return $dt->format('h:i').' '.$suffix;
    }

    public function toggleDoseSlot(?int $slotIndex, User $user, bool $checked = true, ?string $notes = null, bool $isAdmin = false): array
    {
        $entries = $this->getNormalizedAdministrations();
        $userName = $user->full_name ?? $user->name ?? 'كادر التمريض';
        $freq = $this->resolveEffectiveFrequency();
        $baseTime = ($this->start_at ?? $this->created_at ?? now())->copy();
        $interval = ($freq['interval_hours'] && $freq['interval_hours'] > 0) ? (int) $freq['interval_hours'] : 24;

        if ($slotIndex === null || $slotIndex < 0) {
            $usedIndices = array_column($entries, 'slot_index');
            if ($this->is_prn) {
                $slotIndex = empty($usedIndices) ? 0 : (max($usedIndices) + 1);
            } else {
                $expected = max(1, (int) ($this->expected_doses_count ?? 1));
                $slotIndex = 0;
                for ($i = 0; $i < $expected; $i++) {
                    if (! in_array($i, $usedIndices, true)) {
                        $slotIndex = $i;
                        break;
                    }
                }
            }
        }

        if ($checked) {
            foreach ($entries as $existing) {
                if ((int) $existing['slot_index'] === (int) $slotIndex) {
                    return [
                        'ok' => true,
                        'already' => true,
                        'message' => 'هذه الجرعة موثقة ومؤشر عليها مسبقاً.',
                        'slot_index' => $slotIndex,
                    ];
                }
            }

            $scheduledAt = ($this->is_prn || $freq['frequency_mode'] === self::FREQ_STAT)
                ? now()
                : $baseTime->copy()->addHours($slotIndex * $interval);

            $now = now();
            $entries[] = [
                'slot_index' => (int) $slotIndex,
                'scheduled_at' => $scheduledAt->format('Y-m-d H:i'),
                'administered_at' => $now->format('Y-m-d H:i'),
                'administered_by' => $user->id,
                'administered_by_name' => $userName,
                'notes' => $notes,
            ];

            usort($entries, fn ($a, $b) => ((int) $a['slot_index']) <=> ((int) $b['slot_index']));

            $this->update([
                'status' => 'administered',
                'administrations' => array_values($entries),
                'administered_by' => $user->id,
                'administered_at' => $now,
                'admin_notes' => $notes ?? $this->admin_notes,
            ]);

            return [
                'ok' => true,
                'checked' => true,
                'slot_index' => $slotIndex,
                'administered_at_label' => $this->formatArabicTime($now),
                'administered_by_name' => $userName,
                'message' => 'تم تأكيد الصرف وتوثيق تنفيذ الجرعة بنجاح.',
            ];
        }

        // Uncheck / undo dose
        $foundIndex = null;
        $foundEntry = null;
        foreach ($entries as $idx => $entry) {
            if ((int) $entry['slot_index'] === (int) $slotIndex) {
                $foundIndex = $idx;
                $foundEntry = $entry;
                break;
            }
        }

        if ($foundIndex === null || ! $foundEntry) {
            return [
                'ok' => true,
                'checked' => false,
                'slot_index' => $slotIndex,
                'message' => 'الجرعة غير مؤشرة مسبقاً.',
            ];
        }

        if (! $this->canUserUndoEntry($foundEntry, $user, $isAdmin, now())) {
            return [
                'ok' => false,
                'message' => 'لا يمكن التراجع عن تأشير هذه الجرعة لانقضاء مهلة 30 دقيقة أو لأنها مسجلة بحساب ممرض آخر.',
            ];
        }

        unset($entries[$foundIndex]);
        $entries = array_values($entries);

        if (empty($entries)) {
            $this->update([
                'status' => 'planned',
                'administrations' => [],
                'administered_by' => null,
                'administered_at' => null,
            ]);
        } else {
            $latest = end($entries);
            $this->update([
                'status' => 'administered',
                'administrations' => $entries,
                'administered_by' => $latest['administered_by'] ?? $this->administered_by,
                'administered_at' => ! empty($latest['administered_at']) ? Carbon::parse($latest['administered_at']) : $this->administered_at,
            ]);
        }

        return [
            'ok' => true,
            'checked' => false,
            'slot_index' => $slotIndex,
            'message' => 'تم التراجع عن تأشير الجرعة بنجاح.',
        ];
    }

    public function logAdministration($userId, $userName, $notes = null)
    {
        $user = User::find($userId);
        if ($user) {
            $this->toggleDoseSlot(null, $user, true, $notes, false);

            return;
        }

        $entries = $this->getNormalizedAdministrations();
        $usedIndices = array_column($entries, 'slot_index');
        $slotIndex = empty($usedIndices) ? 0 : (max($usedIndices) + 1);
        $entries[] = [
            'slot_index' => $slotIndex,
            'administered_at' => now()->format('Y-m-d H:i'),
            'administered_by' => $userId,
            'administered_by_name' => $userName,
            'notes' => $notes,
        ];
        $this->update([
            'status' => 'administered',
            'administrations' => $entries,
            'administered_by' => $userId,
            'administered_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    public function isSupply(): bool
    {
        return $this->item_type === self::TYPE_SUPPLY;
    }

    public function isMedication(): bool
    {
        return $this->item_type !== self::TYPE_SUPPLY;
    }
}
