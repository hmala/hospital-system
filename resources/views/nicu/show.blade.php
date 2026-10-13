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
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="p-2 bg-white bg-opacity-10 rounded text-center" style="min-width: 100px;">
                        <span class="small opacity-75 d-block">عداد الساعات</span>
                        <h4 class="mb-0 fw-bold">{{ $stayDetails['total_hours'] }} س</h4>
                    </div>
                    <div class="p-2 bg-white bg-opacity-10 rounded text-center" style="min-width: 100px;">
                        <span class="small opacity-75 d-block">الأيام الفندقية</span>
                        <h4 class="mb-0 fw-bold">{{ $stayDetails['calculated_days'] }} يوم</h4>
                    </div>
                    <div class="p-2 bg-white bg-opacity-10 rounded text-center" style="min-width: 120px;">
                        <span class="small opacity-75 d-block">الحساب التراكمي</span>
                        <h4 class="mb-0 fw-bold">{{ number_format($stayDetails['grand_total']) }} د.ع</h4>
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
                        <i class="fas fa-heartbeat text-danger me-1"></i> محطة التمريض (العلامات والوزن والرضاعة)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark" id="consultations-tab" data-bs-toggle="tab" data-bs-target="#consultationsPane" type="button">
                        <i class="fas fa-user-md text-info me-1"></i> استشارات الأطباء (داخلي / خارجي)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark" id="orders-tab" data-bs-toggle="tab" data-bs-target="#ordersPane" type="button">
                        <i class="fas fa-vial text-warning me-1"></i> الطلبات السريرية (المختبر والأشعة والأدوية)
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

                                <!-- فورم تبديل الخدمة السريرية -->
                                @if($reservation->status === 'admitted')
                                    <div class="p-3 bg-white rounded shadow-sm border">
                                        <h6 class="fw-bold text-dark mb-2">
                                            <i class="fas fa-sync-alt text-warning me-1"></i>
                                            تبديل الباقة والخدمة السريرية
                                        </h6>
                                        <p class="small text-muted mb-3">
                                            يمكن للمقيم أو الأخصائي تعديل الخدمة (مثلاً: التحويل من Ventilator إلى CPAP أو أكسجين). يحسب اليوم التالي على السعر الجديد.
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
                                                <label class="form-label small fw-bold">سبب التحويل الطبي:</label>
                                                <input type="text" name="reason" class="form-control" placeholder="مثال: تحسن التنفس وفصل الأنبوب الرغامي" required>
                                            </div>
                                            <button type="submit" class="btn btn-warning w-100 fw-bold shadow-sm">
                                                <i class="fas fa-check me-1"></i> حفظ وتفعيل الباقة الجديدة
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
                                <table class="table table-hover align-middle border">
                                    <thead class="table-light">
                                        <tr>
                                            <th>الخدمة السريرية</th>
                                            <th>السعر اليومي</th>
                                            <th>تاريخ البدء</th>
                                            <th>تاريخ الانتهاء</th>
                                            <th>السبب</th>
                                            <th>المغير</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($reservation->serviceLogs as $log)
                                            <tr>
                                                <td class="fw-bold text-primary">{{ $log->nicuService?->name }}</td>
                                                <td class="text-success fw-bold">{{ number_format($log->daily_fee) }} د.ع</td>
                                                <td>{{ $log->start_time?->format('Y-m-d H:i') }}</td>
                                                <td>
                                                    @if($log->end_time)
                                                        {{ $log->end_time->format('Y-m-d H:i') }}
                                                    @else
                                                        <span class="badge bg-success">نشطة حالياً</span>
                                                    @endif
                                                </td>
                                                <td class="small">{{ $log->reason_for_switch ?? 'دخول أولي' }}</td>
                                                <td class="small text-muted">{{ optional($log->switchedByUser)->name ?? 'النظام' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">
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

                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small">نوع الرضاعة:</label>
                                            <select name="feeding_type" class="form-select form-select-sm">
                                                <option value="حليب أم (EBM)">حليب أم (EBM)</option>
                                                <option value="حليب خدج صناعي">حليب خدج صناعي</option>
                                                <option value="تغذية وريدية TPN">تغذية وريدية TPN</option>
                                                <option value="صيام NPO">صيام NPO</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">كمية الرضعة (مل/CC):</label>
                                            <input type="number" step="0.5" name="feeding_amount_ml" class="form-control form-control-sm" placeholder="15">
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
                                        <label class="form-label small fw-bold">اختر طبيب المستشفى الداخلي:</label>
                                        <select name="doctor_id" class="form-select form-select-sm">
                                            <option value="">-- اختر الطبيب --</option>
                                            @foreach($doctors as $doc)
                                                <option value="{{ $doc->id }}">{{ optional($doc->user)->name }} ({{ $doc->specialization ?? 'أخصائي' }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3 d-none" id="externalDoctorDiv">
                                        <label class="form-label small fw-bold">اسم الطبيب الزائر الخارجي:</label>
                                        <input type="text" name="external_doctor_name" class="form-control form-control-sm" placeholder="د. فلان الفلاني">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">التخصص الدقيق:</label>
                                        <input type="text" name="specialty" class="form-control form-control-sm" placeholder="مثال: جراحة أطفال، قلب أطفال، عيون خدج..." required>
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

                <!-- تبويب 4: الطلبات السريرية (المختبر والأشعة والأدوية) -->
                <div class="tab-pane fade" id="ordersPane">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="card border-0 bg-light p-3 rounded-3">
                                <h6 class="fw-bold text-dark mb-3">
                                    <i class="fas fa-file-medical text-warning me-1"></i>
                                    إصدار طلب فحص مخبري أو أشعة أو علاج
                                </h6>
                                <form action="{{ route('nicu.orders.store', $reservation) }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">نوع الطلب:</label>
                                        <select name="order_type" class="form-select form-select-sm" required>
                                            <option value="lab">🧪 فحص مخبري وتحاليل (ABG, Bilirubin, CBC...)</option>
                                            <option value="radiology">🩻 فحص أشعة / سونار (Portable Chest X-Ray...)</option>
                                            <option value="medication">💊 دواء أو محلول وريدي من الصيدلية</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">اسم الفحص أو العلاج:</label>
                                        <input type="text" name="item_name" class="form-control form-control-sm" placeholder="مثال: غازات الدم الشرياني ABG" required>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">الأولوية:</label>
                                            <select name="priority" class="form-select form-select-sm">
                                                <option value="urgent">عاجل (Urgent)</option>
                                                <option value="stat">فوري طارئ (STAT)</option>
                                                <option value="normal">عادي (Normal)</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">السعر التقديري (د.ع):</label>
                                            <input type="number" step="1000" name="price" class="form-control form-control-sm" value="0">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small">تعليمات إضافية:</label>
                                        <input type="text" name="instructions" class="form-control form-control-sm" placeholder="سحب دم من الوريد، صورة أشعة داخل الحاضنة...">
                                    </div>

                                    <button type="submit" class="btn btn-sm btn-warning w-100 fw-bold shadow-sm">
                                        <i class="fas fa-paper-plane me-1"></i> إرسال الطلب للقسم المعني
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="fas fa-list-check text-primary me-1"></i>
                                سجل الطلبات والفحوصات والأدوية
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>النوع</th>
                                            <th>الفحص / المادة</th>
                                            <th>السعر</th>
                                            <th>الأولوية</th>
                                            <th>النتيجة والتقرير</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($reservation->medicalOrders as $ord)
                                            <tr>
                                                <td>
                                                    <span class="badge bg-{{ $ord->order_type == 'lab' ? 'primary' : ($ord->order_type == 'radiology' ? 'info' : 'success') }}">
                                                        {{ $ord->order_type_name }}
                                                    </span>
                                                </td>
                                                <td class="fw-bold">{{ $ord->item_name }}</td>
                                                <td class="text-success fw-bold">{{ number_format($ord->price) }} د.ع</td>
                                                <td>
                                                    <span class="badge bg-{{ $ord->priority == 'stat' ? 'danger' : 'warning text-dark' }}">
                                                        {{ $ord->priority }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($ord->result)
                                                        <span class="text-dark fw-bold">{{ $ord->result }}</span>
                                                    @else
                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#completeOrderModal_{{ $ord->id }}">
                                                            <i class="fas fa-edit"></i> إدخال النتيجة
                                                        </button>

                                                        <!-- Modal إدخال نتيجة الفحص -->
                                                        <div class="modal fade" id="completeOrderModal_{{ $ord->id }}" tabindex="-1">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header bg-primary text-white">
                                                                        <h6 class="modal-title fw-bold">نتيجة: {{ $ord->item_name }}</h6>
                                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <form action="{{ route('nicu.orders.complete', $ord) }}" method="POST">
                                                                        @csrf
                                                                        @method('PATCH')
                                                                        <div class="modal-body p-3">
                                                                            <div class="mb-3">
                                                                                <label class="form-label small fw-bold">النتيجة المخبرية / تقرير الأشعة:</label>
                                                                                <textarea name="result" rows="3" class="form-control" required></textarea>
                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label class="form-label small fw-bold">تأكيد السعر النهائي (د.ع):</label>
                                                                                <input type="number" step="1000" name="price" class="form-control" value="{{ $ord->price }}">
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="submit" class="btn btn-sm btn-success fw-bold">اعتماد النتيجة والترحيل المالي</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $ord->status == 'completed' ? 'success' : 'secondary' }}">
                                                        {{ $ord->status == 'completed' ? 'تم التنفيذ' : 'قيد الانتظار' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">لا توجد طلبات طبية مسجلة بعد.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
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
function toggleDoctorField(val) {
    if(val === 'internal') {
        document.getElementById('internalDoctorDiv').classList.remove('d-none');
        document.getElementById('externalDoctorDiv').classList.add('d-none');
    } else {
        document.getElementById('internalDoctorDiv').classList.add('d-none');
        document.getElementById('externalDoctorDiv').classList.remove('d-none');
    }
}
</script>
@endpush
@endsection
