@extends('layouts.app')

@section('title', 'جدول إدارة ومتابعة الغرف والأسرّة')

@section('content')
<div class="container-fluid py-3">
    <!-- رأس الصفحة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2 class="mb-1 fw-bold text-dark">
                        <i class="fas fa-bed text-primary me-2"></i>
                        جدول إدارة ومتابعة الغرف
                    </h2>
                    <p class="text-muted mb-0">سجل وحالة غرف الرقود مقسمة حسب الطوابق ونسبة الإشغال</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if(auth()->user()->hasRole(['admin', 'receptionist', 'staff', 'surgery_staff']))
                    <form action="{{ route('rooms.sync-occupancy') }}" method="POST" class="d-inline" onsubmit="return confirm('هل تريد فحص كافة الغرف وتحرير الغرف التي لا يوجد بها مريض فعلي؟')">
                        @csrf
                        <button type="submit" class="btn btn-outline-success shadow-sm" title="تحرير الغرف المحجوزة بدون مريض">
                            <i class="fas fa-sync-alt me-1"></i> فحص ومزامنة الشاغر
                        </button>
                    </form>
                    @endif
                    <button onclick="window.print()" class="btn btn-outline-secondary shadow-sm">
                        <i class="fas fa-print me-1"></i> طباعة الجدول
                    </button>
                    @if(auth()->user()->hasRole('admin'))
                    <a href="{{ route('rooms.create') }}" class="btn btn-primary shadow-sm">
                        <i class="fas fa-plus-circle me-1"></i> إضافة غرفة جديدة
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- كروت الإحصائيات العلوية (KPIs) -->
    <div class="row mb-4 g-3">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-primary bg-gradient text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">إجمالي الغرف</span>
                        <h3 class="mb-0 fw-bold">{{ $stats['total'] }}</h3>
                        <small class="opacity-75">{{ $stats['regular'] }} عادية | {{ $stats['vip'] }} VIP | {{ $stats['vvip'] }} VVIP</small>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-door-open fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-success bg-gradient text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">الغرف المتاحة</span>
                        <h3 class="mb-0 fw-bold">{{ $stats['available'] }}</h3>
                        <small class="opacity-75">
                            {{ $stats['total'] > 0 ? round(($stats['available'] / $stats['total']) * 100) : 0 }}% نسبة التوفر
                        </small>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-danger bg-gradient text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">الغرف المشغولة</span>
                        <h3 class="mb-0 fw-bold">{{ $stats['occupied'] }}</h3>
                        <small class="opacity-75">
                            {{ $stats['total'] > 0 ? round(($stats['occupied'] / $stats['total']) * 100) : 0 }}% نسبة الإشغال
                        </small>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-procedures fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-warning bg-gradient text-dark h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">قيد الصيانة</span>
                        <h3 class="mb-0 fw-bold">{{ $stats['maintenance'] }}</h3>
                        <small class="text-muted">خارج الخدمة مؤقتاً</small>
                    </div>
                    <div class="p-3 bg-dark bg-opacity-10 rounded-circle">
                        <i class="fas fa-tools fa-2x text-dark"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط البحث والتصفية -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- البحث الفوري -->
                <div class="col-lg-5 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="roomLiveSearch" class="form-control border-start-0 ps-0" placeholder="بحث سريع برقم الغرفة، الطابق، المزايا...">
                    </div>
                </div>

                <!-- تصفية الحالة -->
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex flex-wrap gap-1" role="group">
                        <a href="{{ route('rooms.index', request()->except('status')) }}" 
                           class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">
                            الكل ({{ $stats['total'] }})
                        </a>
                        <a href="{{ route('rooms.index', array_merge(request()->except('status'), ['status' => 'available'])) }}" 
                           class="btn btn-sm {{ request('status') == 'available' ? 'btn-success' : 'btn-outline-success' }}">
                            ● متاحة ({{ $stats['available'] }})
                        </a>
                        <a href="{{ route('rooms.index', array_merge(request()->except('status'), ['status' => 'occupied'])) }}" 
                           class="btn btn-sm {{ request('status') == 'occupied' ? 'btn-danger' : 'btn-outline-danger' }}">
                            ● مشغولة ({{ $stats['occupied'] }})
                        </a>
                        <a href="{{ route('rooms.index', array_merge(request()->except('status'), ['status' => 'maintenance'])) }}" 
                           class="btn btn-sm {{ request('status') == 'maintenance' ? 'btn-warning text-dark' : 'btn-outline-warning' }}">
                            ● صيانة ({{ $stats['maintenance'] }})
                        </a>
                    </div>
                </div>

                <!-- تصفية النوع -->
                <div class="col-lg-3 col-md-12 d-flex justify-content-lg-end gap-2 flex-wrap">
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('rooms.index', request()->except('type')) }}" class="btn {{ !request('type') ? 'btn-dark' : 'btn-outline-dark' }}">كافة الأنواع</a>
                        <a href="{{ route('rooms.index', array_merge(request()->except('type'), ['type' => 'regular'])) }}" class="btn {{ request('type') == 'regular' ? 'btn-secondary' : 'btn-outline-secondary' }}">عادية</a>
                        <a href="{{ route('rooms.index', array_merge(request()->except('type'), ['type' => 'vip'])) }}" class="btn {{ request('type') == 'vip' ? 'btn-warning text-dark fw-bold' : 'btn-outline-warning' }}">⭐ VIP</a>
                        <a href="{{ route('rooms.index', array_merge(request()->except('type'), ['type' => 'vvip'])) }}" class="btn {{ request('type') == 'vvip' ? 'btn-dark fw-bold text-white' : 'btn-outline-dark' }}">💎 VVIP</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $roomsByFloor = $rooms->groupBy(function($r) {
            return $r->floor ?: 'بدون طابق';
        });
    @endphp

    <!-- تبويبات الطوابق مع الجداول المنظمة -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-header bg-white p-3 border-bottom">
            <ul class="nav nav-pills card-header-pills flex-wrap gap-2" id="floorTabs" role="tablist">
                <!-- تبويب جميع الطوابق -->
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold px-3 py-2" id="tab-floor-all" data-bs-toggle="pill" data-bs-target="#floor-all" type="button" role="tab" aria-controls="floor-all" aria-selected="true">
                        <i class="fas fa-layer-group me-1 text-primary"></i>
                        كافة الطوابق
                        <span class="badge bg-light text-primary border border-primary ms-1">{{ $rooms->count() }}</span>
                    </button>
                </li>

                <!-- تبويبات كل طابق مستقل -->
                @foreach($roomsByFloor as $floorName => $floorRooms)
                @php
                    $tabSlug = 'floor-' . \Illuminate\Support\Str::slug($floorName, '-');
                    if (empty($tabSlug) || $tabSlug === 'floor-') {
                        $tabSlug = 'floor-item-' . $loop->iteration;
                    }
                    $availCount = $floorRooms->where('status', 'available')->count();
                @endphp
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold px-3 py-2 text-dark bg-light border" id="tab-{{ $tabSlug }}" data-bs-toggle="pill" data-bs-target="#{{ $tabSlug }}" type="button" role="tab" aria-controls="{{ $tabSlug }}" aria-selected="false">
                        <i class="fas fa-building me-1 text-secondary"></i>
                        {{ $floorName }}
                        <span class="badge bg-secondary ms-1">{{ $floorRooms->count() }}</span>
                        @if($availCount > 0)
                            <span class="badge bg-success bg-opacity-25 text-success ms-1" title="متاحة">🟢 {{ $availCount }}</span>
                        @endif
                    </button>
                </li>
                @endforeach
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="floorTabsContent">
                <!-- محتوى كافة الطوابق في جدول -->
                <div class="tab-pane fade show active" id="floor-all" role="tabpanel" aria-labelledby="tab-floor-all">
                    @include('rooms._room_table', ['roomList' => $rooms])
                </div>

                <!-- محتوى كل طابق مستقل في جدول -->
                @foreach($roomsByFloor as $floorName => $floorRooms)
                @php
                    $tabSlug = 'floor-' . \Illuminate\Support\Str::slug($floorName, '-');
                    if (empty($tabSlug) || $tabSlug === 'floor-') {
                        $tabSlug = 'floor-item-' . $loop->iteration;
                    }
                @endphp
                <div class="tab-pane fade" id="{{ $tabSlug }}" role="tabpanel" aria-labelledby="tab-{{ $tabSlug }}">
                    <div class="d-flex justify-content-between align-items-center p-3 bg-light border-bottom">
                        <span class="fw-bold text-dark fs-6"><i class="fas fa-layer-group text-primary me-2"></i>غرف {{ $floorName }}</span>
                        <div class="small">
                            <span class="badge bg-success me-1">متاحة: {{ $floorRooms->where('status', 'available')->count() }}</span>
                            <span class="badge bg-danger me-1">مشغولة: {{ $floorRooms->where('status', 'occupied')->count() }}</span>
                            <span class="badge bg-warning text-dark">صيانة: {{ $floorRooms->where('status', 'maintenance')->count() }}</span>
                        </div>
                    </div>
                    @include('rooms._room_table', ['roomList' => $floorRooms])
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- التوست للإشعارات السريعة -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
    <div id="statusToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">
                تم تحديث حالة الغرفة بنجاح.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. البحث الفوري اللحظي في صفوف الجداول
    const searchInput = document.getElementById('roomLiveSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const val = this.value.toLowerCase().trim();
            document.querySelectorAll('.room-table-row').forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(val) ? '' : 'none';
            });
        });
    }

    // 2. تغيير حالة الغرفة عبر AJAX
    const toastEl = document.getElementById('statusToast');
    const toastMessage = document.getElementById('toastMessage');
    const toast = toastEl ? new bootstrap.Toast(toastEl, { delay: 3000 }) : null;

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.change-room-status');
        if (!btn) return;

        e.preventDefault();
        const roomId = btn.dataset.id;
        const newStatus = btn.dataset.status;

        fetch(`{{ url('/rooms') }}/${roomId}/change-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // تحديث البادجات في جميع جداول الطوابق لنفس الغرفة
                const pills = document.querySelectorAll(`.status-pill-${roomId}`);
                pills.forEach(pill => {
                    pill.className = `badge bg-${data.status_color} status-pill-${roomId} px-2 py-1`;
                    pill.innerText = data.status_name;
                });

                // تحديث تظليل الصف باللون الأحمر للمحجوزة
                const rows = document.querySelectorAll(`.row-room-${roomId}`);
                rows.forEach(row => {
                    row.classList.remove('table-danger', 'bg-danger', 'bg-opacity-10', 'table-warning', 'bg-warning');
                    if (newStatus === 'occupied') {
                        row.classList.add('table-danger', 'bg-danger', 'bg-opacity-10');
                    } else if (newStatus === 'maintenance') {
                        row.classList.add('table-warning', 'bg-warning', 'bg-opacity-10');
                    }
                });

                if (toast && toastMessage) {
                    toastMessage.innerText = data.message;
                    toastEl.className = 'toast align-items-center text-white bg-success border-0';
                    toast.show();
                }
            } else {
                if (toast && toastMessage) {
                    toastMessage.innerText = data.message || 'فشل تغيير الحالة';
                    toastEl.className = 'toast align-items-center text-white bg-danger border-0';
                    toast.show();
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (toast && toastMessage) {
                toastMessage.innerText = 'حدث خطأ في الاتصال بالسيرفر';
                toastEl.className = 'toast align-items-center text-white bg-danger border-0';
                toast.show();
            }
        });
    });
});
</script>

<style>
.nav-pills .nav-link {
    border-radius: 8px;
    transition: all 0.2s ease;
}
.nav-pills .nav-link.active {
    background-color: #0d6efd !important;
    color: #fff !important;
    box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
}
.nav-pills .nav-link.active .badge {
    background-color: #fff !important;
    color: #0d6efd !important;
}
@media print {
    .btn, .dropdown, .input-group, form, #floorTabs {
        display: none !important;
    }
}
</style>
@endsection
