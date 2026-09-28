@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- الترويسة وشريط العمليات -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h3 fw-bold text-primary mb-1">
                <i class="fas fa-eye me-2"></i>استقبال وطابور مركز العيون
            </h2>
            <p class="text-muted mb-0 small">إدارة مراجعي عيادات العيون، حجز المواعيد، ومتابعة تدفق الحالات لحظياً</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('eye.cashier.index') }}" class="btn btn-outline-success">
                <i class="fas fa-cash-register me-1"></i>كاشير العيون
            </a>
            <a href="{{ route('eye.store.index') }}" class="btn btn-outline-info">
                <i class="fas fa-boxes me-1"></i>مخزن العيون والعدسات
            </a>
            <a href="{{ route('eye.reception.create') }}" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i>حجز موعد عيون جديد
            </a>
        </div>
    </div>

    <!-- بطاقات الإحصائيات السريعة لليوم -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-primary text-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small">إجمالي اليوم</div>
                            <h3 class="fw-bold mb-0 mt-1">{{ $stats['total'] }}</h3>
                        </div>
                        <i class="fas fa-users fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-warning text-dark h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-dark small fw-semibold">في الانتظار</div>
                            <h3 class="fw-bold mb-0 mt-1">{{ $stats['waiting'] }}</h3>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-info text-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small">في العيادة</div>
                            <h3 class="fw-bold mb-0 mt-1">{{ $stats['in_clinic'] }}</h3>
                        </div>
                        <i class="fas fa-user-md fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-purple text-white h-100" style="background-color: #6f42c1;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small">في غرفة الأجهزة (OCT/ساحة)</div>
                            <h3 class="fw-bold mb-0 mt-1">{{ $stats['in_investigation'] }}</h3>
                        </div>
                        <i class="fas fa-microscope fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card border-0 shadow-sm rounded-3 bg-success text-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small">اكتمل كشفهم</div>
                            <h3 class="fw-bold mb-0 mt-1">{{ $stats['completed'] }}</h3>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط البحث والفلترة -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('eye.reception.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="ابحث باسم المريض، الهاتف، أو رقم الموعد..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="visit_type" class="form-select">
                        <option value="">-- كل أنواع المراجعة --</option>
                        <option value="consultation" {{ request('visit_type') == 'consultation' ? 'selected' : '' }}>كشف استشاري عيون</option>
                        <option value="optometry" {{ request('visit_type') == 'optometry' ? 'selected' : '' }}>فحص بصريات ونظارات</option>
                        <option value="investigation" {{ request('visit_type') == 'investigation' ? 'selected' : '' }}>فحص أجهزة (OCT/ساحة)</option>
                        <option value="procedure" {{ request('visit_type') == 'procedure' ? 'selected' : '' }}>إجراء / حقن شبكية</option>
                        <option value="follow_up" {{ request('visit_type') == 'follow_up' ? 'selected' : '' }}>مراجعة دورية</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">-- كل الحالات --</option>
                        <option value="waiting" {{ request('status') == 'waiting' ? 'selected' : '' }}>في الانتظار</option>
                        <option value="in_clinic" {{ request('status') == 'in_clinic' ? 'selected' : '' }}>في العيادة</option>
                        <option value="in_investigation" {{ request('status') == 'in_investigation' ? 'selected' : '' }}>في غرفة الأجهزة</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتمل</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">تصفية</button>
                    <a href="{{ route('eye.reception.index') }}" class="btn btn-outline-secondary"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول طابور المراجعين اليومي -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-list-ol me-2 text-primary"></i>قائمة المراجعين اليومية (#طابور العيون)</h5>
            <span class="badge bg-light text-dark border">العدد: {{ $appointments->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 80px;"># الطابور</th>
                        <th>رقم الموعد</th>
                        <th>المريض</th>
                        <th>نوع المراجعة</th>
                        <th>الطبيب المعالج</th>
                        <th>حالة الكاشير</th>
                        <th>الحالة السريرية</th>
                        <th class="text-center" style="width: 180px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $app)
                    <tr>
                        <td class="text-center">
                            <span class="badge bg-primary rounded-pill fs-6 px-3 py-2">#{{ $app->queue_number }}</span>
                        </td>
                        <td>
                            <span class="fw-bold text-dark font-monospace">{{ $app->appointment_number }}</span>
                            <div class="small text-muted">{{ $app->created_at->format('h:i A') }}</div>
                        </td>
                        <td>
                            <div class="fw-bold text-primary">{{ $app->patient->name }}</div>
                            <div class="small text-muted">
                                <span>{{ $app->patient->gender == 'male' ? 'ذكر' : 'أنثى' }}</span> | 
                                <span>{{ $app->patient->age ?? '-' }} سنة</span> | 
                                <span class="badge bg-secondary-subtle text-secondary">{{ $app->insurance_type == 'cash' ? 'نقدي' : 'تأمين صحي' }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                                {{ $app->visit_type_arabic }}
                            </span>
                            @if($app->chief_complaint)
                            <div class="small text-muted text-truncate" style="max-width: 180px;" title="{{ $app->chief_complaint }}">
                                {{ $app->chief_complaint }}
                            </div>
                            @endif
                        </td>
                        <td>
                            @if($app->doctor)
                                <span class="fw-semibold text-dark">{{ $app->doctor->user->name ?? 'طبيب العيون' }}</span>
                            @else
                                <span class="text-muted">أي طبيب متاح</span>
                            @endif
                        </td>
                        <td>
                            @if($app->latestInvoice)
                                @if($app->latestInvoice->status == 'paid')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="fas fa-check-circle me-1"></i>مدفوع ({{ number_format($app->latestInvoice->paid_amount) }})
                                    </span>
                                @else
                                    <a href="{{ route('eye.cashier.show', $app->latestInvoice) }}" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none">
                                        <i class="fas fa-hourglass-half me-1"></i>بانتظار الدفع ({{ number_format($app->latestInvoice->net_amount) }})
                                    </a>
                                @endif
                            @else
                                <span class="badge bg-secondary">لا توجد فاتورة</span>
                            @endif
                        </td>
                        <td>
                            @if($app->status == 'waiting')
                                <span class="badge bg-warning text-dark">في الانتظار</span>
                            @elseif($app->status == 'in_clinic')
                                <span class="badge bg-primary">في عيادة الفحص</span>
                            @elseif($app->status == 'in_investigation')
                                <span class="badge bg-purple text-white" style="background-color: #6f42c1;">غرفة الفحوصات</span>
                            @elseif($app->status == 'completed')
                                <span class="badge bg-success">اكتمل</span>
                            @else
                                <span class="badge bg-secondary">{{ $app->status }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <!-- فتح محطة الفحص السريري -->
                                <a href="{{ route('eye.examinations.create', ['appointment_id' => $app->id]) }}" class="btn btn-outline-primary" title="فتح محطة كشف العيون">
                                    <i class="fas fa-stethoscope me-1"></i>كشف
                                </a>
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <li>
                                        <form action="{{ route('eye.reception.updateStatus', $app) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="in_clinic">
                                            <button type="submit" class="dropdown-item"><i class="fas fa-door-open me-2 text-primary"></i>استدعاء إلى العيادة</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form action="{{ route('eye.reception.updateStatus', $app) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="in_investigation">
                                            <button type="submit" class="dropdown-item"><i class="fas fa-microscope me-2 text-info"></i>تحويل لغرفة الأجهزة</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form action="{{ route('eye.reception.updateStatus', $app) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="dropdown-item"><i class="fas fa-check-double me-2 text-success"></i>إنهاء الزيارة</button>
                                        </form>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    @if($app->latestInvoice)
                                    <li>
                                        <a href="{{ route('eye.cashier.printReceipt', $app->latestInvoice) }}" class="dropdown-item" target="_blank">
                                            <i class="fas fa-print me-2 text-secondary"></i>طباعة الوصل
                                        </a>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-user-clock fa-3x mb-3 text-secondary opacity-50"></i>
                            <div class="h5">لا توجد مواعيد عيون مسجلة لليوم حتى الآن</div>
                            <p class="small">انقر على زر "حجز موعد عيون جديد" للبدء</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($appointments->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $appointments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
