@extends('layouts.app')

@section('title', 'لوحة إشغال الحاضنات اللحظية - قسم الخدج')

@section('content')
<div class="container-fluid py-3">
    <!-- رأس الصفحة وأزرار التحكم السريع -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2 class="mb-1 fw-bold text-dark">
                        <i class="fas fa-baby text-primary me-2"></i>
                        لوحة قسم الخدج والحاضنات (NICU)
                    </h2>
                    <p class="text-muted mb-0">المتابعة السريرية والإشغال اللحظي لحاضنات وكبسولات حديثي الولادة - الطابق 6</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('incubator-reservations.create') }}" class="btn btn-success shadow-sm">
                        <i class="fas fa-plus-circle me-1"></i> حجز ودخول خديج جديد
                    </a>
                    <a href="{{ route('nicu.reservations.index') }}" class="btn btn-outline-primary shadow-sm">
                        <i class="fas fa-list me-1"></i> سجل وإضبارات الخدج
                    </a>
                    <a href="{{ route('nicu.services.index') }}" class="btn btn-outline-warning shadow-sm">
                        <i class="fas fa-tags me-1"></i> إعدادات وأسعار الخدمات
                    </a>
                    <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary shadow-sm">
                        <i class="fas fa-door-open me-1"></i> ردهات وغرف الأطفال
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

    <!-- كروت الإحصائيات السريعة (KPIs) -->
    <div class="row mb-4 g-3">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm bg-primary text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">إجمالي الحاضنات</span>
                        <h3 class="mb-0 fw-bold">{{ $stats['total'] }}</h3>
                        <small class="opacity-75">{{ $stats['capsule'] }} كبسولة ضوئية</small>
                    </div>
                    <div class="p-2 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-baby fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm bg-danger text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">الحاضنات المشغولة</span>
                        <h3 class="mb-0 fw-bold">{{ $stats['occupied'] }}</h3>
                        <small class="opacity-75">
                            {{ $stats['total'] > 0 ? round(($stats['occupied'] / $stats['total']) * 100) : 0 }}% نسبة الإشغال
                        </small>
                    </div>
                    <div class="p-2 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-procedures fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm bg-success text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">المتاحة للاستقبال</span>
                        <h3 class="mb-0 fw-bold">{{ $stats['available'] }}</h3>
                        <small class="opacity-75">جاهزة ومعقمة</small>
                    </div>
                    <div class="p-2 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm bg-warning text-dark h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">قيد التعقيم والتطهير</span>
                        <h3 class="mb-0 fw-bold">{{ $stats['sterilizing'] }}</h3>
                        <small class="text-muted">تنتظر تأكيد التعقيم</small>
                    </div>
                    <div class="p-2 bg-dark bg-opacity-10 rounded-circle">
                        <i class="fas fa-spray-can fa-2x text-dark"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-sm-12">
            <div class="card border-0 shadow-sm bg-info text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">نظام اليوم الفندقي</span>
                        <h5 class="mb-0 fw-bold">12:00 ظهراً</h5>
                        <small class="opacity-75">سماحية 6 ساعات قبل وبعد</small>
                    </div>
                    <div class="p-2 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شبكة الحاضنات التفاعلية -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px;">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-th me-2 text-primary"></i>
                شبكة الحاضنات والكبسولات الحية
            </h5>
            <div class="btn-group btn-group-sm">
                <a href="{{ route('nicu.index') }}" class="btn {{ !request('status') ? 'btn-primary' : 'btn-outline-primary' }}">الكل</a>
                <a href="{{ route('nicu.index', ['status' => 'occupied']) }}" class="btn {{ request('status') == 'occupied' ? 'btn-danger' : 'btn-outline-danger' }}">مشغولة ({{ $stats['occupied'] }})</a>
                <a href="{{ route('nicu.index', ['status' => 'available']) }}" class="btn {{ request('status') == 'available' ? 'btn-success' : 'btn-outline-success' }}">متاحة ({{ $stats['available'] }})</a>
                <a href="{{ route('nicu.index', ['status' => 'sterilizing']) }}" class="btn {{ request('status') == 'sterilizing' ? 'btn-warning' : 'btn-outline-warning' }}">تعقيم ({{ $stats['sterilizing'] }})</a>
            </div>
        </div>
        <div class="card-body p-4 bg-light bg-opacity-50">
            <div class="row g-4">
                @forelse($incubators as $incubator)
                    @php
                        $res = $incubator->activeReservation;
                        $isOccupied = $incubator->status === 'occupied' && $res;
                        $isSterilizing = $incubator->status === 'sterilizing';
                        $isAvailable = $incubator->status === 'available';
                    @endphp
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card h-100 shadow-sm border-0 position-relative incubator-card" 
                             style="border-radius: 12px; border-top: 5px solid {{ $isOccupied ? '#dc3545' : ($isSterilizing ? '#ffc107' : '#198754') }} !important;">
                            
                            <!-- رأس الكارت -->
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="p-2 rounded bg-{{ $incubator->type_color }} bg-opacity-10 text-{{ $incubator->type_color }}">
                                            <i class="fas {{ $incubator->type_icon }} fa-lg"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-0 fw-bold">حاضنة رقم {{ $incubator->incubator_number }}</h5>
                                            <small class="text-muted">{{ $incubator->type_name }}</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-{{ $incubator->status_color }} px-2 py-1">
                                        {{ $incubator->status_name }}
                                    </span>
                                </div>

                                <hr class="my-2 opacity-25">

                                <!-- تفاصيل الطفل إذا كانت مشغولة -->
                                @if($isOccupied)
                                    @php
                                        $latestVital = $res->vitals->first();
                                    @endphp
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="d-flex align-items-center gap-1">
                                                    <h6 class="mb-0 fw-bold text-dark">
                                                        <i class="fas fa-child me-1 text-primary"></i>
                                                        {{ $res->baby_name }}
                                                    </h6>
                                                    @if($res->is_independent_patient)
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-1" style="font-size: 10px;">
                                                            <i class="fas fa-baby"></i> ملف مستقل
                                                        </span>
                                                    @endif
                                                </div>
                                                <small class="text-muted d-block mt-0.5">
                                                    @if($res->is_independent_patient)
                                                        <i class="fas fa-female me-1"></i>الأم: {{ $res->patient?->mother_name ?: 'غير مسجل (ملف مستقل)' }}
                                                    @else
                                                        <i class="fas fa-female me-1"></i>الأم: {{ optional($res->patient?->user)->name ?? 'غير معروف' }}
                                                    @endif
                                                    @if($res->birth_certificate_no)
                                                        <span class="text-secondary ms-1">| بيان: {{ $res->birth_certificate_no }}</span>
                                                    @endif
                                                </small>
                                            </div>
                                            @if($res->nicuService)
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1">
                                                    {{ Str::limit($res->nicuService->name, 22) }}
                                                </span>
                                            @endif
                                        </div>

                                        <!-- عداد الساعات والمدة -->
                                        <div class="mt-2 p-2 bg-light rounded d-flex justify-content-between align-items-center small">
                                            <span>
                                                <i class="fas fa-stopwatch text-danger me-1"></i>
                                                <strong>المدة:</strong> {{ $res->duration_hours }} ساعة
                                            </span>
                                            <span class="text-muted">
                                                {{ $res->current_duration }} يوم
                                            </span>
                                        </div>

                                        <!-- الوزن والعلامات الحيوية -->
                                        <div class="mt-2 row g-1 text-center small">
                                            <div class="col-6">
                                                <div class="p-1 border rounded bg-white">
                                                    <span class="text-muted d-block" style="font-size: 11px;">الوزن الحالي</span>
                                                    <strong class="text-success">{{ $res->current_weight ? number_format($res->current_weight) . ' جم' : ($res->birth_weight ?? '—') }}</strong>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-1 border rounded bg-white">
                                                    <span class="text-muted d-block" style="font-size: 11px;">SpO2 الأكسجين</span>
                                                    <strong class="text-info">{{ $latestVital?->spo2 ? $latestVital->spo2 . '%' : '—' }}</strong>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- تنبيهات وجدول الرعاية الحية (الرضاعة والعلاج) -->
                                        @php
                                            $activeFeeding = $res->activeFeedingSchedule();
                                            $dueMeds = $res->activeMedicationSchedules()->filter(fn($m) => $m->is_due);
                                            $nextMed = $res->activeMedicationSchedules()->sortBy('next_due_at')->first();
                                        @endphp
                                        @if($activeFeeding || $nextMed)
                                            <div class="mt-2 p-1.5 rounded bg-light border d-flex flex-column gap-1" style="font-size: 11.5px;">
                                                <!-- تنبيه الرضاعة -->
                                                @if($activeFeeding)
                                                    @if($activeFeeding->is_due)
                                                        <div class="badge bg-danger text-white py-1.5 px-2 d-flex align-items-center justify-content-between text-wrap text-start shadow-xs">
                                                            <div>
                                                                <i class="fas fa-baby-bottle me-1"></i>
                                                                <strong>حان موعد الرضاعة:</strong> {{ Str::limit($activeFeeding->title, 40) }}
                                                            </div>
                                                            <span class="badge bg-white text-danger font-monospace ms-1">{{ $activeFeeding->due_human }}</span>
                                                        </div>
                                                    @else
                                                        <div class="d-flex align-items-center justify-content-between text-muted px-1">
                                                            <span><i class="fas fa-baby-bottle text-warning me-1"></i> الرضاعة القادمة:</span>
                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle font-monospace">{{ $activeFeeding->due_human }}</span>
                                                        </div>
                                                    @endif
                                                @endif

                                                <!-- تنبيه الدواء -->
                                                @if($dueMeds && $dueMeds->count() > 0)
                                                    @php $firstDueMed = $dueMeds->first(); @endphp
                                                    <div class="badge bg-danger text-white py-1.5 px-2 d-flex align-items-center justify-content-between text-wrap text-start shadow-xs">
                                                        <div>
                                                            <i class="fas fa-pills me-1"></i>
                                                            <strong>حان موعد دواء:</strong> {{ Str::limit($firstDueMed->title, 40) }}
                                                        </div>
                                                        <span class="badge bg-white text-danger font-monospace ms-1">{{ $firstDueMed->due_human }}</span>
                                                    </div>
                                                @elseif($nextMed)
                                                    <div class="d-flex align-items-center justify-content-between text-muted px-1">
                                                        <span><i class="fas fa-pills text-primary me-1"></i> الدواء القادم ({{ Str::limit($nextMed->title, 25) }}):</span>
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">{{ $nextMed->due_human }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <!-- أزرار الإجراءات السريعة للمشغولة -->
                                    <div class="d-flex gap-1 mt-auto">
                                        <a href="{{ route('nicu.show', $res) }}" class="btn btn-sm btn-primary w-100 shadow-sm">
                                            <i class="fas fa-stethoscope me-1"></i> ملف الرعاية
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                title="إرسال رابط الكاميرا للأب"
                                                onclick="sendBabyCamLink('{{ $res->id }}', '{{ optional($res->patient?->user)->phone }}')">
                                            <i class="fab fa-whatsapp"></i>
                                        </button>
                                    </div>

                                @elseif($isSterilizing)
                                    <!-- إذا كانت قيد التعقيم والتطهير -->
                                    <div class="py-4 text-center">
                                        <i class="fas fa-spray-can fa-2x text-warning mb-2 animate-pulse"></i>
                                        <h6 class="fw-bold text-dark mb-1">الحاضنة قيد التعقيم</h6>
                                        <small class="text-muted d-block mb-3">خرج الطفل وتنتظر إنهاء بروتوكول التطهير لمكافحة العدوى</small>
                                        
                                        <form action="{{ route('nicu.incubators.sterilized', $incubator) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning w-100 shadow-sm fw-bold">
                                                <i class="fas fa-check-circle me-1"></i> تم التعقيم وجاهزة للاستقبال 🟢
                                            </button>
                                        </form>
                                    </div>

                                @else
                                    <!-- إذا كانت متاحة للاستقبال -->
                                    <div class="py-4 text-center">
                                        <i class="fas fa-bed fa-2x text-success mb-2"></i>
                                        <h6 class="fw-bold text-success mb-1">الحاضنة جاهزة ومعقمة</h6>
                                        <p class="small text-muted mb-3">{{ $incubator->description ?? 'جاهزة لاستقبال مولود جديد فوراً' }}</p>
                                        
                                        <a href="{{ route('incubator-reservations.create', ['incubator_id' => $incubator->id]) }}" class="btn btn-sm btn-outline-success w-100 shadow-sm">
                                            <i class="fas fa-plus-circle me-1"></i> حجز واستقبال طفل الآن
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-info py-4">
                            <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                            لا توجد حاضنات مسجلة بهذا التصنيف حالياً.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Modal إرسال رابط الكاميرا للأب -->
<div class="modal fade" id="babyCamModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fab fa-whatsapp me-2"></i> إرسال رابط كاميرا الحاضنة للأب</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
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
