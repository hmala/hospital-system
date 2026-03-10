@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- العنوان والأزرار -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">
                        <i class="fas fa-bed me-2 text-primary"></i>
                        لوحة الغرف
                    </h4>
                </div>
                @if(auth()->user()->hasRole('admin'))
                <a href="{{ route('rooms.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i>
                    إضافة غرفة
                </a>
                @endif
            </div>
        </div>
    </div>

    <!-- إحصائيات مختصرة ودليل الألوان -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="badge bg-primary fs-6 me-2">{{ $stats['total'] }}</span>
                    <small class="text-muted">إجمالي</small>
                </div>
                <div class="col-auto">
                    <span class="badge bg-success me-1">●</span>
                    <small>متاحة ({{ $stats['available'] }})</small>
                </div>
                <div class="col-auto">
                    <span class="badge bg-danger me-1">●</span>
                    <small>محجوزة ({{ $stats['occupied'] }})</small>
                </div>
                <div class="col-auto">
                    <span class="badge bg-warning me-1">●</span>
                    <small>صيانة ({{ $stats['maintenance'] }})</small>
                </div>
                <div class="col-auto border-start ps-3">
                    <span class="badge bg-secondary me-1">عادية</span>
                    <span class="badge bg-warning text-dark me-1">VIP</span>
                </div>
                <div class="col-auto border-start ps-3">
                    <small class="text-success fw-bold">40,000</small>
                    <small class="text-muted">عادية |</small>
                    <small class="text-warning fw-bold">100,000</small>
                    <small class="text-muted">VIP</small>
                </div>
            </div>
        </div>
    </div>

    <!-- فلتر سريع -->
    <div class="mb-3">
        <div class="btn-group btn-group-sm" role="group">
            <a href="{{ route('rooms.index') }}" class="btn {{ !request('status') && !request('type') ? 'btn-primary' : 'btn-outline-primary' }}">الكل</a>
            <a href="{{ route('rooms.index', ['status' => 'available']) }}" class="btn {{ request('status') == 'available' ? 'btn-success' : 'btn-outline-success' }}">متاحة</a>
            <a href="{{ route('rooms.index', ['status' => 'occupied']) }}" class="btn {{ request('status') == 'occupied' ? 'btn-danger' : 'btn-outline-danger' }}">محجوزة</a>
            <a href="{{ route('rooms.index', ['status' => 'maintenance']) }}" class="btn {{ request('status') == 'maintenance' ? 'btn-warning' : 'btn-outline-warning' }}">صيانة</a>
        </div>
        <div class="btn-group btn-group-sm ms-2" role="group">
            <a href="{{ route('rooms.index', array_merge(request()->except('type'), ['type' => ''])) }}" class="btn {{ !request('type') ? 'btn-secondary' : 'btn-outline-secondary' }}">الكل</a>
            <a href="{{ route('rooms.index', array_merge(request()->except('type'), ['type' => 'regular'])) }}" class="btn {{ request('type') == 'regular' ? 'btn-secondary' : 'btn-outline-secondary' }}">عادية</a>
            <a href="{{ route('rooms.index', array_merge(request()->except('type'), ['type' => 'vip'])) }}" class="btn {{ request('type') == 'vip' ? 'btn-warning' : 'btn-outline-warning' }}">VIP</a>
        </div>
    </div>

    <!-- عرض الغرف مجمعة حسب الطابق -->
    @php
        $roomsByFloor = $rooms->groupBy('floor');
    @endphp

    @forelse($roomsByFloor as $floor => $floorRooms)
    <div class="card shadow-sm mb-3">
        <div class="card-header py-2 bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-layer-group me-1 text-primary"></i>
                    {{ $floor ?: 'بدون طابق' }}
                </h6>
                <span class="badge bg-primary">{{ $floorRooms->count() }} غرفة</span>
            </div>
        </div>
        <div class="card-body py-2">
            <div class="d-flex flex-wrap gap-2">
                @foreach($floorRooms as $room)
                @php
                    $statusColor = match($room->status) {
                        'available' => 'success',
                        'occupied' => 'danger',
                        'maintenance' => 'warning',
                        default => 'secondary'
                    };
                    $typeColor = $room->room_type === 'vip' ? 'warning' : 'light';
                    $typeBg = $room->room_type === 'vip' ? 'bg-warning bg-opacity-10' : '';
                @endphp
                <div class="room-tile {{ $typeBg }} {{ !$room->is_active ? 'opacity-50' : '' }}" 
                     data-bs-toggle="tooltip" 
                     data-bs-html="true"
                     title="<b>{{ $room->room_number }}</b><br>{{ $room->room_type_name }}<br>{{ number_format($room->daily_fee) }} د.ع/يوم<br>{{ $room->status_name }}"
                     style="border-color: var(--bs-{{ $statusColor }});">
                    
                    <div class="room-number">{{ $room->room_number }}</div>
                    
                    <div class="room-badges">
                        @if($room->room_type === 'vip')
                            <span class="badge bg-warning text-dark" style="font-size: 0.6rem;">VIP</span>
                        @endif
                    </div>
                    
                    <div class="room-status">
                        <span class="status-dot bg-{{ $statusColor }}"></span>
                    </div>
                    
                    <div class="room-actions">
                        <a href="{{ route('rooms.show', $room) }}" class="action-btn" title="عرض">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if(auth()->user()->hasRole('admin'))
                        <a href="{{ route('rooms.edit', $room) }}" class="action-btn" title="تعديل">
                            <i class="fas fa-edit"></i>
                        </a>
                        @endif
                        @if(auth()->user()->hasRole(['admin', 'surgery_staff', 'receptionist']))
                        <div class="dropdown d-inline">
                            <button type="button" class="action-btn dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" style="font-size: 0.8rem;">
                                <li>
                                    <a class="dropdown-item py-1 change-status" href="#" data-room-id="{{ $room->id }}" data-status="available">
                                        <span class="badge bg-success me-1">●</span> متاحة
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-1 change-status" href="#" data-room-id="{{ $room->id }}" data-status="occupied">
                                        <span class="badge bg-danger me-1">●</span> محجوزة
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-1 change-status" href="#" data-room-id="{{ $room->id }}" data-status="maintenance">
                                        <span class="badge bg-warning me-1">●</span> صيانة
                                    </a>
                                </li>
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @empty
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
        <h5>لا توجد غرف</h5>
        <p class="mb-0">لم يتم العثور على غرف تطابق معايير البحث</p>
    </div>
    @endforelse
</div>

<style>
.room-tile {
    width: 80px;
    height: 70px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 4px;
    position: relative;
    background: white;
    transition: all 0.2s ease;
    cursor: default;
}
.room-tile:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10;
}
.room-tile:hover .room-actions {
    opacity: 1;
}
.room-number {
    font-weight: bold;
    font-size: 1rem;
    text-align: center;
    color: #333;
}
.room-badges {
    text-align: center;
    min-height: 16px;
}
.room-status {
    position: absolute;
    top: 4px;
    left: 4px;
}
.status-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}
.room-actions {
    position: absolute;
    bottom: 2px;
    left: 0;
    right: 0;
    text-align: center;
    opacity: 0;
    transition: opacity 0.2s ease;
    background: rgba(255,255,255,0.9);
    padding: 2px;
    border-radius: 0 0 6px 6px;
}
.action-btn {
    color: #6c757d;
    padding: 2px 4px;
    font-size: 0.7rem;
    text-decoration: none;
    border: none;
    background: none;
    cursor: pointer;
}
.action-btn:hover {
    color: #0d6efd;
}
</style>

@section('scripts')
<script>
// تفعيل Tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});

// تغيير الحالة
document.querySelectorAll('.change-status').forEach(function(el) {
    el.addEventListener('click', function(e) {
        e.preventDefault();
        const roomId = this.dataset.roomId;
        const status = this.dataset.status;
        
        console.log('=== Room Status Change ===');
        console.log('Room ID:', roomId);
        console.log('New Status:', status);
        
        // عرض رسالة تأكيد
        const statusNames = {
            'available': 'متاحة',
            'occupied': 'محجوزة',
            'maintenance': 'صيانة'
        };
        
        if (!confirm(`هل أنت متأكد من تغيير حالة الغرفة إلى "${statusNames[status]}"؟`)) {
            console.log('User cancelled status change');
            return;
        }
        
        console.log('Sending request to server...');
        
        fetch(`/rooms/${roomId}/change-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'خطأ في الاستجابة من الخادم');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            
            if (data.success) {
                alert(data.message || 'تم تغيير حالة الغرفة بنجاح');
                console.log('Reloading page...');
                location.reload();
            } else {
                alert('حدث خطأ: ' + (data.message || 'فشل تغيير الحالة'));
                console.error('Error:', data);
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            alert('حدث خطأ أثناء تغيير الحالة: ' + error.message);
        });
    });
});
</script>
@endsection
@endsection
