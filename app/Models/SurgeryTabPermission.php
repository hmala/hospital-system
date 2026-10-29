<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurgeryTabPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_name',
        'tab_key',
        'access_level',
    ];

    public const ACCESS_HIDDEN = 'hidden';

    public const ACCESS_READ = 'read';

    public const ACCESS_WRITE = 'write';

    public const TABS = [
        'history' => [
            'key' => 'history',
            'label' => 'السيرة المرضية',
            'icon' => 'fas fa-history',
            'color' => 'secondary',
            'description' => 'الشكوى الرئيسية، التاريخ المرضي والجراحي، والتاريخ الدوائي والحساسية',
        ],
        'vitals' => [
            'key' => 'vitals',
            'label' => 'العلامات الحيوية والفحص',
            'icon' => 'fas fa-heartbeat',
            'color' => 'danger',
            'description' => 'تسجيل ورصد الضغط، الحرارة، النبض، التنفس، الأكسجين، السكر، والمنحنيات البيانية',
        ],
        'tests' => [
            'key' => 'tests',
            'label' => 'الفحوصات والمختبر والأشعة',
            'icon' => 'fas fa-vials',
            'color' => 'primary',
            'description' => 'نتائج وتفاصيل تحاليل المختبر والفحوصات الإشعاعية للمريض',
        ],
        'plan' => [
            'key' => 'plan',
            'label' => 'الخطة الطبية وتوصيات الجراحة',
            'icon' => 'fas fa-notes-medical',
            'color' => 'info',
            'description' => 'توصيات الجراح والمقيم، مراجعة الأجهزة الحيوية، وخطة ما بعد الجراحة',
        ],
        'treatments' => [
            'key' => 'treatments',
            'label' => 'الأدوية وجدول الإعطاء',
            'icon' => 'fas fa-pills',
            'color' => 'warning',
            'description' => 'إضافة الأدوية الموصوفة وتسجيل إعطائها الفعلي ومواعيدها للمريض',
        ],
        'follow_up' => [
            'key' => 'follow_up',
            'label' => 'سجل المتابعة اليومي',
            'icon' => 'fas fa-calendar-check',
            'color' => 'success',
            'description' => 'سجل مرور ومتابعة الأطباء والتمريض وملاحظات الحالة الدورية',
        ],
        'fluids' => [
            'key' => 'fluids',
            'label' => 'رصد السوائل (Intake & Output)',
            'icon' => 'fas fa-tint',
            'color' => 'info',
            'description' => 'ميزان السوائل الداخلة والخارجة للمريض بالتفصيل واحتساب الصافي آلياً',
        ],
        'nursing_care' => [
            'key' => 'nursing_care',
            'label' => 'الرعاية التمريضية والتخريج',
            'icon' => 'fas fa-user-nurse',
            'color' => 'purple',
            'description' => 'ملاحظات كادر التمريض وخطة التخريج والتعليمات التمريضية للمريض',
        ],
    ];

    /**
     * Default role-tab access permission matrix.
     */
    public static function defaultMatrix(): array
    {
        return [
            'admin' => [
                'history' => self::ACCESS_WRITE,
                'vitals' => self::ACCESS_WRITE,
                'tests' => self::ACCESS_WRITE,
                'plan' => self::ACCESS_WRITE,
                'treatments' => self::ACCESS_WRITE,
                'follow_up' => self::ACCESS_WRITE,
                'fluids' => self::ACCESS_WRITE,
                'nursing_care' => self::ACCESS_WRITE,
            ],
            'doctor' => [
                'history' => self::ACCESS_WRITE,
                'vitals' => self::ACCESS_WRITE,
                'tests' => self::ACCESS_WRITE,
                'plan' => self::ACCESS_WRITE,
                'treatments' => self::ACCESS_WRITE,
                'follow_up' => self::ACCESS_WRITE,
                'fluids' => self::ACCESS_READ,
                'nursing_care' => self::ACCESS_READ,
            ],
            'resident' => [
                'history' => self::ACCESS_WRITE,
                'vitals' => self::ACCESS_WRITE,
                'tests' => self::ACCESS_WRITE,
                'plan' => self::ACCESS_WRITE,
                'treatments' => self::ACCESS_WRITE,
                'follow_up' => self::ACCESS_WRITE,
                'fluids' => self::ACCESS_READ,
                'nursing_care' => self::ACCESS_READ,
            ],
            'nurse' => [
                'history' => self::ACCESS_READ,
                'vitals' => self::ACCESS_WRITE,
                'tests' => self::ACCESS_READ,
                'plan' => self::ACCESS_READ,
                'treatments' => self::ACCESS_WRITE,
                'follow_up' => self::ACCESS_WRITE,
                'fluids' => self::ACCESS_WRITE,
                'nursing_care' => self::ACCESS_WRITE,
            ],
            'surgery_coordinator' => [
                'history' => self::ACCESS_READ,
                'vitals' => self::ACCESS_READ,
                'tests' => self::ACCESS_READ,
                'plan' => self::ACCESS_READ,
                'treatments' => self::ACCESS_READ,
                'follow_up' => self::ACCESS_READ,
                'fluids' => self::ACCESS_READ,
                'nursing_care' => self::ACCESS_READ,
            ],
        ];
    }

    /**
     * Seed default tab permissions if not set.
     */
    public static function seedDefaults(): void
    {
        $matrix = self::defaultMatrix();
        foreach ($matrix as $roleName => $tabs) {
            foreach ($tabs as $tabKey => $level) {
                self::updateOrCreate(
                    ['role_name' => $roleName, 'tab_key' => $tabKey],
                    ['access_level' => $level]
                );
            }
        }
    }

    /**
     * Get computed access levels for all tabs for a given user.
     * Returns associative array [tabKey => 'hidden'|'read'|'write']
     */
    public static function getUserTabPermissions(?User $user): array
    {
        $result = [];
        $tabKeys = array_keys(self::TABS);

        if (! $user) {
            foreach ($tabKeys as $key) {
                $result[$key] = self::ACCESS_HIDDEN;
            }

            return $result;
        }

        // Admins and Super Admins always have write access
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            foreach ($tabKeys as $key) {
                $result[$key] = self::ACCESS_WRITE;
            }

            return $result;
        }

        $userRoles = $user->roles->pluck('name')->toArray();
        if (empty($userRoles)) {
            $userRoles = ['nurse']; // default fallback
        }

        // Load permissions for user roles
        $permissions = self::whereIn('role_name', $userRoles)->get();

        // If table is completely empty, seed defaults on the fly
        if (self::count() === 0) {
            self::seedDefaults();
            $permissions = self::whereIn('role_name', $userRoles)->get();
        }

        $weights = [
            self::ACCESS_HIDDEN => 0,
            self::ACCESS_READ => 1,
            self::ACCESS_WRITE => 2,
        ];

        foreach ($tabKeys as $tabKey) {
            $maxWeight = -1;
            $bestLevel = self::ACCESS_HIDDEN;

            foreach ($userRoles as $role) {
                $perm = $permissions->where('role_name', $role)->where('tab_key', $tabKey)->first();
                if ($perm) {
                    $level = $perm->access_level;
                } else {
                    // Check default matrix fallback
                    $defaultMatrix = self::defaultMatrix();
                    $level = $defaultMatrix[$role][$tabKey] ?? self::ACCESS_READ;
                }

                $w = $weights[$level] ?? 0;
                if ($w > $maxWeight) {
                    $maxWeight = $w;
                    $bestLevel = $level;
                }
            }

            $result[$tabKey] = $bestLevel;
        }

        return $result;
    }

    /**
     * Get single tab access level for a user.
     */
    public static function getAccessForUser(?User $user, string $tabKey): string
    {
        $permissions = self::getUserTabPermissions($user);

        return $permissions[$tabKey] ?? self::ACCESS_HIDDEN;
    }
}
