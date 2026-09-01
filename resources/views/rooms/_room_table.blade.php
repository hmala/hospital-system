<div class="table-responsive">
    <table class="table table-hover align-middle text-center mb-0">
        <thead class="table-light">
            <tr>
                <th style="width: 45px;">#</th>
                <th>رقم الغرفة</th>
                <th>الطابق</th>
                <th>نوع الغرفة</th>
                <th>الأجرة اليومية</th>
                <th>عدد الأسرّة</th>
                <th>المزايا</th>
                <th>حالة الغرفة</th>
                <th>النزيل الحالي (المريض)</th>
                <th style="width: 150px;">الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($roomList as $room)
            @php
                $occupant = $room->current_occupant;
                $statusBadge = match($room->status) {
                    'available' => ['class' => 'bg-success', 'text' => 'متاحة للحجز'],
                    'occupied' => ['class' => 'bg-danger', 'text' => 'مشغولة'],
                    'maintenance' => ['class' => 'bg-warning text-dark', 'text' => 'قيد الصيانة'],
                    default => ['class' => 'bg-secondary', 'text' => $room->status_name]
                };
                $rowClass = match($room->status) {
                    'occupied' => 'table-danger bg-danger bg-opacity-10',
                    'maintenance' => 'table-warning bg-warning bg-opacity-10',
                    default => ''
                };
            @endphp
            <tr class="room-table-row row-room-{{ $room->id }} {{ $rowClass }}" data-number="{{ $room->room_number }}" data-floor="{{ $room->floor }}">
                <td>{{ $loop->iteration }}</td>
                <td>
                    <a href="{{ route('rooms.show', $room) }}" class="fw-bold text-primary text-decoration-none fs-6">
                        <span class="badge bg-light text-primary border border-primary px-3 py-1">
                            <i class="fas fa-door-closed me-1"></i>غرفة {{ $room->room_number }}
                        </span>
                    </a>
                </td>
                <td class="text-muted">{{ $room->floor ?: 'بدون طابق' }}</td>
                <td>
                    @if($room->room_type === 'vip')
                        <span class="badge bg-warning text-dark border border-warning px-2 py-1">
                            <i class="fas fa-crown me-1"></i>VIP
                        </span>
                    @else
                        <span class="badge bg-light text-secondary border px-2 py-1">عادية</span>
                    @endif
                </td>
                <td>
                    <strong class="text-success fs-6">{{ number_format($room->daily_fee, 0) }}</strong>
                    <small class="text-muted" style="font-size: 0.75rem;">د.ع</small>
                </td>
                <td>
                    <span class="badge bg-light text-dark border">
                        <i class="fas fa-bed me-1 text-secondary"></i>{{ $room->beds_count }}
                    </span>
                </td>
                <td>
                    <div class="d-flex justify-content-center gap-1">
                        <span class="badge {{ $room->has_bathroom ? 'bg-info bg-opacity-10 text-info border border-info' : 'bg-light text-muted opacity-50 border' }}" title="حمام خاص">
                            <i class="fas fa-bath"></i>
                        </span>
                        <span class="badge {{ $room->has_ac ? 'bg-primary bg-opacity-10 text-primary border border-primary' : 'bg-light text-muted opacity-50 border' }}" title="تكييف">
                            <i class="fas fa-snowflake"></i>
                        </span>
                        <span class="badge {{ $room->has_tv ? 'bg-secondary bg-opacity-10 text-secondary border border-secondary' : 'bg-light text-muted opacity-50 border' }}" title="تلفاز">
                            <i class="fas fa-tv"></i>
                        </span>
                    </div>
                </td>
                <td>
                    <span class="badge {{ $statusBadge['class'] }} status-pill-{{ $room->id }} px-2 py-1">
                        {{ $statusBadge['text'] }}
                    </span>
                </td>
                <td>
                    @if($occupant)
                        <div class="d-inline-flex align-items-center bg-white px-2 py-1 rounded border shadow-sm text-start">
                            <i class="fas fa-user-injured text-danger me-2"></i>
                            <div>
                                <strong class="text-dark d-block" style="font-size: 0.85rem;">{{ $occupant->patient_name }}</strong>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    <span class="badge bg-{{ $occupant->type_color }} bg-opacity-25 text-{{ $occupant->type_color }}">{{ $occupant->type }}</span>
                                    | د. {{ $occupant->doctor_name }}
                                </small>
                            </div>
                        </div>
                    @elseif($room->status === 'occupied')
                        <span class="badge bg-warning text-dark border border-warning p-1" title="مؤشرة محجوزة لكن بدون مريض مسجل في العمليات أو الرقود">
                            <i class="fas fa-exclamation-circle me-1"></i>محجوزة يدوياً (شاغرة)
                        </span>
                    @elseif($room->status === 'available')
                        <span class="text-success small fw-semibold">
                            <i class="fas fa-check-circle me-1"></i>جاهزة للحجز
                        </span>
                    @else
                        <span class="text-muted small">
                            <i class="fas fa-tools me-1"></i>صيانة
                        </span>
                    @endif
                </td>
                <td>
                    <div class="d-flex justify-content-center align-items-center gap-1">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('rooms.show', $room) }}" class="btn btn-outline-primary" title="عرض السجل والنزلاء">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if(auth()->user()->hasRole('admin'))
                            <a href="{{ route('rooms.edit', $room) }}" class="btn btn-outline-secondary" title="تعديل الغرفة">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endif
                        </div>

                        @if(auth()->user()->hasRole(['admin', 'surgery_staff', 'receptionist']))
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-dark dropdown-toggle" type="button" data-bs-toggle="dropdown" title="تغيير الحالة سريعاً">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 0.85rem;">
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
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center py-5 text-muted">
                    <i class="fas fa-door-closed fa-3x mb-3 text-secondary opacity-50"></i>
                    <h5 class="fw-bold">لا توجد غرف في هذا الطابق</h5>
                    <p class="small text-muted mb-0">جرب تعديل الفلاتر أو إضافة غرف جديدة</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
