@extends('layouts.app')

@section('title', 'إدارة ومتابعة الغرف والأسرّة')

@section('content')
<div class="container-fluid py-3">
    <!-- رأس الصفحة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2 class="mb-1 fw-bold text-dark">
                        <i class="fas fa-hospital-alt text-primary me-2"></i>
                        لوحة إدارة ومتابعة الغرف
                    </h2>
                    <p class="text-muted mb-0">نظام إدارة وتخصيص غرف الرقود ومتابعة نسبة الإشغال</p>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-outline-secondary shadow-sm">
                        <i class="fas fa-print me-1"></i> طباعة الكشف
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
                        <span class="small opacity-75 d-block mb-1">إجمالي الغرف بالمستشفى</span>
                        <h3 class="mb-0 fw-bold">{{ $stats['total'] }}</h3>
                        <small class="opacity-75">
                            {{ $stats['regular'] }} عادية | {{ $stats['vip'] }} VIP
                        </small>
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
                        <span class="small opacity-75 d-block mb-1">الغرف المتاحة للحجز</span>
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
                        <span class="small opacity-75 d-block mb-1">الغرف المشغولة حالياً</span>
                        <h3 class="mb-0 fw-bold">{{ $stats['occupied'] }}</h3>
                        <small class="opacity-75">
                            {{ $stats['total'] > 0 ? round(($stats['occupied'] / $stats['total']) * 100) : 0 }}% نسبة الإشغال
                        </small>
                    </div>
                    <div class="p-3 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-bed fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-warning bg-gradient text-dark h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">غرف قيد الصيانة</span>
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

    <!-- شريط البحث والتصفية المتطور -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- البحث الفوري -->
                <div class="col-lg-4 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="roomLiveSearch" class="form-control border-start-0 ps-0" placeholder="بحث برقم الغرفة، الطابق، المزايا...">
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

                <!-- تصفية النوع والتبديل -->
                <div class="col-lg-4 col-md-12 d-flex justify-content-lg-end gap-2 flex-wrap">
                    <!-- نوع الغرفة -->
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('rooms.index', request()->except('type')) }}" class="btn {{ !request('type') ? 'btn-dark' : 'btn-outline-dark' }}">كافة الأنواع</a>
                        <a href="{{ route('rooms.index', array_merge(request()->except('type'), ['type' => 'regular'])) }}" class="btn {{ request('type') == 'regular' ? 'btn-secondary' : 'btn-outline-secondary' }}">عادية</a>
                        <a href="{{ route('rooms.index', array_merge(request()->except('type'), ['type' => 'vip'])) }}" class="btn {{ request('type') == 'vip' ? 'btn-warning text-dark fw-bold' : 'btn-outline-warning' }}">⭐ VIP</a>
                    </div>

                    <!-- تبديل طريقة العرض (كروت / جدول) -->
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" id="btnViewGrid" title="عرض الكروت الشبكية">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="btnViewTable" title="عرض الجدول المفصل">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- عرض الكروت الشبكية (Grid View) -->
    <div id="viewGridContainer">
        @php
            $roomsByFloor = $rooms->groupBy('floor');
        @endphp

        @forelse($roomsByFloor as $floor => $floorRooms)
        <div class="card border-0 shadow-sm mb-4 floor-section" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-layer-group text-primary me-2"></i>
                    {{ $floor ?: 'الطابق الرئيسي / عام' }}
                </h5>
                <span class="badge bg-light text-primary border border-primary px-3 py-1 fs-6">
                    {{ $floorRooms->count() }} غرفة
                </span>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    @foreach($floorRooms as $room)
                    @php
                        $statusBadge = match($room->status) {
                            'available' => ['class' => 'bg-success', 'text' => 'متاحة للحجز', 'icon' => 'fa-check'],
                            'occupied' => ['class' => 'bg-danger', 'text' => 'مشغولة', 'icon' => 'fa-user-check'],
                            'maintenance' => ['class' => 'bg-warning text-dark', 'text' => 'قيد الصيانة', 'icon' => 'fa-tools'],
                            default => ['class' => 'bg-secondary', 'text' => $room->status_name, 'icon' => 'fa-info']
                        };
                        $isVip = $room->room_type === 'vip';
                    @endphp
                    <div class="col-xxl-3 col-xl-4 col-md-6 room-card-item" 
                         data-number="{{ $room->room_number }}" 
                         data-floor="{{ $room->floor }}" 
                         data-type="{{ $room->room_type }}" 
                         data-status="{{ $room->status }}">
                        
                        <div class="card h-100 border room-card shadow-sm {{ $isVip ? 'border-warning border-opacity-50 bg-warning bg-opacity-10' : 'bg-white' }}" 
                             style="border-radius: 10px; transition: transform 0.2s, box-shadow 0.2s;">
                            
                            <div class="card-body p-3">
                                <!-- الترويسة العلوية للكارت -->
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h4 class="mb-0 fw-bold {{ $isVip ? 'text-warning text-dark' : 'text-primary' }}">
                                            غرفة {{ $room->room_number }}
                                        </h4>
                                        <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>{{ $room->floor ?: 'بدون طابق' }}</small>
                                    </div>
                                    <div class="text-end">
                                        @if($isVip)
                                            <span class="badge bg-warning text-dark border border-warning px-2 py-1 mb-1">
                                                <i class="fas fa-crown me-1"></i>VIP
                                            </span>
                                        @else
                                            <span class="badge bg-light text-secondary border px-2 py-1 mb-1">عادية</span>
                                        @endif
                                        <br>
                                        <span class="badge {{ $statusBadge['class'] }} status-pill-{{ $room->id }} px-2 py-1">
                                            <i class="fas {{ $statusBadge['icon'] }} me-1"></i><span class="status-text-{{ $room->id }}">{{ $statusBadge['text'] }}</span>
                                        </span>
                                    </div>
                                </div>

                                <hr class="my-2 opacity-25">

                                <!-- تفاصيل الغرفة والسعر -->
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <div class="p-2 rounded bg-light border text-center">
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">الأجرة اليومية</small>
                                            <strong class="text-success fs-6">{{ number_format($room->daily_fee, 0) }}</strong>
                                            <small class="text-muted" style="font-size: 0.7rem;">د.ع</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 rounded bg-light border text-center">
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">عدد الأسرّة</small>
                                            <strong class="text-dark fs-6">{{ $room->beds_count }}</strong>
                                            <small class="text-muted" style="font-size: 0.7rem;">أسرّة</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- المزايا والخدمات -->
                                <div class="d-flex gap-2 mb-3">
                                    <span class="badge {{ $room->has_bathroom ? 'bg-info bg-opacity-10 text-info border border-info' : 'bg-light text-muted opacity-50 border' }}" title="حمام خاص">
                                        <i class="fas fa-bath me-1"></i>حمام
                                    </span>
                                    <span class="badge {{ $room->has_ac ? 'bg-primary bg-opacity-10 text-primary border border-primary' : 'bg-light text-muted opacity-50 border' }}" title="تكييف">
                                        <i class="fas fa-snowflake me-1"></i>AC
                                    </span>
                                    <span class="badge {{ $room->has_tv ? 'bg-secondary bg-opacity-10 text-secondary border border-secondary' : 'bg-light text-muted opacity-50 border' }}" title="تلفاز">
                                        <i class="fas fa-tv me-1"></i>TV
                                    </span>
                                </div>

                                <!-- أزرار التحكم -->
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('rooms.show', $room) }}" class="btn btn-outline-primary" title="عرض السجل والنزلاء">
                                            <i class="fas fa-eye me-1"></i> تفاصيل
                                        </a>
                                        @if(auth()->user()->hasRole('admin'))
                                        <a href="{{ route('rooms.edit', $room) }}" class="btn btn-outline-secondary" title="تعديل الغرفة">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endif
                                    </div>

                                    @if(auth()->user()->hasRole(['admin', 'surgery_staff', 'receptionist']))
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-sync-alt me-1"></i> الحالة
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <button type="button" class="dropdown-item py-1 text-success change-room-status" data-id="{{ $room->id }}" data-status="available">
                                                    <i class="fas fa-check-circle me-2"></i> متاحة للحجز
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item py-1 text-danger change-room-status" data-id="{{ $room->id }}" data-status="occupied">
                                                    <i class="fas fa-bed me-2"></i> مشغولة بمريض
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item py-1 text-warning change-room-status" data-id="{{ $room->id }}" data-status="maintenance">
                                                    <i class="fas fa-tools me-2"></i> صيانة مؤقتة
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @empty
        <div class="alert alert-info text-center py-5 border-0 shadow-sm" style="border-radius: 12px;">
            <i class="fas fa-door-closed fa-3x mb-3 text-secondary opacity-50"></i>
            <h5 class="fw-bold">لا توجد غرف مطابقة</h5>
            <p class="text-muted mb-0">جرب تغيير معايير التصفية أو أضف غرف جديدة للنظام</p>
        </div>
        @endforelse
    </div>

    <!-- عرض الجدول المفصل (Table View) -->
    <div id="viewTableContainer" class="d-none">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>رقم الغرفة</th>
                                <th>الطابق</th>
                                <th>نوع الغرفة</th>
                                <th>السعر اليومي</th>
                                <th>الأسرّة</th>
                                <th>المزايا</th>
                                <th>الحالة</th>
                                <th style="width: 140px;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rooms as $room)
                            @php
                                $statusBadge = match($room->status) {
                                    'available' => ['class' => 'bg-success', 'text' => 'متاحة للحجز'],
                                    'occupied' => ['class' => 'bg-danger', 'text' => 'مشغولة'],
                                    'maintenance' => ['class' => 'bg-warning text-dark', 'text' => 'صيانة'],
                                    default => ['class' => 'bg-secondary', 'text' => $room->status_name]
                                };
                            @endphp
                            <tr class="room-table-row" data-number="{{ $room->room_number }}" data-floor="{{ $room->floor }}">
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-bold text-primary fs-6">{{ $room->room_number }}</td>
                                <td>{{ $room->floor ?: '-' }}</td>
                                <td>
                                    @if($room->room_type === 'vip')
                                        <span class="badge bg-warning text-dark border border-warning">⭐ VIP</span>
                                    @else
                                        <span class="badge bg-light text-dark border">عادية</span>
                                    @endif
                                </td>
                                <td class="fw-bold text-success">{{ number_format($room->daily_fee, 0) }} د.ع</td>
                                <td>{{ $room->beds_count }}</td>
                                <td>
                                    @if($room->has_bathroom) <i class="fas fa-bath text-info me-1" title="حمام"></i> @endif
                                    @if($room->has_ac) <i class="fas fa-snowflake text-primary me-1" title="تكييف"></i> @endif
                                    @if($room->has_tv) <i class="fas fa-tv text-secondary me-1" title="TV"></i> @endif
                                </td>
                                <td>
                                    <span class="badge {{ $statusBadge['class'] }} status-pill-{{ $room->id }} px-2 py-1">
                                        {{ $statusBadge['text'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('rooms.show', $room) }}" class="btn btn-outline-primary" title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if(auth()->user()->hasRole('admin'))
                                        <a href="{{ route('rooms.edit', $room) }}" class="btn btn-outline-secondary" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">لا توجد غرف</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
    // 1. التبديل بين الكروت والجدول
    const btnGrid = document.getElementById('btnViewGrid');
    const btnTable = document.getElementById('btnViewTable');
    const gridContainer = document.getElementById('viewGridContainer');
    const tableContainer = document.getElementById('viewTableContainer');

    btnGrid.addEventListener('click', function() {
        btnGrid.classList.add('active');
        btnTable.classList.remove('active');
        gridContainer.classList.remove('d-none');
        tableContainer.classList.add('d-none');
    });

    btnTable.addEventListener('click', function() {
        btnTable.classList.add('active');
        btnGrid.classList.remove('active');
        tableContainer.classList.remove('d-none');
        gridContainer.classList.add('d-none');
    });

    // 2. البحث الفوري اللحظي
    const searchInput = document.getElementById('roomLiveSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const val = this.value.toLowerCase().trim();

            // فحص الكروت
            document.querySelectorAll('.room-card-item').forEach(item => {
                const text = item.innerText.toLowerCase();
                item.style.display = text.includes(val) ? '' : 'none';
            });

            // فحص صفوف الجدول
            document.querySelectorAll('.room-table-row').forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(val) ? '' : 'none';
            });
        });
    }

    // 3. تغيير حالة الغرفة عبر AJAX
    const toastEl = document.getElementById('statusToast');
    const toastMessage = document.getElementById('toastMessage');
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });

    document.querySelectorAll('.change-room-status').forEach(btn => {
        btn.addEventListener('click', function() {
            const roomId = this.dataset.id;
            const newStatus = this.dataset.status;

            fetch(`/rooms/${roomId}/change-status`, {
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
                    // تحديث البادج في الكارت
                    const pills = document.querySelectorAll(`.status-pill-${roomId}`);
                    pills.forEach(pill => {
                        pill.className = `badge bg-${data.status_color} status-pill-${roomId} px-2 py-1`;
                    });

                    const texts = document.querySelectorAll(`.status-text-${roomId}`);
                    texts.forEach(txt => {
                        txt.innerText = data.status_name;
                    });

                    toastMessage.innerText = data.message;
                    toastEl.className = 'toast align-items-center text-white bg-success border-0';
                    toast.show();
                } else {
                    toastMessage.innerText = 'فشل تغيير الحالة';
                    toastEl.className = 'toast align-items-center text-white bg-danger border-0';
                    toast.show();
                }
            })
            .catch(err => {
                console.error(err);
                toastMessage.innerText = 'حدث خطأ في الاتصال بالسيرفر';
                toastEl.className = 'toast align-items-center text-white bg-danger border-0';
                toast.show();
            });
        });
    });
});
</script>

<style>
.room-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1) !important;
}
@media print {
    .btn, .dropdown, #btnViewGrid, #btnViewTable, .input-group, form {
        display: none !important;
    }
}
</style>
@endsection
