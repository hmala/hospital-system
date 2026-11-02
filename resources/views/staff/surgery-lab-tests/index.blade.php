@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white p-2.5 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                        <i class="fas fa-flask fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            تحاليل وفحوصات العمليات الجراحية
                        </h4>
                        <small class="text-muted">
                            تحديد واختيار التحاليل المخبرية للمرضى المحجوزين للعمليات ومتابعة النتائج والاعتماد
                        </small>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('lab.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold rounded-pill px-3">
                        <i class="fas fa-arrow-left me-1"></i> العودة للمختبر العام
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Tabs Switcher Bar -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 p-2 bg-light rounded-4 border">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('staff.surgery-lab-tests.index', array_merge(request()->except(['tab', 'selection_page', 'page']), ['tab' => 'pending_selection'])) }}" 
               class="btn rounded-pill px-3 py-1.5 fw-bold {{ $activeTab === 'pending_selection' ? 'btn-warning text-dark shadow-sm' : 'btn-outline-secondary' }}">
                <i class="fas fa-hourglass-half me-1"></i>
                عمليات بانتظار تحديد التحاليل
                <span class="badge {{ $activeTab === 'pending_selection' ? 'bg-dark text-white' : 'bg-warning text-dark' }} rounded-pill ms-1">
                    {{ $pendingSelectionCount }}
                </span>
            </a>

            <a href="{{ route('staff.surgery-lab-tests.index', array_merge(request()->except(['tab', 'selection_page', 'page']), ['tab' => 'active_tests'])) }}" 
               class="btn rounded-pill px-3 py-1.5 fw-bold {{ $activeTab === 'active_tests' ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary' }}">
                <i class="fas fa-check-circle me-1"></i>
                طلبات الفحوصات والنتائج
                <span class="badge {{ $activeTab === 'active_tests' ? 'bg-white text-primary' : 'bg-secondary text-white' }} rounded-pill ms-1">
                    {{ $surgeries->total() }}
                </span>
            </a>
        </div>

        @if($activeTab === 'active_tests')
            <div class="stats-summary d-flex gap-2">
                <span class="badge bg-info text-dark rounded-pill px-2.5 py-1.5 fw-bold">
                    <i class="fas fa-clock me-1"></i> {{ $stats['pending'] }} في الانتظار
                </span>
                <span class="badge bg-success text-white rounded-pill px-2.5 py-1.5 fw-bold">
                    <i class="fas fa-check-circle me-1"></i> {{ $stats['completed'] }} مكتمل
                </span>
                @if($stats['cancelled'] > 0)
                <span class="badge bg-danger text-white rounded-pill px-2.5 py-1.5 fw-bold">
                    <i class="fas fa-times-circle me-1"></i> {{ $stats['cancelled'] }} ملغي
                </span>
                @endif
            </div>
        @endif
    </div>

    @if($activeTab === 'pending_selection')
        <!-- ======================= تبويب 1: العمليات بانتظار تحديد التحاليل ======================= -->
        <div class="card shadow-sm border-0 mb-3 rounded-4">
            <div class="card-body p-3">
                <form method="GET" action="{{ route('staff.surgery-lab-tests.index') }}" class="row g-2 align-items-center">
                    <input type="hidden" name="tab" value="pending_selection">
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" name="search" value="{{ request('search') }}" placeholder="ابحث باسم المريض أو نوع العملية المحجوزة...">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-warning text-dark fw-bold w-100 rounded-pill shadow-xs">
                            <i class="fas fa-search me-1"></i> بحث
                        </button>
                        @if(request('search'))
                            <a href="{{ route('staff.surgery-lab-tests.index', ['tab' => 'pending_selection']) }}" class="btn btn-outline-secondary rounded-pill">
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-warning bg-opacity-25 border-bottom p-3">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span>
                        <i class="fas fa-procedures text-warning me-2"></i> 
                        العمليات الجراحية المحجوزة بانتظار تحديد باقة التحاليل ({{ $pendingSelectionCount }})
                    </span>
                    <small class="text-muted fw-normal">حدد الفحوصات المخبرية المطلوبة للمريض قبل دخوله صالة العمليات</small>
                </h6>
            </div>
            <div class="card-body p-0">
                @if($pendingSelectionSurgeries->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th>المريض والموقع</th>
                                    <th>العملية الجراحية</th>
                                    <th>الطبيب / الجراح</th>
                                    <th>موعد العملية</th>
                                    <th class="text-center">حالة العملية</th>
                                    <th class="text-center" style="width: 180px;">الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingSelectionSurgeries as $surgery)
                                <tr>
                                    <td class="text-center fw-bold text-muted">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex justify-content-center align-items-center me-2 shadow-xs" style="width: 38px; height: 38px;">
                                                <i class="fas fa-user-injured fs-6"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold text-dark d-block">{{ $surgery->patient?->user?->name ?? 'غير معروف' }}</span>
                                                <div class="d-flex align-items-center gap-1 mt-0.5 flex-wrap">
                                                    <small class="text-muted">ملف: #{{ $surgery->patient?->file_number ?? $surgery->patient_id }}</small>
                                                    @if($surgery->room)
                                                        <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 0.75rem;">
                                                            <i class="fas fa-bed text-primary me-1"></i> غرفة {{ $surgery->room->room_number }} ({{ $surgery->room->floor }})
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark">{{ $surgery->surgery_type }}</span>
                                        @if($surgery->surgery_category)
                                            <br><span class="badge bg-secondary-subtle text-secondary rounded-pill" style="font-size: 0.72rem;">{{ $surgery->surgery_category }}</span>
                                        @endif
                                        @if($surgery->surgery_fee_paid !== 'paid')
                                            <br><span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill mt-1" style="font-size: 0.7rem;">
                                                <i class="fas fa-clock me-1"></i> الرسوم معلقة
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-dark fw-semibold">{{ $surgery->doctor?->user?->name ?? $surgery->referring_doctor_name ?? 'غير محدد' }}</span>
                                    </td>
                                    <td>
                                        @if($surgery->scheduled_date)
                                            <i class="fas fa-calendar-alt text-primary me-1"></i> {{ $surgery->scheduled_date->format('Y-m-d') }}
                                            <br><small class="text-muted">{{ $surgery->scheduled_time ? date('h:i A', strtotime($surgery->scheduled_time)) : '-' }}</small>
                                        @else
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill px-2.5 py-1 fw-bold {{ $surgery->status === 'waiting' ? 'bg-warning text-dark' : ($surgery->status === 'in_progress' ? 'bg-info text-dark' : 'bg-primary') }}">
                                            {{ $surgery->status === 'waiting' ? 'بانتظار الصالة' : ($surgery->status === 'in_progress' ? 'قيد التجهيز' : 'مجدولة') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('staff.surgery-lab-tests.create-selection', $surgery) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary fw-bold rounded-pill px-3 py-1.5 shadow-xs">
                                                <i class="fas fa-vial me-1"></i> تحديد باقة التحاليل
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">
                        {{ $pendingSelectionSurgeries->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="bg-success-subtle text-success rounded-circle d-inline-flex p-3 mb-3">
                            <i class="fas fa-check-circle fa-3x"></i>
                        </div>
                        <h5 class="fw-bold text-dark">لا توجد عمليات بانتظار تحديد التحاليل</h5>
                        <p class="text-muted small">جميع العمليات الجراحية المحجوزة تم تحديد واختيار باقات تحاليلها بنجاح.</p>
                    </div>
                @endif
            </div>
        </div>

    @else
        <!-- ======================= تبويب 2: طلبات الفحوصات والنتائج الجارية ======================= -->
        <div class="card shadow-sm border-0 mb-4 rounded-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-search me-2 text-primary"></i>
                    البحث وتصفية طلبات الفحوصات
                </h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('staff.surgery-lab-tests.index') }}" class="row g-3">
                    <input type="hidden" name="tab" value="active_tests">
                    <div class="col-md-4">
                        <label for="search" class="form-label small fw-bold text-muted">البحث</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="ابحث بالمريض أو نوع العملية أو التحليل...">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label for="status_filter" class="form-label small fw-bold text-muted">الحالة</label>
                        <select class="form-select" id="status_filter" name="status">
                            <option value="">جميع الحالات</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>في الانتظار</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتمل</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغي</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="date_from" class="form-label small fw-bold text-muted">من تاريخ</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-2">
                        <label for="date_to" class="form-label small fw-bold text-muted">إلى تاريخ</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <div class="d-grid gap-2 d-md-flex w-100">
                            <button type="submit" class="btn btn-primary fw-bold rounded-pill shadow-xs w-100">
                                <i class="fas fa-search me-1"></i> تصفية
                            </button>
                            <a href="{{ route('staff.surgery-lab-tests.index', ['tab' => 'active_tests']) }}" class="btn btn-outline-secondary rounded-pill">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-primary text-white p-3">
                <h6 class="mb-0 fw-bold d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span>
                        <i class="fas fa-list me-2"></i>
                        سجل الفحوصات والنتائج المخبرية للعمليات الجراحية
                    </span>
                    @if(request('search') || request('status') || request('date_from') || request('date_to'))
                        <small class="badge bg-light text-primary rounded-pill px-2.5 py-1">مُفلترة</small>
                    @endif
                </h6>
            </div>
            <div class="card-body p-0">
                @if($surgeries->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>المريض</th>
                                <th>نوع العملية</th>
                                <th>موعد العملية</th>
                                <th>التحاليل المطلوبة</th>
                                <th class="text-center">الحالة الإجمالية</th>
                                <th>تاريخ الطلب</th>
                                <th class="text-center" style="width: 150px;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($surgeries as $surgery)
                            @php
                                $firstTest = $surgery->labTests->first();
                                $testCount = $surgery->labTests->count();
                                $pendingCount = $surgery->labTests->where('status', 'pending')->count();
                                $completedCount = $surgery->labTests->where('status', 'completed')->count();
                                $cancelledCount = $surgery->labTests->where('status', 'cancelled')->count();
                                
                                if ($pendingCount > 0) {
                                    $statusClass = 'table-warning';
                                    $statusText = 'قيد الانتظار';
                                    $badgeClass = 'bg-warning text-dark';
                                } elseif ($completedCount > 0) {
                                    $statusClass = 'table-success';
                                    $statusText = 'مكتملة';
                                    $badgeClass = 'bg-success text-white';
                                } else {
                                    $statusClass = 'table-danger';
                                    $statusText = 'ملغاة';
                                    $badgeClass = 'bg-danger text-white';
                                }
                            @endphp
                            <tr class="test-row {{ $statusClass }}" data-surgery-id="{{ $surgery->id }}">
                                <td class="text-center fw-bold text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-2" style="width: 34px; height: 34px; background: linear-gradient(135deg, #007bff, #6610f2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                            {{ optional($surgery->patient?->user)->name ? mb_substr($surgery->patient->user->name, 0, 1, 'utf-8') : '?' }}
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark d-block">{{ optional($surgery->patient?->user)->name ?? 'غير معروف' }}</span>
                                            <div class="d-flex align-items-center gap-1 mt-0.5 flex-wrap">
                                                <small class="text-muted">ملف: #{{ $surgery->patient?->file_number ?? $surgery->patient_id }}</small>
                                                @if($surgery->room)
                                                    <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
                                                        <i class="fas fa-bed text-primary me-1"></i> غرفة {{ $surgery->room->room_number }} ({{ $surgery->room->floor }})
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark rounded-pill">{{ $surgery->surgery_type }}</span>
                                    @if($surgery->surgery_fee_paid !== 'paid')
                                    <br>
                                    <span class="badge bg-danger rounded-pill mt-1" style="font-size: 0.7rem;" title="لا يمكن إجراء التحليل قبل دفع رسوم العملية">
                                        <i class="fas fa-exclamation-triangle me-1"></i> رسوم معلقة
                                    </span>
                                    @endif
                                </td>
                                <td>
                                    @if($surgery->scheduled_date)
                                    <i class="fas fa-calendar-alt text-primary me-1"></i>
                                    {{ $surgery->scheduled_date->format('Y-m-d') }}
                                    <br>
                                    <small class="text-muted">{{ $surgery->scheduled_time ? date('h:i A', strtotime($surgery->scheduled_time)) : '-' }}</small>
                                    @else
                                    <span class="text-muted">غير محدد</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($surgery->labTests as $sTest)
                                            <span class="badge bg-light text-dark border rounded-pill px-2 py-1" style="font-size: 0.75rem;" title="{{ $sTest->status == 'completed' ? 'مكتمل' : ($sTest->status == 'cancelled' ? 'ملغي' : 'في الانتظار') }}">
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
                                    <span class="badge {{ $badgeClass }} rounded-pill px-2.5 py-1 fw-bold">
                                        <i class="fas {{ $pendingCount > 0 ? 'fa-clock' : ($completedCount > 0 ? 'fa-check-circle' : 'fa-times-circle') }} me-1"></i>
                                        {{ $statusText }}
                                    </span>
                                    <br>
                                    <small class="text-muted" style="font-size: 0.75rem;">({{ $completedCount }} من {{ $testCount }} مكتمل)</small>
                                </td>
                                <td>
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
                                        <a href="{{ route('staff.surgery-lab-tests.show', $firstTest) }}" class="btn btn-sm btn-outline-primary rounded-pill px-2.5" title="عرض التفاصيل وإدخال النتائج">
                                            <i class="fas fa-eye me-1"></i> عرض وإدخال
                                        </a>
                                        @endif
                                        @if($completedCount > 0 && $firstTest)
                                        <a href="{{ route('staff.surgery-lab-tests.print', $firstTest) }}"
                                           class="btn btn-sm btn-outline-success rounded-pill ms-1"
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

                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <div class="text-muted small">
                        عرض {{ $surgeries->firstItem() }} إلى {{ $surgeries->lastItem() }} من أصل {{ $surgeries->total() }} عملية
                    </div>
                    <div>
                        {{ $surgeries->appends(request()->query())->links() }}
                    </div>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-flask fa-3x text-muted mb-3 opacity-50"></i>
                    <h5 class="fw-bold text-dark">لا توجد طلبات تحاليل جارية أو مكتملة</h5>
                    <p class="text-muted small mb-0">
                        @if(request('search') || request('status') || request('date_from') || request('date_to'))
                            لا توجد نتائج تطابق معايير البحث المحددة.
                        @else
                            لم يتم إدخال نتائج تحاليل لأي عملية بعد، يمكنك الانتقال لتبويب العمليات بانتظار تحديد التحاليل للبدء.
                        @endif
                    </p>
                </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
