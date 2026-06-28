@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
        <div>
            <h4 class="mb-0 fw-bold text-dark"><i class="fas fa-clock text-primary me-2"></i> قائمة انتظار العمليات الجراحية</h4>
            <small class="text-muted">مراقبة ومتابعة العمليات الجراحية المجدولة والجارية</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('surgeries.create') }}" class="btn btn-primary rounded-pill px-3 shadow-sm">
                <i class="fas fa-plus me-1"></i> عملية جديدة
            </a>
            <a href="{{ route('surgeries.index') }}" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm bg-white">
                <i class="fas fa-list me-1"></i> جميع العمليات
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4">
        <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-white bg-opacity-25 rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-calendar-check fs-4"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold">{{ $scheduledSurgeries->count() + $activeSurgeries->count() }}</div>
                        <div class="small opacity-75">إجمالي العمليات</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7); color: white;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-white bg-opacity-25 rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-hourglass-start fs-4"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold">{{ $scheduledSurgeries->count() }}</div>
                        <div class="small opacity-75">مجدولة</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4" style="background: linear-gradient(135deg, #ffc107, #e0a800); color: #212529;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-dark bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-hourglass-half fs-4"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold">{{ $activeSurgeries->count() }}</div>
                        <div class="small opacity-75">بانتظار/جارية</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-4" style="background: linear-gradient(135deg, #198754, #157347); color: white;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-white bg-opacity-25 rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-procedures fs-4"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold">{{ $activeSurgeries->where('status', 'in_progress')->count() }}</div>
                        <div class="small opacity-75">قيد الإجراء</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div class="btn-group shadow-sm rounded-pill overflow-hidden">
            <button type="button" class="btn btn-light active filter-btn rounded-pill px-3" data-filter="all">
                <i class="fas fa-th-list me-1"></i> الكل
            </button>
            <button type="button" class="btn btn-light filter-btn rounded-pill px-3" data-filter="scheduled">
                <i class="fas fa-calendar me-1"></i> مجدولة
            </button>
            <button type="button" class="btn btn-light filter-btn rounded-pill px-3" data-filter="active">
                <i class="fas fa-play me-1"></i> جارية
            </button>
        </div>
        <div class="ms-auto">
            <input type="text" id="searchInput" class="form-control form-control-sm rounded-pill shadow-sm" placeholder="بحث عن مريض أو عملية..." style="width: 250px;">
        </div>
    </div>

    @php
        $allSurgeries = collect();
        foreach ($scheduledSurgeries as $s) { $s->list_type = 'scheduled'; $allSurgeries->push($s); }
        foreach ($activeSurgeries as $s) { $s->list_type = 'active'; $allSurgeries->push($s); }
        $allSurgeries = $allSurgeries->sortBy('scheduled_date');
    @endphp

    @if($allSurgeries->isEmpty())
    <div class="text-center py-5">
        <div class="mb-3 text-muted">
            <i class="fas fa-check-circle fa-4x"></i>
        </div>
        <h5 class="text-muted">لا توجد عمليات في قائمة الانتظار</h5>
        <p class="text-muted mb-3">جميع العمليات منجزة أو ملغاة</p>
        <a href="{{ route('surgeries.create') }}" class="btn btn-primary rounded-pill px-4">
            <i class="fas fa-plus me-1"></i> إضافة عملية جديدة
        </a>
    </div>
    @else
    <div class="row g-3" id="surgeriesContainer">
        @foreach($allSurgeries as $surgery)
        <div class="col-xl-4 col-lg-6 surgery-card" data-type="{{ $surgery->list_type }}" data-search="{{ $surgery->patient->user->name }} {{ $surgery->patient->user->full_name ?? '' }} {{ $surgery->surgery_type }} {{ $surgery->doctor->user->name }}">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header border-0 p-3 d-flex align-items-center justify-content-between {{ $surgery->list_type === 'scheduled' ? 'bg-primary text-white' : 'bg-warning' }}" style="background: {{ $surgery->list_type === 'scheduled' ? 'linear-gradient(135deg, #0d6efd, #0b5ed7)' : 'linear-gradient(135deg, #ffc107, #e0a800)' }};">
                    <div>
                        <span class="fw-bold">#{{ $surgery->id }}</span>
                        <span class="mx-2 opacity-50">|</span>
                        <small>{{ $surgery->scheduled_date->format('Y-m-d') }}</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if($surgery->list_type === 'scheduled')
                            <span class="badge bg-light text-primary rounded-pill px-2">مجدولة</span>
                        @else
                            @if($surgery->status == 'waiting')
                                <span class="badge bg-dark rounded-pill px-2">بانتظار</span>
                            @elseif($surgery->status == 'checked_in')
                                <span class="badge bg-info text-white rounded-pill px-2">تم التسجيل</span>
                            @elseif($surgery->status == 'in_progress')
                                <span class="badge bg-success rounded-pill px-2">
                                    <span class="spinner-border spinner-border-sm me-1" style="width: 10px; height: 10px;"></span> جارية
                                </span>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 44px; height: 44px; min-width: 44px; background: linear-gradient(135deg, #20c997, #0dcaf0); font-size: 18px;">
                            {{ mb_substr($surgery->patient->user->name, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <div class="fw-bold text-dark text-truncate">{{ $surgery->patient->user->name }}</div>
                            <small class="text-muted">{{ $surgery->patient->file_number ?? 'رقم الملف' }}</small>
                        </div>
                    </div>

                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted ps-0" style="width: 80px;"><i class="fas fa-scalpel me-1 text-primary"></i> العملية</td>
                            <td class="fw-semibold text-dark ps-0">{{ $surgery->surgery_type }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0"><i class="fas fa-user-md me-1 text-success"></i> الطبيب</td>
                            <td class="fw-semibold text-dark ps-0">{{ $surgery->doctor->user->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0"><i class="fas fa-clock me-1 text-warning"></i> الوقت</td>
                            <td class="fw-semibold text-dark ps-0">{{ $surgery->scheduled_time ? \Carbon\Carbon::parse($surgery->scheduled_time)->format('H:i') : 'غير محدد' }}</td>
                        </tr>
                        @if($surgery->room)
                        <tr>
                            <td class="text-muted ps-0"><i class="fas fa-door-open me-1 text-info"></i> الغرفة</td>
                            <td class="fw-semibold text-dark ps-0">{{ $surgery->room->name ?? $surgery->room->room_number ?? '-' }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                <div class="card-footer bg-white border-0 p-3 pt-0 d-flex flex-wrap gap-2">
                    <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-outline-info rounded-pill px-3">
                        <i class="fas fa-eye me-1"></i> عرض
                    </a>

                    @if($surgery->list_type === 'scheduled')
                        <form action="{{ route('surgeries.check-in', $surgery) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                                <i class="fas fa-sign-in-alt me-1"></i> دخول
                            </button>
                        </form>
                    @else
                        @if($surgery->status == 'waiting' || $surgery->status == 'checked_in')
                            <form action="{{ route('surgeries.start', $surgery) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm">
                                    <i class="fas fa-play me-1"></i> بدء
                                </button>
                            </form>
                        @elseif($surgery->status == 'in_progress')
                            <form action="{{ route('surgeries.complete', $surgery) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm" onclick="return confirm('هل أنت متأكد من إنهاء العملية؟')">
                                    <i class="fas fa-stop me-1"></i> إنهاء
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const cards = document.querySelectorAll('.surgery-card');
    const searchInput = document.getElementById('searchInput');

    function filterCards() {
        const activeFilter = document.querySelector('.filter-btn.active');
        const filter = activeFilter ? activeFilter.dataset.filter : 'all';
        const search = searchInput ? searchInput.value.toLowerCase().trim() : '';

        cards.forEach(card => {
            const type = card.dataset.type;
            const searchData = card.dataset.search ? card.dataset.search.toLowerCase() : '';
            let show = true;

            if (filter !== 'all' && type !== filter) show = false;
            if (search && !searchData.includes(search)) show = false;

            card.style.display = show ? '' : 'none';
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filterCards();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', filterCards);
    }

    setInterval(function() {
        location.reload();
    }, 30000);
});
</script>
@endsection
