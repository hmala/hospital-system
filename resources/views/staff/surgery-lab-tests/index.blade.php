@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-flask me-2"></i>
                    طلبات المختبر للعمليات الجراحية
                </h2>
                <div class="stats-summary">
                    <span class="badge bg-info me-2">
                        <i class="fas fa-clock me-1"></i>
                        {{ $labTests->where('status', 'pending')->count() }} في الانتظار
                    </span>
                    <span class="badge bg-success me-2">
                        <i class="fas fa-check-circle me-1"></i>
                        {{ $labTests->where('status', 'completed')->count() }} مكتمل
                    </span>
                    <span class="badge bg-danger">
                        <i class="fas fa-times-circle me-1"></i>
                        {{ $labTests->where('status', 'cancelled')->count() }} ملغي
                    </span>
                </div>
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
                البحث والتصفية
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('staff.surgery-lab-tests.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">البحث</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="ابحث بالمريض أو نوع العملية أو التحليل...">
                    </div>
                </div>

                <div class="col-md-2">
                    <label for="status_filter" class="form-label">الحالة</label>
                    <select class="form-select" id="status_filter" name="status">
                        <option value="">جميع الحالات</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>في الانتظار</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتمل</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغي</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="date_from" class="form-label">من تاريخ</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>

                <div class="col-md-2">
                    <label for="date_to" class="form-label">إلى تاريخ</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>بحث
                        </button>
                        <a href="{{ route('staff.surgery-lab-tests.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i>إعادة تعيين
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
                قائمة الطلبات المختبرية
                @if(request('search') || request('status') || request('date_from') || request('date_to'))
                <small class="text-light ms-2">(تم تطبيق التصفية)</small>
                @endif
            </h6>
        </div>
        <div class="card-body">
            @if($labTests->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 60px;">#</th>
                            <th>المريض</th>
                            <th>نوع العملية</th>
                            <th>تاريخ العملية</th>
                            <th>اسم التحليل</th>
                            <th class="text-center">الحالة</th>
                            <th>تاريخ الطلب</th>
                            <th class="text-center" style="width: 150px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($labTests as $index => $test)
                        <tr class="test-row {{ $test->status == 'pending' ? 'table-warning' : ($test->status == 'completed' ? 'table-success' : 'table-danger') }}">
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #007bff, #6610f2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                        {{ substr($test->surgery->patient->user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('patients.show', $test->surgery->patient) }}" class="text-decoration-none fw-bold">
                                            {{ $test->surgery->patient->user->name }}
                                        </a>
                                        <br>
                                        <small class="text-muted">ID: {{ $test->surgery->patient->id }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $test->surgery->surgery_type }}</span>
                            </td>
                            <td>
                                <i class="fas fa-calendar-alt text-primary me-1"></i>
                                {{ $test->surgery->scheduled_date->format('Y-m-d') }}
                                <br>
                                <small class="text-muted">{{ $test->surgery->scheduled_time }}</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-vial text-success me-2"></i>
                                    <div>
                                        <strong>{{ $test->labTest->name }}</strong>
                                        @if($test->labTest->category)
                                        <br><small class="text-muted">{{ $test->labTest->category }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $test->status_color }} fs-6">
                                    <i class="fas {{ $test->status == 'pending' ? 'fa-clock' : ($test->status == 'completed' ? 'fa-check-circle' : 'fa-times-circle') }} me-1"></i>
                                    {{ $test->status_text }}
                                </span>
                            </td>
                            <td>
                                <i class="fas fa-clock text-info me-1"></i>
                                {{ $test->created_at->format('Y-m-d') }}
                                <br>
                                <small class="text-muted">{{ $test->created_at->format('H:i') }}</small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('staff.surgery-lab-tests.show', $test) }}" class="btn btn-sm btn-outline-primary" title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($test->status == 'pending')
                                    <button class="btn btn-sm btn-outline-success" title="تحديث النتائج" onclick="quickUpdate({{ $test->id }}, 'completed')">
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
                    عرض {{ $labTests->firstItem() }} إلى {{ $labTests->lastItem() }} من أصل {{ $labTests->total() }} طلب
                </div>
                <div>
                    {{ $labTests->appends(request()->query())->links() }}
                </div>
            </div>
            @else
            <div class="text-center py-5">
                <i class="fas fa-flask fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">لا توجد طلبات مختبرية للعمليات</h4>
                <p class="text-muted">
                    @if(request('search') || request('status') || request('date_from') || request('date_to'))
                    لا توجد نتائج تطابق معايير البحث المحددة
                    @else
                    جميع الطلبات المختبرية للعمليات مكتملة أو لا توجد طلبات حالياً
                    @endif
                </p>
                @if(request('search') || request('status') || request('date_from') || request('date_to'))
                <a href="{{ route('staff.surgery-lab-tests.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-undo me-1"></i>عرض جميع الطلبات
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function quickUpdate(testId, status) {
    if (confirm('هل أنت متأكد من تحديث حالة هذا التحليل؟')) {
        // إنشاء نموذج مؤقت للإرسال
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/staff/surgery-lab-tests/${testId}`;

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

        // إضافة نتيجة فارغة للتحديث السريع
        const resultField = document.createElement('input');
        resultField.type = 'hidden';
        resultField.name = 'result';
        resultField.value = 'تم التحديث السريع - يرجى إدخال النتائج التفصيلية';
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