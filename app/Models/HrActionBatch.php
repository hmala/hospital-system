<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrActionBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope',
        'department_id',
        'hr_action_setting_id',
        'action_date',
        'reason',
        'target_count',
        'status',
        'created_by',
        'cancellable_until',
        'cancel_requested_by',
        'cancel_requested_at',
        'cancel_request_reason',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'action_date' => 'date',
            'cancellable_until' => 'datetime',
            'cancel_requested_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $batch) {
            if (! $batch->cancellable_until) {
                $batch->cancellable_until = now()->addHours(48);
            }
        });
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function actionSetting()
    {
        return $this->belongsTo(HrActionSetting::class, 'hr_action_setting_id');
    }

    public function actions()
    {
        return $this->hasMany(HrEmployeeAction::class, 'hr_action_batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelRequester()
    {
        return $this->belongsTo(User::class, 'cancel_requested_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isWithin48Hours(): bool
    {
        $deadline = $this->cancellable_until ?? $this->created_at?->copy()->addHours(48);

        return $deadline ? now()->lte($deadline) : false;
    }

    public function hasPendingCancellationRequest(): bool
    {
        return ! is_null($this->cancel_requested_at) && $this->status !== 'cancelled';
    }

    public function getScopeLabelAttribute(): string
    {
        return match ($this->scope) {
            'hospital' => 'كل المستشفى',
            'department' => 'قسم محدد: '.($this->department?->name ?? '—'),
            'selected' => 'موظفون محددون',
            default => $this->scope,
        };
    }

    public function refreshStatusFromActions(): void
    {
        $total = $this->actions()->count();
        if ($total === 0) {
            return;
        }

        $cancelled = $this->actions()->where('status', 'cancelled')->count();

        if ($cancelled === 0) {
            $this->update(['status' => 'active']);
        } elseif ($cancelled === $total) {
            $this->update(['status' => 'cancelled']);
        } else {
            $this->update(['status' => 'partially_cancelled']);
        }
    }
}
