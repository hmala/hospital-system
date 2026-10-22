@extends('layouts.app')

@section('title', 'ملف رعاية الخديج - ' . $reservation->baby_name)

@section('content')
<div class="container-fluid py-3">
    <!-- رأس ملف الطفل -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px; background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
        <div class="card-body p-4 text-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-white bg-opacity-20 rounded-circle text-white">
                        <i class="fas fa-baby fa-3x"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h3 class="mb-0 fw-bold">{{ $reservation->baby_name }}</h3>
                            <span class="badge bg-white text-primary fw-bold px-2 py-1">
                                حاضنة رقم {{ $reservation->incubator->incubator_number }}
                            </span>
                            <span class="badge bg-warning text-dark fw-bold px-2 py-1">
                                {{ $reservation->status_name }}
                            </span>
                        </div>
                        <p class="mb-0 opacity-75 small">
                            <span class="me-3"><i class="fas fa-female me-1"></i> الأم: {{ optional($reservation->patient?->user)->name ?? 'غير معروف' }}</span>
                            <span class="me-3"><i class="fas fa-calendar me-1"></i> تاريخ الدخول: {{ $reservation->admission_date->format('Y-m-d') }} ({{ $reservation->admission_time ? $reservation->admission_time->format('H:i') : '' }})</span>
                            <span><i class="fas fa-user-md me-1"></i> الطبيب المشرف: {{ optional($reservation->doctor?->user)->name ?? 'غير محدد' }}</span>
                        </p>
                    </div>
                </div>

                <!-- إحصائيات سريعة وأزرار -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="p-2 bg-white bg-opacity-10 rounded text-center" style="min-width: 85px;">
                        <span class="small opacity-75 d-block">عداد الساعات</span>
                        <h5 class="mb-0 fw-bold">{{ $stayDetails['total_hours'] }} س</h5>
                    </div>
                    <div class="p-2 bg-white bg-opacity-10 rounded text-center" style="min-width: 85px;">
                        <span class="small opacity-75 d-block">الأيام الفندقية</span>
                        <h5 class="mb-0 fw-bold">{{ $stayDetails['calculated_days'] }} يوم</h5>
                    </div>
                    <div class="p-2 bg-white bg-opacity-10 rounded text-center" style="min-width: 110px;">
                        <span class="small opacity-75 d-block">الحساب التراكمي</span>
                        <h5 class="mb-0 fw-bold">{{ number_format($stayDetails['patient_grand_total'] ?? $stayDetails['grand_total']) }} د.ع</h5>
                    </div>
                    <div class="p-2 bg-white bg-opacity-10 rounded text-center" style="min-width: 100px;">
                        <span class="small opacity-75 d-block">المسدد (أمانات)</span>
                        <h5 class="mb-0 fw-bold" style="color: #86efac;">{{ number_format($stayDetails['total_paid'] ?? 0) }} د.ع</h5>
                    </div>
                    <div class="p-2 bg-white bg-opacity-10 rounded text-center" style="min-width: 100px;">
                        <span class="small opacity-75 d-block">الرصيد المتبقي</span>
                        <h5 class="mb-0 fw-bold {{ ($stayDetails['net_balance'] ?? 0) > 0 ? 'text-warning' : 'text-white' }}">{{ number_format($stayDetails['net_balance'] ?? 0) }} د.ع</h5>
                    </div>
                </div>
            </div>

            <!-- أزرار الإجراءات العلوية السريعة -->
            <div class="mt-3 pt-3 border-top border-white border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('nicu.index') }}" class="btn btn-sm btn-light text-primary shadow-sm">
                        <i class="fas fa-arrow-right me-1"></i> العودة للوحة الحاضنات
                    </a>
                    <a href="{{ route('nicu.statement', $reservation) }}" class="btn btn-sm btn-outline-light shadow-sm">
                        <i class="fas fa-receipt me-1"></i> كشف الحساب التراكمي
                    </a>
                    <a href="{{ route('nicu.print.crib-card', $reservation) }}" target="_blank" class="btn btn-sm btn-outline-light shadow-sm">
                        <i class="fas fa-id-card me-1"></i> طباعة كارت الحاضنة
                    </a>
                    <a href="{{ route('nicu.print.wristband', $reservation) }}" target="_blank" class="btn btn-sm btn-outline-light shadow-sm">
                        <i class="fas fa-barcode me-1"></i> طباعة أسورة المعصم
                    </a>
                    <a href="{{ route('nicu.print.prescription', $reservation) }}" target="_blank" class="btn btn-sm btn-outline-light shadow-sm">
                        <i class="fas fa-prescription me-1"></i> طباعة الوصفة (RX)
                    </a>
                </div>
                <div>
                    @if($reservation->status === 'admitted')
                        <button type="button" class="btn btn-sm btn-danger shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#dischargeModal">
                            <i class="fas fa-sign-out-alt me-1"></i> أمر الخروج الطبي وتصفية الحساب
                        </button>
                    @endif
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

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- تبويبات المركز السريري -->
    <div class="card border-0 shadow-sm" style="border-radius: 14px;">
        <div class="card-header bg-white border-0 pt-3 px-4">
            <ul class="nav nav-tabs card-header-tabs" id="nicuTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold text-dark" id="service-tab" data-bs-toggle="tab" data-bs-target="#servicePane" type="button">
                        <i class="fas fa-cogs text-primary me-1"></i> باقة الخدمة والتبديل (12:00 ظهراً)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark" id="vitals-tab" data-bs-toggle="tab" data-bs-target="#vitalsPane" type="button">
                        <i class="fas fa-heartbeat text-danger me-1"></i> محطة التمريض وإعطاء العلاج (MAR)
                        @if($reservation->medicationAdministrations->count() > 0)
                            <span class="badge bg-danger rounded-pill ms-1">{{ $reservation->medicationAdministrations->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark" id="care-schedules-tab" data-bs-toggle="tab" data-bs-target="#careSchedulesPane" type="button">
                        <i class="fas fa-clipboard-list text-warning me-1"></i> 📋 التوصيات وخطة الرعاية
                        @php $activeSchedulesCount = $reservation->careSchedules->where('is_active', true)->count(); @endphp
                        @if($activeSchedulesCount > 0)
                            <span class="badge bg-warning text-dark rounded-pill ms-1">{{ $activeSchedulesCount }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark" id="consultations-tab" data-bs-toggle="tab" data-bs-target="#consultationsPane" type="button">
                        <i class="fas fa-user-md text-info me-1"></i> استشارات الأطباء (داخلي / خارجي)
                        @if($reservation->consultations->count() > 0)
                            <span class="badge bg-info rounded-pill ms-1">{{ $reservation->consultations->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark" id="lab-tab" data-bs-toggle="tab" data-bs-target="#labPane" type="button">
                        <i class="fas fa-microscope text-primary me-1"></i> 🧪 تحاليل المختبر
                        @php $labCount = $reservation->medicalOrders->where('order_type', 'lab')->count(); @endphp
                        @if($labCount > 0)
                            <span class="badge bg-primary rounded-pill ms-1">{{ $labCount }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark" id="radiology-tab" data-bs-toggle="tab" data-bs-target="#radiologyPane" type="button">
                        <i class="fas fa-x-ray text-info me-1"></i> 🩻 الأشعة والتصوير
                        @php $radCount = $reservation->medicalOrders->where('order_type', 'radiology')->count(); @endphp
                        @if($radCount > 0)
                            <span class="badge bg-info rounded-pill ms-1">{{ $radCount }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark" id="prescriptions-tab" data-bs-toggle="tab" data-bs-target="#prescriptionsPane" type="button">
                        <i class="fas fa-pills text-success me-1"></i> 💊 الوصفة والعلاج
                        @php $medCount = $reservation->medicalOrders->where('order_type', 'medication')->count(); @endphp
                        @if($medCount > 0)
                            <span class="badge bg-success rounded-pill ms-1">{{ $medCount }}</span>
                        @endif
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="nicuTabsContent">
                
                <!-- تبويب 1: باقة الخدمة والتبديل اليومي -->
                <div class="tab-pane fade show active" id="servicePane">
                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="card border-0 bg-light p-4 rounded-3 h-100">
                                <h5 class="fw-bold text-dark mb-3">
                                    <i class="fas fa-shield-alt text-primary me-2"></i>
                                    الباقة السريرية الحالية
                                </h5>
                                <div class="p-3 bg-white rounded shadow-sm border mb-3">
                                    <span class="small text-muted d-block mb-1">نوع الرعاية المسجل:</span>
                                    <h4 class="fw-bold text-primary mb-1">
                                        {{ $reservation->nicuService?->name ?? 'حاضنة رعاية الخدج' }}
                                    </h4>
                                    <p class="small text-muted mb-2">{{ $reservation->nicuService?->description }}</p>
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <span class="text-muted small">الأجرة اليومية:</span>
                                        <strong class="fs-5 text-success">{{ number_format($reservation->nicuService?->daily_fee ?? $reservation->incubator->daily_fee) }} د.ع/يوم</strong>
                                    </div>
                                </div>

                                <!-- فورم تبديل وتعديل الخدمة السريرية -->
                                @if($reservation->status === 'admitted')
                                    @php
                                        $activeLog = $reservation->serviceLogs->whereNull('end_time')->first();
                                    @endphp
                                    <div class="p-3 bg-white rounded shadow-sm border">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="fw-bold text-dark mb-0">
                                                <i class="fas fa-sync-alt text-warning me-1"></i>
                                                تبديل / تعديل الباقة السريرية
                                            </h6>
                                            @if($activeLog)
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editServiceLogModal{{ $activeLog->id }}">
                                                    <i class="fas fa-edit me-1"></i> تعديل الباقة الحالية
                                                </button>
                                            @endif
                                        </div>
                                        <p class="small text-muted mb-2">
                                            يمكن تعديل الباقة السريرية (مثلاً: التحويل من فنتليتر إلى CPAP أو أكسجين). التبديل في نفس اليوم الفندقي يُعدّل الباقة الحالية مباشرة دون مضاعفة اليوم الفندقي.
                                        </p>

                                        <form action="{{ route('nicu.switch-service', $reservation) }}" method="POST">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">اختر الباقة الجديدة:</label>
                                                <select name="nicu_service_id" class="form-select" required>
                                                    @foreach($nicuServices as $service)
                                                        <option value="{{ $service->id }}" {{ $reservation->nicu_service_id == $service->id ? 'selected' : '' }}>
                                                            {{ $service->name }} ({{ number_format($service->daily_fee) }} د.ع)
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">سبب التحويل / الملاحظات الطبية:</label>
                                                <input type="text" name="reason" class="form-control" placeholder="مثال: تحسن التنفس وفصل الأنبوب الرغامي أو تصحيح الباقة" required>
                                            </div>
                                            <button type="submit" class="btn btn-warning w-100 fw-bold shadow-sm">
                                                <i class="fas fa-check me-1"></i> حفظ وتفعيل الباقة
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- جدول سجل تبديل الخدمات -->
                        <div class="col-lg-7">
                            <h5 class="fw-bold text-dark mb-3">
                                <i class="fas fa-history text-secondary me-2"></i>
                                السجل الزمني للخدمات والباقات (Timeline)
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border text-center">
                                    <thead class="table-light small">
                                        <tr>
                                            <th class="text-start">الخدمة السريرية</th>
                                            <th>الأجرة اليومية</th>
                                            <th>الأيام</th>
                                            <th>المجموع</th>
                                            <th>الفترة</th>
                                            <th>السبب</th>
                                            <th>المغير</th>
                                            <th>إجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($reservation->serviceLogs as $log)
                                            @php
                                                $isActive = is_null($log->end_time);
                                                $days = (int)$log->days_count;
                                                $cost = (float)($log->total_cost ?? ($days * $log->daily_fee));
                                            @endphp
                                            <tr>
                                                <td class="text-start fw-bold text-primary">
                                                    {{ $log->nicuService?->name }}
                                                    @if($isActive)
                                                        <span class="badge bg-success ms-1" style="font-size: 10px;">نشطة حالياً</span>
                                                    @endif
                                                </td>
                                                <td class="text-dark fw-bold">{{ number_format($log->daily_fee) }} د.ع</td>
                                                <td>
                                                    @if($days > 0)
                                                        <span class="badge bg-info text-white">{{ $days }} يوم</span>
                                                    @else
                                                        <span class="badge bg-secondary" title="دُمج مع باقة نفس اليوم">0 (مدمج)</span>
                                                    @endif
                                                </td>
                                                <td class="text-success fw-bold">
                                                    {{ number_format($cost) }} د.ع
                                                </td>
                                                <td class="small text-muted">
                                                    <div>من: {{ $log->start_time?->format('Y-m-d H:i') }}</div>
                                                    <div>إلى: {{ $log->end_time ? $log->end_time->format('Y-m-d H:i') : 'مستمر' }}</div>
                                                </td>
                                                <td class="small text-start">{{ $log->reason_for_switch ?? 'دخول أولي' }}</td>
                                                <td class="small text-muted">{{ optional($log->switchedByUser)->name ?? 'النظام' }}</td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-1">
                                                        @if($isActive)
                                                            <!-- زر تعديل الباقة النشطة -->
                                                            <button type="button" class="btn btn-sm btn-outline-primary" title="تعديل الباقة الحالية" data-bs-toggle="modal" data-bs-target="#editServiceLogModal{{ $log->id }}">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        @endif

                                                        @if($reservation->serviceLogs->count() > 1 && $isActive)
                                                            <!-- زر تراجع وحذف التبديل -->
                                                            <form action="{{ route('nicu.service-logs.destroy', [$reservation, $log]) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا التبديل والتراجع عنه للباقة السابقة؟');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف وتراجع عن التبديل">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>

                                                    <!-- Modal تعديل السجل المفتوح -->
                                                    @if($isActive)
                                                        <div class="modal fade text-start" id="editServiceLogModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content border-0 shadow">
                                                                    <div class="modal-header bg-primary text-white">
                                                                        <h5 class="modal-title fw-bold">
                                                                            <i class="fas fa-edit me-2"></i> تعديل باقة الخدمة السريرية
                                                                        </h5>
                                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <form action="{{ route('nicu.service-logs.update', [$reservation, $log]) }}" method="POST">
                                                                        @csrf
                                                                        @method('PUT')
                                                                        <div class="modal-body p-4">
                                                                            <div class="mb-3">
                                                                                <label class="form-label small fw-bold">اختر باقة الخدمة:</label>
                                                                                <select name="nicu_service_id" class="form-select" required>
                                                                                    @foreach($nicuServices as $service)
                                                                                        <option value="{{ $service->id }}" {{ $log->nicu_service_id == $service->id ? 'selected' : '' }}>
                                                                                            {{ $service->name }} ({{ number_format($service->daily_fee) }} د.ع)
                                                                                        </option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label class="form-label small fw-bold">سبب التعديل / الملاحظات:</label>
                                                                                <input type="text" name="reason" class="form-control" value="{{ $log->reason_for_switch }}" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer bg-light">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                                            <button type="submit" class="btn btn-primary fw-bold">
                                                                                <i class="fas fa-save me-1"></i> حفظ التعديل
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-4 text-muted">
                                                    لا توجد سجلات تبديل بعد، الخدمة الحالية هي الخدمة الأساسية منذ الدخول.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تبويب 2: محطة التمريض (العلامات والوزن والرضاعة) -->
                <div class="tab-pane fade" id="vitalsPane">
                    <div class="row g-4">
                        <!-- نموذج إضافة قراءة جديدة -->
                        <div class="col-lg-4">
                            <div class="card border-0 bg-light p-3 rounded-3">
                                <h6 class="fw-bold text-dark mb-3">
                                    <i class="fas fa-plus-circle text-danger me-1"></i>
                                    تسجيل قراءة سريرية وتمريضية
                                </h6>
                                <form action="{{ route('nicu.vitals.store', $reservation) }}" method="POST">
                                    @csrf
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small">حرارة الطفل (°C):</label>
                                            <input type="number" step="0.1" name="temperature" class="form-control form-control-sm" placeholder="37.0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">حرارة الحاضنة (°C):</label>
                                            <input type="number" step="0.1" name="incubator_temperature" class="form-control form-control-sm" placeholder="34.5">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">نبض القلب HR (bpm):</label>
                                            <input type="number" name="heart_rate" class="form-control form-control-sm" placeholder="140">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">التنفس RR (cpm):</label>
                                            <input type="number" name="respiratory_rate" class="form-control form-control-sm" placeholder="45">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">الأكسجين SpO2 (%):</label>
                                            <input type="number" name="spo2" class="form-control form-control-sm" placeholder="96">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">ضغط الدم BP:</label>
                                            <input type="text" name="blood_pressure" class="form-control form-control-sm" placeholder="70/45">
                                        </div>
                                    </div>

                                    <hr class="my-2">
                                    <h6 class="fw-bold text-dark small mb-2"><i class="fas fa-weight me-1 text-success"></i> الوزن والتغذية:</h6>
                                    
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-success">الوزن الحالي (جرام):</label>
                                        <input type="number" name="weight" class="form-control form-control-sm" value="{{ $reservation->current_weight }}" placeholder="مثال: 1450">
                                    </div>

                                    @php
                                        $activeFeedingSched = $reservation->activeFeedingSchedule();
                                        $defaultFeedingAmount = null;
                                        if ($activeFeedingSched && preg_match('/(\d+(\.\d+)?)/', $activeFeedingSched->dose_or_amount ?? '', $fMatches)) {
                                            $defaultFeedingAmount = $fMatches[1];
                                        }
                                    @endphp
                                    @if($activeFeedingSched)
                                        <div class="alert alert-warning py-1.5 px-2 mb-2 small d-flex justify-content-between align-items-center border-0 bg-warning bg-opacity-10 text-dark">
                                            <div>
                                                <i class="fas fa-baby-bottle text-warning me-1"></i>
                                                <strong>الخطة المعتمدة:</strong> {{ $activeFeedingSched->title }} ({{ $activeFeedingSched->dose_or_amount }})
                                            </div>
                                            <span class="badge {{ $activeFeedingSched->is_due ? 'bg-danger text-white' : 'bg-secondary' }}">
                                                {{ $activeFeedingSched->due_human }}
                                            </span>
                                        </div>
                                    @endif

                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small">نوع الرضاعة:</label>
                                            <input type="text" name="feeding_type" class="form-control form-control-sm" list="quickVitalsFeedingTypes" value="{{ $activeFeedingSched ? $activeFeedingSched->title : 'حليب أم (EBM)' }}">
                                            <datalist id="quickVitalsFeedingTypes">
                                                <option value="حليب أم (EBM)">
                                                <option value="حليب خدج صناعي">
                                                <option value="تغذية وريدية TPN">
                                                <option value="صيام NPO">
                                            </datalist>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">كمية الرضعة (مل/CC):</label>
                                            <input type="number" step="0.5" name="feeding_amount_ml" class="form-control form-control-sm" value="{{ $defaultFeedingAmount }}" placeholder="15">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small">ملاحظات التمريض:</label>
                                        <input type="text" name="feeding_notes" class="form-control form-control-sm" placeholder="تحمل الرضاعة، لون الجلد، نشاط الطفل...">
                                    </div>

                                    <button type="submit" class="btn btn-sm btn-danger w-100 fw-bold shadow-sm">
                                        <i class="fas fa-save me-1"></i> حفظ وتوثيق القراءة
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- جدول السجلات السريرية والتمريضية -->
                        <div class="col-lg-8">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="fas fa-table me-1 text-primary"></i>
                                سجل العلامات الحيوية والوزن والرضاعة
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle text-center small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>الوقت</th>
                                            <th>حرارة الطفل</th>
                                            <th>الحاضنة</th>
                                            <th>النبض HR</th>
                                            <th>التنفس RR</th>
                                            <th>SpO2</th>
                                            <th>الوزن (جم)</th>
                                            <th>الرضاعة</th>
                                            <th>الممرض</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($reservation->vitals as $v)
                                            <tr>
                                                <td>{{ $v->recorded_at->format('d/m H:i') }}</td>
                                                <td class="{{ $v->temperature > 37.5 || $v->temperature < 36.5 ? 'text-danger fw-bold' : '' }}">{{ $v->temperature ?? '—' }}</td>
                                                <td>{{ $v->incubator_temperature ?? '—' }}</td>
                                                <td>{{ $v->heart_rate ?? '—' }}</td>
                                                <td>{{ $v->respiratory_rate ?? '—' }}</td>
                                                <td class="{{ $v->spo2 < 92 ? 'text-danger fw-bold' : 'text-success' }}">{{ $v->spo2 ? $v->spo2 . '%' : '—' }}</td>
                                                <td class="fw-bold text-success">{{ $v->weight ? number_format($v->weight) : '—' }}</td>
                                                <td>{{ $v->feeding_type }} ({{ $v->feeding_amount_ml ? $v->feeding_amount_ml . ' مل' : '—' }})</td>
                                                <td class="text-muted">{{ optional($v->nurse)->name ?? 'ممرض' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="py-4 text-muted">لم يتم توثيق علامات حيوية بعد.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- فاصل أنيق بين العلامات الحيوية وجدول إعطاء العلاج MAR -->
                    <hr class="my-4 border-2">

                    <!-- قسم سجل إعطاء وتطبيق العلاج التمريضي (MAR) -->
                    <div class="row g-4">
                        <!-- نموذج توثيق إعطاء الجرعة -->
                        <div class="col-lg-4">
                            <div class="card border-0 bg-light p-3 rounded-3 shadow-xs">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="fw-bold text-dark mb-0">
                                        <i class="fas fa-syringe text-danger me-1"></i>
                                        توثيق إعطاء جرعة علاجية (MAR)
                                    </h6>
                                    <span class="badge bg-danger text-white small">تمريض الحاضنة</span>
                                </div>
                                <div class="alert alert-info py-2 px-3 small mb-3 border-0 bg-info bg-opacity-10 text-info-emphasis" style="font-size: 11px;">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>توثيق سريري مباشر:</strong> يُسجل هذا النموذج وقت وطريقة إعطاء الجرعة للطفل بالحاضنة ولا يرتبط بطلب المخزون أو الصيدلية.
                                </div>

                                @php $activeMedSchedules = $reservation->activeMedicationSchedules(); @endphp
                                @if($activeMedSchedules->count() > 0)
                                    <div class="card border-0 bg-white p-2.5 rounded-3 mb-3 border border-warning-subtle shadow-xs">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="fw-bold small text-dark"><i class="fas fa-bell text-warning me-1"></i> أدوية مجدولة مستحقة / نشطة:</span>
                                            <span class="badge bg-warning text-dark">{{ $activeMedSchedules->count() }} مجدول</span>
                                        </div>
                                        <div class="d-flex flex-column gap-2">
                                            @foreach($activeMedSchedules as $aMed)
                                                <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded border small">
                                                    <div>
                                                        <strong class="text-dark">{{ $aMed->title }}</strong>
                                                        <small class="text-muted d-block">{{ $aMed->dose_or_amount ?: 'جرعة موصوفة' }} • {{ $aMed->route ?: 'وريدي' }} (كل {{ $aMed->interval_hours }} س)</small>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <span class="badge {{ $aMed->is_overdue ? 'bg-danger' : ($aMed->is_due ? 'bg-warning text-dark' : 'bg-light text-dark border') }} font-monospace" style="font-size: 10px;">
                                                            {{ $aMed->due_human }}
                                                        </span>
                                                        <form action="{{ route('nicu.care-schedules.administer-quick', $aMed) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success fw-bold py-0.5 px-2" style="font-size: 11px;" title="توثيق الإعطاء الآن وتحديث الموعد القادم">
                                                                <i class="fas fa-check me-1"></i> إعطاء
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <form action="{{ route('nicu.medications-admin.store', $reservation) }}" method="POST">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold">اسم الدواء أو المادة المعطاة:</label>
                                        <input type="text" name="medication_name" list="prescribedMedsDatalist" class="form-control form-control-sm" placeholder="اكتب اسم العلاج أو اختر من الوصفة..." required>
                                        <datalist id="prescribedMedsDatalist">
                                            @foreach($reservation->medicalOrders->where('order_type', 'medication') as $pMed)
                                                <option value="{{ $pMed->item_name }}"></option>
                                            @endforeach
                                        </datalist>
                                        <small class="text-muted" style="font-size: 10.5px;">اختر من قائمة أدوية الطفل الموصوفة أو اكتب أي محلول/علاج حر.</small>
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">الجرعة المعطاة:</label>
                                            <input type="text" name="dose" class="form-control form-control-sm" placeholder="مثال: 50 mg أو 1 ml" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">طريقة الإعطاء (Route):</label>
                                            <select name="route" class="form-select form-select-sm" required>
                                                <option value="iv">حقن وريدي (IV)</option>
                                                <option value="infusion">تسريب وريدي مستمر (IV Infusion)</option>
                                                <option value="oral">فموي / أنبوب تغذية (Oral / NG)</option>
                                                <option value="im">حقن عضلي (IM)</option>
                                                <option value="nebulizer">استنشاق / رذاذ (Nebulizer)</option>
                                                <option value="drops">قطرات (Drops)</option>
                                                <option value="sc">تحت الجلد (SC)</option>
                                                <option value="pr">شرجي (PR)</option>
                                                <option value="topical">موضعي / دهان (Topical)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold">وقت وتاريخ الإعطاء:</label>
                                        <input type="datetime-local" name="given_at" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}">
                                        <small class="text-muted" style="font-size: 10.5px;">محدد تلقائياً على اللحظة الحالية.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small">ملاحظات التمريض واستجابة الطفل:</label>
                                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="مثال: تم إعطاؤه ببطء، استجابة جيدة، سلامة الكانيولا...">
                                    </div>

                                    <button type="submit" class="btn btn-sm btn-danger w-100 fw-bold shadow-sm">
                                        <i class="fas fa-check-circle me-1"></i> توثيق إعطاء الجرعة
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- جدول سجل إعطاء الأدوية MAR -->
                        <div class="col-lg-8">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fas fa-history text-danger me-1"></i>
                                    سجل إعطاء وتطبيق الأدوية التمريضي (MAR Timeline)
                                </h6>
                                <span class="badge bg-light text-dark border">
                                    إجمالي الجرعات المنفذة: {{ $reservation->medicationAdministrations->count() }}
                                </span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle border text-center small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>وقت الإعطاء</th>
                                            <th class="text-start">العلاج الممنوح</th>
                                            <th>الجرعة</th>
                                            <th>مسار الإعطاء</th>
                                            <th>ملاحظات التمريض</th>
                                            <th>الممرض المنفذ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($reservation->medicationAdministrations->sortByDesc('given_at') as $adm)
                                            <tr>
                                                <td class="fw-bold text-dark">
                                                    {{ $adm->given_at ? $adm->given_at->format('Y-m-d H:i') : '—' }}
                                                </td>
                                                <td class="text-start fw-bold text-primary">
                                                    <i class="fas fa-capsules me-1 text-muted"></i>
                                                    {{ $adm->medication_name }}
                                                </td>
                                                <td class="fw-bold text-dark">{{ $adm->dose }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $adm->route_badge_color }}">
                                                        {{ $adm->route_name }}
                                                    </span>
                                                </td>
                                                <td class="text-start small text-muted">
                                                    {{ $adm->notes ?? '—' }}
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-secondary border">
                                                        <i class="fas fa-user-nurse me-1 text-danger"></i>
                                                        {{ optional($adm->nurse)->name ?? 'ممرض الحاضنة' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="py-4 text-muted">
                                                    <i class="fas fa-clipboard-list fa-2x mb-2 d-block opacity-25"></i>
                                                    لم يتم توثيق أي جرعة علاجية معطاة للطفل بعد.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تبويب 3: استشارات الأطباء (داخلي / خارجي) -->
                <div class="tab-pane fade" id="consultationsPane">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="card border-0 bg-light p-3 rounded-3">
                                <h6 class="fw-bold text-dark mb-3">
                                    <i class="fas fa-stethoscope text-info me-1"></i>
                                    طلب استشارة طبيب أخصائي
                                </h6>
                                <form action="{{ route('nicu.consultations.store', $reservation) }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">نوع الاستشارة:</label>
                                        <select name="consultation_type" id="consultationTypeSelect" class="form-select form-select-sm" required onchange="toggleDoctorField(this.value)">
                                            <option value="internal">استشارة داخلية (طبيب من داخل المستشفى)</option>
                                            <option value="external">استشارة خارجية (طبيب زائر من خارج المستشفى)</option>
                                        </select>
                                    </div>

                                    <div class="mb-3" id="internalDoctorDiv">
                                        <label class="form-label small fw-bold">
                                            <i class="fas fa-user-md text-info me-1"></i> اختر طبيب المستشفى الداخلي:
                                        </label>
                                        
                                        <!-- مربع البحث الفوري بدلاً من القائمة المنسدلة -->
                                        <div id="doctorSearchWrapper" class="position-relative">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                                <input type="text" 
                                                       id="internalDoctorSearchInput" 
                                                       class="form-control form-control-sm" 
                                                       placeholder="اكتب اسم الطبيب للبحث بالمقترحات..." 
                                                       autocomplete="off">
                                                <button class="btn btn-outline-secondary" type="button" id="toggleAllDoctorsBtn" title="عرض جميع الأطباء">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </div>

                                            <!-- قائمة مقترحات الأطباء المنسدلة -->
                                            <div id="doctorSearchResults" class="list-group position-absolute w-100 shadow-lg mt-1" 
                                                 style="z-index: 1060; max-height: 220px; overflow-y: auto; display: none;">
                                            </div>
                                        </div>

                                        <!-- بطاقة الطبيب المختار -->
                                        <div id="selectedDoctorCard" class="card border-info bg-white shadow-sm mt-2 p-2" style="display: none;">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                        <i class="fas fa-user-md fa-lg"></i>
                                                    </div>
                                                    <div>
                                                        <strong class="d-block text-dark small" id="selectedDoctorName"></strong>
                                                        <span class="text-muted" style="font-size: 11px;" id="selectedDoctorSpecialty"></span>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" id="clearSelectedDoctorBtn" title="تغيير الطبيب">
                                                    <i class="fas fa-times me-1"></i> تغيير
                                                </button>
                                            </div>
                                        </div>

                                        <input type="hidden" name="doctor_id" id="selectedDoctorId" value="{{ old('doctor_id') }}">
                                        @error('doctor_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3 d-none" id="externalDoctorDiv">
                                        <label class="form-label small fw-bold">اسم الطبيب الزائر الخارجي:</label>
                                        <input type="text" name="external_doctor_name" id="externalDoctorNameInput" class="form-control form-control-sm" placeholder="د. فلان الفلاني" value="{{ old('external_doctor_name') }}">
                                    </div>

                                    <div class="mb-3 d-none" id="specialtyDiv">
                                        <label class="form-label small fw-bold">التخصص الدقيق:</label>
                                        <input type="text" name="specialty" id="specialtyInput" class="form-control form-control-sm" placeholder="مثال: جراحة أطفال، قلب أطفال، عيون خدج..." value="{{ old('specialty') }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">أجرة الاستشارة (د.ع):</label>
                                        <input type="number" step="1000" name="fee" class="form-control form-control-sm" value="35000" required>
                                        <small class="text-muted" style="font-size: 11px;">تضاف تلقائياً إلى كشف الحساب التراكمي للطفل.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">سبب ومبررات الاستشارة:</label>
                                        <textarea name="reason" rows="2" class="form-control form-control-sm" placeholder="الأعراض السريرية وموضوع الفحص المطلوب..." required></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-sm btn-info w-100 text-white fw-bold shadow-sm">
                                        <i class="fas fa-paper-plane me-1"></i> إرسال طلب الاستشارة
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="fas fa-clipboard-check text-success me-1"></i>
                                سجل استشارات الأطباء التخصصية
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>النوع</th>
                                            <th>الطبيب والتخصص</th>
                                            <th>الأجرة</th>
                                            <th>السبب</th>
                                            <th>التقرير والتوصيات</th>
                                            <th>الحالة والإجراء</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($reservation->consultations as $c)
                                            <tr>
                                                <td>
                                                    <span class="badge bg-{{ $c->consultation_type == 'internal' ? 'primary' : 'warning text-dark' }}">
                                                        {{ $c->consultation_type == 'internal' ? 'داخلي' : 'خارجي' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong>{{ $c->doctor_display_name }}</strong>
                                                    <small class="text-muted d-block">{{ $c->specialty }}</small>
                                                </td>
                                                <td class="text-success fw-bold">{{ number_format($c->fee) }} د.ع</td>
                                                <td class="text-muted">{{ $c->reason }}</td>
                                                <td>
                                                    @if($c->findings)
                                                        <div class="text-dark"><strong>فحص:</strong> {{ $c->findings }}</div>
                                                        <div class="text-muted"><strong>توصيات:</strong> {{ $c->recommendations }}</div>
                                                    @else
                                                        <span class="text-muted fst-italic">بانتظار حضور وكتابة تقرير المستشار</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($c->status === 'requested')
                                                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#completeConsultationModal_{{ $c->id }}">
                                                            <i class="fas fa-check"></i> تدوين التقرير
                                                        </button>

                                                        <!-- Modal تدوين تقرير الاستشارة -->
                                                        <div class="modal fade" id="completeConsultationModal_{{ $c->id }}" tabindex="-1">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header bg-info text-white">
                                                                        <h6 class="modal-title fw-bold">تقرير فحص الاستشارة: {{ $c->doctor_display_name }}</h6>
                                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <form action="{{ route('nicu.consultations.complete', $c) }}" method="POST">
                                                                        @csrf
                                                                        @method('PATCH')
                                                                        <div class="modal-body p-3">
                                                                            <div class="mb-3">
                                                                                <label class="form-label small fw-bold">فحص الطبيب المستشار (Clinical Findings):</label>
                                                                                <textarea name="findings" rows="3" class="form-control" required></textarea>
                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label class="form-label small fw-bold">التوصيات وخطة العلاج (Recommendations):</label>
                                                                                <textarea name="recommendations" rows="3" class="form-control"></textarea>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="submit" class="btn btn-sm btn-success fw-bold">حفظ التقرير واعتماده</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="badge bg-success">مكتملة</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">لم يتم طلب استشارات تخصصية لهذا الطفل.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تبويب 4: التحاليل والفحوصات المخبرية (Lab Workstation) -->
                <div class="tab-pane fade" id="labPane">
                    <div class="row g-4">
                        <!-- عمود تقديم طلب التحاليل المخبرية -->
                        <div class="col-lg-5">
                            <div class="card border-0 bg-light p-3 rounded-3 shadow-xs">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary text-white p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                            <i class="fas fa-microscope"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-primary mb-0">طلب فحوصات مخبرية</h6>
                                            <small class="text-muted" style="font-size: 11px;">اختر من الباقات أو ابحث بالاسم واضغط Enter</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-primary rounded-pill px-2 py-1">مختبر المستشفى</span>
                                </div>

                                <form action="{{ route('nicu.lab-orders.store', $reservation) }}" method="POST" id="nicuLabOrderForm">
                                    @csrf

                                    <!-- باقات حديثي الولادة والخدج السريعة -->
                                    <div class="mb-3 p-2 bg-white rounded-3 border">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="small fw-bold text-dark">
                                                <i class="fas fa-bolt text-warning me-1"></i> باقات الخدج السريعة (نقرة واحدة للإدراج):
                                            </span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 nicu-quick-lab-pkg" data-tests='["Complete Blood Count (CBC)", "Total Serum Bilirubin (TSB)", "C-Reactive Protein (CRP)", "Random Blood Sugar (RBS)", "Blood Group & Rh"]'>
                                                <i class="fas fa-baby me-1"></i> دخول روتيني (5)
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-2 py-1 nicu-quick-lab-pkg text-dark" data-tests='["Total Serum Bilirubin (TSB)", "Direct Bilirubin", "Indirect Bilirubin", "Blood Group & Rh", "Direct Coombs Test"]'>
                                                <i class="fas fa-sun me-1"></i> اليرقان والصفار (5)
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-2 py-1 nicu-quick-lab-pkg" data-tests='["Arterial Blood Gases (ABG)", "Serum Electrolytes (Na, K, Cl)", "Blood Lactate"]'>
                                                <i class="fas fa-lungs me-1"></i> غازات الدم ABG (3)
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1 nicu-quick-lab-pkg" data-tests='["Complete Blood Count (CBC)", "Blood Culture", "C-Reactive Protein (CRP)", "Procalcitonin", "Micro-ESR"]'>
                                                <i class="fas fa-shield-virus me-1"></i> مسح الإنتان Sepsis (5)
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1 nicu-quick-lab-pkg" data-tests='["Serum Creatinine", "Blood Urea", "Serum Electrolytes (Na, K, Cl)", "Serum Calcium"]'>
                                                <i class="fas fa-vial me-1"></i> وظائف الكلى والأملاح (4)
                                            </button>
                                        </div>
                                    </div>

                                    <!-- مربع البحث التفاعلي الفوري عن التحاليل -->
                                    <div class="mb-3 position-relative">
                                        <label class="form-label small fw-bold text-dark">
                                            <i class="fas fa-search text-primary me-1"></i> ابحث عن التحليل المخبري:
                                        </label>
                                        <div class="input-group input-group-sm shadow-xs">
                                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                            <input type="text" id="nicuLabSearchInput" class="form-control" placeholder="اكتب اسم التحليل (مثل: CBC, Bilirubin, ABG, CRP...) واضغط Enter..." autocomplete="off">
                                            <button type="button" class="btn btn-outline-secondary" id="nicuLabClearInputBtn" title="مسح">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div id="nicuLabAutocompleteMenu" class="list-group position-absolute w-100 shadow-lg mt-1" style="max-height: 220px; overflow-y: auto; display: none; z-index: 1060;"></div>
                                    </div>

                                    <!-- صينية التحاليل المختارة للإرسال (Chips Tray) -->
                                    <div id="nicuLabSelectedTray" class="mb-3 p-3 bg-white rounded-3 border d-none">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small fw-bold text-primary">
                                                <i class="fas fa-check-circle me-1"></i> التحاليل المختارة (<span id="nicuLabSelectedCount">0</span>):
                                            </span>
                                            <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none" id="nicuLabClearAllBtn">
                                                <i class="fas fa-trash-alt me-1"></i> إفراغ الكل
                                            </button>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2" id="nicuLabChipsContainer"></div>
                                    </div>

                                    <!-- الأولوية وملاحظات الطبيب -->
                                    <div class="row g-2 mb-3">
                                        <div class="col-12">
                                            <label class="form-label small fw-bold">الأولوية والسرعة المطلوبة:</label>
                                            <div class="d-flex gap-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="priority" id="labPriNormal" value="normal">
                                                    <label class="form-check-label small" for="labPriNormal">عادي (Normal)</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="priority" id="labPriUrgent" value="urgent" checked>
                                                    <label class="form-check-label small text-warning fw-bold" for="labPriUrgent">عاجل (Urgent)</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="priority" id="labPriStat" value="stat">
                                                    <label class="form-check-label small text-danger fw-bold" for="labPriStat">🚨 فوري طارئ (STAT)</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">ملاحظات وتوجيهات الطبيب للمختبر:</label>
                                        <input type="text" name="instructions" class="form-control form-control-sm" placeholder="مثال: عينة كعب قدم دقيقة، فحص عاجل قبل العلاج الضوئي...">
                                    </div>

                                    <button type="submit" id="btnSubmitNicuLab" class="btn btn-primary w-100 fw-bold shadow-sm">
                                        <i class="fas fa-paper-plane me-1"></i> إرسال طلب التحاليل للمختبر
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- سجل ومتابعة طلبات المختبر والتحاليل بنمط محطة الطبيب -->
                        <div class="col-lg-7">
                            @php
                                $labRequests = $reservation->medicalRequests->where('type', 'lab')->sortByDesc('id');
                                $legacyLabOrders = $reservation->medicalOrders->where('order_type', 'lab')->filter(function($o) {
                                    return empty($o->order_group_id) || !str_starts_with($o->order_group_id, 'REQ-');
                                });
                            @endphp
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fas fa-vial text-primary me-1"></i>
                                    سجل ومتابعة طلبات المختبر والتحاليل (Laboratory Requests & Results)
                                </h6>
                                <span class="badge bg-light text-primary border">
                                    الطلبات: {{ $labRequests->count() + ($legacyLabOrders->count() > 0 ? $legacyLabOrders->count() : 0) }}
                                </span>
                            </div>

                            @forelse($labRequests as $medRequest)
                                @php
                                    $reqDetails = is_string($medRequest->details) ? json_decode($medRequest->details, true) : ($medRequest->details ?? []);
                                    $isCompleted = ($medRequest->status === 'completed');
                                    $isInProgress = ($medRequest->status === 'in_progress');
                                    $isPaid = true; // الخدج: الحساب موحد وتراكمي لدى كاشير الخدج
                                    $priority = $medRequest->priority ?? ($reqDetails['priority'] ?? 'normal');
                                    $doctorNotes = $reqDetails['doctor_notes'] ?? null;
                                    $labItems = $reqDetails['tests'] ?? [];
                                    if (empty($labItems) && !empty($medRequest->description)) {
                                        $labItems = [str_replace('تحاليل خديج: ', '', $medRequest->description)];
                                    }
                                    
                                    // النتيجة وبيانات المختبر
                                    $resultData = is_string($medRequest->result) ? json_decode($medRequest->result, true) : ($medRequest->result ?? []);
                                    $testResults = $resultData['test_results'] ?? [];
                                    
                                    // إذا لم توجد في حقل النتيجة مباشرة، نتفقد نتائج labResults المرتبطة
                                    if (empty($testResults) && $medRequest->labResults->isNotEmpty()) {
                                        $latestLabRes = $medRequest->labResults->first();
                                        $testResults = is_string($latestLabRes->results) ? json_decode($latestLabRes->results, true) : ($latestLabRes->results ?? []);
                                    }

                                    $technicianNotes = $resultData['notes'] ?? null;
                                    $technicianName = $resultData['technician'] ?? null;
                                    $completedAt = $resultData['completed_at'] ?? null;
                                    $hasAttachment = !empty($reqDetails['attachment_path']);
                                    $testAttachments = $reqDetails['test_attachments'] ?? [];
                                @endphp

                                <div class="card border rounded-3 mb-3 shadow-xs overflow-hidden">
                                    <!-- رأس الطلب -->
                                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary text-white font-monospace">طلب #{{ $medRequest->id }}</span>
                                            <span class="text-muted small">
                                                <i class="fas fa-clock me-1"></i>{{ $medRequest->created_at->format('Y-m-d H:i') }}
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                            @if($priority === 'stat')
                                                <span class="badge bg-danger text-white"><i class="fas fa-bolt me-1"></i>STAT طارئ جداً</span>
                                            @elseif($priority === 'urgent')
                                                <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>عاجل</span>
                                            @else
                                                <span class="badge bg-secondary text-white">عادي</span>
                                            @endif

                                            <span class="badge bg-success-subtle text-success border border-success-subtle" title="مضمن ضمن الحساب المالي الموحد للحاضنة">
                                                <i class="fas fa-check-circle me-1"></i>حساب الحاضنة
                                            </span>

                                            @if($isCompleted)
                                                <span class="badge bg-success text-white"><i class="fas fa-check-double me-1"></i>مكتمل ومعتمد</span>
                                            @elseif($isInProgress)
                                                <span class="badge bg-info text-dark"><i class="fas fa-spinner fa-spin me-1"></i>قيد التحليل</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle"><i class="fas fa-hourglass-half me-1"></i>بانتظار المختبر</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- شريط مسار ومراحل الطلب التفاعلي (Status Progress Stepper) -->
                                    <div class="px-3 py-2 bg-light-subtle border-bottom">
                                        <div class="d-flex align-items-center justify-content-between position-relative" style="font-size: 0.72rem;">
                                            <div class="position-absolute start-0 end-0 top-50 translate-middle-y border-top border-2" style="border-color: #dee2e6 !important; z-index: 1;"></div>
                                            
                                            <!-- المرحلة 1: طلب الطبيب -->
                                            <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 22px; height: 22px; font-size: 0.7rem;">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                                <span class="fw-semibold text-dark" style="font-size: 0.7rem;">طلب الطبيب</span>
                                            </div>

                                            <!-- المرحلة 2: القيد المالي -->
                                            <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 22px; height: 22px; font-size: 0.7rem;">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                                <span class="fw-semibold text-success" style="font-size: 0.7rem;">حساب الحاضنة</span>
                                            </div>

                                            <!-- المرحلة 3: المختبر المركزي -->
                                            <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                @if($isCompleted)
                                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 22px; height: 22px; font-size: 0.7rem;">
                                                        <i class="fas fa-check"></i>
                                                    </span>
                                                    <span class="fw-semibold text-success" style="font-size: 0.7rem;">أُنجز بالمختبر</span>
                                                @elseif($isInProgress)
                                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-primary text-white mb-1 shadow-xs" style="width: 22px; height: 22px; font-size: 0.7rem;">
                                                        <i class="fas fa-spinner fa-spin"></i>
                                                    </span>
                                                    <span class="fw-bold text-primary" style="font-size: 0.7rem;">قيد التحليل</span>
                                                @else
                                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-white text-muted mb-1 border" style="width: 22px; height: 22px; font-size: 0.7rem;">
                                                        <i class="fas fa-hourglass-start"></i>
                                                    </span>
                                                    <span class="text-muted" style="font-size: 0.7rem;">بانتظار العينة</span>
                                                @endif
                                            </div>

                                            <!-- المرحلة 4: النتيجة -->
                                            <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                @if($isCompleted)
                                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 22px; height: 22px; font-size: 0.7rem;">
                                                        <i class="fas fa-file-medical"></i>
                                                    </span>
                                                    <span class="fw-bold text-success" style="font-size: 0.7rem;">النتيجة جاهزة</span>
                                                @else
                                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-white text-muted mb-1 border" style="width: 22px; height: 22px; font-size: 0.7rem;">
                                                        <i class="fas fa-file"></i>
                                                    </span>
                                                    <span class="text-muted" style="font-size: 0.7rem;">التقرير</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- محتوى جسم البطاقة -->
                                    <div class="card-body p-3">
                                        <!-- جدول الفحوصات السريري المصغر -->
                                        <div class="table-responsive rounded-2 border bg-white mb-2 shadow-2xs">
                                            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                                <thead class="table-light text-muted" style="font-size: 0.72rem;">
                                                    <tr>
                                                        <th class="py-1 px-2 text-center" style="width: 28px;">#</th>
                                                        <th class="py-1 px-2">اسم التحليل المخبري</th>
                                                        <th class="py-1 px-2 text-center" style="width: 110px;">الحالة</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($labItems as $itemIdx => $lItem)
                                                        <tr>
                                                            <td class="text-muted font-monospace text-center py-1 px-2">{{ $itemIdx + 1 }}</td>
                                                            <td class="fw-semibold text-dark py-1 px-2">
                                                                <i class="fas fa-vial text-primary me-1 opacity-75"></i> {{ $lItem }}
                                                            </td>
                                                            <td class="text-center py-1 px-2">
                                                                @if($isCompleted)
                                                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">مكتمل</span>
                                                                @elseif($isInProgress)
                                                                    <span class="badge bg-info-subtle text-primary border border-info-subtle" style="font-size: 0.68rem;">قيد التحليل</span>
                                                                @else
                                                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size: 0.68rem;">بالمختبر</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        @if($doctorNotes)
                                            <div class="small text-muted bg-light p-2 rounded mb-2 border">
                                                <i class="fas fa-comment-medical text-primary me-1"></i> <strong>ملاحظات الطبيب:</strong> {{ $doctorNotes }}
                                            </div>
                                        @endif

                                        <!-- عرض النتائج التفصيلية للفحوصات عند اكتمالها -->
                                        @if($isCompleted)
                                            @if(!empty($testResults) && is_array($testResults) && count($testResults) > 0)
                                                <div class="table-responsive rounded-2 border bg-white mb-2 shadow-2xs mt-2">
                                                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.8rem;">
                                                        <thead class="table-light text-muted" style="font-size: 0.72rem;">
                                                            <tr>
                                                                <th class="py-1.5 px-2">الفحص / المكون</th>
                                                                <th class="py-1.5 px-2 text-center">النتيجة</th>
                                                                <th class="py-1.5 px-2 text-center">الوحدة</th>
                                                                <th class="py-1.5 px-2 text-center">المرجع الطبيعي</th>
                                                                <th class="py-1.5 px-2 text-center">التقييم السريري</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($testResults as $tName => $tData)
                                                                @php
                                                                    $val = is_array($tData) ? ($tData['value'] ?? '-') : $tData;
                                                                    $unit = is_array($tData) ? ($tData['unit'] ?? '-') : '-';
                                                                    $ref = is_array($tData) ? ($tData['reference_range'] ?? ($tData['reference'] ?? '-')) : '-';
                                                                    $status = is_array($tData) ? ($tData['status'] ?? ($tData['result_status'] ?? 'normal')) : 'normal';
                                                                    $isCritical = in_array(strtolower($status), ['critical', 'danger']);
                                                                    $isHigh = in_array(strtolower($status), ['high', 'h']);
                                                                    $isLow = in_array(strtolower($status), ['low', 'l']);
                                                                    $isAbnormal = $isCritical || $isHigh || $isLow || $status === 'abnormal';
                                                                @endphp
                                                                <tr class="{{ $isCritical ? 'table-danger' : ($isAbnormal ? 'table-warning' : '') }}">
                                                                    <td class="fw-semibold py-1.5 px-2">
                                                                        <i class="fas fa-flask text-primary me-1 opacity-75"></i> {{ $tName }}
                                                                    </td>
                                                                    <td class="text-center fw-bold font-monospace py-1.5 px-2 {{ $isCritical ? 'text-danger fs-6' : ($isAbnormal ? 'text-dark' : 'text-success') }}">
                                                                        {{ $val }}
                                                                    </td>
                                                                    <td class="text-center text-muted py-1.5 px-2">{{ $unit }}</td>
                                                                    <td class="text-center text-muted font-monospace py-1.5 px-2"><small>{{ $ref }}</small></td>
                                                                    <td class="text-center py-1.5 px-2">
                                                                        @if($isCritical)
                                                                            <span class="badge bg-danger text-white"><i class="fas fa-exclamation-circle me-1"></i>حرج 🚨</span>
                                                                        @elseif($isHigh)
                                                                            <span class="badge bg-warning text-dark"><i class="fas fa-arrow-up me-1"></i>مرتفع (H)</span>
                                                                        @elseif($isLow)
                                                                            <span class="badge bg-warning text-dark"><i class="fas fa-arrow-down me-1"></i>منخفض (L)</span>
                                                                        @else
                                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">طبيعي</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif

                                            @if($technicianNotes)
                                                <div class="small bg-light p-2 rounded mb-2 border text-muted">
                                                    <i class="fas fa-user-check text-success me-1"></i> <strong>ملاحظات فني المختبر:</strong> {{ $technicianNotes }}
                                                </div>
                                            @endif

                                            <!-- شريط الإجراءات والطباعة الرسمية -->
                                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top flex-wrap gap-2">
                                                <div>
                                                    <a href="{{ route('lab.print', $medRequest->id) }}" target="_blank" class="btn btn-sm btn-success fw-bold shadow-xs">
                                                        <i class="fas fa-print me-1"></i> طباعة النتيجة الرسمية (A5)
                                                    </a>
                                                    <a href="{{ route('lab.show', $medRequest->id) }}" class="btn btn-sm btn-outline-secondary ms-1">
                                                        <i class="fas fa-external-link-alt me-1"></i> فتح في المختبر المركزي
                                                    </a>
                                                </div>
                                                @if($technicianName)
                                                    <span class="text-muted small">
                                                        <i class="fas fa-user-tie me-1"></i> الفني المنفذ: <strong class="text-dark">{{ $technicianName }}</strong>
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <!-- شريط الحالة أثناء الانتظار -->
                                            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top flex-wrap gap-2">
                                                <span class="text-muted small">
                                                    <i class="fas fa-clock me-1 text-warning"></i> الطلب مرسل وبانتظار سحب العينات والتحليل في المختبر المركزي
                                                </span>
                                                <a href="{{ route('lab.show', $medRequest->id) }}" class="btn btn-xs btn-outline-primary py-1 px-2.5 shadow-xs" style="font-size: 0.75rem;">
                                                    <i class="fas fa-microscope me-1"></i> فتح محطة إدخال نتائج المختبر ↗
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                @if($legacyLabOrders->isEmpty())
                                    <div class="card border-0 bg-light p-4 text-center rounded-3">
                                        <i class="fas fa-microscope fa-3x mb-2 text-muted opacity-25"></i>
                                        <p class="text-muted mb-0">لا توجد طلبات تحاليل مخبرية مسجلة بعد لهذا الطفل الخديج.</p>
                                    </div>
                                @endif
                            @endforelse

                            <!-- عرض السجلات القديمة السابقة إن وجدت لحفظ الأرشيف -->
                            @if($legacyLabOrders->isNotEmpty())
                                <div class="mt-3 pt-3 border-top">
                                    <h6 class="text-muted small fw-bold mb-2">
                                        <i class="fas fa-history me-1"></i> سجلات التحاليل الفردية السابقة (الأرشيف):
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle border text-center small mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-start">اسم التحليل</th>
                                                    <th>الأولوية</th>
                                                    <th>النتيجة</th>
                                                    <th>الحالة</th>
                                                    <th>إجراء</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($legacyLabOrders as $ord)
                                                    <tr>
                                                        <td class="text-start fw-semibold text-primary">{{ $ord->item_name }}</td>
                                                        <td><span class="badge bg-secondary">{{ $ord->priority }}</span></td>
                                                        <td>{{ $ord->result ?? 'بانتظار النتيجة' }}</td>
                                                        <td><span class="badge bg-{{ $ord->status == 'completed' ? 'success' : 'secondary' }}">{{ $ord->status == 'completed' ? 'تم الفحص' : 'قيد الانتظار' }}</span></td>
                                                        <td>
                                                            @if($ord->result)
                                                                <a href="{{ route('lab.nicu-orders.print', $ord) }}" target="_blank" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size: 11px;">
                                                                    <i class="fas fa-print me-1"></i> طباعة
                                                                </a>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- تبويب 5: الفحوصات الشعاعية والتصوير والسونار (Radiology Workstation) -->
                <div class="tab-pane fade" id="radiologyPane">
                    <div class="row g-4">
                        <!-- عمود تقديم طلب الأشعة والسونار -->
                        <div class="col-lg-5">
                            <div class="card border-0 bg-light p-3 rounded-3 shadow-xs">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-info text-white p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                            <i class="fas fa-x-ray"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-info mb-0">طلب فحص شعاعي / سونار</h6>
                                            <small class="text-muted" style="font-size: 11px;">أشعة نقالة بالحاضنة، سونار دماغ، إيكو قلب...</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-info text-white rounded-pill px-2 py-1">قسم الأشعة</span>
                                </div>

                                <form action="{{ route('nicu.radiology-orders.store', $reservation) }}" method="POST" id="nicuRadOrderForm">
                                    @csrf

                                    <!-- فحوصات التصوير الشائعة لحديثي الولادة -->
                                    <div class="mb-3 p-2 bg-white rounded-3 border">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="small fw-bold text-dark">
                                                <i class="fas fa-bolt text-warning me-1"></i> تصوير الخدج الشائع (نقرة واحدة للإدراج):
                                            </span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-2 py-1 nicu-quick-rad-pkg" data-types='["Portable Chest X-Ray (Incubator)"]'>
                                                <i class="fas fa-lungs me-1"></i> أشعة صدر نقالة
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 nicu-quick-rad-pkg" data-types='["Cranial Ultrasound (Neurosonogram)"]'>
                                                <i class="fas fa-brain me-1"></i> سونار الدماغ واليافوخ
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1 nicu-quick-rad-pkg" data-types='["Pediatric Echocardiogram (PDA Screen)"]'>
                                                <i class="fas fa-heart me-1"></i> إيكو قلب أطفال
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-2 py-1 nicu-quick-rad-pkg text-dark" data-types='["Portable Abdominal X-Ray (NEC Evaluation)"]'>
                                                <i class="fas fa-circle-notch me-1"></i> أشعة بطن نقالة
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1 nicu-quick-rad-pkg" data-types='["Babygram (Whole Body X-Ray)"]'>
                                                <i class="fas fa-child me-1"></i> تصوير كامل (Babygram)
                                            </button>
                                        </div>
                                    </div>

                                    <!-- مربع البحث التفاعلي الفوري عن الفحص الشعاعي -->
                                    <div class="mb-3 position-relative">
                                        <label class="form-label small fw-bold text-dark">
                                            <i class="fas fa-search text-info me-1"></i> ابحث عن نوع الفحص الشعاعي:
                                        </label>
                                        <div class="input-group input-group-sm shadow-xs">
                                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                            <input type="text" id="nicuRadSearchInput" class="form-control" placeholder="اكتب اسم الفحص (مثل: Chest, Brain, Echo, Abdomen...) واضغط Enter..." autocomplete="off">
                                            <button type="button" class="btn btn-outline-secondary" id="nicuRadClearInputBtn" title="مسح">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div id="nicuRadAutocompleteMenu" class="list-group position-absolute w-100 shadow-lg mt-1" style="max-height: 220px; overflow-y: auto; display: none; z-index: 1060;"></div>
                                    </div>

                                    <!-- صينية الفحوصات الشعاعية المختارة (Chips Tray) -->
                                    <div id="nicuRadSelectedTray" class="mb-3 p-3 bg-white rounded-3 border d-none">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small fw-bold text-info">
                                                <i class="fas fa-check-circle me-1"></i> الفحوصات المختارة (<span id="nicuRadSelectedCount">0</span>):
                                            </span>
                                            <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none" id="nicuRadClearAllBtn">
                                                <i class="fas fa-trash-alt me-1"></i> إفراغ الكل
                                            </button>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2" id="nicuRadChipsContainer"></div>
                                    </div>

                                    <!-- الأولوية ودواعي الفحص -->
                                    <div class="row g-2 mb-3">
                                        <div class="col-12">
                                            <label class="form-label small fw-bold">الأولوية والسرعة المطلوبة:</label>
                                            <div class="d-flex gap-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="priority" id="radPriNormal" value="normal">
                                                    <label class="form-check-label small" for="radPriNormal">عادي (Normal)</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="priority" id="radPriUrgent" value="urgent" checked>
                                                    <label class="form-check-label small text-warning fw-bold" for="radPriUrgent">عاجل (Urgent)</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="priority" id="radPriStat" value="stat">
                                                    <label class="form-check-label small text-danger fw-bold" for="radPriStat">🚨 فوري طارئ (STAT)</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">الدواعي السريرية وتوجيهات التصوير:</label>
                                        <input type="text" name="instructions" class="form-control form-control-sm" placeholder="مثال: تصوير داخل الحاضنة بدون تحريك، تفقد موقع أنبوب الرغامي ETT...">
                                    </div>

                                    <button type="submit" id="btnSubmitNicuRad" class="btn btn-info text-white w-100 fw-bold shadow-sm">
                                        <i class="fas fa-paper-plane me-1"></i> إرسال طلب الأشعة لقسم التصوير
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- عمود عرض ومتابعة تقارير الأشعة والسونار بنمط محطة الطبيب -->
                        <div class="col-lg-7">
                            @php
                                $radRequests = $reservation->medicalRequests->where('type', 'radiology')->sortByDesc('id');
                                $legacyRadOrders = $reservation->medicalOrders->where('order_type', 'radiology')->filter(function($o) {
                                    return empty($o->order_group_id) || !str_starts_with($o->order_group_id, 'REQ-');
                                });
                            @endphp
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fas fa-x-ray text-info me-1"></i>
                                    سجل ومتابعة طلبات الأشعة والسونار (Radiology & Ultrasound)
                                </h6>
                                <span class="badge bg-light text-info border">
                                    الطلبات: {{ $radRequests->count() + ($legacyRadOrders->count() > 0 ? $legacyRadOrders->count() : 0) }}
                                </span>
                            </div>

                            @forelse($radRequests as $medRequest)
                                @php
                                    $reqDetails = is_string($medRequest->details) ? json_decode($medRequest->details, true) : ($medRequest->details ?? []);
                                    $isCompleted = ($medRequest->status === 'completed');
                                    $isInProgress = ($medRequest->status === 'in_progress');
                                    $priority = $medRequest->priority ?? ($reqDetails['priority'] ?? 'normal');
                                    $doctorNotes = $reqDetails['doctor_notes'] ?? null;
                                    
                                    // استخراج مسميات الفحوصات المطلوبة
                                    $radNames = $medRequest->radiology_names;
                                    if (empty($radNames) && !empty($reqDetails['radiology_names'])) {
                                        $radNames = $reqDetails['radiology_names'];
                                    }
                                    if (empty($radNames) && !empty($medRequest->description)) {
                                        $radNames = [str_replace('طلب أشعة وسونار خديج: ', '', $medRequest->description)];
                                    }

                                    // نتيجة وتقرير الأشعة
                                    $radResult = is_string($medRequest->result) ? json_decode($medRequest->result, true) : ($medRequest->result ?? []);
                                    if (!is_array($radResult) && !empty($medRequest->result)) {
                                        $radResult = ['findings' => (string)$medRequest->result];
                                    }
                                    $radFindings = $radResult['findings'] ?? $radResult['result_text'] ?? null;
                                    $radImpression = $radResult['impression'] ?? null;
                                    $radRecommendations = $radResult['recommendations'] ?? null;
                                    $radImages = $radResult['images'] ?? [];
                                    $radiologist = $radResult['radiologist'] ?? null;
                                    $radReportedAt = $radResult['reported_at'] ?? null;
                                @endphp

                                <div class="card border rounded-3 mb-3 shadow-xs overflow-hidden">
                                    <!-- رأس الطلب -->
                                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-info text-white font-monospace">طلب #{{ $medRequest->id }}</span>
                                            <span class="badge bg-light text-dark border">{{ $medRequest->type_text }} ({{ $medRequest->subtype ?? 'عام' }})</span>
                                            <span class="text-muted small">
                                                <i class="fas fa-clock me-1"></i>{{ $medRequest->created_at->format('Y-m-d H:i') }}
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                            @if($priority === 'stat')
                                                <span class="badge bg-danger text-white"><i class="fas fa-bolt me-1"></i>STAT طارئ جداً</span>
                                            @elseif($priority === 'urgent')
                                                <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>عاجل</span>
                                            @else
                                                <span class="badge bg-secondary text-white">عادي</span>
                                            @endif

                                            <span class="badge bg-success-subtle text-success border border-success-subtle" title="مضمن ضمن الحساب المالي الموحد للحاضنة">
                                                <i class="fas fa-check-circle me-1"></i>حساب الحاضنة
                                            </span>

                                            @if($isCompleted)
                                                <span class="badge bg-success text-white"><i class="fas fa-check-double me-1"></i>مكتمل ومعتمد</span>
                                            @elseif($isInProgress)
                                                <span class="badge bg-info text-dark"><i class="fas fa-spinner fa-spin me-1"></i>قيد الفحص</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle"><i class="fas fa-hourglass-half me-1"></i>بانتظار الأشعة</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- شريط مسار ومراحل الطلب التفاعلي (Status Progress Stepper) -->
                                    <div class="px-3 py-2 bg-light-subtle border-bottom">
                                        <div class="d-flex align-items-center justify-content-between position-relative" style="font-size: 0.72rem;">
                                            <div class="position-absolute start-0 end-0 top-50 translate-middle-y border-top border-2" style="border-color: #dee2e6 !important; z-index: 1;"></div>
                                            
                                            <!-- المرحلة 1: طلب الطبيب -->
                                            <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 22px; height: 22px; font-size: 0.7rem;">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                                <span class="fw-semibold text-dark" style="font-size: 0.7rem;">طلب الطبيب</span>
                                            </div>

                                            <!-- المرحلة 2: القيد المالي -->
                                            <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 22px; height: 22px; font-size: 0.7rem;">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                                <span class="fw-semibold text-success" style="font-size: 0.7rem;">حساب الحاضنة</span>
                                            </div>

                                            <!-- المرحلة 3: قسم الأشعة والسونار -->
                                            <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                @if($isCompleted)
                                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 22px; height: 22px; font-size: 0.7rem;">
                                                        <i class="fas fa-check"></i>
                                                    </span>
                                                    <span class="fw-semibold text-success" style="font-size: 0.7rem;">أُنجز بالأشعة</span>
                                                @elseif($isInProgress)
                                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-info text-white mb-1 shadow-xs" style="width: 22px; height: 22px; font-size: 0.7rem;">
                                                        <i class="fas fa-spinner fa-spin"></i>
                                                    </span>
                                                    <span class="fw-bold text-info" style="font-size: 0.7rem;">قيد التصوير</span>
                                                @else
                                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-white text-muted mb-1 border" style="width: 22px; height: 22px; font-size: 0.7rem;">
                                                        <i class="fas fa-hourglass-start"></i>
                                                    </span>
                                                    <span class="text-muted" style="font-size: 0.7rem;">بالانتظار</span>
                                                @endif
                                            </div>

                                            <!-- المرحلة 4: التقرير الطبي -->
                                            <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                @if($isCompleted)
                                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 22px; height: 22px; font-size: 0.7rem;">
                                                        <i class="fas fa-file-medical-alt"></i>
                                                    </span>
                                                    <span class="fw-bold text-success" style="font-size: 0.7rem;">التقرير جاهز</span>
                                                @else
                                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-white text-muted mb-1 border" style="width: 22px; height: 22px; font-size: 0.7rem;">
                                                        <i class="fas fa-file"></i>
                                                    </span>
                                                    <span class="text-muted" style="font-size: 0.7rem;">التقرير</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- محتوى جسم البطاقة -->
                                    <div class="card-body p-3">
                                        <!-- جدول الفحوصات المطلوبة -->
                                        <div class="table-responsive rounded-2 border bg-white mb-2 shadow-2xs">
                                            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                                <thead class="table-light text-muted" style="font-size: 0.72rem;">
                                                    <tr>
                                                        <th class="py-1 px-2 text-center" style="width: 28px;">#</th>
                                                        <th class="py-1 px-2">الفحص الشعاعي المطلوب</th>
                                                        <th class="py-1 px-2 text-center" style="width: 110px;">الحالة</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($radNames as $itemIdx => $rItem)
                                                        <tr>
                                                            <td class="text-muted font-monospace text-center py-1 px-2">{{ $itemIdx + 1 }}</td>
                                                            <td class="fw-semibold text-dark py-1 px-2">
                                                                <i class="fas fa-x-ray text-info me-1 opacity-75"></i> {{ $rItem }}
                                                            </td>
                                                            <td class="text-center py-1 px-2">
                                                                @if($isCompleted)
                                                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">مكتمل</span>
                                                                @elseif($isInProgress)
                                                                    <span class="badge bg-info-subtle text-dark border border-info-subtle" style="font-size: 0.68rem;">قيد التصوير</span>
                                                                @else
                                                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size: 0.68rem;">بالأشعة</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        @if($doctorNotes)
                                            <div class="small text-muted bg-light p-2 rounded mb-2 border">
                                                <i class="fas fa-comment-medical text-info me-1"></i> <strong>توجيهات الطبيب:</strong> {{ $doctorNotes }}
                                            </div>
                                        @endif

                                        <!-- تقرير الأشعة والسونار عند اكتماله -->
                                        @if($isCompleted && (!empty($radFindings) || !empty($radImpression) || count($radImages) > 0))
                                            <div class="card border border-info-subtle bg-info-subtle bg-opacity-10 rounded-3 mt-2 mb-0 shadow-xs">
                                                <div class="card-header bg-white py-1.5 px-3 border-bottom d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center gap-1.5 text-info-emphasis">
                                                        <i class="fas fa-file-medical-alt fs-6"></i>
                                                        <strong class="small">تقرير نتائج الأشعة والتصوير السريري</strong>
                                                    </div>
                                                    @if($radiologist)
                                                        <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">
                                                            <i class="fas fa-user-md me-1 text-primary"></i> أخصائي الأشعة: {{ $radiologist }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="card-body p-3 small">
                                                    @if(!empty($radFindings))
                                                        <div class="mb-2">
                                                            <span class="fw-bold text-dark d-block mb-1">
                                                                <i class="fas fa-microscope text-info me-1"></i> المشاهدات والنتائج (Findings):
                                                            </span>
                                                            <div class="p-2 bg-white rounded border text-dark" style="white-space: pre-line; line-height: 1.6;">{{ $radFindings }}</div>
                                                        </div>
                                                    @endif

                                                    @if(!empty($radImpression))
                                                        <div class="mb-2">
                                                            <span class="fw-bold text-primary d-block mb-1">
                                                                <i class="fas fa-clipboard-check me-1"></i> الانطباع التشخيصي (Impression):
                                                            </span>
                                                            <div class="p-2 bg-primary-subtle text-primary-emphasis rounded border border-primary-subtle fw-semibold" style="white-space: pre-line;">{{ $radImpression }}</div>
                                                        </div>
                                                    @endif

                                                    @if(!empty($radRecommendations))
                                                        <div class="mb-2">
                                                            <span class="fw-bold text-secondary d-block mb-1">
                                                                <i class="fas fa-comment-medical me-1"></i> التوصيات والملاحظات الإضافية:
                                                            </span>
                                                            <div class="p-2 bg-white rounded border text-muted" style="white-space: pre-line;">{{ $radRecommendations }}</div>
                                                        </div>
                                                    @endif

                                                    @if(is_array($radImages) && count($radImages) > 0)
                                                        <div class="mt-2 pt-2 border-top">
                                                            <span class="fw-bold text-dark d-block mb-1.5">
                                                                <i class="fas fa-images text-info me-1"></i> الصور والمرفقات الإشعاعية ({{ count($radImages) }}):
                                                            </span>
                                                            <div class="d-flex flex-wrap gap-2">
                                                                @foreach($radImages as $imgIndex => $imgPath)
                                                                    @php
                                                                        $imgUrl = asset('storage/' . $imgPath);
                                                                        $isPdf = str_ends_with(strtolower($imgPath), '.pdf');
                                                                    @endphp
                                                                    @if($isPdf)
                                                                        <a href="{{ $imgUrl }}" target="_blank" class="btn btn-xs btn-outline-info rounded-pill py-1 px-2.5 d-inline-flex align-items-center gap-1">
                                                                            <i class="fas fa-file-pdf text-danger"></i>
                                                                            <span>ملف PDF #{{ $imgIndex + 1 }} ↗</span>
                                                                        </a>
                                                                    @else
                                                                        <a href="{{ $imgUrl }}" target="_blank" class="position-relative d-inline-block border rounded overflow-hidden shadow-xs" style="width: 75px; height: 75px;">
                                                                            <img src="{{ $imgUrl }}" alt="Radiology Image" style="width: 100%; height: 100%; object-fit: cover;">
                                                                            <span class="position-absolute bottom-0 start-0 w-100 bg-dark bg-opacity-75 text-white text-center py-0.5" style="font-size: 0.6rem;">معاينة ↗</span>
                                                                        </a>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- شريط الإجراءات والطباعة الرسمية للأشعة -->
                                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top flex-wrap gap-2">
                                                <div>
                                                    <a href="{{ route('radiology-staff.print', $medRequest->id) }}" target="_blank" class="btn btn-sm btn-info text-white fw-bold shadow-xs">
                                                        <i class="fas fa-print me-1"></i> طباعة التقرير الشعاعي الرسمي (A5)
                                                    </a>
                                                    <a href="{{ route('radiology-staff.show', $medRequest->id) }}" class="btn btn-sm btn-outline-secondary ms-1">
                                                        <i class="fas fa-external-link-alt me-1"></i> فتح في قسم الأشعة
                                                    </a>
                                                </div>
                                                @if($radReportedAt)
                                                    <span class="text-muted small">
                                                        <i class="fas fa-calendar-check me-1"></i> تاريخ التقرير: {{ $radReportedAt }}
                                                    </span>
                                                @endif
                                            </div>
                                        @elseif($isCompleted)
                                            <!-- تقرير منجز بدون حقول مفصلة -->
                                            <div class="alert alert-success mt-2 mb-0 py-2 px-3 small">
                                                <i class="fas fa-check-circle me-1"></i> تم إنجاز الفحص الشعاعي من قبل الكادر المركزي.
                                                <a href="{{ route('radiology-staff.print', $medRequest->id) }}" target="_blank" class="btn btn-xs btn-outline-success ms-2">
                                                    <i class="fas fa-print me-1"></i> طباعة التقرير
                                                </a>
                                            </div>
                                        @else
                                            <!-- شريط الحالة أثناء الانتظار -->
                                            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top flex-wrap gap-2">
                                                <span class="text-muted small">
                                                    <i class="fas fa-clock me-1 text-warning"></i> الطلب مرسل وبانتظار إجراء الفحص وكتابة التقرير في قسم الأشعة والسونار
                                                </span>
                                                <a href="{{ route('radiology-staff.show', $medRequest->id) }}" class="btn btn-xs btn-outline-info py-1 px-2.5 shadow-xs" style="font-size: 0.75rem;">
                                                    <i class="fas fa-x-ray me-1"></i> فتح محطة التقرير الشعاعي ↗
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                @if($legacyRadOrders->isEmpty())
                                    <div class="card border-0 bg-light p-4 text-center rounded-3">
                                        <i class="fas fa-x-ray fa-3x mb-2 text-muted opacity-25"></i>
                                        <p class="text-muted mb-0">لا توجد طلبات أشعة أو سونار مسجلة بعد لهذا الطفل الخديج.</p>
                                    </div>
                                @endif
                            @endforelse

                            <!-- عرض السجلات القديمة السابقة إن وجدت لحفظ الأرشيف -->
                            @if($legacyRadOrders->isNotEmpty())
                                <div class="mt-3 pt-3 border-top">
                                    <h6 class="text-muted small fw-bold mb-2">
                                        <i class="fas fa-history me-1"></i> سجلات الأشعة الفردية السابقة (الأرشيف):
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle border text-center small mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-start">اسم الفحص الشعاعي</th>
                                                    <th>الأولوية</th>
                                                    <th>التقرير</th>
                                                    <th>الحالة</th>
                                                    <th>إجراء</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($legacyRadOrders as $ord)
                                                    <tr>
                                                        <td class="text-start fw-semibold text-info">{{ $ord->item_name }}</td>
                                                        <td><span class="badge bg-secondary">{{ $ord->priority }}</span></td>
                                                        <td>{{ Str::limit($ord->result, 40) ?? 'بانتظار التقرير' }}</td>
                                                        <td><span class="badge bg-{{ $ord->status == 'completed' ? 'success' : 'secondary' }}">{{ $ord->status == 'completed' ? 'تم الإنجاز' : 'قيد الانتظار' }}</span></td>
                                                        <td>
                                                            @if($ord->result)
                                                                <a href="{{ route('radiology-staff.nicu-orders.print', $ord) }}" target="_blank" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size: 11px;">
                                                                    <i class="fas fa-print me-1"></i> طباعة
                                                                </a>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- تبويب 6: الوصفة والعلاج الدوائي (E-Prescriptions & Clinical RX) -->
                <div class="tab-pane fade" id="prescriptionsPane">
                    <div class="row g-4">
                        <!-- عمود إنشاء وصياغة الوصفة الطبية -->
                        <div class="col-lg-5">
                            <div class="card border-0 bg-light p-3 rounded-3 shadow-xs">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-success text-white p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                            <i class="fas fa-prescription"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-success mb-0">الوصفة الطبية والعلاج</h6>
                                            <small class="text-muted" style="font-size: 11px;">تحديد الجرعات، التكرار، والمسار للخديج</small>
                                        </div>
                                    </div>
                                    <a href="{{ route('nicu.print.prescription', $reservation) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-print me-1"></i> طباعة RX
                                    </a>
                                </div>

                                <form action="{{ route('nicu.prescriptions.store', $reservation) }}" method="POST" id="nicuPrescriptionForm">
                                    @csrf

                                    <!-- أدوية الوصفة الحالية ديناميكية -->
                                    <div id="prescriptionRowsContainer">
                                        <div class="card border bg-white p-2 mb-2 rounded-3 prescription-row" data-row="0">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <strong class="text-success small"><i class="fas fa-pills me-1"></i> الدواء #<span class="row-number">1</span></strong>
                                            </div>

                                            <div class="mb-2 position-relative">
                                                <label class="form-label small fw-bold text-dark mb-1">اسم الدواء:</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white"><i class="fas fa-capsules text-success"></i></span>
                                                    <input type="text" name="medications[0][name]" class="form-control med-name-input" placeholder="اكتب اسم الدواء التجاري أو العلمي..." autocomplete="off" required>
                                                </div>
                                                <div class="list-group position-absolute w-100 shadow-lg mt-1 med-autocomplete-menu" style="max-height: 180px; overflow-y: auto; display: none; z-index: 1060;"></div>
                                            </div>

                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold mb-1">الجرعة (Dose):</label>
                                                    <input type="text" name="medications[0][dose]" class="form-control form-control-sm" placeholder="مثال: 50 mg أو 0.5 ml" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold mb-1">مسار الإعطاء:</label>
                                                    <select name="medications[0][route]" class="form-select form-select-sm">
                                                        <option value="وريدي IV">وريدي (IV)</option>
                                                        <option value="تسريب وريدي مستمر Infusion">تسريب وريدي مستمر</option>
                                                        <option value="فموي Oral">فموي (Oral)</option>
                                                        <option value="عضلي IM">عضلي (IM)</option>
                                                        <option value="استنشاق Nebulizer">استنشاق / رذاذ (Nebulizer)</option>
                                                        <option value="قطرات Drops">قطرات (Drops)</option>
                                                        <option value="موضعي Topical">موضعي (Topical)</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold mb-1">التكرار (Frequency):</label>
                                                    <select name="medications[0][frequency]" class="form-select form-select-sm">
                                                        <option value="كل 8 ساعات (TID)">كل 8 ساعات (TID)</option>
                                                        <option value="كل 12 ساعة (BID)">كل 12 ساعة (BID)</option>
                                                        <option value="مرة يومياً (OD)">مرة يومياً (OD)</option>
                                                        <option value="كل 6 ساعات (QID)">كل 6 ساعات (QID)</option>
                                                        <option value="عند اللزوم (PRN)">عند اللزوم (PRN)</option>
                                                        <option value="جرعة وحيدة Stat">جرعة وحيدة (Single Dose)</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold mb-1">المدة (Duration):</label>
                                                    <input type="text" name="medications[0][duration]" class="form-control form-control-sm" placeholder="مثال: 5 أيام أو أسبوع">
                                                </div>
                                            </div>

                                            <div class="mb-1">
                                                <label class="form-label small mb-1">تعليمات وتوجيهات الطبيب:</label>
                                                <input type="text" name="medications[0][instructions]" class="form-control form-control-sm" placeholder="مثال: يُعطى ببطء عبر مضخة المحاليل، يُحفظ بعيداً عن الضوء...">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <button type="button" class="btn btn-outline-success btn-sm fw-bold" id="btnAddMedicationRow">
                                            <i class="fas fa-plus me-1"></i> إضافة دواء آخر للوصفة
                                        </button>
                                        <span class="text-muted small" id="prescriptionRowsCount">دواء واحد مضاف</span>
                                    </div>

                                    <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm">
                                        <i class="fas fa-save me-1"></i> اعتماد وحفظ الوصفة الطبية
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- جدول الأدوية الموصوفة للطفل -->
                        <div class="col-lg-7">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fas fa-pills text-success me-1"></i>
                                    سجل الأدوية الموصوفة للطفل (Active Prescriptions)
                                </h6>
                                <div class="d-flex gap-2">
                                    <span class="badge bg-light text-success border">
                                        الموصوف: {{ $reservation->medicalOrders->where('order_type', 'medication')->count() }}
                                    </span>
                                    <a href="{{ route('nicu.print.prescription', $reservation) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-print me-1"></i> طباعة الوصفة (RX)
                                    </a>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle border text-center small">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start">اسم الدواء والعلاج</th>
                                            <th class="text-start">تفاصيل الجرعة والمسار والتعليمات</th>
                                            <th>الحالة</th>
                                            <th>الطبيب الموصي</th>
                                            <th>وقت الوصف</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($reservation->medicalOrders->where('order_type', 'medication') as $ord)
                                            <tr>
                                                <td class="text-start fw-bold text-success">
                                                    <i class="fas fa-capsules text-muted me-1"></i>
                                                    {{ $ord->item_name }}
                                                </td>
                                                <td class="text-start small text-dark">
                                                    {{ $ord->instructions ?? '—' }}
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i> موصوف
                                                    </span>
                                                </td>
                                                <td class="text-muted small">
                                                    {{ optional($ord->orderedByUser)->name ?? 'طبيب الحاضنة' }}
                                                </td>
                                                <td class="text-muted small">
                                                    {{ $ord->ordered_at ? $ord->ordered_at->format('d/m H:i') : $ord->created_at->format('d/m H:i') }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="py-4 text-muted">
                                                    <i class="fas fa-pills fa-2x mb-2 d-block opacity-25"></i>
                                                    لا توجد وصفات أو أدوية مسجلة بعد لهذا الطفل.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تبويب 7: التوصيات وخطة الرعاية وجداول المواعيد (Care Plan & Schedules) -->
                <div class="tab-pane fade" id="careSchedulesPane">
                    <div class="row g-4">
                        <!-- نموذج إضافة التوصيات -->
                        <div class="col-lg-5">
                            <div class="card border-0 bg-light p-3 rounded-3 shadow-xs mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-warning text-dark p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                            <i class="fas fa-clipboard-list"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">إضافة توصية / جدول رعاية جديد</h6>
                                            <small class="text-muted" style="font-size: 11px;">جدولة مواعيد الرضاعة أو إعطاء العلاج الدوري</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-warning text-dark small">طبيب / تمريض</span>
                                </div>

                                <div class="alert alert-info py-2 px-3 small mb-3 border-0 bg-info bg-opacity-10 text-info-emphasis" style="font-size: 11px;">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    <strong>إجراء سريري داخلي للحاضنة:</strong> هذا النظام خاص بمواعيد رعاية الطفل والتنبيهات الحية فقط، ولا ينشئ أي التزامات على الصيدلية أو المخزن.
                                </div>

                                <!-- أزرار الاختيار السريع للنوع -->
                                <ul class="nav nav-pills nav-fill mb-3" id="scheduleTypePill" role="tablist">
                                    <li class="nav-item">
                                        <button class="nav-link active btn-sm fw-bold py-1" id="pill-feeding-tab" data-bs-toggle="pill" data-bs-target="#pill-feeding" type="button">
                                            <i class="fas fa-baby-bottle me-1"></i> جدول رضاعة
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link btn-sm fw-bold py-1" id="pill-medication-tab" data-bs-toggle="pill" data-bs-target="#pill-medication" type="button">
                                            <i class="fas fa-pills me-1"></i> جدول علاج دوائي
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content" id="scheduleTypeContent">
                                    <!-- نموذج الرضاعة -->
                                    <div class="tab-pane fade show active" id="pill-feeding">
                                        <form action="{{ route('nicu.care-schedules.store', $reservation) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="type" value="feeding">

                                            <div class="mb-2">
                                                <label class="form-label small fw-bold">نوع الرضاعة / التغذية:</label>
                                                <input type="text" name="title" class="form-control form-control-sm" placeholder="مثلاً: حليب أم EBM، حليب سيميلاك نيوشور..." list="quickFeedingTypes" required>
                                                <datalist id="quickFeedingTypes">
                                                    <option value="حليب أم طبيعي (EBM)">
                                                    <option value="حليب خدج صناعي (Pre-term Formula)">
                                                    <option value="حليب سيميلاك نيوشور (Neosure)">
                                                    <option value="حليب ببتي جونيور قليل التحسس">
                                                    <option value="تغذية وريدية كاملة (TPN)">
                                                </datalist>
                                            </div>

                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold">كمية الوجبة (مل):</label>
                                                    <input type="text" name="dose_or_amount" class="form-control form-control-sm" placeholder="مثال: 20 مل أو 35 CC" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold">طريقة الإعطاء (Route):</label>
                                                    <select name="route" class="form-select form-select-sm">
                                                        <option value="رضاعة بالزجاجة / المصاصة (Oral / Bottle)">رضاعة بالزجاجة (Oral)</option>
                                                        <option value="أنبوب أنفي معدي (NG Tube)">أنبوب أنفي معدي (NG Tube)</option>
                                                        <option value="أنبوب فموي معدي (OG Tube)">أنبوب فموي معدي (OG Tube)</option>
                                                        <option value="مضخة تغذية مستمرة (Infusion)">مضخة تغذية مستمرة</option>
                                                        <option value="وريدي (IV / TPN)">وريدي (IV / TPN)</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold text-danger">التكرار (كل كم ساعة؟):</label>
                                                    <select name="interval_hours" class="form-select form-select-sm fw-bold border-danger-subtle" required>
                                                        <option value="2">كل ساعتين (q2h - 12 وجبة/يوم)</option>
                                                        <option value="3" selected>كل 3 ساعات (q3h - 8 وجبات/يوم)</option>
                                                        <option value="4">كل 4 ساعات (q4h - 6 وجبات/يوم)</option>
                                                        <option value="6">كل 6 ساعات (q6h - 4 وجبات/يوم)</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold">وقت أول وجبة:</label>
                                                    <input type="datetime-local" name="start_time" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small">تعليمات سريرية وملاحظات الطبيب:</label>
                                                <textarea name="instructions" rows="2" class="form-control form-control-sm" placeholder="مثال: فحص بقايا المعدة قبل الوجبة، رفع الرأس 30 درجة، تجشؤ بعد الرضعة..."></textarea>
                                            </div>

                                            <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold shadow-sm">
                                                <i class="fas fa-save me-1"></i> اعتماد جدول الرضاعة والتنبيهات
                                            </button>
                                        </form>
                                    </div>

                                    <!-- نموذج العلاج الدوائي -->
                                    <div class="tab-pane fade" id="pill-medication">
                                        <form action="{{ route('nicu.care-schedules.store', $reservation) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="type" value="medication">

                                            <div class="mb-2">
                                                <label class="form-label small fw-bold">اسم الدواء:</label>
                                                <input type="text" name="title" class="form-control form-control-sm" placeholder="اكتب اسم الدواء أو اختر من القائمة..." list="quickMedScheduleList" required>
                                                <datalist id="quickMedScheduleList">
                                                    @foreach($reservation->medicalOrders->where('order_type', 'medication') as $pMed)
                                                        <option value="{{ $pMed->item_name }}">من وصفة الطفل</option>
                                                    @endforeach
                                                    @foreach($medicines->take(200) as $med)
                                                        <option value="{{ $med->name }}">{{ $med->strength }}</option>
                                                    @endforeach
                                                </datalist>
                                            </div>

                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold">الجرعة المحددة:</label>
                                                    <input type="text" name="dose_or_amount" class="form-control form-control-sm" placeholder="مثال: 50 mg أو 0.5 ml" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold">مسار الإعطاء (Route):</label>
                                                    <select name="route" class="form-select form-select-sm">
                                                        <option value="وريدي (IV)">حقن وريدي (IV)</option>
                                                        <option value="تسريب وريدي مستمر (IV Infusion)">تسريب وريدي مستمر</option>
                                                        <option value="عضلي (IM)">حقن عضلي (IM)</option>
                                                        <option value="فموي (PO / Oral)">فموي (Oral / NG)</option>
                                                        <option value="استنشاق (Nebulizer)">استنشاق (Nebulizer)</option>
                                                        <option value="قطرات (Drops)">قطرات (Drops)</option>
                                                        <option value="تحت الجلد (SC)">تحت الجلد (SC)</option>
                                                        <option value="موضعي (Topical)">موضعي (Topical)</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold text-danger">التكرار (كل كم ساعة؟):</label>
                                                    <select name="interval_hours" class="form-select form-select-sm fw-bold border-danger-subtle" required>
                                                        <option value="4">كل 4 ساعات (q4h - 6 مرات/يوم)</option>
                                                        <option value="6">كل 6 ساعات (q6h - 4 مرات/يوم)</option>
                                                        <option value="8" selected>كل 8 ساعات (q8h - 3 مرات/يوم)</option>
                                                        <option value="12">كل 12 ساعة (q12h - مرتان/يوم)</option>
                                                        <option value="24">كل 24 ساعة (q24h - مرة يومياً)</option>
                                                        <option value="48">كل 48 ساعة (q48h - كل يومين)</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold">وقت أول جرعة:</label>
                                                    <input type="datetime-local" name="start_time" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small">تعليمات سريرية وملاحظات الطبيب:</label>
                                                <textarea name="instructions" rows="2" class="form-control form-control-sm" placeholder="مثال: إعطاء المحلول ببطء خلال 30 دقيقة، مراقبة الكانيولا..."></textarea>
                                            </div>

                                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm">
                                                <i class="fas fa-save me-1"></i> اعتماد جدول الدواء والتنبيهات
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- جدول وقائمة التوصيات وجداول الرعاية الحالية -->
                        <div class="col-lg-7">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fas fa-tasks text-primary me-1"></i>
                                    الجداول والتوصيات المعتمدة للطفل
                                </h6>
                                <span class="badge bg-light text-dark border">
                                    إجمالي التوصيات: {{ $reservation->careSchedules->count() }}
                                </span>
                            </div>

                            @forelse($reservation->careSchedules as $sched)
                                @php
                                    $isFeeding = $sched->type === 'feeding';
                                    $isDue = $sched->is_due;
                                    $isOverdue = $sched->is_overdue;
                                @endphp
                                <div class="card mb-3 border-0 shadow-xs {{ !$sched->is_active ? 'opacity-50 bg-light' : ($isOverdue ? 'border-start border-danger border-4' : ($isDue ? 'border-start border-warning border-4' : 'border-start border-success border-4')) }}">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="{{ $isFeeding ? 'bg-warning-subtle text-warning' : 'bg-primary-subtle text-primary' }} p-2 rounded-circle" style="width: 38px; height: 38px; text-align: center;">
                                                    <i class="{{ $sched->icon }}"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark fs-6">{{ $sched->title }}</div>
                                                    <small class="text-muted">
                                                        <span class="badge {{ $isFeeding ? 'bg-warning-subtle text-dark border border-warning-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle' }} me-1">
                                                            {{ $sched->type_arabic }}
                                                        </span>
                                                        @if($sched->dose_or_amount)
                                                            <strong class="text-dark">{{ $sched->dose_or_amount }}</strong> •
                                                        @endif
                                                        <span>{{ $sched->route ?: 'مسار موصوف' }}</span>
                                                    </small>
                                                </div>
                                            </div>

                                            <div class="text-end">
                                                @if(!$sched->is_active)
                                                    <span class="badge bg-secondary">متوقف / ملغى</span>
                                                @elseif($isOverdue)
                                                    <span class="badge bg-danger py-1 px-2">
                                                        <i class="fas fa-exclamation-circle me-1"></i> متأخر ({{ $sched->due_human }})
                                                    </span>
                                                @elseif($isDue)
                                                    <span class="badge bg-warning text-dark py-1 px-2">
                                                        <i class="fas fa-bell me-1"></i> مستحق الآن
                                                    </span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2">
                                                        <i class="fas fa-clock me-1"></i> القادم: {{ $sched->due_human }}
                                                    </span>
                                                @endif
                                                <div class="small text-muted font-monospace mt-1">كل {{ $sched->interval_hours }} ساعة</div>
                                            </div>
                                        </div>

                                        @if($sched->instructions)
                                            <div class="bg-light p-2 rounded mb-2 small text-dark border">
                                                <i class="fas fa-stethoscope text-primary me-1"></i>
                                                <strong>ملاحظات:</strong> {{ $sched->instructions }}
                                            </div>
                                        @endif

                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
                                            <small class="text-muted" style="font-size: 11px;">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                الموعد القادم: <strong>{{ $sched->next_due_at ? $sched->next_due_at->format('Y-m-d H:i') : '—' }}</strong>
                                                @if($sched->last_administered_at)
                                                    • آخر إعطاء: {{ $sched->last_administered_at->format('H:i') }}
                                                @endif
                                            </small>

                                            <div class="d-flex gap-1 align-items-center">
                                                @if($sched->is_active)
                                                    <form action="{{ route('nicu.care-schedules.administer-quick', $sched) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success fw-bold px-2 py-1 shadow-xs" title="توثيق الإعطاء الآن وتحديث الموعد القادم تلقائياً">
                                                            <i class="fas fa-check-circle me-1"></i> توثيق الإعطاء الآن
                                                        </button>
                                                    </form>
                                                @endif

                                                <form action="{{ route('nicu.care-schedules.toggle', $sched) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary px-2 py-1" title="{{ $sched->is_active ? 'إيقاف التوصية' : 'إعادة تفعيل التوصية' }}">
                                                        <i class="fas {{ $sched->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                                    </button>
                                                </form>

                                                <form action="{{ route('nicu.care-schedules.destroy', $sched) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه التوصية؟');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" title="حذف التوصية">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="card border-0 bg-light p-4 text-center text-muted rounded-3">
                                    <i class="fas fa-clipboard-list fa-3x mb-2 text-secondary opacity-50"></i>
                                    <h6>لا توجد خطة رعاية أو توصيات مجدولة للطفل حتى الآن</h6>
                                    <p class="small mb-0">يمكن للطبيب أو التمريض إضافة جدول الرضاعة أو جداول إعطاء العلاجات بالأوقات المحددة من النموذج المقابل.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal أمر الخروج الطبي وتصفية الحساب -->
<div class="modal fade" id="dischargeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-sign-out-alt me-2"></i> أمر الخروج الطبي للطفل</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('nicu.discharge', $reservation) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-warning small mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        عند تأكيد الخروج، سيتم حساب التكلفة الإجمالية التراكمية، وتتحول الحاضنة تلقائياً إلى <strong>«قيد التعقيم والتطهير 🟡»</strong> لحين تأكيد تعقيمها.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">نوع ومسار الخروج:</label>
                        <select name="discharge_type" class="form-select" required>
                            <option value="improved">خروج تحسن واستقرار (عادي لحضن الأم)</option>
                            <option value="step_down">تنزيل إلى ردهة الأطفال العادية (رقود عادي)</option>
                            <option value="transferred_out">تحويل إلى مستشفى خارجي تخصصي</option>
                            <option value="dama">خروج على مسؤولية الأهل (DAMA)</option>
                            <option value="expired">وفاة المولود (Expired)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">تقرير وتوصيات الخروج الطبي:</label>
                        <textarea name="discharge_notes" rows="3" class="form-control" placeholder="حالة الطفل عند الخروج، العلاج المنزلي، موعد المراجعة..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger fw-bold">تأكيد الخروج وتوليد الفاتورة</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const internalDoctors = [
    @foreach($doctors as $doc)
    {
        id: {{ $doc->id }},
        name: @json(optional($doc->user)->name ?? 'طبيب'),
        specialty: @json($doc->specialization ?? 'أخصائي')
    },
    @endforeach
];

function toggleDoctorField(val) {
    const internalDiv = document.getElementById('internalDoctorDiv');
    const externalDiv = document.getElementById('externalDoctorDiv');
    const specialtyDiv = document.getElementById('specialtyDiv');
    const specialtyInput = document.getElementById('specialtyInput');
    const externalDoctorNameInput = document.getElementById('externalDoctorNameInput');

    if(val === 'internal') {
        if (internalDiv) internalDiv.classList.remove('d-none');
        if (externalDiv) externalDiv.classList.add('d-none');
        if (specialtyDiv) specialtyDiv.classList.add('d-none');
        if (specialtyInput) {
            specialtyInput.removeAttribute('required');
        }
        if (externalDoctorNameInput) {
            externalDoctorNameInput.removeAttribute('required');
        }
    } else {
        if (internalDiv) internalDiv.classList.add('d-none');
        if (externalDiv) externalDiv.classList.remove('d-none');
        if (specialtyDiv) specialtyDiv.classList.remove('d-none');
        if (specialtyInput) {
            specialtyInput.setAttribute('required', 'required');
        }
        if (externalDoctorNameInput) {
            externalDoctorNameInput.setAttribute('required', 'required');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('internalDoctorSearchInput');
    const resultsDiv = document.getElementById('doctorSearchResults');
    const toggleBtn = document.getElementById('toggleAllDoctorsBtn');
    const selectedDoctorId = document.getElementById('selectedDoctorId');
    const selectedDoctorCard = document.getElementById('selectedDoctorCard');
    const searchWrapper = document.getElementById('doctorSearchWrapper');
    const selectedDoctorName = document.getElementById('selectedDoctorName');
    const selectedDoctorSpecialty = document.getElementById('selectedDoctorSpecialty');
    const clearBtn = document.getElementById('clearSelectedDoctorBtn');
    const consultationTypeSelect = document.getElementById('consultationTypeSelect');

    // تفعيل الحالة الأولية لنوع الاستشارة (إخفاء التخصص الدقيق في الداخلي)
    if (consultationTypeSelect) {
        toggleDoctorField(consultationTypeSelect.value);
    }

    function renderResults(list) {
        if (!resultsDiv) return;
        resultsDiv.innerHTML = '';
        if (!list || list.length === 0) {
            resultsDiv.innerHTML = '<div class="list-group-item text-muted text-center py-2 small"><i class="fas fa-search me-1"></i> لا توجد نتائج مطابقة</div>';
            resultsDiv.style.display = 'block';
            return;
        }

        list.forEach(doc => {
            const item = document.createElement('a');
            item.href = 'javascript:void(0);';
            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2';
            item.innerHTML = `
                <div>
                    <strong class="text-dark small d-block">${doc.name}</strong>
                    <span class="text-muted" style="font-size: 11px;"><i class="fas fa-stethoscope me-1 text-info"></i>${doc.specialty}</span>
                </div>
                <span class="badge bg-light text-primary border" style="font-size: 10px;">اختيار</span>
            `;
            item.addEventListener('click', function(e) {
                e.preventDefault();
                chooseDoctor(doc);
            });
            resultsDiv.appendChild(item);
        });
        resultsDiv.style.display = 'block';
    }

    function chooseDoctor(doc) {
        if (!doc) return;
        selectedDoctorId.value = doc.id;
        selectedDoctorName.textContent = doc.name;
        selectedDoctorSpecialty.textContent = doc.specialty;
        selectedDoctorCard.style.display = 'block';
        searchWrapper.style.display = 'none';
        resultsDiv.style.display = 'none';
    }

    function clearDoctor() {
        selectedDoctorId.value = '';
        selectedDoctorCard.style.display = 'none';
        searchWrapper.style.display = 'block';
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }
        resultsDiv.style.display = 'none';
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', clearDoctor);
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            if (query.length === 0) {
                resultsDiv.style.display = 'none';
                return;
            }
            const filtered = internalDoctors.filter(d => 
                (d.name && d.name.toLowerCase().includes(query)) || 
                (d.specialty && d.specialty.toLowerCase().includes(query))
            );
            renderResults(filtered);
        });

        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length > 0) {
                const query = this.value.trim().toLowerCase();
                const filtered = internalDoctors.filter(d => 
                    (d.name && d.name.toLowerCase().includes(query)) || 
                    (d.specialty && d.specialty.toLowerCase().includes(query))
                );
                renderResults(filtered);
            }
        });
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (resultsDiv.style.display === 'block') {
                resultsDiv.style.display = 'none';
            } else {
                renderResults(internalDoctors);
            }
        });
    }

    // إخفاء القائمة عند النقر خارجها
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#doctorSearchWrapper')) {
            if (resultsDiv) resultsDiv.style.display = 'none';
        }
    });

    // استرجاع الطبيب المختار مسبقاً إن وجد (مثل old('doctor_id'))
    if (selectedDoctorId && selectedDoctorId.value) {
        const found = internalDoctors.find(d => String(d.id) === String(selectedDoctorId.value));
        if (found) {
            chooseDoctor(found);
        }
    }

    // التحقق عند إرسال النموذج في حالة الاستشارة الداخلية
    const consultationForm = selectedDoctorId ? selectedDoctorId.closest('form') : null;
    if (consultationForm) {
        consultationForm.addEventListener('submit', function(e) {
            if (consultationTypeSelect && consultationTypeSelect.value === 'internal') {
                if (!selectedDoctorId.value) {
                    e.preventDefault();
                    alert('يرجى اختيار طبيب المستشفى الداخلي أولاً عبر البحث وكتابة الاسم.');
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.classList.add('is-invalid');
                    }
                }
            }
        });
    }

    // ==================== 1. مختبر الخدج (Lab Workstation) ====================
    const nicuLabTests = [
        @foreach($labTests as $t)
        { id: {{ $t->id }}, name: @json($t->name), code: @json($t->code ?? ''), price: {{ (float)($t->price ?? 0) }} },
        @endforeach
    ];

    const selectedLabTests = new Set();
    const labInput = document.getElementById('nicuLabSearchInput');
    const labMenu = document.getElementById('nicuLabAutocompleteMenu');
    const labTray = document.getElementById('nicuLabSelectedTray');
    const labChips = document.getElementById('nicuLabChipsContainer');
    const labCountSpan = document.getElementById('nicuLabSelectedCount');
    const labClearAllBtn = document.getElementById('nicuLabClearAllBtn');
    const labClearInputBtn = document.getElementById('nicuLabClearInputBtn');

    function updateLabChipsUI() {
        if (!labTray || !labCountSpan) return;
        labCountSpan.textContent = selectedLabTests.size;
        if (selectedLabTests.size > 0) {
            labTray.classList.remove('d-none');
        } else {
            labTray.classList.add('d-none');
        }
    }

    function addLabTest(testName, price = 0) {
        const cleanName = (testName || '').trim();
        if (!cleanName || selectedLabTests.has(cleanName)) return;
        selectedLabTests.add(cleanName);

        const chip = document.createElement('span');
        chip.className = 'badge bg-light text-primary border p-2 d-inline-flex align-items-center gap-1 rounded-pill shadow-xs';
        chip.setAttribute('data-test-name', cleanName);
        chip.innerHTML = `
            <i class="fas fa-vial me-1"></i>
            <span>${cleanName}</span>
            <input type="hidden" name="tests[]" value="${cleanName}">
            <button type="button" class="btn-close ms-1" style="font-size: 8px;" title="إزالة"></button>
        `;
        chip.querySelector('.btn-close').addEventListener('click', function() {
            removeLabTest(cleanName);
        });
        if (labChips) labChips.appendChild(chip);
        updateLabChipsUI();
    }

    function removeLabTest(testName) {
        selectedLabTests.delete(testName);
        if (labChips) {
            const chip = labChips.querySelector(`[data-test-name="${CSS.escape(testName)}"]`);
            if (chip) chip.remove();
        }
        updateLabChipsUI();
    }

    function clearAllLabTests() {
        selectedLabTests.clear();
        if (labChips) labChips.innerHTML = '';
        updateLabChipsUI();
    }

    if (labClearAllBtn) {
        labClearAllBtn.addEventListener('click', clearAllLabTests);
    }

    if (labClearInputBtn) {
        labClearInputBtn.addEventListener('click', function() {
            if (labInput) {
                labInput.value = '';
                labInput.focus();
            }
            if (labMenu) labMenu.style.display = 'none';
        });
    }

    // أزرار باقات الخدج السريعة
    document.querySelectorAll('.nicu-quick-lab-pkg').forEach(btn => {
        btn.addEventListener('click', function() {
            try {
                const tests = JSON.parse(this.getAttribute('data-tests') || '[]');
                tests.forEach(t => addLabTest(t));
            } catch(e) {
                console.error('Error parsing tests:', e);
            }
        });
    });

    if (labInput) {
        labInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            if (!query) {
                if (labMenu) labMenu.style.display = 'none';
                return;
            }
            const matches = nicuLabTests.filter(t => 
                (t.name && t.name.toLowerCase().includes(query)) || 
                (t.code && t.code.toLowerCase().includes(query))
            ).slice(0, 15);

            if (!labMenu) return;
            labMenu.innerHTML = '';
            if (matches.length === 0) {
                labMenu.innerHTML = `<div class="list-group-item text-muted text-center py-2 small">
                    <i class="fas fa-info-circle me-1"></i> لا يوجد تحليل مطابق، اضغط Enter لإضافة "${query}" كفحص مخصص
                </div>`;
            } else {
                matches.forEach(m => {
                    const item = document.createElement('a');
                    item.href = 'javascript:void(0);';
                    item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2';
                    item.innerHTML = `
                        <div>
                            <strong class="text-dark small d-block">${m.name}</strong>
                            ${m.code ? `<span class="badge bg-secondary-subtle text-secondary me-1" style="font-size: 10px;">${m.code}</span>` : ''}
                        </div>
                        <span class="badge bg-primary-subtle text-primary" style="font-size: 10px;">${Number(m.price).toLocaleString()} د.ع</span>
                    `;
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        addLabTest(m.name, m.price);
                        labInput.value = '';
                        labMenu.style.display = 'none';
                        labInput.focus();
                    });
                    labMenu.appendChild(item);
                });
            }
            labMenu.style.display = 'block';
        });

        labInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = this.value.trim();
                if (val) {
                    const firstMatch = nicuLabTests.find(t => 
                        (t.name && t.name.toLowerCase().includes(val.toLowerCase())) || 
                        (t.code && t.code.toLowerCase() === val.toLowerCase())
                    );
                    if (firstMatch) {
                        addLabTest(firstMatch.name, firstMatch.price);
                    } else {
                        addLabTest(val);
                    }
                    this.value = '';
                    if (labMenu) labMenu.style.display = 'none';
                }
            }
        });
    }

    // ==================== 2. أشعة وسونار الخدج (Radiology Workstation) ====================
    const nicuRadiologyTypes = [
        @foreach($radiologyTypes as $r)
        { id: {{ $r->id }}, name: @json($r->name), price: {{ (float)($r->price ?? 0) }} },
        @endforeach
    ];

    const selectedRadExams = new Set();
    const radInput = document.getElementById('nicuRadSearchInput');
    const radMenu = document.getElementById('nicuRadAutocompleteMenu');
    const radTray = document.getElementById('nicuRadSelectedTray');
    const radChips = document.getElementById('nicuRadChipsContainer');
    const radCountSpan = document.getElementById('nicuRadSelectedCount');
    const radClearAllBtn = document.getElementById('nicuRadClearAllBtn');
    const radClearInputBtn = document.getElementById('nicuRadClearInputBtn');

    function updateRadChipsUI() {
        if (!radTray || !radCountSpan) return;
        radCountSpan.textContent = selectedRadExams.size;
        if (selectedRadExams.size > 0) {
            radTray.classList.remove('d-none');
        } else {
            radTray.classList.add('d-none');
        }
    }

    function addRadExam(examName, price = 0) {
        const cleanName = (examName || '').trim();
        if (!cleanName || selectedRadExams.has(cleanName)) return;
        selectedRadExams.add(cleanName);

        const chip = document.createElement('span');
        chip.className = 'badge bg-light text-info border p-2 d-inline-flex align-items-center gap-1 rounded-pill shadow-xs';
        chip.setAttribute('data-exam-name', cleanName);
        chip.innerHTML = `
            <i class="fas fa-x-ray me-1"></i>
            <span>${cleanName}</span>
            <input type="hidden" name="radiology_types[]" value="${cleanName}">
            <button type="button" class="btn-close ms-1" style="font-size: 8px;" title="إزالة"></button>
        `;
        chip.querySelector('.btn-close').addEventListener('click', function() {
            removeRadExam(cleanName);
        });
        if (radChips) radChips.appendChild(chip);
        updateRadChipsUI();
    }

    function removeRadExam(examName) {
        selectedRadExams.delete(examName);
        if (radChips) {
            const chip = radChips.querySelector(`[data-exam-name="${CSS.escape(examName)}"]`);
            if (chip) chip.remove();
        }
        updateRadChipsUI();
    }

    function clearAllRadExams() {
        selectedRadExams.clear();
        if (radChips) radChips.innerHTML = '';
        updateRadChipsUI();
    }

    if (radClearAllBtn) {
        radClearAllBtn.addEventListener('click', clearAllRadExams);
    }

    if (radClearInputBtn) {
        radClearInputBtn.addEventListener('click', function() {
            if (radInput) {
                radInput.value = '';
                radInput.focus();
            }
            if (radMenu) radMenu.style.display = 'none';
        });
    }

    // أزرار تصوير الخدج الشائعة
    document.querySelectorAll('.nicu-quick-rad-pkg').forEach(btn => {
        btn.addEventListener('click', function() {
            try {
                const types = JSON.parse(this.getAttribute('data-types') || '[]');
                types.forEach(t => addRadExam(t));
            } catch(e) {
                console.error('Error parsing rad types:', e);
            }
        });
    });

    if (radInput) {
        radInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            if (!query) {
                if (radMenu) radMenu.style.display = 'none';
                return;
            }
            const matches = nicuRadiologyTypes.filter(r => 
                r.name && r.name.toLowerCase().includes(query)
            ).slice(0, 15);

            if (!radMenu) return;
            radMenu.innerHTML = '';
            if (matches.length === 0) {
                radMenu.innerHTML = `<div class="list-group-item text-muted text-center py-2 small">
                    <i class="fas fa-info-circle me-1"></i> اضغط Enter لإضافة "${query}" كفحص شعاعي مخصص
                </div>`;
            } else {
                matches.forEach(m => {
                    const item = document.createElement('a');
                    item.href = 'javascript:void(0);';
                    item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2';
                    item.innerHTML = `
                        <strong class="text-dark small">${m.name}</strong>
                        <span class="badge bg-info-subtle text-info" style="font-size: 10px;">${Number(m.price).toLocaleString()} د.ع</span>
                    `;
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        addRadExam(m.name, m.price);
                        radInput.value = '';
                        radMenu.style.display = 'none';
                        radInput.focus();
                    });
                    radMenu.appendChild(item);
                });
            }
            radMenu.style.display = 'block';
        });

        radInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = this.value.trim();
                if (val) {
                    const firstMatch = nicuRadiologyTypes.find(r => 
                        r.name && r.name.toLowerCase().includes(val.toLowerCase())
                    );
                    if (firstMatch) {
                        addRadExam(firstMatch.name, firstMatch.price);
                    } else {
                        addRadExam(val);
                    }
                    this.value = '';
                    if (radMenu) radMenu.style.display = 'none';
                }
            }
        });
    }

    // ==================== 3. وصفة أدوية الخدج (Prescription Workstation) ====================
    const nicuMedicines = [
        @foreach($medicines as $m)
        { id: {{ $m->id }}, name: @json($m->name), form: @json($m->dosage_form ?? ''), strength: @json($m->strength ?? '') },
        @endforeach
    ];

    let prescriptionRowIndex = 1;
    const btnAddMedRow = document.getElementById('btnAddMedicationRow');
    const prescriptionContainer = document.getElementById('prescriptionRowsContainer');
    const prescriptionCountSpan = document.getElementById('prescriptionRowsCount');

    function updatePrescriptionCount() {
        if (!prescriptionContainer || !prescriptionCountSpan) return;
        const rows = prescriptionContainer.querySelectorAll('.prescription-row');
        rows.forEach((row, idx) => {
            const numSpan = row.querySelector('.row-number');
            if (numSpan) numSpan.textContent = idx + 1;
        });
        prescriptionCountSpan.textContent = `${rows.length} أدوية مضافة`;
    }

    function attachMedAutocomplete(row) {
        const input = row.querySelector('.med-name-input');
        const menu = row.querySelector('.med-autocomplete-menu');
        if (!input || !menu) return;

        input.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            if (!query) {
                menu.style.display = 'none';
                return;
            }
            const matches = nicuMedicines.filter(m => 
                m.name && m.name.toLowerCase().includes(query)
            ).slice(0, 15);

            menu.innerHTML = '';
            if (matches.length === 0) {
                menu.style.display = 'none';
                return;
            }
            matches.forEach(m => {
                const item = document.createElement('a');
                item.href = 'javascript:void(0);';
                item.className = 'list-group-item list-group-item-action py-2';
                item.innerHTML = `
                    <strong class="text-dark small d-block">${m.name}</strong>
                    <small class="text-muted" style="font-size: 10px;">${m.form} ${m.strength ? '• ' + m.strength : ''}</small>
                `;
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    input.value = m.name;
                    menu.style.display = 'none';
                });
                menu.appendChild(item);
            });
            menu.style.display = 'block';
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                menu.style.display = 'none';
            }
        });
    }

    if (prescriptionContainer) {
        prescriptionContainer.querySelectorAll('.prescription-row').forEach(row => {
            attachMedAutocomplete(row);
        });
    }

    if (btnAddMedRow && prescriptionContainer) {
        btnAddMedRow.addEventListener('click', function() {
            const newRow = document.createElement('div');
            newRow.className = 'card border bg-white p-2 mb-2 rounded-3 prescription-row';
            newRow.setAttribute('data-row', prescriptionRowIndex);
            newRow.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong class="text-success small"><i class="fas fa-pills me-1"></i> الدواء #<span class="row-number">${prescriptionRowIndex + 1}</span></strong>
                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 border-0 btn-remove-med-row" title="حذف الدواء"><i class="fas fa-times"></i></button>
                </div>

                <div class="mb-2 position-relative">
                    <label class="form-label small fw-bold text-dark mb-1">اسم الدواء:</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-capsules text-success"></i></span>
                        <input type="text" name="medications[${prescriptionRowIndex}][name]" class="form-control med-name-input" placeholder="اكتب اسم الدواء التجاري أو العلمي..." autocomplete="off" required>
                    </div>
                    <div class="list-group position-absolute w-100 shadow-lg mt-1 med-autocomplete-menu" style="max-height: 180px; overflow-y: auto; display: none; z-index: 1060;"></div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small fw-bold mb-1">الجرعة (Dose):</label>
                        <input type="text" name="medications[${prescriptionRowIndex}][dose]" class="form-control form-control-sm" placeholder="مثال: 50 mg أو 0.5 ml" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold mb-1">مسار الإعطاء:</label>
                        <select name="medications[${prescriptionRowIndex}][route]" class="form-select form-select-sm">
                            <option value="وريدي IV">وريدي (IV)</option>
                            <option value="تسريب وريدي مستمر Infusion">تسريب وريدي مستمر</option>
                            <option value="فموي Oral">فموي (Oral)</option>
                            <option value="عضلي IM">عضلي (IM)</option>
                            <option value="استنشاق Nebulizer">استنشاق / رذاذ (Nebulizer)</option>
                            <option value="قطرات Drops">قطرات (Drops)</option>
                            <option value="موضعي Topical">موضعي (Topical)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold mb-1">التكرار (Frequency):</label>
                        <select name="medications[${prescriptionRowIndex}][frequency]" class="form-select form-select-sm">
                            <option value="كل 8 ساعات (TID)">كل 8 ساعات (TID)</option>
                            <option value="كل 12 ساعة (BID)">كل 12 ساعة (BID)</option>
                            <option value="مرة يومياً (OD)">مرة يومياً (OD)</option>
                            <option value="كل 6 ساعات (QID)">كل 6 ساعات (QID)</option>
                            <option value="عند اللزوم (PRN)">عند اللزوم (PRN)</option>
                            <option value="جرعة وحيدة Stat">جرعة وحيدة (Single Dose)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold mb-1">المدة (Duration):</label>
                        <input type="text" name="medications[${prescriptionRowIndex}][duration]" class="form-control form-control-sm" placeholder="مثال: 5 أيام أو أسبوع">
                    </div>
                </div>

                <div class="mb-1">
                    <label class="form-label small mb-1">تعليمات وتوجيهات الطبيب:</label>
                    <input type="text" name="medications[${prescriptionRowIndex}][instructions]" class="form-control form-control-sm" placeholder="مثال: يُعطى ببطء عبر مضخة المحاليل، يُحفظ بعيداً عن الضوء...">
                </div>
            `;

            newRow.querySelector('.btn-remove-med-row').addEventListener('click', function() {
                newRow.remove();
                updatePrescriptionCount();
            });

            prescriptionContainer.appendChild(newRow);
            attachMedAutocomplete(newRow);
            prescriptionRowIndex++;
            updatePrescriptionCount();
            newRow.querySelector('.med-name-input').focus();
        });
    }

    // إغلاق أي قائمة منسدلة عند النقر خارجها
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#nicuLabSearchInput') && !e.target.closest('#nicuLabAutocompleteMenu')) {
            if (labMenu) labMenu.style.display = 'none';
        }
        if (!e.target.closest('#nicuRadSearchInput') && !e.target.closest('#nicuRadAutocompleteMenu')) {
            if (radMenu) radMenu.style.display = 'none';
        }
        if (!e.target.closest('.med-name-input') && !e.target.closest('.med-autocomplete-menu')) {
            document.querySelectorAll('.med-autocomplete-menu').forEach(m => m.style.display = 'none');
        }
    });
});
</script>
@endpush
@endsection
