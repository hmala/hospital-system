@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- عنوان الصفحة وزر الإضافة -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-users-cog me-2"></i>إدارة الموارد البشرية - سجل الموظفين
            </h2>
            <p class="text-muted small mb-0">إدارة وتوثيق بيانات الكوادر الطبية، التمريضية، الإدارية والخدمية بالمستشفى</p>
        </div>
        <div>
            @can('create employees')
                <a href="{{ route('hr.employees.create') }}" class="btn btn-primary shadow-sm px-4">
                    <i class="fas fa-user-plus me-1"></i> إضافة موظف جديد
                </a>
            @endcan
        </div>
    </div>

    <!-- بطاقات الإحصائيات السريعة -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary me-3">
                        <i class="fas fa-id-card fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">إجمالي الموظفين</div>
                        <h4 class="fw-bold mb-0 text-dark">{{ $stats['total'] }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-success bg-opacity-10 text-success me-3">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">على رأس العمل</div>
                        <h4 class="fw-bold mb-0 text-success">{{ $stats['active'] }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-info bg-opacity-10 text-info me-3">
                        <i class="fas fa-user-md fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">الكوادر الطبية والتمريضية</div>
                        <h4 class="fw-bold mb-0 text-info">{{ $stats['medical_staff'] }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-secondary bg-opacity-10 text-secondary me-3">
                        <i class="fas fa-briefcase fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">الإداريون والخدمات</div>
                        <h4 class="fw-bold mb-0 text-secondary">{{ $stats['admin_staff'] }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 border-start border-warning border-4">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-warning bg-opacity-10 text-warning me-3">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">تراخيص تنتهي قريباً (30 يوم)</div>
                        <h4 class="fw-bold mb-0 text-warning">{{ $stats['license_expiring'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- صندوق الفلترة والبحث -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('hr.employees.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">بحث سريع</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="الاسم، الرقم الوظيفي، الهاتف، أو الترخيص..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">نوع الكادر</label>
                    <select name="staff_type" class="form-select form-select-sm">
                        <option value="">جميع الكوادر</option>
                        <option value="medical" {{ request('staff_type') == 'medical' ? 'selected' : '' }}>🩺 كادر طبي</option>
                        <option value="nursing" {{ request('staff_type') == 'nursing' ? 'selected' : '' }}>💉 كادر تمريضي</option>
                        <option value="technical" {{ request('staff_type') == 'technical' ? 'selected' : '' }}>🔬 فني ومختبري</option>
                        <option value="administrative" {{ request('staff_type') == 'administrative' ? 'selected' : '' }}>💼 إداري ومالي</option>
                        <option value="service" {{ request('staff_type') == 'service' ? 'selected' : '' }}>🛠️ خدمات وصيانة</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">القسم</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">جميع الأقسام</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">الحالة الوظيفية</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">جميع الحالات</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>على رأس العمل</option>
                        <option value="on_leave" {{ request('status') == 'on_leave' ? 'selected' : '' }}>في إجازة</option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>موقوف مؤقتاً</option>
                        <option value="resigned" {{ request('status') == 'resigned' ? 'selected' : '' }}>مستقيل</option>
                        <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>منهي خدماته</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">تراخيص الممارسة</label>
                    <select name="license_status" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        <option value="expiring_soon" {{ request('license_status') == 'expiring_soon' ? 'selected' : '' }}>⚠️ تنتهي خلال 30 يوم</option>
                        <option value="expired" {{ request('license_status') == 'expired' ? 'selected' : '' }}>⛔ منتهية الصلاحية</option>
                    </select>
                </div>

                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100" title="تطبيق الفلتر">
                        <i class="fas fa-filter"></i>
                    </button>
                    @if(request()->hasAny(['search', 'staff_type', 'department_id', 'status', 'license_status']))
                        <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary btn-sm" title="إلغاء الفلاتر">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- جدول الموظفين -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small">
                        <tr>
                            <th class="ps-3" style="width: 100px;">الرمز</th>
                            <th>الموظف</th>
                            <th>نوع الكادر / المسمى</th>
                            <th>القسم</th>
                            <th>نوع التعاقد</th>
                            <th>تاريخ المباشرة</th>
                            <th>الحالة</th>
                            <th>رخصة الممارسة</th>
                            <th class="text-center pe-3" style="width: 140px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border font-monospace">{{ $emp->employee_code }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($emp->profile_photo)
                                            <img src="{{ asset('storage/' . $emp->profile_photo) }}" alt="" class="rounded-circle me-2" style="width: 36px; height: 36px; object-fit: cover;">
                                        @else
                                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 36px; height: 36px; font-size: 14px;">
                                                {{ mb_substr($emp->full_name, 0, 1) }}
                                            </div>
                                        @endif
                                        <div>
                                            <a href="{{ route('hr.employees.show', $emp->id) }}" class="fw-bold text-dark text-decoration-none">
                                                {{ $emp->full_name }}
                                            </a>
                                            <div class="text-muted small">
                                                <i class="fas fa-phone-alt me-1 text-secondary" style="font-size: 11px;"></i>{{ $emp->phone }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        @if($emp->staff_type === 'medical')
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mb-1"><i class="fas fa-user-md me-1"></i>طبي</span>
                                        @elseif($emp->staff_type === 'nursing')
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 mb-1"><i class="fas fa-user-nurse me-1"></i>تمريض</span>
                                        @elseif($emp->staff_type === 'technical')
                                            <span class="badge bg-purple bg-opacity-10 text-purple border border-purple border-opacity-25 mb-1"><i class="fas fa-microscope me-1"></i>فني</span>
                                        @elseif($emp->staff_type === 'administrative')
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 mb-1"><i class="fas fa-user-tie me-1"></i>إداري</span>
                                        @else
                                            <span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25 mb-1"><i class="fas fa-tools me-1"></i>خدمات</span>
                                        @endif
                                    </div>
                                    <span class="text-dark small fw-semibold">{{ $emp->job_title }}</span>
                                </td>
                                <td>
                                    @if($emp->department)
                                        <span class="text-secondary small"><i class="far fa-hospital me-1"></i>{{ $emp->department->name }}</span>
                                    @else
                                        <span class="text-muted small">غير محدد</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">{{ $emp->employment_type_name }}</span>
                                </td>
                                <td>
                                    <span class="small text-muted">{{ $emp->hire_date ? $emp->hire_date->format('Y-m-d') : '-' }}</span>
                                </td>
                                <td>
                                    @if($emp->status === 'active')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">على رأس العمل</span>
                                    @elseif($emp->status === 'on_leave')
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">في إجازة</span>
                                    @elseif($emp->status === 'suspended')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">موقوف مؤقتاً</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border">{{ $emp->status_name }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($emp->isMedicalStaff())
                                        @if($emp->medical_license_number)
                                            @if($emp->isLicenseExpired())
                                                <span class="badge bg-danger text-white" title="انتهت في {{ $emp->license_expiry_date?->format('Y-m-d') }}">
                                                    <i class="fas fa-ban me-1"></i>منتهية!
                                                </span>
                                            @elseif($emp->isLicenseExpiringSoon())
                                                <span class="badge bg-warning text-dark" title="تنتهي في {{ $emp->license_expiry_date?->format('Y-m-d') }}">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>تجديد قريب
                                                </span>
                                            @else
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" title="صالحة لغاية {{ $emp->license_expiry_date?->format('Y-m-d') }}">
                                                    <i class="fas fa-check-circle me-1"></i>سارية
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted small">غير مسجل</span>
                                        @endif
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('hr.employees.show', $emp->id) }}" class="btn btn-outline-info" title="عرض الإضبارة الكاملة">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @can('edit employees')
                                            <a href="{{ route('hr.employees.edit', $emp->id) }}" class="btn btn-outline-primary" title="تعديل البيانات">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endcan
                                        @can('delete employees')
                                            <form action="{{ route('hr.employees.destroy', $emp->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من أرشفة أو حذف هذا الموظف؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="حذف / أرشفة">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-users-slash fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-0">لا توجد سجلات موظفين مطابقة للبحث أو الفلاتر المحددة.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- الترقيم والتنقل -->
            @if($employees->hasPages())
                <div class="p-3 border-top">
                    {{ $employees->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
