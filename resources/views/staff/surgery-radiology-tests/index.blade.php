@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    @php
        $pendingSelectionCount = \App\Models\Surgery::whereIn('status', ['scheduled', 'waiting', 'in_progress'])
            ->whereDoesntHave('radiologyTests', fn($q) => $q->whereNotNull('radiology_type_id'))
            ->count();
    @endphp

    <!-- الرأس التنفيذي والزر البارز للعمليات التي تحتاج اختيار أشعة -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border: 1px solid #e2e8f0 !important;">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-4 d-flex align-items-center justify-content-center shadow-xs" 
                         style="width: 52px; height: 52px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #fff;">
                        <i class="fas fa-x-ray fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-1 text-slate-800" style="font-size: 1.35rem;">
                            {{ __("طلبات الأشعة للعمليات الجراحية") }}
                        </h3>
                        <p class="text-muted small mb-0">
                            {{ __("إدارة ومتابعة طلبات وفحوصات الأشعة والتصوير الطبي لمرضى صالات العمليات") }}
                        </p>
                    </div>
                </div>

                <!-- زر بارز جداً ومميز للعمليات التي تحتاج اختيار أشعة -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="{{ route('staff.surgery-radiology-tests.selection') }}" 
                       class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold shadow-sm d-inline-flex align-items-center gap-2 text-white"
                       style="background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%); border: none; font-size: 0.95rem;">
                        <i class="fas fa-procedures fa-lg"></i>
                        <span>{{ __("العمليات التي تحتاج اختيار أشعة") }}</span>
                        @if($pendingSelectionCount > 0)
                            <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1 font-monospace fw-bold shadow-xs">
                                {{ $pendingSelectionCount }} {{ __('عملية') }}
                            </span>
                        @else
                            <span class="badge bg-white bg-opacity-25 text-white rounded-pill px-2 py-0.5 small">0</span>
                        @endif
                        <i class="fas fa-arrow-left fa-xs ms-1"></i>
                    </a>
                </div>
            </div>

            <hr class="my-3 text-muted opacity-25">

            <!-- مؤشرات سريعة هادئة (بدون أحمر) -->
            <div class="d-flex flex-wrap align-items-center gap-2.5">
                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3 py-1.5 fw-semibold" style="font-size: 0.82rem;">
                    <i class="fas fa-clock me-1"></i>
                    {{ __('في الانتظار:') }} <strong class="font-monospace ms-1">{{ $radiologyTests->where('status', 'pending')->count() }}</strong>
                </span>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-semibold" style="font-size: 0.82rem;">
                    <i class="fas fa-check-circle me-1"></i>
                    {{ __('مكتمل:') }} <strong class="font-monospace ms-1">{{ $radiologyTests->where('status', 'completed')->count() }}</strong>
                </span>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1.5 fw-semibold" style="font-size: 0.82rem;">
                    <i class="fas fa-ban me-1"></i>
                    {{ __('ملغي:') }} <strong class="font-monospace ms-1">{{ $radiologyTests->where('status', 'cancelled')->count() }}</strong>
                </span>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- شريط البحث والتصفية -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0">
                <i class="fas fa-search me-2"></i>
                {{ __("البحث والتصفية") }}
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('staff.surgery-radiology-tests.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">{{ __("البحث") }}</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="{{ __("ابحث بالمريض أو نوع العملية أو التصوير...") }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <label for="status_filter" class="form-label">{{ __("الحالة") }}</label>
                    <select class="form-select" id="status_filter" name="status">
                        <option value="">{{ __("جميع الحالات") }}</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __("في الانتظار") }}</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>{{ __("مكتمل") }}</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ __("ملغي") }}</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="date_from" class="form-label">{{ __("من تاريخ") }}</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>

                <div class="col-md-2">
                    <label for="date_to" class="form-label">{{ __("إلى تاريخ") }}</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>{{ __("بحث") }}
                        </button>
                        <a href="{{ route('staff.surgery-radiology-tests.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i>{{ __("إعادة تعيين") }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول النتائج -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0">
                <i class="fas fa-list me-2"></i>
                {{ __("قائمة الطلبات الإشعاعية") }}
                @if(request('search') || request('status') || request('date_from') || request('date_to'))
                <small class="text-light ms-2">({{ __("تم تطبيق التصفية") }})</small>
                @endif
            </h6>
        </div>
        <div class="card-body">
            @if($radiologyTests->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 60px;">#</th>
                            <th>{{ __("المريض") }}</th>
                            <th>{{ __("نوع العملية") }}</th>
                            <th>{{ __("تاريخ العملية") }}</th>
                            <th>{{ __("نوع التصوير") }}</th>
                            <th class="text-center">{{ __("الحالة") }}</th>
                            <th>{{ __("تاريخ الطلب") }}</th>
                            <th class="text-center" style="width: 150px;">{{ __("الإجراءات") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($radiologyTests as $index => $test)
                        <tr class="test-row">
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #007bff, #6610f2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                        {{ optional($test->surgery?->patient?->user)->name ? substr($test->surgery->patient->user->name, 0, 1) : '?' }}
                                    </div>
                                    <div>
                                        @if($test->surgery && $test->surgery->patient)
                                        <a href="{{ route('patients.show', $test->surgery->patient) }}" class="text-decoration-none fw-bold">
                                            {{ optional($test->surgery->patient->user)->name ?? __('غير معروف') }}
                                        </a>
                                        <br>
                                        <small class="text-muted">ID: {{ $test->surgery->patient->id }}</small>
                                        @else
                                        <span class="text-muted">{{ __("غير معروف") }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($test->surgery)
                                <span class="badge bg-info">{{ $test->surgery->surgery_type }}</span>
                                @if($test->payment_status !== 'paid')
                                <br>
                                <span class="badge bg-warning text-dark mt-1" title="{{ __("يمر للفحص ويُحاسب في الكاشير قبل تخريج المريض") }}">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ __("بانتظار الكاشير (يُحاسب قبل التخريج)") }}
                                </span>
                                @else
                                <br>
                                <span class="badge bg-success-subtle text-success border mt-1">
                                    <i class="fas fa-check-circle me-1"></i>
                                    {{ __("مسدد بالكاشير") }}
                                </span>
                                @endif
                                @else
                                <span class="badge bg-secondary">{{ __("غير محدد") }}</span>
                                @endif
                            </td>
                            <td>
                                @if($test->surgery && $test->surgery->scheduled_date)
                                <i class="fas fa-calendar-alt text-primary me-1"></i>
                                {{ $test->surgery->scheduled_date->format('Y-m-d') }}
                                <br>
                                <small class="text-muted">{{ $test->surgery->scheduled_time }}</small>
                                @else
                                <span class="text-muted">{{ __("غير محدد") }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-x-ray text-warning me-2"></i>
                                    <div>
                                        @if($test->radiologyType)
                                            <strong>{{ $test->radiologyType->name }}</strong>
                                            @if($test->radiologyType->code)
                                            <br><small class="text-muted">{{ $test->radiologyType->code }}</small>
                                            @endif
                                        @else
                                            <strong class="text-warning">{{ __("طلب أشعة عام") }}</strong>
                                            <br><small class="text-muted">{{ __("لم يتم تحديد نوع الأشعة بعد") }}</small>
                                        @endif
                                        @if($test->doctor_id && $test->doctor?->user)
                                            <br><span class="badge bg-info-subtle text-info border border-info-subtle mt-1" style="font-size: 0.70rem;">
                                                <i class="fas fa-user-md me-1"></i>{{ __('الفاحص:') }} د. {{ $test->doctor->user->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($test->status === 'completed')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-bold">
                                        <i class="fas fa-check-circle me-1"></i>{{ $test->status_text }}
                                    </span>
                                @elseif($test->status === 'pending')
                                    <span class="badge bg-warning-subtle text-dark border border-warning rounded-pill px-2.5 py-1 fw-bold">
                                        <i class="fas fa-clock me-1 text-warning"></i>{{ $test->status_text }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1 fw-semibold">
                                        <i class="fas fa-ban me-1"></i>{{ $test->status_text }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <i class="fas fa-clock text-info me-1"></i>
                                {{ $test->created_at->format('Y-m-d') }}
                                <br>
                                <small class="text-muted">{{ $test->created_at->format('H:i') }}</small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('staff.surgery-radiology-tests.show', $test) }}" class="btn btn-sm btn-outline-primary" title="{{ __("عرض التفاصيل") }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($test->status == 'completed')
                                    <a href="{{ route('staff.surgery-radiology-tests.print', $test) }}" target="_blank" class="btn btn-sm btn-outline-success me-1" title="{{ __("طباعة الطلب") }}">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    @endif
                                    @if($test->status == 'pending')
                                    <button class="btn btn-sm btn-outline-success" 
                                            title="{{ __('تحديث النتائج') }}" 
                                            onclick="quickUpdate({{ $test->id }}, 'completed')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-muted">
                    {{ __("عرض") }} {{ $radiologyTests->firstItem() }} {{ __("إلى") }} {{ $radiologyTests->lastItem() }} {{ __("من أصل") }} {{ $radiologyTests->total() }} {{ __("طلب") }}
                </div>
                <div>
                    {{ $radiologyTests->appends(request()->query())->links() }}
                </div>
            </div>
            @else
            <div class="text-center py-5">
                <i class="fas fa-x-ray fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">{{ __("لا توجد طلبات إشعاعية للعمليات") }}</h4>
                <p class="text-muted">
                    @if(request('search') || request('status') || request('date_from') || request('date_to'))
                    {{ __("لا توجد نتائج تطابق معايير البحث المحددة") }}
                    @else
                    {{ __("جميع الطلبات الإشعاعية للعمليات مكتملة أو لا توجد طلبات حالياً") }}
                    @endif
                </p>
                @if(request('search') || request('status') || request('date_from') || request('date_to'))
                <a href="{{ route('staff.surgery-radiology-tests.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-undo me-1"></i>{{ __("عرض جميع الطلبات") }}
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function quickUpdate(testId, status) {
    if (confirm('{{ __("هل أنت متأكد من تحديث حالة هذا التصوير الإشعاعي؟") }}')) {
        // إنشاء نموذج مؤقت للإرسال
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/staff/surgery-radiology-tests/${testId}`;

        // إضافة token CSRF
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);

        // إضافة method PUT
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'PUT';
        form.appendChild(methodField);

        // إضافة الحالة
        const statusField = document.createElement('input');
        statusField.type = 'hidden';
        statusField.name = 'status';
        statusField.value = status;
        form.appendChild(statusField);

        // إضافة تقرير فارغ للتحديث السريع
        const resultField = document.createElement('input');
        resultField.type = 'hidden';
        resultField.name = 'result';
        resultField.value = '{{ __("تم التحديث السريع - يرجى إدخال التقرير التفصيلي") }}';
        form.appendChild(resultField);

        document.body.appendChild(form);
        form.submit();
    }
}

// تحسين البحث التلقائي
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    let searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            if (searchInput.value.length >= 2 || searchInput.value.length === 0) {
                searchInput.closest('form').submit();
            }
        }, 1000);
    });

    // تفعيل tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<style>
.stats-summary {
    font-size: 0.9rem;
}

.card-header {
    border-bottom: 2px solid #dee2e6;
}

.table th {
    background: linear-gradient(135deg, #343a40, #495057);
    color: white;
    font-weight: 600;
    border: none;
    text-align: center;
    vertical-align: middle;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #dee2e6;
}

.test-row {
    transition: all 0.3s ease;
}

.test-row:hover {
    background-color: rgba(0, 123, 255, 0.05) !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.avatar-circle {
    font-size: 0.8rem;
}

.btn-group .btn {
    margin: 0 1px;
    border-radius: 4px !important;
}

.btn-outline-primary:hover, .btn-outline-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.table-warning {
    background-color: rgba(255, 193, 7, 0.1);
}

.table-success {
    background-color: rgba(40, 167, 69, 0.1);
}

.table-danger {
    background-color: rgba(220, 53, 69, 0.1);
}

@media (max-width: 768px) {
    .stats-summary {
        display: none;
    }

    .table-responsive {
        font-size: 0.9rem;
    }

    .btn-group {
        flex-direction: column;
        gap: 2px;
    }

    .btn-group .btn {
        margin: 1px 0;
    }
}
</style>
@endsection