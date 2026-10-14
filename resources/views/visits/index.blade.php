@extends('layouts.app')

@section('styles')
<style>
    .kpi-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    }
    .table-visits thead th {
        background-color: #f8fafc !important;
        color: #334155 !important;
        font-weight: 700 !important;
        font-size: 0.84rem !important;
        padding: 12px 14px !important;
        border-bottom: 2px solid #e2e8f0 !important;
        white-space: nowrap !important;
    }
    .table-visits tbody td {
        padding: 12px 14px !important;
        vertical-align: middle !important;
        font-size: 0.88rem !important;
        border-bottom: 1px solid #f1f5f9 !important;
        color: #1e293b !important;
    }
    .table-visits tbody tr:hover td {
        background-color: #f8fafc !important;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg text-white" style="background: linear-gradient(135deg, #1e40af 0%, #1e1b4b 100%); border-radius: 20px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row gap-3">
                        <div>
                            <h2 class="mb-1 fw-bold">
                                <i class="fas fa-stethoscope me-3 text-cyan-300"></i>سجل زيارات العيادات والاستشارية
                            </h2>
                            <p class="mb-0 opacity-75">الأرشيف المركزي لكشوفات المرضى، متابعة التشخيصات الطبية، وتحويلات العمليات الجراحية</p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('visits.export', request()->query()) }}" class="btn btn-light text-primary fw-bold px-4 py-2 rounded-pill shadow-sm">
                                <i class="fas fa-file-excel me-2 text-success"></i>تصدير إكسل
                            </a>
                            @if(auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || auth()->user()->can('create visits'))
                            <a href="{{ route('visits.create') }}" class="btn btn-primary bg-gradient border-0 fw-bold px-4 py-2 rounded-pill shadow-sm" style="background-color: #3b82f6;">
                                <i class="fas fa-plus me-2"></i>تسجيل زيارة جديدة
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas fa-times-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- KPI Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card bg-white p-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3 text-primary">
                        <i class="fas fa-history fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">إجمالي الزيارات</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($stats['total']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card bg-white p-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3 text-info">
                        <i class="fas fa-calendar-day fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">زيارات اليوم</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($stats['today']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card kpi-card bg-white p-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3 text-success">
                        <i class="fas fa-check-double fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">مكتملة</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($stats['completed']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-6">
            <div class="card kpi-card bg-white p-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3 text-warning">
                        <i class="fas fa-spinner fa-spin-pulse fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">قيد الفحص حالياً</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($stats['in_progress']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card kpi-card bg-white p-3 border-start border-4 border-danger">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3 text-danger">
                        <i class="fas fa-procedures fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">تحويلات لعمليات بانتظار الحجز</div>
                        <div class="fs-4 fw-bold text-danger">{{ number_format($stats['needs_surgery']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- قسم الزيارات التي تحتاج حجز عملية جراحية -->
    @if($needsSurgeryVisits->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden border-start border-warning border-5">
                <div class="card-header bg-warning bg-opacity-10 text-dark py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            زيارات تستوجب حجز عملية جراحية عاجلة ({{ $needsSurgeryVisits->count() }})
                        </h5>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">تحتاج تنسيق العمليات</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>التاريخ</th>
                                    <th>المريض</th>
                                    <th>الطبيب المعالج</th>
                                    <th>القسم</th>
                                    <th>ملاحظات وتفاصيل العملية</th>
                                    <th class="text-center pe-4">الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($needsSurgeryVisits as $nsVisit)
                                <tr>
                                    <td class="ps-4 fw-bold text-muted">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="fw-bold">{{ $nsVisit->visit_date ? $nsVisit->visit_date->format('Y-m-d') : '-' }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('patients.show', $nsVisit->patient_id) }}" class="fw-bold text-decoration-none text-primary">
                                            {{ optional($nsVisit->patient->user)->name ?? 'غير معروف' }}
                                        </a>
                                        <div class="text-muted small">{{ optional($nsVisit->patient->user)->phone ?? 'لا يوجد هاتف' }}</div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark">د. {{ optional($nsVisit->doctor->user)->name ?? 'غير محدد' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-dark">{{ optional($nsVisit->department)->name ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-danger fw-semibold">{{ Str::limit($nsVisit->surgery_notes, 80) ?: 'لم تدون تفاصيل' }}</span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <a href="{{ route('surgeries.create', ['visit_id' => $nsVisit->id, 'patient_id' => $nsVisit->patient_id, 'doctor_id' => $nsVisit->doctor_id, 'department_id' => $nsVisit->department_id]) }}" 
                                           class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                                            <i class="fas fa-procedures me-1"></i>حجز العملية الآن
                                        </a>
                                        <a href="{{ route('visits.show', $nsVisit) }}" class="btn btn-sm btn-light border rounded-pill px-3 ms-1">
                                            <i class="fas fa-eye me-1 text-muted"></i>عرض
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- بطاقة البحث والفلاتر المتقدمة -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('visits.index') }}" class="row g-3 align-items-end">
                <!-- البحث النصي -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">بحث سريع:</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0" placeholder="اسم المريض، الهاتف، الإضبارة...">
                    </div>
                </div>

                <!-- الطبيب -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">الطبيب المعالج:</label>
                    <select name="doctor_id" class="form-select">
                        <option value="">-- كافة الأطباء --</option>
                        @foreach($doctors as $doc)
                            <option value="{{ $doc->id }}" {{ request('doctor_id') == $doc->id ? 'selected' : '' }}>
                                د. {{ optional($doc->user)->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- العيادة / القسم -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">القسم / العيادة:</label>
                    <select name="department_id" class="form-select">
                        <option value="">-- كافة العيادات --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- حالة الزيارة -->
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">حالة الزيارة:</label>
                    <select name="status" class="form-select">
                        <option value="">-- كافة الحالات --</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتملة</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>قيد الفحص</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                    </select>
                </div>

                <!-- الحاجة لعملية -->
                <div class="col-md-1">
                    <label class="form-label small fw-bold text-muted">عملية؟</label>
                    <select name="needs_surgery" class="form-select px-2">
                        <option value="">الكل</option>
                        <option value="1" {{ request('needs_surgery') === '1' ? 'selected' : '' }}>نعم</option>
                        <option value="0" {{ request('needs_surgery') === '0' ? 'selected' : '' }}>لا</option>
                    </select>
                </div>

                <!-- من تاريخ -->
                <div class="col-md-1">
                    <label class="form-label small fw-bold text-muted">من تاريخ:</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control px-2">
                </div>

                <!-- إلى تاريخ -->
                <div class="col-md-1">
                    <label class="form-label small fw-bold text-muted">إلى تاريخ:</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control px-2">
                </div>

                <!-- أزرار الإجراء -->
                <div class="col-12 d-flex justify-content-end gap-2 pt-2 border-top">
                    @if(request()->anyFilled(['search', 'doctor_id', 'department_id', 'status', 'needs_surgery', 'date_from', 'date_to', 'visit_type']))
                    <a href="{{ route('visits.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fas fa-undo me-1"></i>إلغاء الفلاتر
                    </a>
                    @endif
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="fas fa-filter me-1"></i>تطبيق الفلترة
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول الزيارات -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="card-header bg-white py-3 px-4 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-list-check text-primary me-2"></i>قائمة الزيارات
                </h5>
                <span class="text-muted small">عرض {{ $visits->firstItem() ?? 0 }} إلى {{ $visits->lastItem() ?? 0 }} من أصل {{ $visits->total() }} زيارة</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-visits table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">#</th>
                            <th>التاريخ والوقت</th>
                            <th>المريض</th>
                            <th>الطبيب المعالج</th>
                            <th>العيادة</th>
                            <th>نوع الزيارة</th>
                            <th>الحالة</th>
                            <th>الشكوى والأعراض</th>
                            <th>التشخيص</th>
                            <th class="text-center pe-4">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($visits as $visit)
                        <tr>
                            <td class="ps-4 fw-bold text-muted">{{ $visit->id }}</td>
                            <td>
                                <div class="fw-bold">{{ $visit->visit_date ? $visit->visit_date->format('Y-m-d') : '-' }}</div>
                                <div class="text-muted small">{{ $visit->visit_time ? $visit->visit_time->format('h:i A') : '' }}</div>
                            </td>
                            <td>
                                <a href="{{ route('patients.show', $visit->patient_id) }}" class="fw-bold text-decoration-none text-primary">
                                    {{ optional($visit->patient?->user)->name ?? 'غير معروف' }}
                                </a>
                                <div class="text-muted small">
                                    <i class="fas fa-phone-alt me-1 text-muted" style="font-size: 0.75rem;"></i>{{ optional($visit->patient?->user)->phone ?? '-' }}
                                    @if($visit->patient?->file_number)
                                        <span class="badge bg-light text-dark ms-1">#{{ $visit->patient->file_number }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">د. {{ optional($visit->doctor?->user)->name ?? 'غير محدد' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-dark">{{ optional($visit->department)->name ?? '-' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-primary border border-primary-subtle">{{ $visit->visit_type_text }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $visit->status == 'completed' ? 'success' : ($visit->status == 'in_progress' ? 'warning' : ($visit->status == 'cancelled' ? 'danger' : 'secondary')) }} px-2 py-1">
                                    {{ $visit->status_text }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted small">{{ Str::limit($visit->chief_complaint, 45) ?: '-' }}</span>
                            </td>
                            <td>
                                @php
                                    $diagStr = '-';
                                    if (is_array($visit->diagnosis)) {
                                        $diagStr = $visit->diagnosis['description'] ?? ($visit->diagnosis['name'] ?? '-');
                                    } elseif (!empty($visit->diagnosis)) {
                                        $diagStr = (string)$visit->diagnosis;
                                    }
                                @endphp
                                @if($diagStr !== '-')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle fw-semibold">{{ Str::limit($diagStr, 40) }}</span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif

                                @if($visit->needs_surgery)
                                    <div><span class="badge bg-danger mt-1"><i class="fas fa-procedures me-1"></i>يحتاج عملية</span></div>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group btn-group-sm">
                                    @if(auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || auth()->user()->can('view visits'))
                                    <a href="{{ route('visits.show', $visit) }}" class="btn btn-outline-primary" title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @endif

                                    @if($visit->latestPrescription && \Illuminate\Support\Facades\Route::has('doctor.visits.prescription.print'))
                                    <a href="{{ route('doctor.visits.prescription.print', $visit) }}" target="_blank" class="btn btn-outline-success" title="طباعة الوصفة الطبية">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    @endif

                                    @if(auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || auth()->user()->can('edit visits'))
                                    <a href="{{ route('visits.edit', $visit) }}" class="btn btn-outline-warning" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="fas fa-calendar-xmark fa-3x mb-3 text-secondary opacity-50"></i>
                                <h6 class="fw-bold">لا توجد زيارات مسجلة تطابق خيارات البحث</h6>
                                <p class="text-muted small mb-0">يمكنك تعديل خيارات الفلترة أو تسجيل زيارة جديدة</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($visits->hasPages())
            <div class="d-flex justify-content-center p-3 border-top">
                {{ $visits->links('pagination::bootstrap-5') }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection