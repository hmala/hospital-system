<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserActivityLogger
{
    /**
     * Log a user action.
     *
     * @param string $action (e.g. login, create, update, delete, payment, consultation)
     * @param string $category (auth, reception, cashier, doctor, lab, radiology, pharmacy, treasury, surgery, emergency, security)
     * @param string $description (Human-readable Arabic text)
     * @param Model|null $subject (Related Eloquent model)
     * @param bool $isSensitive (Whether this is a high-risk or auditing action)
     * @param array|null $properties (Additional context/metadata)
     * @param User|null $user (Explicit user, defaults to Auth::user())
     */
    public static function log(
        string $action,
        string $category,
        string $description,
        ?Model $subject = null,
        bool $isSensitive = false,
        ?array $properties = null,
        ?User $user = null
    ): ?UserActivity {
        try {
            $currentUser = $user ?? Auth::user();

            $userName = $currentUser?->name ?? 'النظام / غير مسجل';
            $roleName = $currentUser?->roles?->first()?->name ?? 'مستخدم';

            // منع تكرار نفس حركة الدخول أو الخروج لنفس المستخدم خلال ثانيتين
            if ($currentUser && in_array($action, ['login', 'logout'])) {
                $recentlyLogged = UserActivity::where('user_id', $currentUser->id)
                    ->where('action', $action)
                    ->where('created_at', '>=', now()->subSeconds(2))
                    ->exists();
                if ($recentlyLogged) {
                    return null;
                }
            }

            $activity = UserActivity::create([
                'user_id' => $currentUser?->id,
                'user_name' => $userName,
                'role_name' => $roleName,
                'action' => $action,
                'category' => $category,
                'description' => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject?->getKey(),
                'properties' => $properties,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'is_sensitive' => $isSensitive,
            ]);

            // تحديث وقت آخر نشاط للمستخدم (إلا إذا كانت الحركة تسجيل خروج)
            if ($currentUser && $action !== 'logout') {
                $currentUser->forceFill([
                    'last_activity_at' => now(),
                ])->saveQuietly();
            }

            return $activity;
        } catch (\Throwable $e) {
            Log::warning('UserActivityLogger failed to record activity: ' . $e->getMessage());
            return null;
        }
    }
}
