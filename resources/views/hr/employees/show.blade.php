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
@endpush
