@extends('layouts.app')

@section('title', 'معاينات الوليد الحية (ساعتان)')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" id="nicuObservationsHub">
    {{-- الترويسة --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="p-2 rounded-3 bg-warning bg-opacity-15 text-warning d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="fas fa-stopwatch fa-lg"></i>
                </span>
                <div>
                    <h3 class="mb-0 fw-bold text-dark">
                        معاينات الوليد الحية ({{ $observationSettings['duration_minutes'] ?? 120 }} دقيقة)
                    </h3>
                    <p class="text-muted small mb-0">متابعة المواليد بعد الولادة بمؤقت مباشر، توثيق التسليم للأهل، طباعة استمارة Obstetric Case Sheet أو التحويل للحاضنة</p>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($canManageObservations ?? false)
                <button type="button" class="btn btn-danger shadow-sm rounded-3 fw-bold" data-bs-toggle="modal" data-bs-target="#manualObservationModal">
                    <i class="fas fa-plus-circle me-1"></i>
                    بدء معاينة ولادة طبيعية / خارج الرادار
                </button>
            @endif
            <a href="{{ route('nicu.delivery-radar.index') }}" class="btn btn-outline-primary shadow-sm rounded-3">
                <i class="fas fa-satellite-dish me-1"></i>
                رادار صالات العمليات
            </a>
            @if($canManageSettings ?? false)
                <a href="{{ route('nicu.delivery-radar.settings') }}" class="btn btn-outline-dark shadow-sm rounded-3">
                    <i class="fas fa-sliders-h me-1"></i>
                    إعدادات المعاينة
                </a>
            @endif
        </div>
    </div>

    {{-- التنبيهات --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- بطاقات الإحصائيات السريعة --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-warning border-4">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-warning small fw-bold d-block mb-1">تحت المعاينة حالياً</span>
                        <h3 class="fw-bold mb-0 text-dark">{{ $stats['under_observation'] }}</h3>
                        <small class="text-muted">لم تكمل {{ $observationSettings['duration_minutes'] ?? 120 }} دقيقة</small>
                    </div>
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3">
                        <i class="fas fa-hourglass-half fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-success border-4">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-success small fw-bold d-block mb-1">أكملت المدة / جاهزة للتسليم</span>
                        <h3 class="fw-bold mb-0 text-success">{{ $stats['ready_for_handover'] }}</h3>
                        <small class="text-muted">جاهزة لحسم التسليم أو الحاضنة</small>
                    </div>
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-info border-4">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-info small fw-bold d-block mb-1">محولة لحاضنة NICU اليوم</span>
                        <h3 class="fw-bold mb-0 text-dark">{{ $stats['admitted_today'] }}</h3>
                        <small class="text-muted">مواليد نُقلوا للرقود بالحاضنة</small>
                    </div>
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-3">
                        <i class="fas fa-hospital-user fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-primary border-4">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-primary small fw-bold d-block mb-1">سُلّمت للأهل اليوم</span>
                        <h3 class="fw-bold mb-0 text-primary">{{ $stats['handed_over_today'] }}</h3>
                        <small class="text-muted">بمحضر استلام رسمي</small>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3">
                        <i class="fas fa-hands-holding-child fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- تبويبان: المعاينات الحية (بطاقات) | أرشيف التسليم والتحويل --}}
    @php
        $initialTab = request('tab', ($archiveSearch ?? '') !== '' ? 'archive' : 'live');
    @endphp
    <ul class="nav nav-pills mb-3 gap-2" id="obsPageTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 fw-bold px-4 {{ $initialTab === 'live' ? 'active' : '' }}" id="obs-live-tab" data-bs-toggle="pill" data-bs-target="#obs-live-pane" type="button" role="tab">
                <i class="fas fa-stopwatch me-1"></i>
                المعاينات الحية الآن
                <span class="badge bg-danger ms-1">{{ $activeObservations->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 fw-bold px-4 {{ $initialTab === 'archive' ? 'active' : '' }}" id="obs-archive-tab" data-bs-toggle="pill" data-bs-target="#obs-archive-pane" type="button" role="tab">
                <i class="fas fa-archive me-1"></i>
                أرشيف التسليم والتحويل ({{ $archiveObservations->count() }})
            </button>
        </li>
    </ul>

    <div class="tab-content" id="obsPageTabContent">
        {{-- التبويب 1: البطاقات الحية --}}
        <div class="tab-pane fade {{ $initialTab === 'live' ? 'show active' : '' }}" id="obs-live-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="fas fa-baby text-danger me-2"></i>
                            بطاقات المواليد تحت المعاينة المباشرة
                        </h5>
                        <small class="text-muted">يتحول لون المؤقت إلى الأخضر تلقائياً عند إتمام {{ $observationSettings['duration_minutes'] ?? 120 }} دقيقة</small>
                    </div>
                    <div class="input-group input-group-sm" style="max-width: 280px;">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="liveObsSearch" class="form-control" placeholder="بحث سريع باسم الأم أو المولود...">
                    </div>
                </div>

                @if($activeObservations->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-baby-carriage fa-3x mb-3 opacity-50"></i>
                        <h5 class="fw-bold text-dark mb-1">لا توجد حالات تحت المعاينة حالياً</h5>
                        <p class="small mb-3">يمكنك بدء معاينة مباشرة من «رادار صالات العمليات» أو إضافة حالة ولادة طبيعية يدوياً.</p>
                        @if($canManageObservations ?? false)
                            <button type="button" class="btn btn-danger rounded-3 fw-bold" data-bs-toggle="modal" data-bs-target="#manualObservationModal">
                                <i class="fas fa-plus me-1"></i> بدء معاينة ولادة طبيعية
                            </button>
                        @endif
                    </div>
                @else
                    <div class="row g-3" id="liveObsCardsGrid">
                        @foreach($activeObservations as $obs)
                            @php
                                $cardSearch = mb_strtolower($obs->mother_name . ' ' . ($obs->operative_description ?? '') . ' ' . $obs->babies->pluck('born_name')->implode(' '));
                            @endphp
                            <div class="col-md-6 col-xl-4 live-obs-card" data-search="{{ $cardSearch }}">
                                <div class="card h-100 border shadow-sm rounded-4 overflow-hidden">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 px-3">
                                        <span class="badge {{ $obs->status_badge_class }} rounded-pill">{{ $obs->status_label }}</span>
                                        <div class="live-obs-timer badge px-3 py-2 font-monospace {{ $obs->isDurationCompleted() ? 'bg-success text-white' : 'bg-warning text-dark' }}"
                                             data-started-at="{{ $obs->started_at?->toIso8601String() }}"
                                             data-target-minutes="{{ $obs->target_duration_minutes }}">
                                            {{ str_pad(intdiv($obs->elapsed_minutes, 60), 2, '0', STR_PAD_LEFT) }}:{{ str_pad($obs->elapsed_minutes % 60, 2, '0', STR_PAD_LEFT) }} / {{ $obs->target_duration_minutes }}m
                                        </div>
                                    </div>
                                    <div class="card-body p-3 d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                            <h6 class="fw-bold text-dark mb-0">{{ $obs->mother_name }}</h6>
                                            <small class="text-muted flex-shrink-0">{{ $obs->started_at?->format('h:i A') }}</small>
                                        </div>
                                        <div class="small text-muted mb-3">
                                            <span>{{ $obs->operative_description ?? 'ولادة' }}</span>
                                            <span class="mx-1">•</span>
                                            <span>{{ $obs->case_of_delivery }} ({{ $obs->no_of_borns }})</span>
                                            @if($obs->surgeon_name)
                                                <span class="mx-1">•</span>
                                                <span>{{ $obs->surgeon_name }}</span>
                                            @endif
                                        </div>

                                        <div class="d-flex flex-column gap-2 mb-3 flex-grow-1">
                                            @foreach($obs->babies as $b)
                                                <div class="p-2 rounded-3 bg-light border small d-flex justify-content-between align-items-center gap-2">
                                                    <div>
                                                        <div class="fw-bold text-dark">{{ $b->born_name }}</div>
                                                        <div class="text-muted">
                                                            {{ $b->gender ?? '-' }}
                                                            @if($b->weight_kg)
                                                                • {{ rtrim(rtrim(number_format((float) $b->weight_kg, 3, '.', ''), '0'), '.') }} Kg
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @if($b->disposition === 'admitted_to_nicu' && $b->incubatorReservation)
                                                        <a href="{{ route('incubator-reservations.show', $b->incubatorReservation) }}" class="badge bg-info text-dark text-decoration-none">
                                                            حاضنة #{{ $b->incubatorReservation->incubator?->incubator_number ?? $b->incubatorReservation->id }}
                                                        </a>
                                                    @elseif($b->disposition === 'handed_over')
                                                        <span class="badge bg-primary">تم التسليم</span>
                                                    @else
                                                        <a href="{{ route('incubator-reservations.create', array_filter(['patient_id' => $obs->patient_id, 'observation_baby_id' => $b->id])) }}"
                                                           class="btn btn-xs btn-outline-danger rounded-2 py-0 px-2 small fw-bold" title="تحويل مباشر لحاضنة NICU">
                                                            <i class="fas fa-ambulance me-1"></i>حاضنة
                                                        </a>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="d-flex gap-2 mt-auto">
                                            <a href="{{ route('nicu.observations.show', $obs) }}" class="btn btn-sm btn-primary flex-grow-1 rounded-3 fw-bold">
                                                <i class="fas fa-folder-open me-1"></i> فتح الملف والتسليم
                                            </a>
                                            <a href="{{ route('nicu.observations.print', $obs) }}" target="_blank" class="btn btn-sm btn-outline-dark rounded-3" title="طباعة استمارة A4">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- التبويب 2: أرشيف التسليم والتحويل --}}
        <div class="tab-pane fade {{ $initialTab === 'archive' ? 'show active' : '' }}" id="obs-archive-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 p-md-4">
                <form action="{{ route('nicu.observations.index') }}" method="GET" class="row g-2 align-items-center mb-3">
                    <input type="hidden" name="tab" value="archive">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="archive_search" class="form-control" value="{{ $archiveSearch ?? '' }}" placeholder="ابحث باسم الأم، المولود، المستلم (الأب)، رقم الهوية، أو الهاتف...">
                            <button type="submit" class="btn btn-primary px-3">بحث بالأرشيف</button>
                        </div>
                    </div>
                    @if(($archiveSearch ?? '') !== '')
                        <div class="col-auto">
                            <a href="{{ route('nicu.observations.index', ['tab' => 'archive']) }}" class="btn btn-sm btn-outline-secondary">مسح البحث</a>
                        </div>
                    @endif
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>#</th>
                                <th>تاريخ الولادة</th>
                                <th>الأم والمصدر</th>
                                <th>المواليد والحسم</th>
                                <th>بيانات المستلم (الأهل)</th>
                                <th>الورقة المؤرشفة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($archiveObservations as $arch)
                                <tr>
                                    <td><span class="badge bg-light text-muted border">#{{ $arch->id }}</span></td>
                                    <td class="small">{{ $arch->delivery_date?->format('Y-m-d') ?? $arch->started_at?->format('Y-m-d') }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $arch->mother_name }}</div>
                                        <small class="text-muted">{{ $arch->operative_description ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $arch->status_badge_class }} rounded-pill mb-1">{{ $arch->status_label }}</span>
                                        <div class="small text-muted">
                                            {{ $arch->babies->pluck('born_name')->implode('، ') }}
                                        </div>
                                    </td>
                                    <td class="small">
                                        @if($arch->recipient_name)
                                            <div class="fw-bold">{{ $arch->recipient_name }} ({{ $arch->recipient_relation ?? 'الأب' }})</div>
                                            <div class="text-muted">{{ $arch->recipient_phone }} | هوية: {{ $arch->recipient_id_number }}</div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($arch->signed_form_path)
                                            <a href="{{ route('nicu.observations.signed-form.download', $arch) }}" target="_blank" class="badge bg-success text-decoration-none">
                                                <i class="fas fa-file-check me-1"></i> محفوظة
                                            </a>
                                        @else
                                            <span class="badge bg-light text-muted border">غير مرفوعة</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('nicu.observations.show', $arch) }}" class="btn btn-xs btn-outline-primary rounded-2 px-2 py-1 small">فتح</a>
                                            <a href="{{ route('nicu.observations.print', $arch) }}" target="_blank" class="btn btn-xs btn-outline-dark rounded-2 px-2 py-1 small" title="طباعة A4">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">لا توجد سجلات معاينة مطابقة في الأرشيف.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- مودال فتح معاينة ولادة طبيعية / حالة خارج رادار العمليات --}}
@if($canManageObservations ?? false)
<div class="modal fade" id="manualObservationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-danger text-white rounded-top-4">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-baby-carriage me-2"></i>
                    بدء معاينة ({{ $observationSettings['duration_minutes'] ?? 120 }} دقيقة) لولادة طبيعية / حالة خارج الرادار
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('nicu.observations.store-manual') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">اسم الأم الكامل <span class="text-danger">*</span></label>
                            <input type="text" name="mother_name" class="form-control" required placeholder="مثال: رسل باسل أحمد">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">نوع الولادة / الوصف</label>
                            <input type="text" name="operative_description" class="form-control" value="ولادة طبيعية" placeholder="ولادة طبيعية / قيصرية طارئة">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">تاريخ الولادة</label>
                            <input type="date" name="delivery_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">وقت الولادة</label>
                            <input type="text" name="delivery_time" class="form-control" value="{{ now()->format('h:i a') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">عدد المواليد</label>
                            <input type="number" name="no_of_borns" min="1" max="6" class="form-control" value="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">جنس المولود الأول</label>
                            <select name="baby_gender" class="form-select">
                                <option value="">-- يحدد لاحقاً --</option>
                                <option value="Male">Male (ذكر)</option>
                                <option value="Female">Female (أنثى)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">الوزن (Kg)</label>
                            <input type="number" step="0.01" min="0.2" max="10" name="baby_weight_kg" class="form-control" placeholder="3.0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">الطبيبة المشرفة / القابلة</label>
                            <input type="text" name="requesting_doctor_name" class="form-control" placeholder="اسم الطبيبة">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">ملاحظة الولادة (Delivery Note)</label>
                            <textarea name="delivery_note" rows="2" class="form-control" placeholder="ملاحظات أولية عن الولادة وحالة الوليد..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger rounded-3 fw-bold px-4">
                        <i class="fas fa-stopwatch me-1"></i> فتح ملف المعاينة وبدء المؤقت
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const liveSearch = document.getElementById('liveObsSearch');
    const cards = document.querySelectorAll('.live-obs-card');

    if (liveSearch) {
        liveSearch.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            cards.forEach(card => {
                const hay = card.getAttribute('data-search') || '';
                card.style.display = (!q || hay.includes(q)) ? '' : 'none';
            });
        });
    }

    const liveTimers = document.querySelectorAll('.live-obs-timer');
    if (liveTimers.length > 0) {
        const refreshTimers = () => {
            const now = new Date();
            liveTimers.forEach(el => {
                const startedAt = new Date(el.dataset.startedAt);
                const targetMins = parseInt(el.dataset.targetMinutes || '120', 10);
                const elapsedMins = Math.max(0, Math.floor((now - startedAt) / 60000));
                const hrs = String(Math.floor(elapsedMins / 60)).padStart(2, '0');
                const mins = String(elapsedMins % 60).padStart(2, '0');
                el.textContent = `${hrs}:${mins} / ${targetMins}m`;
                if (elapsedMins >= targetMins) {
                    el.classList.remove('bg-warning', 'text-dark');
                    el.classList.add('bg-success', 'text-white');
                }
            });
        };
        refreshTimers();
        setInterval(refreshTimers, 15000);
    }
});
</script>
@endsection
