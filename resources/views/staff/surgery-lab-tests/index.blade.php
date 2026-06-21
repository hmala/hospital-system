@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-flask me-2"></i>
                        طلبات المختبر للعمليات الجراحية
                    </h2>
                    <a href="{{ route('staff.surgery-lab-tests.selection') }}" class="btn btn-outline-light btn-sm mt-2">
                        <i class="fas fa-list me-1"></i>
                        العمليات التي تحتاج اختيار تحاليل
                    </a>
                </div>
                <div class="stats-summary realtime-section" data-section="stats">
                    <span class="badge bg-info me-2">
                        <i class="fas fa-clock me-1"></i>
                        {{ $stats['pending'] }} في الانتظار
                    </span>
                    <span class="badge bg-success me-2">
                        <i class="fas fa-check-circle me-1"></i>
                        {{ $stats['completed'] }} مكتمل
                    </span>
                    <span class="badge bg-danger">
                        <i class="fas fa-times-circle me-1"></i>
                        {{ $stats['cancelled'] }} ملغي
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
                قائمة الطلبات المختبرية للعمليات
                @if(request('search') || request('status') || request('date_from') || request('date_to'))
                <small class="text-light ms-2">(تم تطبيق التصفية)</small>
                @endif
            </h6>
        </div>
        <div class="card-body">
            @if($surgeries->count() > 0)
            <div class="table-responsive realtime-section" data-section="table">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 60px;">#</th>
                            <th>المريض</th>
                            <th>نوع العملية</th>
                            <th>تاريخ العملية</th>
                            <th>التحاليل المطلوبة</th>
                            <th class="text-center">الحالة الإجمالية</th>
                            <th>تاريخ الطلب</th>
                            <th class="text-center" style="width: 150px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($surgeries as $index => $surgery)
                        @php
                            $firstTest = $surgery->labTests->first();
                            $testCount = $surgery->labTests->count();
                            $pendingCount = $surgery->labTests->where('status', 'pending')->count();
                            $completedCount = $surgery->labTests->where('status', 'completed')->count();
                            $cancelledCount = $surgery->labTests->where('status', 'cancelled')->count();
                            
                            if ($pendingCount > 0) {
                                $statusClass = 'table-warning';
                                $statusText = 'قيد الانتظار';
                                $badgeClass = 'bg-warning';
                            } elseif ($completedCount > 0) {
                                $statusClass = 'table-success';
                                $statusText = 'مكتملة';
                                $badgeClass = 'bg-success';
                            } else {
                                $statusClass = 'table-danger';
                                $statusText = 'ملغاة';
                                $badgeClass = 'bg-danger';
                            }
                        @endphp
                        <tr class="test-row {{ $statusClass }}" data-surgery-id="{{ $surgery->id }}">
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, #007bff, #6610f2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                        {{ optional($surgery->patient?->user)->name ? mb_substr($surgery->patient->user->name, 0, 1, 'utf-8') : '?' }}
                                    </div>
                                    <div>
                                        @if($surgery->patient)
                                        <a href="{{ route('patients.show', $surgery->patient) }}" class="text-decoration-none fw-bold">
                                            {{ optional($surgery->patient->user)->name ?? 'غير معروف' }}
                                        </a>
                                        <br>
                                        <small class="text-muted">ID: {{ $surgery->patient->id }}</small>
                                        @else
                                        <span class="text-muted">غير معروف</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $surgery->surgery_type }}</span>
                                @if($surgery->surgery_fee_paid !== 'paid')
                                <br>
                                <span class="badge bg-danger mt-1" title="لا يمكن إجراء التحليل قبل دفع رسوم العملية">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    غير مدفوعة
                                </span>
                                @endif
                            </td>
                            <td>
                                @if($surgery->scheduled_date)
                                <i class="fas fa-calendar-alt text-primary me-1"></i>
                                {{ $surgery->scheduled_date->format('Y-m-d') }}
                                <br>
                                <small class="text-muted">{{ $surgery->scheduled_time }}</small>
                                @else
                                <span class="text-muted">غير محدد</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($surgery->labTests as $sTest)
                                        <span class="badge bg-secondary" title="{{ $sTest->status == 'completed' ? 'مكتمل' : ($sTest->status == 'cancelled' ? 'ملغي' : 'في الانتظار') }}">
                                            {{ $sTest->labTest?->name ?? 'طلب عام' }}
                                            @if($sTest->status == 'completed')
                                                <i class="fas fa-check-circle text-success ms-1"></i>
                                            @elseif($sTest->status == 'cancelled')
                                                <i class="fas fa-times-circle text-danger ms-1"></i>
                                            @else
                                                <i class="fas fa-clock text-warning ms-1"></i>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $badgeClass }} fs-6">
                                    <i class="fas {{ $pendingCount > 0 ? 'fa-clock' : ($completedCount > 0 ? 'fa-check-circle' : 'fa-times-circle') }} me-1"></i>
                                    {{ $statusText }}
                                </span>
                                <br>
                                <small class="text-muted">({{ $completedCount }} من {{ $testCount }} مكتمل)</small>
                            </td>
                            <td>
                                <i class="fas fa-clock text-info me-1"></i>
                                @if($surgery->labTests->max('created_at'))
                                    {{ $surgery->labTests->max('created_at')->format('Y-m-d') }}
                                    <br>
                                    <small class="text-muted">{{ $surgery->labTests->max('created_at')->format('H:i') }}</small>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    @if($firstTest)
                                    <a href="{{ route('staff.surgery-lab-tests.show', $firstTest) }}" class="btn btn-sm btn-outline-primary" title="عرض التفاصيل وإدخال النتائج">
                                        <i class="fas fa-eye"></i> عرض وإدخال
                                    </a>
                                    @endif
                                    @if($completedCount > 0 && $firstTest)
                                    <a href="{{ route('staff.surgery-lab-tests.print', $firstTest) }}"
                                       class="btn btn-sm btn-outline-success"
                                       target="_blank"
                                       title="طباعة نتائج العملية">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-muted text-right">
                    عرض {{ $surgeries->firstItem() }} إلى {{ $surgeries->lastItem() }} من أصل {{ $surgeries->total() }} عملية
                </div>
                <div>
                    {{ $surgeries->appends(request()->query())->links() }}
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