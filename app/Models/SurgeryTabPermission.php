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

    public const CORE_CLINICAL_ROLES = [
        'resident',
        'nurse',
        'doctor',
        'anesthesia',
        'surgery_coordinator',
        'surgery_staff',
        'admin',
    ];

    public const CORE_DETAIL_ROLES = [
        'anesthesia',
        'doctor',
        'surgery_staff',
        'resident',
        'nurse',
        'surgery_coordinator',
        'receptionist',
        'admin',
    ];

    public const EXCLUDED_CONFIGURABLE_ROLES = [
        'patient',
        'admin',
        'admin-hsop',
        'hospital_admin',
        'super_admin',
        'الجراح',
        'جراح',
        'التخدير',
    ];

    public static function knownRoleLabel(string $roleName): string
    {
        $labels = [
            'resident' => 'الطبيب المقيم (Resident)',
            'nurse' => 'كادر التمريض (Nurse)',
            'doctor' => 'الطبيب الجراح / الأخصائي (Surgeon/Doctor)',
            'anesthesia' => 'طبيب / حساب التخدير (Anesthesiologist)',
            'surgery_coordinator' => 'منسق العمليات (Surgery Coordinator)',
            'surgery_staff' => 'موظف / كادر العمليات (Surgery Staff)',
            'receptionist' => 'الاستقبال (Receptionist)',
            'admin' => 'مدير النظام (Administrator)',
            'emergency_staff' => 'كادر / محطة الطوارئ (Emergency Staff)',
            'icu_doctor' => 'طبيب العناية المركزة (ICU Doctor)',
            'icu_nurse' => 'تمريض العناية المركزة (ICU Nurse)',
            'nicu_doctor' => 'طبيب الخدج (NICU Doctor)',
            'nicu_nurse' => 'تمريض الخدج (NICU Nurse)',
            'NICU' => 'محطة الخدج (NICU)',
            'lab_staff' => 'كادر المختبر (Lab Staff)',
            'radiology_staff' => 'كادر الأشعة (Radiology Staff)',
            'radiology_general' => 'الأشعة العامة (General Radiology)',
            'radiology_ultrasound' => 'السونار (Ultrasound)',
            'radiology_mri' => 'الرنين المغناطيسي (MRI)',
            'radiology_echo' => 'الإيكو (Echo)',
            'pharmacy_staff' => 'كادر الصيدلية (Pharmacy Staff)',
            'inquiry_staff' => 'كادر الاستعلامات (Inquiry Staff)',
            'consultation_receptionist' => 'استعلامات الاستشارية',
            'inventory_manager' => 'إدارة المخزن (Inventory Manager)',
            'cashier' => 'الكاشير (Cashier)',
            'casshieradmin' => 'مسؤول الكاشير',
            'accountant' => 'المحاسب (Accountant)',
            'hr_manager' => 'إدارة الموارد البشرية (HR)',
            'staff' => 'كادر عام (Staff)',
        ];

        return $labels[$roleName] ?? $roleName;
    }

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
            'label' => 'العلامات الحيوية والفحص والسوائل',
            'icon' => 'fas fa-heartbeat',
            'color' => 'danger',
            'description' => 'تسجيل ورصد الضغط، الحرارة، النبض، التنفس، الأكسجين، السكر، السوائل (Intake & Output)، والمنحنيات البيانية',
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
            'description' => 'وصف الأدوية والمغذيات وتسجيل جدول الإعطاء الفعلي ومواعيدها للمريض',
        ],
        'supplies' => [
            'key' => 'supplies',
            'label' => 'المستلزمات الطبية',
            'icon' => 'fas fa-box-open',
            'color' => 'info',
            'description' => 'صرف وتوثيق المستلزمات الطبية والضمادات والمستهلكات من عهدة الطابق لحساب المريض',
        ],
        'follow_up' => [
            'key' => 'follow_up',
            'label' => 'سجل المتابعة اليومي',
            'icon' => 'fas fa-calendar-check',
            'color' => 'success',
            'description' => 'سجل مرور ومتابعة الأطباء والتمريض وملاحظات الحالة الدورية',
        ],
        'nursing_care' => [
            'key' => 'nursing_care',
            'label' => 'الرعاية التمريضية والتخريج',
            'icon' => 'fas fa-user-nurse',
            'color' => 'purple',
            'description' => 'ملاحظات كادر التمريض وخطة التخريج والتعليمات التمريضية للمريض',
        ],
        'inpatient_survey' => [
            'key' => 'inpatient_survey',
            'label' => 'استبيان تقييم الرقود',
            'icon' => 'fas fa-star',
            'color' => 'warning',
            'description' => 'استبيان تقييم غرفة الرقود وجودة الخدمة والرعاية برمز QR وهاتف المريض',
        ],
    ];

    /**
     * Tabs for the Unified Surgery File / Theater Screen (/surgeries/{id}).
     */
    public const DETAIL_TABS = [
        'detail_tests' => [
            'key' => 'detail_tests',
            'label' => 'الفحوصات والتحاليل',
            'icon' => 'fas fa-vials',
            'color' => 'primary',
            'description' => 'عرض نتائج وتقارير التحاليل المخبرية والأشعة للعملية والزيارة',
        ],
        'detail_type' => [
            'key' => 'detail_type',
            'label' => 'نوع العملية والإضافات',
            'icon' => 'fas fa-procedures',
            'color' => 'info',
            'description' => 'تعديل نوع العملية الرئيسية وإضافة العمليات التكميلية وسجل التغييرات',
        ],
        'detail_diagnosis' => [
            'key' => 'detail_diagnosis',
            'label' => 'التشخيص وخطة العلاج',
            'icon' => 'fas fa-stethoscope',
            'color' => 'primary',
            'description' => 'التشخيص الطبي للجراح وجدول الأدوية والعلاجات الموصوفة للعملية',
        ],
        'detail_anesthesia' => [
            'key' => 'detail_anesthesia',
            'label' => 'تفاصيل التخدير',
            'icon' => 'fas fa-syringe',
            'color' => 'success',
            'description' => 'توثيق نوع التخدير، أطباء التخدير، أوقات التخدير وملاحظات طبيب التخدير',
        ],
        'detail_team_timing' => [
            'key' => 'detail_team_timing',
            'label' => 'الفريق الطبي والتوقيت',
            'icon' => 'fas fa-users-cog',
            'color' => 'warning',
            'description' => 'تحديد صالة العمليات/الردهة والفريق الجراحي المساعد وتوقيت ومدة العملية',
        ],
        'detail_supplies' => [
            'key' => 'detail_supplies',
            'label' => 'المستلزمات والأجهزة',
            'icon' => 'fas fa-box-open',
            'color' => 'info',
            'description' => 'تسجيل المستلزمات الطبية المطلوبة وربط الأجهزة الطبية المستخدمة في العملية',
        ],
        'detail_followups' => [
            'key' => 'detail_followups',
            'label' => 'المتابعات',
            'icon' => 'fas fa-clipboard-list',
            'color' => 'secondary',
            'description' => 'سجل مرور ومتابعات الأطباء المقيمين الخاصة بالعملية',
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
                'supplies' => self::ACCESS_WRITE,
                'follow_up' => self::ACCESS_WRITE,
                'nursing_care' => self::ACCESS_WRITE,
                'inpatient_survey' => self::ACCESS_WRITE,
            ],
            'doctor' => [
                'history' => self::ACCESS_WRITE,
                'vitals' => self::ACCESS_WRITE,
                'tests' => self::ACCESS_WRITE,
                'plan' => self::ACCESS_WRITE,
                'treatments' => self::ACCESS_WRITE,
                'supplies' => self::ACCESS_READ,
                'follow_up' => self::ACCESS_WRITE,
                'nursing_care' => self::ACCESS_READ,
                'inpatient_survey' => self::ACCESS_READ,
            ],
            'resident' => [
                'history' => self::ACCESS_WRITE,
                'vitals' => self::ACCESS_WRITE,
                'tests' => self::ACCESS_WRITE,
                'plan' => self::ACCESS_WRITE,
                'treatments' => self::ACCESS_WRITE,
                'supplies' => self::ACCESS_READ,
                'follow_up' => self::ACCESS_WRITE,
                'nursing_care' => self::ACCESS_READ,
                'inpatient_survey' => self::ACCESS_READ,
            ],
            'nurse' => [
                'history' => self::ACCESS_READ,
                'vitals' => self::ACCESS_WRITE,
                'tests' => self::ACCESS_READ,
                'plan' => self::ACCESS_READ,
                'treatments' => self::ACCESS_WRITE,
                'supplies' => self::ACCESS_WRITE,
                'follow_up' => self::ACCESS_WRITE,
                'nursing_care' => self::ACCESS_WRITE,
                'inpatient_survey' => self::ACCESS_WRITE,
            ],
            'surgery_coordinator' => [
                'history' => self::ACCESS_READ,
                'vitals' => self::ACCESS_READ,
                'tests' => self::ACCESS_READ,
                'plan' => self::ACCESS_READ,
                'treatments' => self::ACCESS_READ,
                'supplies' => self::ACCESS_READ,
                'follow_up' => self::ACCESS_READ,
                'nursing_care' => self::ACCESS_READ,
                'inpatient_survey' => self::ACCESS_WRITE,
            ],
            'anesthesia' => [
                'history' => self::ACCESS_READ,
                'vitals' => self::ACCESS_READ,
                'tests' => self::ACCESS_READ,
                'plan' => self::ACCESS_READ,
                'treatments' => self::ACCESS_READ,
                'supplies' => self::ACCESS_READ,
                'follow_up' => self::ACCESS_READ,
                'nursing_care' => self::ACCESS_READ,
                'inpatient_survey' => self::ACCESS_READ,
            ],
            'surgery_staff' => [
                'history' => self::ACCESS_READ,
                'vitals' => self::ACCESS_READ,
                'tests' => self::ACCESS_READ,
                'plan' => self::ACCESS_READ,
                'treatments' => self::ACCESS_READ,
                'supplies' => self::ACCESS_READ,
                'follow_up' => self::ACCESS_READ,
                'nursing_care' => self::ACCESS_READ,
                'inpatient_survey' => self::ACCESS_READ,
            ],
        ];
    }

    /**
     * Default role-tab access permission matrix for Surgery Details (/surgeries/{id}).
     */
    public static function detailDefaultMatrix(): array
    {
        return [
            'admin' => [
                'detail_tests' => self::ACCESS_WRITE,
                'detail_type' => self::ACCESS_WRITE,
                'detail_diagnosis' => self::ACCESS_WRITE,
                'detail_anesthesia' => self::ACCESS_WRITE,
                'detail_team_timing' => self::ACCESS_WRITE,
                'detail_supplies' => self::ACCESS_WRITE,
                'detail_followups' => self::ACCESS_WRITE,
            ],
            'anesthesia' => [
                'detail_tests' => self::ACCESS_READ,
                'detail_type' => self::ACCESS_READ,
                'detail_diagnosis' => self::ACCESS_READ,
                'detail_anesthesia' => self::ACCESS_WRITE,
                'detail_team_timing' => self::ACCESS_READ,
                'detail_supplies' => self::ACCESS_READ,
                'detail_followups' => self::ACCESS_READ,
            ],
            'doctor' => [
                'detail_tests' => self::ACCESS_READ,
                'detail_type' => self::ACCESS_WRITE,
                'detail_diagnosis' => self::ACCESS_WRITE,
                'detail_anesthesia' => self::ACCESS_READ,
                'detail_team_timing' => self::ACCESS_READ,
                'detail_supplies' => self::ACCESS_READ,
                'detail_followups' => self::ACCESS_READ,
            ],
            'surgery_staff' => [
                'detail_tests' => self::ACCESS_READ,
                'detail_type' => self::ACCESS_WRITE,
                'detail_diagnosis' => self::ACCESS_READ,
                'detail_anesthesia' => self::ACCESS_READ,
                'detail_team_timing' => self::ACCESS_WRITE,
                'detail_supplies' => self::ACCESS_WRITE,
                'detail_followups' => self::ACCESS_READ,
            ],
            'resident' => [
                'detail_tests' => self::ACCESS_READ,
                'detail_type' => self::ACCESS_READ,
                'detail_diagnosis' => self::ACCESS_READ,
                'detail_anesthesia' => self::ACCESS_READ,
                'detail_team_timing' => self::ACCESS_HIDDEN,
                'detail_supplies' => self::ACCESS_READ,
                'detail_followups' => self::ACCESS_READ,
            ],
            'nurse' => [
                'detail_tests' => self::ACCESS_READ,
                'detail_type' => self::ACCESS_READ,
                'detail_diagnosis' => self::ACCESS_READ,
                'detail_anesthesia' => self::ACCESS_READ,
                'detail_team_timing' => self::ACCESS_HIDDEN,
                'detail_supplies' => self::ACCESS_READ,
                'detail_followups' => self::ACCESS_READ,
            ],
            'surgery_coordinator' => [
                'detail_tests' => self::ACCESS_READ,
                'detail_type' => self::ACCESS_READ,
                'detail_diagnosis' => self::ACCESS_READ,
                'detail_anesthesia' => self::ACCESS_READ,
                'detail_team_timing' => self::ACCESS_READ,
                'detail_supplies' => self::ACCESS_READ,
                'detail_followups' => self::ACCESS_READ,
            ],
            'receptionist' => [
                'detail_tests' => self::ACCESS_READ,
                'detail_type' => self::ACCESS_READ,
                'detail_diagnosis' => self::ACCESS_HIDDEN,
                'detail_anesthesia' => self::ACCESS_HIDDEN,
                'detail_team_timing' => self::ACCESS_READ,
                'detail_supplies' => self::ACCESS_HIDDEN,
                'detail_followups' => self::ACCESS_HIDDEN,
            ],
        ];
    }

    /**
     * Determine whether the given user acts as an Anesthesiologist account.
     */
    public static function isAnesthesiaUser(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole(['التخدير', 'anesthesia', 'Anesthesia']) || in_array(strtolower(trim((string) $user->role)), ['anesthesia', 'anesthesiologist', 'التخدير'], true)) {
            return true;
        }

        if ($user->doctor) {
            $type = strtolower(trim((string) $user->doctor->type));
            $spec = trim((string) $user->doctor->specialization);

            if (in_array($type, ['anesthesiologist', 'anesthesia'], true) || str_contains($spec, 'تخدير')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve effective matrix role keys for /surgeries/{id} permissions.
     */
    public static function resolveUserRolesForDetail(?User $user): array
    {
        return self::normalizeMatrixRoles($user, 'receptionist');
    }

    /**
     * Resolve effective matrix role keys for Unified Clinical Station (/surgery-stations/clinical/{id}).
     */
    public static function resolveUserRolesForClinical(?User $user): array
    {
        return self::normalizeMatrixRoles($user, 'nurse');
    }

    /**
     * Normalize user Spatie roles (plus Arabic/doctor aliases) into canonical matrix role keys.
     */
    protected static function normalizeMatrixRoles(?User $user, string $emptyFallback): array
    {
        if (! $user) {
            return [];
        }

        $rawRoles = $user->roles->pluck('name')->toArray();
        if (empty($rawRoles) && ! empty($user->role) && strtolower(trim((string) $user->role)) !== 'patient') {
            $rawRoles = [trim((string) $user->role)];
        }

        $normalized = [];

        if (self::isAnesthesiaUser($user)) {
            foreach ($rawRoles as $roleName) {
                $trimmed = trim((string) $roleName);
                if (in_array(strtolower($trimmed), ['doctor', 'surgeon', 'anesthesia', 'anesthesiologist', 'patient'], true)
                    || in_array($trimmed, ['الجراح', 'جراح', 'التخدير'], true)) {
                    continue;
                }
                $normalized[] = $trimmed;
            }
            $normalized[] = 'anesthesia';
        } else {
            foreach ($rawRoles as $roleName) {
                $trimmed = trim((string) $roleName);
                $lower = strtolower($trimmed);

                if ($lower === 'patient') {
                    continue;
                }

                if (in_array($trimmed, ['الجراح', 'جراح'], true) || $lower === 'surgeon') {
                    $normalized[] = 'doctor';
                } elseif ($trimmed === 'التخدير' || in_array($lower, ['anesthesia', 'anesthesiologist'], true)) {
                    $normalized[] = 'anesthesia';
                } elseif ($trimmed === 'مقيم') {
                    $normalized[] = 'resident';
                } elseif (in_array($trimmed, ['ممرض', 'تمريض'], true)) {
                    $normalized[] = 'nurse';
                } else {
                    $normalized[] = $trimmed;
                }
            }
        }

        $normalized = array_values(array_unique(array_filter($normalized, fn (string $r) => $r !== '')));

        if (empty($normalized)) {
            return [$emptyFallback];
        }

        return $normalized;
    }

    /**
     * Resolve effective per-tab access levels without letting unmapped roles override explicit 'hidden'.
     */
    protected static function resolveEffectiveTabAccess(
        array $userRoles,
        array $tabKeys,
        \Illuminate\Support\Collection $permissions,
        array $defaultMatrix
    ): array {
        $result = [];
        $weights = [
            self::ACCESS_HIDDEN => 0,
            self::ACCESS_READ => 1,
            self::ACCESS_WRITE => 2,
        ];

        foreach ($tabKeys as $tabKey) {
            $maxWeight = -1;
            $bestLevel = self::ACCESS_HIDDEN;
            $hasConfiguredRule = false;

            foreach ($userRoles as $role) {
                $perm = $permissions->where('role_name', $role)->where('tab_key', $tabKey)->first();
                if ($perm) {
                    $level = $perm->access_level;
                    $hasConfiguredRule = true;
                } elseif (isset($defaultMatrix[$role]) && array_key_exists($tabKey, $defaultMatrix[$role])) {
                    $level = $defaultMatrix[$role][$tabKey];
                    $hasConfiguredRule = true;
                } else {
                    continue;
                }

                $w = $weights[$level] ?? 0;
                if ($w > $maxWeight) {
                    $maxWeight = $w;
                    $bestLevel = $level;
                }
            }

            $result[$tabKey] = $hasConfiguredRule ? $bestLevel : self::ACCESS_READ;
        }

        return $result;
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

        $detailMatrix = self::detailDefaultMatrix();
        foreach ($detailMatrix as $roleName => $tabs) {
            foreach ($tabs as $tabKey => $level) {
                self::updateOrCreate(
                    ['role_name' => $roleName, 'tab_key' => $tabKey],
                    ['access_level' => $level]
                );
            }
        }
    }

    /**
     * Get computed access levels for /surgeries/{id} tabs for a given user.
     */
    public static function getDetailTabPermissions(?User $user): array
    {
        $result = [];
        $tabKeys = array_keys(self::DETAIL_TABS);

        if (! $user) {
            foreach ($tabKeys as $key) {
                $result[$key] = self::ACCESS_HIDDEN;
            }

            return $result;
        }

        if ($user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin'])) {
            foreach ($tabKeys as $key) {
                $result[$key] = self::ACCESS_WRITE;
            }

            return $result;
        }

        $userRoles = self::resolveUserRolesForDetail($user);
        $permissions = self::whereIn('role_name', $userRoles)
            ->whereIn('tab_key', $tabKeys)
            ->get();

        return self::resolveEffectiveTabAccess(
            $userRoles,
            $tabKeys,
            $permissions,
            self::detailDefaultMatrix()
        );
    }

    /**
     * Get single /surgeries/{id} tab access level for a user.
     */
    public static function getDetailAccessForUser(?User $user, string $tabKey): string
    {
        $permissions = self::getDetailTabPermissions($user);

        return $permissions[$tabKey] ?? self::ACCESS_HIDDEN;
    }

    /**
     * Determine whether an extra role was explicitly granted at least one visible detail tab in DB.
     */
    public static function hasConfiguredVisibleDetailTab(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $userRoles = self::resolveUserRolesForDetail($user);
        if (empty($userRoles)) {
            return false;
        }

        return self::whereIn('role_name', $userRoles)
            ->whereIn('tab_key', array_keys(self::DETAIL_TABS))
            ->whereIn('access_level', [self::ACCESS_READ, self::ACCESS_WRITE])
            ->exists();
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
        if ($user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin'])) {
            foreach ($tabKeys as $key) {
                $result[$key] = self::ACCESS_WRITE;
            }

            return $result;
        }

        $userRoles = self::resolveUserRolesForClinical($user);

        // Load permissions for user roles
        $permissions = self::whereIn('role_name', $userRoles)
            ->whereIn('tab_key', $tabKeys)
            ->get();

        // If table is completely empty, seed defaults on the fly
        if (self::count() === 0) {
            self::seedDefaults();
            $permissions = self::whereIn('role_name', $userRoles)
                ->whereIn('tab_key', $tabKeys)
                ->get();
        }

        return self::resolveEffectiveTabAccess(
            $userRoles,
            $tabKeys,
            $permissions,
            self::defaultMatrix()
        );
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
