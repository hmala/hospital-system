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
                    <h4 class="mb-0 fw-bold {{ $isVip ? 'text-dark' : 'text-primary' }}">
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
