@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- الترويسة وأزرار التحكم -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-id-card me-2"></i>إضبارة الموظف: {{ $employee->full_name }}
            </h2>
            <p class="text-muted small mb-0">كود الموظف: <span class="badge bg-light text-dark border font-monospace">{{ $employee->employee_code }}</span> | المسمى: {{ $employee->job_title }}</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i> طباعة الإضبارة
            </button>
            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                <i class="fas fa-file-upload me-1"></i> إضافة مستمسك رسمي
            </button>
            @can('edit employees')
                <a href="{{ route('hr.employees.edit', $employee->id) }}" class="btn btn-primary btn-sm shadow-sm">
                    <i class="fas fa-user-edit me-1"></i> تعديل البيانات
                </a>
            @endcan
            <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> القائمة
            </a>
        </div>
    </div>

    <!-- رسائل النجاح أو التنبيه -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- تنبيهات التراخيص الطبية -->
    @if($employee->isMedicalStaff() && $employee->license_expiry_date)
        @if($employee->isLicenseExpired())
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4">
                <i class="fas fa-ban fa-2x me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">تنبيه: ترخيص مزاولة المهنة منتهي الصلاحية!</h6>
                    <small>انتهت صلاحية إجازة الممارسة في تاريخ {{ $employee->license_expiry_date->format('Y-m-d') }} (منذ {{ $employee->license_expiry_date->diffForHumans() }}). يرجى مراجعة الموظف لتجديد الترخيص.</small>
                </div>
            </div>
        @elseif($employee->isLicenseExpiringSoon())
            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4">
                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">تنبيه: ترخيص مزاولة المهنة ينتهي قريباً!</h6>
                    <small>يتبقى على انتهاء الترخيص <strong>{{ now()->diffInDays($employee->license_expiry_date) }} يوم</strong> (تاريخ الانتهاء: {{ $employee->license_expiry_date->format('Y-m-d') }}). يرجى اتخاذ إجراءات التجديد.</small>
                </div>
            </div>
        @endif
    @endif

    <div class="row g-4">
        <!-- البطاقة الجانبية: الباجة والملخص السريع -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 text-center p-4 mb-4 bg-white">
                <div class="mb-3">
                    @if($employee->profile_photo)
                        <img src="{{ asset('storage/' . $employee->profile_photo) }}" alt="" class="rounded-circle shadow-sm border p-1" style="width: 140px; height: 140px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 140px; height: 140px; font-size: 50px;">
                            {{ mb_substr($employee->full_name, 0, 1) }}
                        </div>
                    @endif
                </div>

                <h5 class="fw-bold text-dark mb-1">{{ $employee->full_name }}</h5>
                <p class="text-primary fw-semibold mb-2">{{ $employee->job_title }}</p>

                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-light text-dark border font-monospace fs-6 px-3 py-2">
                        <i class="fas fa-id-badge me-1 text-secondary"></i>{{ $employee->employee_code }}
                    </span>
                </div>

                <div class="d-flex justify-content-center gap-2 mb-3">
                    @if($employee->staff_type === 'medical')
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1"><i class="fas fa-user-md me-1"></i>كادر طبي</span>
                    @elseif($employee->staff_type === 'nursing')
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1"><i class="fas fa-user-nurse me-1"></i>كادر تمريضي</span>
                    @elseif($employee->staff_type === 'technical')
                        <span class="badge bg-purple bg-opacity-10 text-purple border border-purple border-opacity-25 px-2 py-1"><i class="fas fa-microscope me-1"></i>كادر فني</span>
                    @elseif($employee->staff_type === 'administrative')
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1"><i class="fas fa-user-tie me-1"></i>كادر إداري</span>
                    @else
                        <span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25 px-2 py-1"><i class="fas fa-tools me-1"></i>خدمات</span>
                    @endif

                    @if($employee->status === 'active')
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">على رأس العمل</span>
                    @elseif($employee->status === 'on_leave')
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">في إجازة</span>
                    @elseif($employee->status === 'suspended')
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">موقوف مؤقتاً</span>
                    @else
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">{{ $employee->status_name }}</span>
                    @endif
                </div>

                <hr class="my-3 opacity-25">

                <div class="text-start small">
                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                        <span class="text-muted"><i class="fas fa-hospital me-1"></i> القسم:</span>
                        <span class="fw-semibold text-dark">{{ $employee->department?->name ?? 'غير محدد' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                        <span class="text-muted"><i class="fas fa-phone-alt me-1"></i> الهاتف:</span>
                        <span class="fw-semibold text-dark font-monospace">{{ $employee->phone }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                        <span class="text-muted"><i class="fas fa-envelope me-1"></i> البريد:</span>
                        <span class="fw-semibold text-dark">{{ $employee->email ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                        <span class="text-muted"><i class="fas fa-tint me-1 text-danger"></i> فصيلة الدم:</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger">{{ $employee->blood_group ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                        <span class="text-muted"><i class="fas fa-calendar-check me-1"></i> تاريخ المباشرة:</span>
                        <span class="fw-semibold text-dark">{{ $employee->hire_date?->format('Y-m-d') ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted"><i class="fas fa-file-pdf me-1 text-danger"></i> المستمسكات المؤرشفة:</span>
                        <span class="badge bg-primary rounded-pill">{{ $employee->documents->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- بطاقة حساب النظام المرتبط -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-user-shield text-primary me-2"></i>حساب الدخول للنظام
                    </h6>
                </div>
                <div class="card-body p-3">
                    @if($employee->user)
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-success bg-opacity-10 text-success p-2 me-2">
                                <i class="fas fa-check fa-lg"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">{{ $employee->user->name }}</div>
                                <div class="text-muted small">{{ $employee->user->email }}</div>
                                <div class="mt-1">
                                    <span class="badge bg-secondary-subtle text-secondary small">الدور: {{ $employee->user->role ?? 'مستخدم' }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-2 text-muted small">
                            <i class="fas fa-user-slash fa-2x mb-1 text-secondary opacity-50"></i>
                            <p class="mb-0">الموظف غير مرتبط بأي حساب دخول للنظام.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- العمود الرئيسي: التفاصيل والمستمسكات -->
        <div class="col-lg-8">
            <!-- 1. البيانات الشخصية وجهات الاتصال -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-user text-primary me-2"></i>البيانات الشخصية وجهات الاتصال
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small">الاسم الكامل</label>
                            <div class="fw-bold text-dark">{{ $employee->full_name }}</div>
                        </div>

                        <div class="col-sm-6">
                            <label class="text-muted small">رقم البطاقة الموحدة / الهوية الوطنية</label>
                            <div class="fw-bold text-dark font-monospace">{{ $employee->national_id ?? '—' }}</div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">الجنس</label>
                            <div class="fw-bold text-dark">{{ $employee->gender === 'male' ? 'ذكر' : 'أنثى' }}</div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">تاريخ الميلاد</label>
                            <div class="fw-bold text-dark">
                                {{ $employee->date_of_birth ? $employee->date_of_birth->format('Y-m-d') . ' (' . $employee->date_of_birth->age . ' سنة)' : '—' }}
                            </div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">هاتف الطوارئ</label>
                            <div class="fw-bold text-dark font-monospace">{{ $employee->emergency_phone ?? '—' }}</div>
                        </div>

                        <div class="col-12">
                            <label class="text-muted small">عنوان السكن الكامل</label>
                            <div class="fw-bold text-dark">{{ $employee->address ?? 'غير محدد' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. البيانات الوظيفية والتعاقد والراتب -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-briefcase text-primary me-2"></i>تفاصيل التعاقد والوظيفة
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="text-muted small">نوع الكادر</label>
                            <div class="fw-bold text-dark">{{ $employee->staff_type_name }}</div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">المسمى الوظيفي</label>
                            <div class="fw-bold text-dark">{{ $employee->job_title }}</div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">القسم</label>
                            <div class="fw-bold text-dark">{{ $employee->department?->name ?? 'غير محدد' }}</div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">نوع التعاقد</label>
                            <div class="fw-bold text-dark">{{ $employee->employment_type_name }}</div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">تاريخ المباشرة</label>
                            <div class="fw-bold text-dark">{{ $employee->hire_date?->format('Y-m-d') }}</div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">تاريخ انتهاء العقد</label>
                            <div class="fw-bold text-dark">{{ $employee->contract_end_date?->format('Y-m-d') ?? 'عقد دائم / غير محدد' }}</div>
                        </div>

                        <div class="col-sm-6">
                            <label class="text-muted small">الراتب الأساسي الشهري</label>
                            <div class="h5 fw-bold text-success mb-0">
                                {{ number_format($employee->basic_salary) }} <small class="fs-6 text-muted">د.ع</small>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <label class="text-muted small">مدة الخدمة بالمستشفى</label>
                            <div class="fw-bold text-dark">
                                {{ $employee->hire_date ? $employee->hire_date->diffForHumans(['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) : '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. التراخيص والبيانات الطبية (إذا كان كادر طبي) -->
            @if($employee->isMedicalStaff())
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-info bg-opacity-10 py-3 border-bottom border-info border-opacity-25">
                        <h6 class="card-title fw-bold text-info mb-0">
                            <i class="fas fa-stethoscope me-2"></i>التراخيص المهنية والبيانات الطبية
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="text-muted small">رقم إجازة / ترخيص ممارسة المهنة</label>
                                <div class="fw-bold text-dark font-monospace">{{ $employee->medical_license_number ?? 'غير مسجل' }}</div>
                            </div>

                            <div class="col-sm-6">
                                <label class="text-muted small">تاريخ انتهاء ترخيص الممارسة</label>
                                <div class="fw-bold">
                                    @if($employee->license_expiry_date)
                                        <span class="font-monospace {{ $employee->isLicenseExpired() ? 'text-danger' : ($employee->isLicenseExpiringSoon() ? 'text-warning' : 'text-success') }}">
                                            {{ $employee->license_expiry_date->format('Y-m-d') }}
                                        </span>
                                    @else
                                        <span class="text-muted">غير محدد</span>
                                    @endif
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <label class="text-muted small">رقم هوية النقابة</label>
                                <div class="fw-bold text-dark">{{ $employee->syndicate_card_number ?? '—' }}</div>
                            </div>

                            <div class="col-sm-4">
                                <label class="text-muted small">المؤهل العلمي / الشهادة</label>
                                <div class="fw-bold text-dark">{{ $employee->qualification ?? '—' }}</div>
                            </div>

                            <div class="col-sm-4">
                                <label class="text-muted small">التخصص الدقيق</label>
                                <div class="fw-bold text-dark">{{ $employee->sub_specialty ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            
            <!-- 3.5. سجل العقوبات والمكافآت والإنذارات -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-balance-scale text-warning me-2"></i>سجل العقوبات والمكافآت
                    </h6>
                    <button type="button" class="btn btn-outline-warning btn-sm text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#addActionModal">
                        <i class="fas fa-gavel me-1"></i> تسجيل إجراء جديد
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light small">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>نوع الإجراء</th>
                                    <th>اسم اللائحة / السبب</th>
                                    <th>التأثير المالي (د.ع)</th>
                                    <th>حالة الترحيل للراتب</th>
                                    <th>مسجل بواسطة</th>
                                    <th>حذف</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @forelse($employee->actions()->latest('action_date')->get() as $action)
                                    <tr>
                                        <td>{{ $action->action_date->format('Y-m-d') }}</td>
                                        <td>
                                            @if($action->actionSetting->category == 'penalty')
                                                <span class="badge bg-danger"><i class="fas fa-minus-circle"></i> عقوبة</span>
                                            @elseif($action->actionSetting->category == 'bonus')
                                                <span class="badge bg-success"><i class="fas fa-plus-circle"></i> مكافأة</span>
                                            @else
                                                <span class="badge bg-secondary"><i class="fas fa-exclamation-triangle"></i> إنذار</span>
                                            @endif
                                        </td>
                                        <td class="text-start">
                                            <strong>{{ $action->actionSetting->title }}</strong><br>
                                            <span class="text-muted">{{ Str::limit($action->reason, 40) }}</span>
                                        </td>
                                        <td>
                                            @if($action->financial_amount == 0)
                                                <span class="text-muted">بدون تأثير مالي</span>
                                            @elseif($action->financial_amount > 0)
                                                <span class="text-success fw-bold">+{{ number_format($action->financial_amount, 0) }}</span>
                                            @else
                                                <span class="text-danger fw-bold">{{ number_format($action->financial_amount, 0) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($action->status == 'pending')
                                                <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half"></i> بانتظار الراتب</span>
                                            @else
                                                <span class="badge bg-success"><i class="fas fa-check-double"></i> مُرحّل ({{ $action->payrollCycle->cycle_month ?? '' }})</span>
                                            @endif
                                        </td>
                                        <td>{{ $action->creator->name ?? 'النظام' }}</td>
                                        <td>
                                            @if($action->status == 'pending')
                                            <form action="{{ route('hr.employees.actions.destroy', $action->id) }}" method="POST" class="d-inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('هل أنت متأكد من الحذف؟')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @else
                                                <i class="fas fa-lock text-muted" title="لا يمكن حذفه لأنه مرحّل للرواتب"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-check-circle fa-2x mb-2 text-success opacity-50"></i>
                                            <p class="mb-0">سجل الموظف نظيف، لا توجد عقوبات أو مكافآت مسجلة.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 4. جدول المستمسكات والوثائق الرسمية (Documents Table) -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-folder-open text-primary me-2"></i>الأرشيف والمستمسكات الرسمية ({{ $employee->documents->count() }})
                    </h6>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                        <i class="fas fa-plus me-1"></i> رفع مستمسك جديد
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="ps-3">الرمز الوظيفي</th>
                                    <th>نوع المستمسك</th>
                                    <th>اسم الملف المورث</th>
                                    <th>الحجم</th>
                                    <th>تاريخ الرفع</th>
                                    <th class="text-center pe-3" style="width: 120px;">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employee->documents as $doc)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge bg-light text-dark border font-monospace">{{ $doc->employee_code }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                                <i class="fas fa-file-alt me-1"></i>{{ $doc->document_type }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-dark small font-monospace fw-semibold">
                                                @if($doc->isPdf())
                                                    <i class="fas fa-file-pdf text-danger me-1"></i>
                                                @else
                                                    <i class="fas fa-file-image text-info me-1"></i>
                                                @endif
                                                {{ $doc->file_name }}
                                            </div>
                                            @if($doc->notes)
                                                <small class="text-muted">{{ $doc->notes }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="small text-muted">{{ $doc->formatted_file_size }}</span>
                                        </td>
                                        <td>
                                            <span class="small text-muted">{{ $doc->created_at->format('Y-m-d H:i') }}</span>
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('hr.employees.documents.download', $doc->id) }}" class="btn btn-outline-primary" title="تحميل / معاينة" target="_blank">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                @can('delete employees')
                                                    <form action="{{ route('hr.employees.documents.destroy', $doc->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستمسك؟')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger" title="حذف المستمسك">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fas fa-folder-empty fa-2x mb-2 opacity-50"></i>
                                            <p class="mb-0">لا توجد مستمسكات أو وثائق مرفقة لهذا الموظف حالياً.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            
            <!-- ═══════════════════════════════════════════════════ -->
            <!-- 📊 إعدادات هيكلة الراتب -->
            <!-- ═══════════════════════════════════════════════════ -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-info">
                <div class="card-header bg-info bg-opacity-10 py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold text-info mb-0">
                        <i class="fas fa-cog me-2"></i>إعدادات هيكلة الراتب والدفع
                    </h6>
                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#editPayrollSettingsModal">
                        <i class="fas fa-edit"></i> تعديل الإعدادات
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="row g-0 text-center">
                        <div class="col-md-3 border-end p-3">
                            <div class="text-muted small">الراتب الأساسي</div>
                            <div class="fw-bold fs-5 text-primary">{{ number_format($employee->basic_salary, 0) }} <small class="text-muted">د.ع</small></div>
                        </div>
                        <div class="col-md-3 border-end p-3">
                            <div class="text-muted small">إجمالي المخصصات الثابتة</div>
                            <div class="fw-bold fs-5 text-success">{{ number_format($employee->activeAllowances->sum('amount'), 0) }} <small class="text-muted">د.ع</small></div>
                        </div>
                        <div class="col-md-3 border-end p-3">
                            <div class="text-muted small">طريقة صرف الراتب</div>
                            <div class="fw-bold">
                                @if($employee->payment_method == 'bank')
                                    <span class="badge bg-primary"><i class="fas fa-university"></i> بنك</span>
                                    <div class="small text-muted mt-1">{{ $employee->bank_name }} - {{ $employee->bank_account_number }}</div>
                                @else
                                    <span class="badge bg-success"><i class="fas fa-money-bill-wave"></i> كاش</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3 p-3">
                            <div class="text-muted small">الاستقطاعات</div>
                            <div class="small mt-1">
                                @if($employee->subject_to_social_security)
                                    <span class="badge bg-warning text-dark">ضمان {{ $employee->social_security_percentage }}%</span>
                                @endif
                                @if($employee->subject_to_tax)
                                    <span class="badge bg-danger">ضريبة {{ $employee->tax_percentage }}%</span>
                                @endif
                                @if(!$employee->subject_to_social_security && !$employee->subject_to_tax)
                                    <span class="text-muted">بدون استقطاعات</span>
                                @endif
                            </div>
                            @if($employee->overtime_hourly_rate > 0)
                                <div class="small text-muted mt-1">سعر الإضافي: {{ number_format($employee->overtime_hourly_rate, 0) }} د.ع/ساعة</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════ -->
            <!-- 💰 المخصصات الثابتة -->
            <!-- ═══════════════════════════════════════════════════ -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-layer-group text-success me-2"></i>المخصصات والعلاوات الثابتة
                        <span class="badge bg-success ms-1">{{ $employee->activeAllowances->count() }}</span>
                    </h6>
                    <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#addAllowanceModal">
                        <i class="fas fa-plus"></i> إضافة مخصص
                    </button>
                </div>
                <div class="card-body p-0">
                    @forelse($employee->allowances as $allowance)
                    <div class="d-flex justify-content-between align-items-center px-4 py-2 border-bottom {{ $allowance->is_active ? '' : 'bg-light opacity-50' }}">
                        <div>
                            <span class="fw-bold">{{ $allowance->title }}</span>
                            @if(!$allowance->is_active) <span class="badge bg-secondary ms-2">معطّل</span> @endif
                            @if($allowance->notes) <div class="text-muted small">{{ $allowance->notes }}</div> @endif
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="fw-bold text-success fs-6">{{ number_format($allowance->amount, 0) }} د.ع</span>
                            <form action="{{ route('hr.employees.allowances.destroy', $allowance->id) }}" method="POST" class="d-inline-block">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('حذف هذا المخصص؟')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2 opacity-25"></i>
                        <p class="mb-0 small">لم يتم إضافة أي مخصصات ثابتة لهذا الموظف بعد.</p>
                    </div>
                    @endforelse
                    @if($employee->activeAllowances->count() > 0)
                    <div class="bg-success-subtle d-flex justify-content-between align-items-center px-4 py-2 fw-bold">
                        <span>إجمالي المخصصات الشهرية</span>
                        <span class="text-success fs-6">{{ number_format($employee->activeAllowances->sum('amount'), 0) }} د.ع</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════ -->
            <!-- 🏦 السلف والأقساط -->
            <!-- ═══════════════════════════════════════════════════ -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-hand-holding-usd text-warning me-2"></i>السلف والأقساط
                    </h6>
                    <button class="btn btn-outline-warning btn-sm text-dark" data-bs-toggle="modal" data-bs-target="#addLoanModal">
                        <i class="fas fa-plus"></i> تسجيل سلفة جديدة
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0 small text-center">
                        <thead class="table-light">
                            <tr>
                                <th>تاريخ البدء</th>
                                <th>إجمالي السلفة</th>
                                <th>القسط الشهري</th>
                                <th>المسدد</th>
                                <th>المتبقي</th>
                                <th>الحالة</th>
                                <th>السبب</th>
                                <th>إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employee->loans()->latest()->get() as $loan)
                            <tr>
                                <td>{{ $loan->start_date->format('Y-m-d') }}</td>
                                <td class="fw-bold">{{ number_format($loan->total_amount, 0) }}</td>
                                <td class="text-warning fw-bold">{{ number_format($loan->monthly_installment, 0) }}</td>
                                <td class="text-success">{{ number_format($loan->paid_amount, 0) }}</td>
                                <td class="text-danger fw-bold">{{ number_format($loan->remaining_amount, 0) }}</td>
                                <td>
                                    @if($loan->status == 'active')
                                        <span class="badge bg-primary">نشطة</span>
                                    @elseif($loan->status == 'completed')
                                        <span class="badge bg-success"><i class="fas fa-check"></i> مكتملة</span>
                                    @else
                                        <span class="badge bg-secondary">ملغاة</span>
                                    @endif
                                </td>
                                <td>{{ $loan->reason ?? '—' }}</td>
                                <td>
                                    @if($loan->status == 'active')
                                    <form action="{{ route('hr.employees.loans.cancel', $loan->id) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-secondary border-0 small" onclick="return confirm('إلغاء هذه السلفة؟')">
                                            إلغاء
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">لا توجد سلف مسجلة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════ -->
            <!-- 📅 الغيابات والتأخيرات -->
            <!-- ═══════════════════════════════════════════════════ -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-calendar-times text-danger me-2"></i>سجل الغيابات والتأخيرات
                    </h6>
                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addAbsenceModal">
                        <i class="fas fa-plus"></i> تسجيل غياب / تأخير
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0 small text-center">
                        <thead class="table-light">
                            <tr>
                                <th>التاريخ</th>
                                <th>النوع</th>
                                <th>الأيام</th>
                                <th>بعذر؟</th>
                                <th>مبلغ الخصم</th>
                                <th>الحالة</th>
                                <th>السبب</th>
                                <th>حذف</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employee->absences()->latest('absence_date')->limit(20)->get() as $abs)
                            <tr>
                                <td>{{ $abs->absence_date->format('Y-m-d') }}</td>
                                <td>
                                    @if($abs->type == 'absence') <span class="badge bg-danger">غياب</span>
                                    @elseif($abs->type == 'late') <span class="badge bg-warning text-dark">تأخير</span>
                                    @else <span class="badge bg-secondary">انصراف مبكر</span>
                                    @endif
                                </td>
                                <td>{{ $abs->days_count }}</td>
                                <td>
                                    @if($abs->is_excused) <span class="badge bg-success">بعذر</span>
                                    @else <span class="badge bg-danger">بدون عذر</span>
                                    @endif
                                </td>
                                <td class="{{ $abs->deduction_amount > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                    {{ $abs->deduction_amount > 0 ? number_format($abs->deduction_amount, 0) . ' د.ع' : 'بدون خصم' }}
                                </td>
                                <td>
                                    @if($abs->status == 'pending')
                                        <span class="badge bg-warning text-dark">بانتظار الراتب</span>
                                    @else
                                        <span class="badge bg-success"><i class="fas fa-check"></i> مُرحّل</span>
                                    @endif
                                </td>
                                <td>{{ Str::limit($abs->reason ?? '—', 30) }}</td>
                                <td>
                                    @if($abs->status == 'pending')
                                    <form action="{{ route('hr.employees.absences.destroy', $abs->id) }}" method="POST" class="d-inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('حذف؟')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @else <i class="fas fa-lock text-muted"></i> @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">لا توجد غيابات مسجلة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════ -->
            <!-- ⏰ العمل الإضافي -->
            <!-- ═══════════════════════════════════════════════════ -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-clock text-primary me-2"></i>سجل العمل الإضافي
                    </h6>
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addOvertimeModal">
                        <i class="fas fa-plus"></i> تسجيل إضافي
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0 small text-center">
                        <thead class="table-light">
                            <tr>
                                <th>التاريخ</th>
                                <th>طريقة الحساب</th>
                                <th>الساعات</th>
                                <th>سعر الساعة</th>
                                <th>المبلغ الإجمالي</th>
                                <th>الحالة</th>
                                <th>الوصف</th>
                                <th>حذف</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employee->overtimes()->latest('overtime_date')->limit(20)->get() as $ot)
                            <tr>
                                <td>{{ $ot->overtime_date->format('Y-m-d') }}</td>
                                <td>
                                    @if($ot->input_type == 'hours')
                                        <span class="badge bg-info text-dark">بالساعات</span>
                                    @else
                                        <span class="badge bg-secondary">مبلغ مقطوع</span>
                                    @endif
                                </td>
                                <td>{{ $ot->hours_count ?? '—' }}</td>
                                <td>{{ $ot->hourly_rate_used ? number_format($ot->hourly_rate_used, 0) . ' د.ع' : '—' }}</td>
                                <td class="text-success fw-bold">{{ number_format($ot->total_amount, 0) }} د.ع</td>
                                <td>
                                    @if($ot->status == 'pending')
                                        <span class="badge bg-warning text-dark">بانتظار الراتب</span>
                                    @else
                                        <span class="badge bg-success"><i class="fas fa-check"></i> مُرحّل</span>
                                    @endif
                                </td>
                                <td>{{ Str::limit($ot->description ?? '—', 30) }}</td>
                                <td>
                                    @if($ot->status == 'pending')
                                    <form action="{{ route('hr.employees.overtimes.destroy', $ot->id) }}" method="POST" class="d-inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('حذف؟')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @else <i class="fas fa-lock text-muted"></i> @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">لا يوجد عمل إضافي مسجل.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 5. الملاحظات -->
            @if($employee->notes)
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="card-title fw-bold text-dark mb-0">
                            <i class="fas fa-sticky-note text-secondary me-2"></i>ملاحظات إدارية
                        </h6>
                    </div>
                    <div class="card-body p-4 text-secondary">
                        {!! nl2br(e($employee->notes)) !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('modals')
<!-- Modal رفع مستمسك جديد مباشرة من الإضبارة -->
<div class="modal fade" id="uploadDocModal" tabindex="-1" aria-labelledby="uploadDocModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold" id="uploadDocModalLabel">
                    <i class="fas fa-upload me-1"></i> رفع مستمسك رسمي للموظف: {{ $employee->full_name }}
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('hr.employees.documents.upload', $employee->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-light border small text-muted mb-3">
                        الرمز الوظيفي: <strong>{{ $employee->employee_code }}</strong> — سيتم ترميز الملف وتسميته آلياً وفق الرمز والنوع.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">نوع المستمسك <span class="text-danger">*</span></label>
                        <select name="document_type" class="form-select" required>
                            <option value="">اختر نوع المستمسك...</option>
                            @foreach($documentTypes as $dt)
                                <option value="{{ $dt->name }}">{{ $dt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">الملف (PDF أو صورة) <span class="text-danger">*</span></label>
                        <input type="file" name="document_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                        <div class="form-text text-muted small">الحد الأقصى للملف: 10 ميغابايت.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">ملاحظات إضافية (اختياري)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="أي تفاصيل حول هذا المستند..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">
                        <i class="fas fa-save me-1"></i> تأكيد الرفع والأرشفة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add Action (Penalty/Bonus) -->
<div class="modal fade" id="addActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.employees.actions.store', $employee->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning bg-opacity-10">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-gavel text-warning me-2"></i> تسجيل إجراء جديد للموظف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">اختر نوع الإجراء من اللائحة <span class="text-danger">*</span></label>
                        <select name="hr_action_setting_id" class="form-select" required>
                            <option value="">-- اختر الإجراء --</option>
                            @php
                                $penalties = $actionSettings->where('category', 'penalty');
                                $bonuses = $actionSettings->where('category', 'bonus');
                                $warnings = $actionSettings->where('category', 'warning');
                            @endphp
                            
                            @if($penalties->count() > 0)
                                <optgroup label="🔴 العقوبات والخصومات">
                                    @foreach($penalties as $s)
                                        <option value="{{ $s->id }}">{{ $s->title }} ({{ $s->effect_type == 'none' ? 'بدون تأثير مالي' : 'تأثير مالي' }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            
                            @if($bonuses->count() > 0)
                                <optgroup label="🟢 المكافآت والحوافز">
                                    @foreach($bonuses as $s)
                                        <option value="{{ $s->id }}">{{ $s->title }} ({{ $s->effect_type == 'none' ? 'بدون تأثير مالي' : 'تأثير مالي' }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            
                            @if($warnings->count() > 0)
                                <optgroup label="⚪ إنذارات وتوبيخ">
                                    @foreach($warnings as $s)
                                        <option value="{{ $s->id }}">{{ $s->title }} ({{ $s->effect_type == 'none' ? 'بدون تأثير مالي' : 'تأثير مالي' }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <small class="text-muted d-block mt-1">سيقوم النظام باحتساب قيمة الخصم/المكافأة آلياً بناءً على الراتب الأساسي وإعدادات اللائحة وإدراجها في راتب الشهر الحالي.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">تاريخ الإجراء/المخالفة <span class="text-danger">*</span></label>
                        <input type="date" name="action_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">السبب التفصيلي والملاحظات <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="اكتب تفاصيل المخالفة أو المكافأة لحفظها في الملف..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning fw-bold text-dark"><i class="fas fa-save me-1"></i> حفظ الإجراء وتطبيق التأثير</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ════════════════════════════════════════════════════════════════ -->
<!-- MODALS: Payroll Settings, Allowances, Loans, Absences, Overtime -->
<!-- ════════════════════════════════════════════════════════════════ -->

<!-- Modal: إعدادات هيكلة الراتب -->
<div class="modal fade" id="editPayrollSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.employees.update', $employee->id) }}" method="POST">
                @csrf @method('PUT')
                <input type="hidden" name="_section" value="payroll_settings">
                <div class="modal-header bg-info bg-opacity-10">
                    <h5 class="modal-title fw-bold"><i class="fas fa-cog text-info me-2"></i> تعديل إعدادات الراتب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">طريقة صرف الراتب</label>
                            <select name="payment_method" class="form-select" id="paymentMethodSelect">
                                <option value="cash" {{ $employee->payment_method == 'cash' ? 'selected' : '' }}>💵 كاش (نقداً)</option>
                                <option value="bank" {{ $employee->payment_method == 'bank' ? 'selected' : '' }}>🏦 بنك (توطين)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">سعر ساعة الإضافي (د.ع)</label>
                            <input type="number" name="overtime_hourly_rate" class="form-control" value="{{ $employee->overtime_hourly_rate }}" step="500" min="0">
                        </div>
                        <div id="bankFields" class="{{ $employee->payment_method == 'bank' ? '' : 'd-none' }} col-12 row g-2">
                            <div class="col-md-6">
                                <label class="form-label">اسم البنك</label>
                                <input type="text" name="bank_name" class="form-control" value="{{ $employee->bank_name }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">رقم الحساب</label>
                                <input type="text" name="bank_account_number" class="form-control" value="{{ $employee->bank_account_number }}">
                            </div>
                        </div>
                        <div class="col-12"><hr class="my-2"><p class="fw-bold mb-2">الاستقطاعات الإلزامية</p></div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="subject_to_social_security" id="ssCheck" value="1" {{ $employee->subject_to_social_security ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="ssCheck">مشمول بالضمان الاجتماعي</label>
                            </div>
                            <div class="input-group input-group-sm">
                                <input type="number" name="social_security_percentage" class="form-control" value="{{ $employee->social_security_percentage }}" step="0.5" min="0" max="25">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="subject_to_tax" id="taxCheck" value="1" {{ $employee->subject_to_tax ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="taxCheck">مشمول بالضريبة</label>
                            </div>
                            <div class="input-group input-group-sm">
                                <input type="number" name="tax_percentage" class="form-control" value="{{ $employee->tax_percentage }}" step="0.5" min="0" max="50">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-info fw-bold text-white"><i class="fas fa-save me-1"></i> حفظ الإعدادات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: إضافة مخصص -->
<div class="modal fade" id="addAllowanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.employees.allowances.store', $employee->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-success bg-opacity-10">
                    <h5 class="modal-title fw-bold"><i class="fas fa-layer-group text-success me-2"></i> إضافة مخصص ثابت</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">نوع المخصص <span class="text-danger">*</span></label>
                        <select name="title" class="form-select" required>
                            <option value="">-- اختر نوع المخصص --</option>
                            <optgroup label="مخصصات عائلية">
                                <option>مخصص زوجية</option>
                                <option>مخصص أطفال</option>
                            </optgroup>
                            <optgroup label="مخصصات وظيفية">
                                <option>مخصص خطورة</option>
                                <option>مخصص نقل</option>
                                <option>مخصص شهادة</option>
                                <option>مخصص امتياز</option>
                                <option>مخصص تفرغ</option>
                                <option>مخصص مناوبة</option>
                                <option>علاوة سنوية</option>
                            </optgroup>
                            <option value="_custom">أخرى (كتابة يدوية)</option>
                        </select>
                    </div>
                    <div class="mb-3" id="customTitleDiv" style="display:none;">
                        <label class="form-label">اسم المخصص (مخصص)</label>
                        <input type="text" name="title_custom" class="form-control" placeholder="أدخل اسم المخصص...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">المبلغ الشهري (د.ع) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="1000" min="0" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">ملاحظات</label>
                        <input type="text" name="notes" class="form-control" placeholder="اختياري...">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success fw-bold"><i class="fas fa-plus me-1"></i> إضافة المخصص</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: تسجيل سلفة -->
<div class="modal fade" id="addLoanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.employees.loans.store', $employee->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning bg-opacity-10">
                    <h5 class="modal-title fw-bold"><i class="fas fa-hand-holding-usd text-warning me-2"></i> تسجيل سلفة جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        الراتب الأساسي للموظف: <strong>{{ number_format($employee->basic_salary, 0) }} د.ع</strong>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">إجمالي مبلغ السلفة <span class="text-danger">*</span></label>
                            <input type="number" name="total_amount" class="form-control" step="50000" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">قيمة القسط الشهري <span class="text-danger">*</span></label>
                            <input type="number" name="monthly_installment" class="form-control" step="50000" min="0" required>
                            <small class="text-muted">0 = دفعة واحدة</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">تاريخ بدء الاستقطاع <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-01') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">سبب السلفة</label>
                            <input type="text" name="reason" class="form-control" placeholder="اختياري...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning fw-bold text-dark"><i class="fas fa-save me-1"></i> تسجيل السلفة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: تسجيل غياب -->
<div class="modal fade" id="addAbsenceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.employees.absences.store', $employee->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger bg-opacity-10">
                    <h5 class="modal-title fw-bold"><i class="fas fa-calendar-times text-danger me-2"></i> تسجيل غياب / تأخير</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning py-2 small">
                        <i class="fas fa-calculator me-1"></i>
                        قيمة اليوم الواحد: <strong>{{ number_format($employee->basic_salary / 30, 0) }} د.ع</strong>
                        (الراتب الأساسي {{ number_format($employee->basic_salary, 0) }} ÷ 30 يوم)
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">تاريخ الغياب <span class="text-danger">*</span></label>
                            <input type="date" name="absence_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">نوع الغياب <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="absence">غياب كامل</option>
                                <option value="late">تأخير</option>
                                <option value="early_leave">انصراف مبكر</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">عدد الأيام <span class="text-danger">*</span></label>
                            <input type="number" name="days_count" class="form-control" step="0.25" min="0.25" max="30" value="1" required>
                            <small class="text-muted">يمكن كسر (0.5 = نصف يوم)</small>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="is_excused" id="isExcusedCheck" value="1">
                                <label class="form-check-label fw-bold" for="isExcusedCheck">غياب بعذر (بدون خصم مالي)</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">السبب</label>
                            <input type="text" name="reason" class="form-control" placeholder="اكتب سبب الغياب...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger fw-bold"><i class="fas fa-save me-1"></i> تسجيل الغياب</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: تسجيل عمل إضافي -->
<div class="modal fade" id="addOvertimeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.employees.overtimes.store', $employee->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary bg-opacity-10">
                    <h5 class="modal-title fw-bold"><i class="fas fa-clock text-primary me-2"></i> تسجيل عمل إضافي</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    @if($employee->overtime_hourly_rate > 0)
                    <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        سعر الساعة المسجل: <strong>{{ number_format($employee->overtime_hourly_rate, 0) }} د.ع/ساعة</strong>
                    </div>
                    @else
                    <div class="alert alert-warning py-2 small">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        لم يتم تحديد سعر ساعة الإضافي. سيتم استخدام المبلغ المقطوع فقط.
                        <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#editPayrollSettingsModal" class="ms-1">تحديث السعر</a>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-bold">طريقة الحساب <span class="text-danger">*</span></label>
                        <select name="input_type" class="form-select" id="overtimeInputType" required>
                            <option value="hours">⏱️ بالساعات (ساعات × سعر الساعة)</option>
                            <option value="manual_amount">💰 مبلغ مقطوع يدوي</option>
                        </select>
                    </div>
                    <div id="hoursDiv" class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label">عدد الساعات</label>
                            <input type="number" name="hours_count" class="form-control" step="0.5" min="0.5">
                        </div>
                        <div class="col">
                            <label class="form-label">التاريخ</label>
                            <input type="date" name="overtime_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div id="manualDiv" class="mb-3 d-none">
                        <label class="form-label fw-bold">المبلغ (د.ع)</label>
                        <input type="number" name="manual_amount" class="form-control" step="5000" min="0">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">وصف / ملاحظة</label>
                        <input type="text" name="description" class="form-control" placeholder="مثال: خفارة ليلية، عمل عطلة رسمية...">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-save me-1"></i> تسجيل الإضافي</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Bank fields toggle
document.getElementById("paymentMethodSelect")?.addEventListener("change", function() {
    document.getElementById("bankFields").classList.toggle("d-none", this.value !== "bank");
});

// Overtime type toggle
document.getElementById("overtimeInputType")?.addEventListener("change", function() {
    const isHours = this.value === "hours";
    document.getElementById("hoursDiv").classList.toggle("d-none", !isHours);
    document.getElementById("manualDiv").classList.toggle("d-none", isHours);
});

// Allowance custom title
document.querySelector("select[name='title']")?.addEventListener("change", function() {
    const custom = document.getElementById("customTitleDiv");
    if (this.value === "_custom") {
        custom.style.display = "block";
        this.name = "title_dropdown";
        custom.querySelector("input").name = "title";
    } else {
        custom.style.display = "none";
        this.name = "title";
        custom.querySelector("input").name = "title_custom";
    }
});
</script>

@endpush
