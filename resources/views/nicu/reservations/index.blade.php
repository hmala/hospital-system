@extends('layouts.app')

@section('title', 'سجل وإضبارات الخدج - قسم الحاضنات (NICU)')

@section('content')
<div class="container-fluid py-3">
    <!-- رأس الصفحة وأزرار التحكم السريع -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2 class="mb-1 fw-bold text-dark">
                        <i class="fas fa-clipboard-list text-primary me-2"></i>
                        سجل وإضبارات الخدج وحجوزات الحاضنات
                    </h2>
                    <p class="text-muted mb-0">إدارة الأرشيف السريري، كشوفات الإقامة، متابعة المواليد، والحالات المنومة والمخرجة</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('incubator-reservations.create') }}" class="btn btn-success shadow-sm">
                        <i class="fas fa-plus-circle me-1"></i> حجز ودخول خديج جديد
                    </a>
                    <a href="{{ route('nicu.index') }}" class="btn btn-outline-primary shadow-sm">
                        <i class="fas fa-th-large me-1"></i> لوحة الحاضنات اللحظية
                    </a>
                    <a href="{{ route('nicu.services.index') }}" class="btn btn-outline-warning shadow-sm">
                        <i class="fas fa-tags me-1"></i> تسعير وباقات الخدمات
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- التنبيهات -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- بطاقات الإحصائيات السريعة (KPIs) -->
    <div class="row mb-4 g-3">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-primary text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">إجمالي الإضبارات والحجوزات</span>
                        <h3 class="mb-0 fw-bold">{{ number_format($kpis['total']) }}</h3>
                        <small class="opacity-75">سجل الخدج الشامل</small>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-archive fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-success text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">الأطفال بالحاضنات حالياً</span>
                        <h3 class="mb-0 fw-bold">{{ number_format($kpis['admitted']) }}</h3>
                        <small class="opacity-75">إقامة نشطة ورعاية مستمرة</small>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-baby fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-info text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">المخرجون خلال هذا الشهر</span>
                        <h3 class="mb-0 fw-bold">{{ number_format($kpis['discharged_month']) }}</h3>
                        <small class="opacity-75">إجمالي المخرجين: {{ number_format($kpis['discharged']) }}</small>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-warning text-dark h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">حجوزات قيد الانتظار</span>
                        <h3 class="mb-0 fw-bold">{{ number_format($kpis['pending']) }}</h3>
                        <small class="opacity-75">مجدولة لدخول الحاضنة</small>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط البحث والفلترة الذكي -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('nicu.reservations.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label small fw-bold text-muted mb-1">
                            <i class="fas fa-search me-1"></i> بحث شامل
                        </label>
                        <input type="text" name="search" class="form-control form-control-sm" 
                               placeholder="اسم الطفل، الأم، الهاتف، رقم الحاضنة، رقم الحجز..." 
                               value="{{ request('search') }}">
                    </div>

                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">
                            <i class="fas fa-filter me-1"></i> حالة الإقامة
                        </label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="all">كافة الحالات</option>
                            <option value="admitted" {{ request('status') === 'admitted' ? 'selected' : '' }}>داخل الحاضنة (نشط)</option>
                            <option value="discharged" {{ request('status') === 'discharged' ? 'selected' : '' }}>تم الخروج</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                            <option value="transferred" {{ request('status') === 'transferred' ? 'selected' : '' }}>تم النقل</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغي</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">
                            <i class="fas fa-tags me-1"></i> باقة الخدمة
                        </label>
                        <select name="nicu_service_id" class="form-select form-select-sm">
                            <option value="all">كافة الباقات والخدمات</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}" {{ request('nicu_service_id') == $service->id ? 'selected' : '' }}>
                                    {{ $service->name }} ({{ number_format($service->daily_fee) }} د.ع)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">
                            <i class="fas fa-calendar-alt me-1"></i> من تاريخ
                        </label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                    </div>

                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">
                            <i class="fas fa-calendar-alt me-1"></i> إلى تاريخ
                        </label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                    </div>

                    <div class="col-lg-1 col-md-4 col-sm-12 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary w-100" title="تصفية النتائج">
                            <i class="fas fa-filter"></i>
                        </button>
                        <a href="{{ route('nicu.reservations.index') }}" class="btn btn-sm btn-outline-secondary" title="إعادة تعيين">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول إضبارات الخدج -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-list me-2 text-primary"></i>
                قائمة إضبارات وحجوزات الخدج
                <span class="badge bg-secondary ms-1 fs-6 fw-normal">{{ $reservations->total() }}</span>
            </h5>
            <small class="text-muted">
                نظام اليوم الفندقي القياسي: 12:00 ظهراً بتوقيت العراق
            </small>
        </div>

        <div class="card-body p-0">
            @if($reservations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th class="text-start">الطفل والوالدة</th>
                                <th>الحاضنة والغرفة</th>
                                <th>باقة الخدمة السريرية</th>
                                <th>تاريخ ووقت الدخول</th>
                                <th>مدة الإقامة</th>
                                <th>الأوزان والنمو</th>
                                <th>الحالة</th>
                                <th>التكلفة الإجمالية</th>
                                <th style="width: 140px;">إجراءات سريعة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservations as $reservation)
                                @php
                                    $isAdmitted = $reservation->status === 'admitted';
                                    $isDischarged = $reservation->status === 'discharged';
                                    $latestVital = $reservation->vitals->first();
                                    $weightDiff = $reservation->weight_difference;
                                @endphp
                                <tr>
                                    <!-- رقم الحجز -->
                                    <td>
                                        <span class="badge bg-light text-dark border fw-bold">
                                            #{{ $reservation->id }}
                                        </span>
                                    </td>

                                    <!-- تفاصيل الطفل والأم -->
                                    <td class="text-start">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary">
                                                <i class="fas fa-baby"></i>
                                            </div>
                                            <div>
                                                <a href="{{ route('nicu.show', $reservation) }}" class="fw-bold text-dark text-decoration-none">
                                                    {{ $reservation->baby_name }}
                                                </a>
                                                <div class="small text-muted">
                                                    <i class="fas fa-female me-1 text-danger"></i>
                                                    الأم: {{ optional($reservation->patient?->user)->name ?? 'غير معروف' }}
                                                    @if(optional($reservation->patient?->user)->phone)
                                                        <span class="mx-1">•</span>
                                                        <span dir="ltr">{{ optional($reservation->patient?->user)->phone }}</span>
                                                    @endif
                                                </div>
                                                @if($reservation->gestational_age)
                                                    <span class="badge bg-light text-muted border" style="font-size: 10px;">
                                                        عمر الحمل: {{ $reservation->gestational_age }} أسبوع
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <!-- الحاضنة والغرفة -->
                                    <td>
                                        @if($reservation->incubator)
                                            <div>
                                                <span class="badge bg-{{ $reservation->incubator->type_color }} px-2 py-1">
                                                    {{ $reservation->incubator->incubator_number }} - {{ $reservation->incubator->type_name }}
                                                </span>
                                                @if(optional($reservation->incubator->room)->room_number)
                                                    <div class="small text-muted mt-1">
                                                        <i class="fas fa-door-open me-1"></i>
                                                        غرفة {{ $reservation->incubator->room->room_number }}
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <!-- باقة الخدمة السريرية -->
                                    <td>
                                        @if($reservation->nicuService)
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1">
                                                {{ $reservation->nicuService->name }}
                                            </span>
                                            <div class="small text-muted mt-1">
                                                {{ number_format($reservation->nicuService->daily_fee) }} د.ع / يوم
                                            </div>
                                        @else
                                            <span class="badge bg-secondary">غير محدد</span>
                                        @endif
                                    </td>

                                    <!-- تاريخ ووقت الدخول -->
                                    <td>
                                        <div class="fw-bold">{{ $reservation->admission_date ? $reservation->admission_date->format('Y-m-d') : '—' }}</div>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            {{ $reservation->admission_time ? \Carbon\Carbon::parse($reservation->admission_time)->format('h:i A') : '—' }}
                                        </small>
                                    </td>

                                    <!-- مدة الإقامة -->
                                    <td>
                                        @if($isAdmitted)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1">
                                                <i class="fas fa-stopwatch me-1"></i>
                                                {{ $reservation->duration_hours }} ساعة
                                            </span>
                                            <div class="small text-muted mt-1">
                                                {{ $reservation->current_duration }} يوم فندقي
                                            </div>
                                        @elseif($isDischarged)
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info px-2 py-1">
                                                {{ $reservation->actual_duration }} يوم
                                            </span>
                                            <div class="small text-muted mt-1">
                                                خروج: {{ $reservation->discharge_date ? $reservation->discharge_date->format('Y-m-d') : '—' }}
                                            </div>
                                        @else
                                            <span class="badge bg-secondary">
                                                متوقع: {{ $reservation->expected_duration }} يوم
                                            </span>
                                        @endif
                                    </td>

                                    <!-- الوزن والنمو -->
                                    <td>
                                        <div class="small">
                                            <span>الولادة: <strong>{{ $reservation->birth_weight ? number_format($reservation->birth_weight) . ' جم' : '—' }}</strong></span>
                                            <br>
                                            <span>الحالي: <strong class="text-primary">{{ $reservation->current_weight ? number_format($reservation->current_weight) . ' جم' : '—' }}</strong></span>
                                        </div>
                                        @if($weightDiff !== null)
                                            <span class="badge {{ $weightDiff >= 0 ? 'bg-success' : 'bg-danger' }} mt-1" style="font-size: 10px;">
                                                <i class="fas {{ $weightDiff >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }} me-1"></i>
                                                {{ $weightDiff >= 0 ? '+' : '' }}{{ $weightDiff }} جم
                                            </span>
                                        @endif
                                    </td>

                                    <!-- الحالة -->
                                    <td>
                                        <span class="badge bg-{{ $reservation->status_color }} px-2 py-1">
                                            {{ $reservation->status_name }}
                                        </span>
                                        @if($isDischarged && $reservation->discharge_type)
                                            <div class="small text-muted mt-1" style="font-size: 11px;">
                                                {{ $reservation->discharge_type_name }}
                                            </div>
                                        @endif
                                    </td>

                                    <!-- التكلفة الإجمالية -->
                                    <td>
                                        <div class="fw-bold text-success">
                                            @php
                                                $costDisplay = $reservation->total_cost > 0 
                                                    ? $reservation->total_cost 
                                                    : ($reservation->nicuService ? $reservation->current_duration * $reservation->nicuService->daily_fee : $reservation->calculateActualCost());
                                            @endphp
                                            {{ number_format($costDisplay) }} د.ع
                                        </div>
                                        <small class="text-muted d-block" style="font-size: 10px;">
                                            {{ $isDischarged ? 'نهائي' : 'تراكمي تقديري' }}
                                        </small>
                                    </td>

                                    <!-- الإجراءات السريعة -->
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <!-- زر ملف الرعاية السريرية -->
                                            <a href="{{ route('nicu.show', $reservation) }}" class="btn btn-sm btn-primary shadow-sm" title="محطة الرعاية السريرية للطفل">
                                                <i class="fas fa-stethoscope"></i>
                                            </a>

                                            <!-- كشف الحساب التراكمي -->
                                            <a href="{{ route('nicu.statement', $reservation) }}" class="btn btn-sm btn-outline-info" title="كشف الحساب التراكمي الشفاف" target="_blank">
                                                <i class="fas fa-file-invoice-dollar"></i>
                                            </a>

                                            <!-- قائمة الإجراءات الإضافية -->
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                    <li>
                                                        <a class="dropdown-item py-2" href="{{ route('nicu.print.wristband', $reservation) }}" target="_blank">
                                                            <i class="fas fa-id-badge text-primary me-2"></i> طباعة أسورة المعصم
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2" href="{{ route('nicu.print.crib-card', $reservation) }}" target="_blank">
                                                            <i class="fas fa-id-card text-success me-2"></i> طباعة كارت الحاضنة
                                                        </a>
                                                    </li>
                                                    @if($isAdmitted)
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item py-2 text-success" type="button" 
                                                                    onclick="sendBabyCamLink('{{ $reservation->id }}', '{{ optional($reservation->patient?->user)->phone }}')">
                                                                <i class="fab fa-whatsapp me-2"></i> رابط كاميرا الطفل للأهل
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- الترقيم والصفحات -->
                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="small text-muted">
                        عرض {{ $reservations->firstItem() }} إلى {{ $reservations->lastItem() }} من أصل {{ $reservations->total() }} إضبارة
                    </span>
                    <div>
                        {{ $reservations->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-clipboard-list fa-4x text-muted opacity-50"></i>
                    </div>
                    <h5 class="fw-bold text-dark">لا توجد إضبارات أو نتائج مطابقة</h5>
                    <p class="text-muted small">لم يتم العثور على أي نتائج وفق معايير البحث أو الفلترة المحددة.</p>
                    <div class="d-flex justify-content-center gap-2 mt-3">
                        <a href="{{ route('nicu.reservations.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-undo me-1"></i> إعادة ضبط الفلاتر
                        </a>
                        <a href="{{ route('incubator-reservations.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus-circle me-1"></i> حجز ودخول خديج جديد
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal مشاركة كاميرا الحاضنة للأب -->
<div class="modal fade" id="babyCamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="fas fa-video text-success me-2"></i>
                    كاميرا الاطمئنان للأهل (Live Baby Cam)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-video fa-3x text-success mb-3"></i>
                <h6 class="fw-bold text-dark">رابط مشاهدة مباشر لدقيقة واحدة</h6>
                <p class="small text-muted mb-3">
                    سيتم توليد رابط مؤمن يتيح للأب مشاهدة طفله مباشرة لمدة 60 ثانية لطمأنته، وتنقطع الكاميرا تلقائياً وتظهر له رسالة المستشفى بالدعاء.
                </p>
                <div class="input-group mb-3">
                    <input type="text" id="camLinkInput" class="form-control text-center" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="copyCamLink()"><i class="fas fa-copy"></i> نسخ</button>
                </div>
                <a id="whatsappSendBtn" href="#" target="_blank" class="btn btn-success w-100 shadow-sm fw-bold">
                    <i class="fab fa-whatsapp me-1"></i> فتح المحادثة وإرسال الرابط في واتساب
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function sendBabyCamLink(reservationId, phone) {
    fetch(`/nicu/reservations/${reservationId}/cam-token`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            document.getElementById('camLinkInput').value = data.url;
            const msg = encodeURIComponent(`السلام عليكم ورحمة الله،\nيمكنكم الآن الاطمئنان على طفلكم الحبيب (${data.baby_name}) في قسم الخدج عبر الرابط المباشر التالي لمدة دقيقة:\n${data.url}\n\nنسأل الله له الشفاء التام والعافية 💚`);
            let waUrl = `https://wa.me/${phone ? phone.replace(/[^0-9]/g, '') : ''}?text=${msg}`;
            document.getElementById('whatsappSendBtn').href = waUrl;
            new bootstrap.Modal(document.getElementById('babyCamModal')).show();
        }
    })
    .catch(e => console.error(e));
}

function copyCamLink() {
    const input = document.getElementById('camLinkInput');
    input.select();
    document.execCommand('copy');
    alert('تم نسخ رابط الكاميرا بنجاح!');
}
</script>
@endpush
@endsection
