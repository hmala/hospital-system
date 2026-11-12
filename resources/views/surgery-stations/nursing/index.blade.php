@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-gradient bg-primary text-white p-3 rounded-top-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px;">
                            <i class="fas fa-user-nurse fs-5"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">محطة التمريض والرقود</h5>
                            <small class="text-white-50">متابعة المرضى، إعطاء العلاجات، وسحب مستلزمات عهدة الطابق</small>
                        </div>
                    </div>

                    {{-- شارات الطابق والعهدة المعتمدة --}}
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-white bg-opacity-20 text-white border border-white border-opacity-25 rounded-pill px-3 py-2 fs-6">
                            <i class="fas fa-warehouse text-warning me-1"></i> {{ $currentLocation?->name ?? 'مخزن الطابق' }}
                        </span>
                        @if($isFloorCustodian)
                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2 fw-bold shadow-xs">
                                <i class="fas fa-key me-1"></i> مسؤولة عهدة المخزن
                            </span>
                        @else
                            <span class="badge bg-info bg-opacity-25 text-white border border-info border-opacity-50 rounded-pill px-3 py-2 fw-medium">
                                <i class="fas fa-user-check me-1"></i> سحب وصرف تمريضي
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Floor Filter Bar (شريط تصفية الطوابق السريع) -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 p-2.5 bg-light rounded-4 border">
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            <span class="small fw-bold text-muted ms-1"><i class="fas fa-building text-primary me-1"></i> الطابق:</span>
                            <a href="{{ route('nursing-station.index', ['floor' => 'all']) }}" 
                               class="btn btn-sm rounded-pill px-3 py-1 fw-bold {{ empty($selectedFloor) ? 'btn-primary text-white shadow-xs' : 'btn-outline-secondary' }}">
                                كافة الطوابق
                            </a>
                            @foreach($availableFloors as $floorName)
                                <a href="{{ route('nursing-station.index', ['floor' => $floorName]) }}" 
                                   class="btn btn-sm rounded-pill px-3 py-1 fw-bold {{ $selectedFloor === $floorName ? 'btn-primary text-white shadow-xs' : 'btn-outline-secondary' }}">
                                    {{ $floorName }}
                                    @if($currentLocation && (str_contains($currentLocation->name, $floorName) || str_contains($floorName, $currentLocation->name)))
                                        <span class="badge bg-warning text-dark ms-1 rounded-pill" style="font-size: 0.7rem;">طابقك</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                        @if($selectedFloor)
                            <div class="text-muted small">
                                <i class="fas fa-filter text-primary me-1"></i> يتم عرض مرضى <strong>{{ $selectedFloor }}</strong> حالياً
                            </div>
                        @endif
                    </div>

                    <!-- Tabs Switcher -->
                    <ul class="nav nav-pills mb-4 gap-2 bg-light p-1.5 rounded-pill shadow-sm overflow-auto flex-nowrap" id="nursingTabs" role="tablist" style="width: fit-content; max-width: 100%;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill fw-bold px-4 py-2 text-nowrap" id="pre-op-patients-tab" data-bs-toggle="tab" data-bs-target="#pre-op-patients" type="button" role="tab" aria-selected="true">
                                <i class="fas fa-notes-medical me-1 text-primary"></i> حالات التهيئة قبل الصالة
                                <span class="badge bg-primary text-white rounded-pill ms-1.5">{{ $preOpSurgeries->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold px-4 py-2 text-nowrap" id="post-op-patients-tab" data-bs-toggle="tab" data-bs-target="#post-op-patients" type="button" role="tab" aria-selected="false">
                                <i class="fas fa-procedures me-1 text-info"></i> حالات المتابعة بعد الصالة
                                <span class="badge bg-info text-white rounded-pill ms-1.5">{{ $postOpSurgeries->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold px-4 py-2 text-nowrap" id="completed-patients-tab" data-bs-toggle="tab" data-bs-target="#completed-patients" type="button" role="tab" aria-selected="false">
                                <i class="fas fa-archive me-1 text-success"></i> أرشيف الحالات المكتملة
                                <span class="badge bg-success text-white rounded-pill ms-1.5">{{ $completedSurgeries->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold px-4 py-2 text-nowrap" id="floor-store-tab" data-bs-toggle="tab" data-bs-target="#floor-store" type="button" role="tab" aria-selected="false">
                                <i class="fas fa-boxes me-1 text-warning"></i> مخزن وعُهدة الطابق
                                <span class="badge bg-warning text-dark rounded-pill ms-1.5">{{ $stockBatches->count() }}</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="nursingTabsContent">
                        <!-- TAB 1: PRE-OP PATIENTS -->
                        <div class="tab-pane fade show active" id="pre-op-patients" role="tabpanel" aria-labelledby="pre-op-patients-tab">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="py-3">رقم الملف</th>
                                            <th class="py-3">اسم المريض</th>
                                            <th class="py-3">العملية</th>
                                            <th class="py-3">التاريخ والوقت المحدد</th>
                                            <th class="py-3">الممرض/ة المسؤول</th>
                                            <th class="py-3">حالة المحطة</th>
                                            <th class="py-3 text-center">الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($preOpSurgeries as $surgery)
                                        <tr>
                                            <td class="fw-bold text-primary">{{ $surgery->patient?->file_number ?? $surgery->id }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex justify-content-center align-items-center me-2 shadow-xs" style="width: 38px; height: 38px;">
                                                        <i class="fas fa-user-injured fs-6"></i>
                                                    </div>
                                                    <div>
                                                        <span class="fw-bold text-dark">{{ $surgery->patient?->user?->name ?? $surgery->patient?->name ?? 'غير معروف' }}</span>
                                                        @if($surgery->room)
                                                            <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                                                                <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 0.78rem;">
                                                                    <i class="fas fa-bed text-primary me-1"></i> غرفة {{ $surgery->room->room_number }} ({{ $surgery->room->floor }})
                                                                </span>
                                                                @if($surgery->room_checkin_at)
                                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.72rem;" title="تاريخ ووقت الاستلام: {{ $surgery->room_checkin_at->format('Y-m-d h:i A') }}">
                                                                        <i class="fas fa-check-circle me-1"></i> تم الدخول ({{ $surgery->room_checkin_at->format('h:i A') }})
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
                                                                        <i class="fas fa-hourglass-start me-1"></i> بانتظار استلام الغرفة
                                                                    </span>
                                                                @endif
                                                                @if($surgery->discharged_at)
                                                                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
                                                                        <i class="fas fa-sign-out-alt me-1"></i> تم التخريج
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2.5 py-1.5">{{ $surgery->surgery_name }}</span>
                                            </td>
                                            <td>
                                                <div>
                                                    <span class="text-dark d-block"><i class="far fa-calendar-alt text-muted me-1"></i> {{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '-' }}</span>
                                                    <small class="text-muted"><i class="far fa-clock text-muted me-1"></i> {{ $surgery->scheduled_time ? \Carbon\Carbon::parse($surgery->scheduled_time)->format('h:i A') : '-' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                @if($surgery->nursingStation?->nurse?->full_name)
                                                    <span class="text-secondary"><i class="fas fa-user-nurse text-muted me-1"></i> {{ $surgery->nursingStation->nurse->full_name }}</span>
                                                @else
                                                    <span class="text-muted italic"><i class="fas fa-user-nurse text-muted me-1"></i> لم يحدد بعد</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($surgery->status === 'waiting')
                                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-1.5 fw-bold shadow-xs">
                                                        <i class="fas fa-clock me-1"></i> بانتظار الصالة (مسجل دخول)
                                                    </span>
                                                @elseif($surgery->status === 'in_progress')
                                                    <span class="badge bg-primary text-white rounded-pill px-3 py-1.5 fw-bold shadow-xs">
                                                        <i class="fas fa-spinner fa-spin me-1"></i> قيد التجهيز / الصالة
                                                    </span>
                                                @elseif($surgery->nursingStation?->status === 'in_progress')
                                                    <span class="badge bg-info text-dark rounded-pill px-3 py-1.5 fw-bold">
                                                        <i class="fas fa-spinner fa-spin me-1"></i> تهيئة جارية
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1.5 fw-medium">
                                                        <i class="far fa-calendar-check me-1"></i> مجدولة
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($surgery->room_id && !$surgery->room_checkin_at)
                                                    <form action="{{ route('nursing-station.room-checkin', $surgery) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من تسجيل استلام المريض للغرفة الآن؟ سيبدأ احتساب الإقامة الفندقية.')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 py-1.5 shadow-sm fw-bold">
                                                            <i class="fas fa-bed me-1"></i> تسجيل دخول الغرفة
                                                        </button>
                                                    </form>
                                                @else
                                                    <div class="d-inline-flex align-items-center gap-1">
                                                        <a href="{{ route('nursing-station.show', $surgery) }}" class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 shadow-sm">
                                                            <i class="fas fa-eye me-1"></i> عرض وتعديل
                                                        </a>
                                                        @if($surgery->room_id && $surgery->room_checkin_at && !$surgery->discharged_at)
                                                            <form action="{{ route('nursing-station.room-checkout', $surgery) }}" method="POST" class="d-inline" onsubmit="return confirm('تأكيد تخريج المريض من الغرفة وإخلاء السرير؟ سيتم إيقاف احتساب الإقامة وتحرير الغرفة.')">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1.5 shadow-sm" title="تخريج المريض وإخلاء السرير">
                                                                    <i class="fas fa-sign-out-alt me-1"></i> تخريج
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                                                <p class="mb-0 fw-semibold">لا توجد حالات تهيئة قبل الصالة حالياً</p>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 2: POST-OP PATIENTS -->
                        <div class="tab-pane fade" id="post-op-patients" role="tabpanel" aria-labelledby="post-op-patients-tab">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="py-3">رقم الملف</th>
                                            <th class="py-3">اسم المريض</th>
                                            <th class="py-3">العملية</th>
                                            <th class="py-3">التاريخ والوقت المحدد</th>
                                            <th class="py-3">الممرض/ة المسؤول</th>
                                            <th class="py-3">حالة المحطة</th>
                                            <th class="py-3 text-center">الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($postOpSurgeries as $surgery)
                                        <tr>
                                            <td class="fw-bold text-primary">{{ $surgery->patient?->file_number ?? $surgery->id }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-2 d-flex justify-content-center align-items-center me-2 shadow-xs" style="width: 38px; height: 38px;">
                                                        <i class="fas fa-user-injured fs-6"></i>
                                                    </div>
                                                    <div>
                                                        <span class="fw-bold text-dark">{{ $surgery->patient?->user?->name ?? $surgery->patient?->name ?? 'غير معروف' }}</span>
                                                        @if($surgery->room)
                                                            <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                                                                <span class="badge bg-light text-info border border-info-subtle rounded-pill px-2 py-0.5" style="font-size: 0.78rem;">
                                                                    <i class="fas fa-bed text-info me-1"></i> غرفة {{ $surgery->room->room_number }} ({{ $surgery->room->floor }})
                                                                </span>
                                                                @if($surgery->room_checkin_at)
                                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.72rem;" title="تاريخ ووقت الاستلام: {{ $surgery->room_checkin_at->format('Y-m-d h:i A') }}">
                                                                        <i class="fas fa-check-circle me-1"></i> تم الدخول ({{ $surgery->room_checkin_at->format('h:i A') }})
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
                                                                        <i class="fas fa-hourglass-start me-1"></i> بانتظار استلام الغرفة
                                                                    </span>
                                                                @endif
                                                                @if($surgery->discharged_at)
                                                                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
                                                                        <i class="fas fa-sign-out-alt me-1"></i> تم التخريج
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-info border border-info-subtle rounded-pill px-2.5 py-1.5">{{ $surgery->surgery_name }}</span>
                                            </td>
                                            <td>
                                                <div>
                                                    <span class="text-dark d-block"><i class="far fa-calendar-alt text-muted me-1"></i> {{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '-' }}</span>
                                                    <small class="text-muted"><i class="far fa-clock text-muted me-1"></i> {{ $surgery->scheduled_time ? \Carbon\Carbon::parse($surgery->scheduled_time)->format('h:i A') : '-' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                @if($surgery->nursingStation?->nurse?->full_name)
                                                    <span class="text-secondary"><i class="fas fa-user-nurse text-muted me-1"></i> {{ $surgery->nursingStation->nurse->full_name }}</span>
                                                @else
                                                    <span class="text-muted italic"><i class="fas fa-user-nurse text-muted me-1"></i> لم يحدد بعد</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($surgery->nursingStation?->status === 'in_progress')
                                                    <span class="badge bg-info text-dark rounded-pill px-3 py-1.5 fw-bold shadow-xs">
                                                        <i class="fas fa-heartbeat me-1"></i> متابعة ورعاية جارية
                                                    </span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold shadow-xs">
                                                        <i class="fas fa-check-circle me-1"></i> خرج من صالة العمليات
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($surgery->room_id && !$surgery->room_checkin_at)
                                                    <form action="{{ route('nursing-station.room-checkin', $surgery) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من تسجيل استلام المريض للغرفة الآن؟ سيبدأ احتساب الإقامة الفندقية.')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 py-1.5 shadow-sm fw-bold">
                                                            <i class="fas fa-bed me-1"></i> تسجيل دخول الغرفة
                                                        </button>
                                                    </form>
                                                @else
                                                    <div class="d-inline-flex align-items-center gap-1">
                                                        <a href="{{ route('nursing-station.show', $surgery) }}" class="btn btn-sm btn-info rounded-pill px-3 py-1.5 text-white shadow-sm">
                                                            <i class="fas fa-eye me-1"></i> عرض وتعديل
                                                        </a>
                                                        @if($surgery->room_id && $surgery->room_checkin_at && !$surgery->discharged_at)
                                                            <form action="{{ route('nursing-station.room-checkout', $surgery) }}" method="POST" class="d-inline" onsubmit="return confirm('تأكيد تخريج المريض من الغرفة وإخلاء السرير؟ سيتم إيقاف احتساب الإقامة وتحرير الغرفة.')">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1.5 shadow-sm" title="تخريج المريض وإخلاء السرير">
                                                                    <i class="fas fa-sign-out-alt me-1"></i> تخريج
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                                                <p class="mb-0 fw-semibold">لا توجد حالات متابعة بعد الصالة حالياً</p>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 3: COMPLETED PATIENTS -->
                        <div class="tab-pane fade" id="completed-patients" role="tabpanel" aria-labelledby="completed-patients-tab">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="py-3">رقم الملف</th>
                                            <th class="py-3">اسم المريض</th>
                                            <th class="py-3">العملية</th>
                                            <th class="py-3">تاريخ العملية</th>
                                            <th class="py-3">الممرض/ة المسؤول</th>
                                            <th class="py-3">حالة المحطة</th>
                                            <th class="py-3 text-center">الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($completedSurgeries as $surgery)
                                        <tr>
                                            <td class="fw-bold text-primary">{{ $surgery->patient?->file_number ?? $surgery->id }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 d-flex justify-content-center align-items-center me-2 shadow-xs" style="width: 38px; height: 38px;">
                                                        <i class="fas fa-user-injured fs-6"></i>
                                                    </div>
                                                    <div>
                                                        <span class="fw-bold text-dark">{{ $surgery->patient?->user?->name ?? $surgery->patient?->name ?? 'غير معروف' }}</span>
                                                        @if($surgery->room)
                                                            <div class="mt-0.5">
                                                                <span class="badge bg-light text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.78rem;">
                                                                    <i class="fas fa-bed text-success me-1"></i> غرفة {{ $surgery->room->room_number }} ({{ $surgery->room->floor }})
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2.5 py-1.5">{{ $surgery->surgery_name }}</span>
                                            </td>
                                            <td>
                                                <span class="text-dark"><i class="far fa-calendar-alt text-muted me-1"></i> {{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '-' }}</span>
                                            </td>
                                            <td>
                                                <span class="text-secondary"><i class="fas fa-user-nurse text-muted me-1"></i> {{ $surgery->nursingStation?->nurse?->full_name ?? '-' }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success rounded-pill px-3 py-2">
                                                    <i class="fas fa-check-circle me-1"></i> مكتملة
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('nursing-station.show', $surgery) }}" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1.5 shadow-sm">
                                                    <i class="fas fa-eye me-1"></i> عرض التفاصيل
                                                </a>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="fas fa-archive fa-3x mb-3 text-secondary opacity-50"></i>
                                                <p class="mb-0 fw-semibold">لا توجد حالات مكتملة في الأرشيف حالياً</p>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 4: FLOOR STORE & CUSTODY (مخزن وعُهدة الطابق) -->
                        <div class="tab-pane fade" id="floor-store" role="tabpanel" aria-labelledby="floor-store-tab">
                            {{-- بطاقة الترويسة والإجراءات السريعة للمخزن --}}
                            <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
                                <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-warning bg-opacity-25 text-warning-emphasis d-flex align-items-center justify-content-center shadow-xs" style="width: 48px; height: 48px;">
                                            <i class="fas fa-boxes fa-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 fw-bold text-dark">دولاب ومخزن: {{ $currentLocation?->name ?? 'الطابق' }}</h6>
                                            <p class="text-muted small mb-0 d-flex flex-wrap align-items-center gap-2">
                                                <span>المسؤول عن الطلبيات:</span>
                                                @if($isFloorCustodian)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-0.5">
                                                        <i class="fas fa-check-circle me-1"></i> حسابك الحالي مخول بالطلب
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-0.5">مخصص لمسؤول عهدة الطابق</span>
                                                @endif
                                                <span>• إجمالي المواد المتوفرة: <strong class="text-primary">{{ $stockBatches->sum('current_qty') }}</strong> وحدة</span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        @if($isFloorCustodian)
                                            <a href="{{ route('stock-transfers.create', ['to_location_id' => $currentLocation?->id]) }}" class="btn btn-warning rounded-pill px-3 py-2 fw-bold shadow-sm text-dark">
                                                <i class="fas fa-plus-circle me-1"></i> + طلب تعزيز مواد
                                            </a>
                                            <a href="{{ route('stock-transfers.returns.create', ['from_location_id' => $currentLocation?->id, 'from_nursing' => 1]) }}" class="btn btn-outline-danger rounded-pill px-3 py-2 fw-bold shadow-xs">
                                                <i class="fas fa-undo-alt me-1"></i> إرجاع عهدة للمخزن الرئيسي
                                            </a>
                                        @else
                                            <button class="btn btn-light rounded-pill px-3 py-2 text-muted border shadow-xs" disabled title="خاص بمسؤول عهدة الطابق فقط">
                                                <i class="fas fa-lock me-1"></i> طلب المواد مخصص لمسؤول العهدة
                                            </button>
                                        @endif
                                        <a href="{{ route('stock-transfers.requests.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2 shadow-xs">
                                            <i class="fas fa-history me-1"></i> سجل الطلبيات
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4">
                                {{-- 1. جدول رصيد المواد في عهدة الطابق --}}
                                <div class="col-lg-8">
                                    <div class="card border-0 rounded-4 shadow-sm h-100 bg-white">
                                        <div class="card-header bg-white py-3 px-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-boxes text-primary fs-5"></i>
                                                <h6 class="mb-0 fw-bold text-dark">المستلزمات والمواد المتوفرة في عهدة الطابق</h6>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-primary text-white rounded-pill px-3 py-1.5" id="stockItemsCountBadge">{{ $stockBatches->count() }} مادة</span>
                                            </div>
                                        </div>

                                        {{-- شريط البحث اللحظي وشارات التصفية --}}
                                        <div class="p-3 bg-light border-bottom">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-md-6">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                                                        <input type="text" id="floorStockSearchInput" class="form-control bg-white border-start-0 ps-0" placeholder="بحث باسم المادة، الباركود، أو رقم الوجبة...">
                                                        <button class="btn btn-outline-secondary border-start-0 bg-white" type="button" id="clearFloorStockSearch" style="display:none;">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 d-flex align-items-center gap-1.5 flex-wrap justify-content-md-end">
                                                    <span class="text-muted small me-1">تصفية:</span>
                                                    <button type="button" class="btn btn-xs btn-dark rounded-pill px-2.5 py-1 stock-filter-btn active" data-filter="all">الكل</button>
                                                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2.5 py-1 stock-filter-btn" data-filter="supplies">🩹 مستلزمات</button>
                                                    <button type="button" class="btn btn-xs btn-outline-warning rounded-pill px-2.5 py-1 stock-filter-btn" data-filter="low">⚠️ نفاد</button>
                                                    <button type="button" class="btn btn-xs btn-outline-danger rounded-pill px-2.5 py-1 stock-filter-btn" data-filter="expiring">⏳ قريبة</button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0" id="floorStockTable">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th class="py-2.5 px-3">المستلزم / المادة</th>
                                                            <th class="py-2.5 text-center">الرصيد الحالي</th>
                                                            <th class="py-2.5 text-center">تاريخ الصلاحية</th>
                                                            <th class="py-2.5 text-center">رقم الوجبة</th>
                                                            <th class="py-2.5 text-center">الحالة</th>
                                                            <th class="py-2.5 text-center" style="width: 100px;">إجراء</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="floorStockTableBody">
                                                        @forelse($stockBatches as $batch)
                                                            @php
                                                                $isExpiringSoon = $batch->expiry_date && $batch->expiry_date->diffInDays(now()) <= 60;
                                                                $isLowStock = $batch->current_qty <= ($batch->product?->alert_quantity ?? 5);
                                                                $categoryRaw = mb_strtolower($batch->product?->category ?? '');
                                                                $isMedicine = $batch->product?->isMedication() ?? false;
                                                                $isSupply = $batch->product?->isSupply() ?? false;
                                                            @endphp
                                                            <tr class="stock-row"
                                                                data-name="{{ mb_strtolower($batch->product?->name ?? '') }}"
                                                                data-barcode="{{ mb_strtolower($batch->internal_barcode ?: ($batch->manufacturer_lot_number ?: '#'.$batch->id)) }}"
                                                                data-category="{{ $categoryRaw }}"
                                                                data-is-medicine="{{ $isMedicine ? '1' : '0' }}"
                                                                data-is-supply="{{ $isSupply ? '1' : '0' }}"
                                                                data-is-low="{{ $isLowStock ? '1' : '0' }}"
                                                                data-is-expiring="{{ $isExpiringSoon ? '1' : '0' }}">
                                                                <td class="px-3">
                                                                    <div class="fw-bold text-dark item-name">{{ $batch->product?->name ?? 'مادة غير معروفة' }}</div>
                                                                    <small class="text-muted">{{ $batch->product?->category ?? 'عام' }} • وحدة: {{ $batch->product?->unit ?? 'قطعة' }}</small>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="fw-bolder fs-6 {{ $isLowStock ? 'text-danger' : 'text-success' }}">
                                                                        {{ $batch->current_qty }}
                                                                    </span>
                                                                    <small class="text-muted d-block">{{ $batch->product?->unit ?? 'وحدة' }}</small>
                                                                </td>
                                                                <td class="text-center">
                                                                    @if($batch->expiry_date)
                                                                        <span class="badge {{ $isExpiringSoon ? 'bg-danger text-white' : 'bg-light text-dark border' }} rounded-pill px-2.5 py-1">
                                                                            {{ $batch->expiry_date->format('Y-m-d') }}
                                                                        </span>
                                                                    @else
                                                                        <span class="text-muted">-</span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">
                                                                        {{ $batch->internal_barcode ?: ($batch->manufacturer_lot_number ?: '#'.$batch->id) }}
                                                                    </span>
                                                                </td>
                                                                <td class="text-center">
                                                                    @if($isLowStock)
                                                                        <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1">شارف على النفاد</span>
                                                                    @else
                                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">متوفر</span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center">
                                                                    @if($isFloorCustodian)
                                                                        <a href="{{ route('stock-transfers.returns.create', ['from_location_id' => $currentLocation?->id, 'batch_id' => $batch->id, 'from_nursing' => 1]) }}"
                                                                           class="btn btn-sm btn-outline-warning rounded-pill px-2.5 py-1 fw-bold text-nowrap shadow-xs"
                                                                           title="إرجاع هذه الوجبة للمخزن الرئيسي">
                                                                            <i class="fas fa-undo-alt me-1"></i> إرجاع
                                                                        </a>
                                                                    @else
                                                                        <span class="text-muted small">-</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="6" class="text-center py-5 text-muted">
                                                                    <i class="fas fa-box-open fa-3x mb-3 text-secondary opacity-50"></i>
                                                                    <p class="mb-0 fw-semibold">لا يوجد رصيد مواد مسجل في مخزن هذا الطابق حالياً</p>
                                                                    @if($isFloorCustodian)
                                                                        <a href="{{ route('stock-transfers.create', ['to_location_id' => $currentLocation?->id]) }}" class="btn btn-sm btn-warning rounded-pill mt-3 fw-bold">
                                                                            <i class="fas fa-plus-circle me-1"></i> اطلب أول شحنة تعزيز من المخزن الرئيسي
                                                                        </a>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                        <tr id="noMatchingStockRow" style="display: none;">
                                                            <td colspan="6" class="text-center py-4 text-muted">
                                                                <i class="fas fa-search fa-2x mb-2 text-secondary opacity-50"></i>
                                                                <p class="mb-0 fw-semibold">لا توجد مواد تطابق معايير البحث والتصفية المحددة.</p>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- 2. سجل سحوبات واستهلاك التمريض اليومي --}}
                                <div class="col-lg-4">
                                    <div class="card border-0 rounded-4 shadow-sm h-100 bg-white">
                                        <div class="card-header bg-white py-3 px-3 border-bottom d-flex align-items-center justify-content-between">
                                            <h6 class="mb-0 fw-bold text-dark">
                                                <i class="fas fa-history text-info me-2"></i> سجل السحوبات الأخيرة
                                            </h6>
                                            <span class="badge bg-info text-white rounded-pill px-2.5 py-1 small">مباشر</span>
                                        </div>
                                        <div class="card-body p-3">
                                            @if($recentMovements->count())
                                                <div class="list-group list-group-flush">
                                                    @foreach($recentMovements as $mv)
                                                        <div class="list-group-item px-0 py-2.5 border-bottom">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div>
                                                                    <div class="fw-bold text-dark small">{{ $mv->batch?->product?->name ?? 'مادة مسحوبة' }}</div>
                                                                    <small class="text-muted d-block mt-0.5">
                                                                        <i class="fas fa-user-nurse text-primary me-1"></i> الساحب: <strong>{{ $mv->user?->name ?? 'الكادر التمريضي' }}</strong>
                                                                    </small>
                                                                </div>
                                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill px-2.5 py-1">
                                                                    -{{ $mv->quantity }}
                                                                </span>
                                                            </div>
                                                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                                                <i class="far fa-clock me-1"></i> {{ $mv->created_at ? $mv->created_at->diffForHumans() : '-' }}
                                                            </small>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="text-center py-5 text-muted small">
                                                    <i class="fas fa-clipboard-check fa-2x mb-2 text-secondary opacity-50"></i>
                                                    <p class="mb-0">لا توجد حركات سحب مسجلة حديثاً في هذا الطابق.</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('floorStockSearchInput');
        const clearBtn = document.getElementById('clearFloorStockSearch');
        const filterBtns = document.querySelectorAll('.stock-filter-btn');
        const rows = document.querySelectorAll('#floorStockTableBody .stock-row');
        const noMatchRow = document.getElementById('noMatchingStockRow');
        const countBadge = document.getElementById('stockItemsCountBadge');

        let currentFilter = 'all';

        function filterStockTable() {
            const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
            let visibleCount = 0;

            if (clearBtn) {
                clearBtn.style.display = query.length > 0 ? 'inline-block' : 'none';
            }

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const barcode = row.getAttribute('data-barcode') || '';
                const isMedicine = row.getAttribute('data-is-medicine') === '1';
                const isSupply = row.getAttribute('data-is-supply') === '1';
                const isLow = row.getAttribute('data-is-low') === '1';
                const isExpiring = row.getAttribute('data-is-expiring') === '1';

                const matchesText = !query || name.includes(query) || barcode.includes(query);

                let matchesCategory = true;
                if (currentFilter === 'supplies') {
                    matchesCategory = isSupply;
                } else if (currentFilter === 'low') {
                    matchesCategory = isLow;
                } else if (currentFilter === 'expiring') {
                    matchesCategory = isExpiring;
                }

                if (matchesText && matchesCategory) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (noMatchRow) {
                noMatchRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
            }

            if (countBadge) {
                countBadge.textContent = visibleCount + ' مادة';
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterStockTable);
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                searchInput.value = '';
                filterStockTable();
                searchInput.focus();
            });
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                filterBtns.forEach(b => {
                    b.classList.remove('active', 'btn-dark', 'btn-warning', 'btn-danger');
                    if (b.getAttribute('data-filter') === 'low') {
                        b.classList.add('btn-outline-warning');
                    } else if (b.getAttribute('data-filter') === 'expiring') {
                        b.classList.add('btn-outline-danger');
                    } else {
                        b.classList.add('btn-outline-secondary');
                    }
                });

                this.classList.add('active');
                this.classList.remove('btn-outline-secondary', 'btn-outline-warning', 'btn-outline-danger');
                const filter = this.getAttribute('data-filter');
                if (filter === 'low') {
                    this.classList.add('btn-warning');
                } else if (filter === 'expiring') {
                    this.classList.add('btn-danger');
                } else {
                    this.classList.add('btn-dark');
                }

                currentFilter = filter;
                filterStockTable();
            });
        });
    });
</script>
@endpush
