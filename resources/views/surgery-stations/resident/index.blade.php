@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-gradient bg-primary text-white p-3 rounded-top-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0 fs-5"><i class="fas fa-user-graduate me-2"></i> {{ __('محطة الطبيب المقيم') }}</h5>
                    @if($selectedFloor)
                        <span class="badge bg-white bg-opacity-25 text-white border border-white border-opacity-50 rounded-pill px-3 py-1.5 fs-6">
                            <i class="fas fa-building text-warning me-1"></i> {{ $selectedFloor }}
                        </span>
                    @endif
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                            <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Floor Filter Bar & Search (شريط تصفية الطوابق والبحث السريع) -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 p-2.5 bg-light rounded-4 border">
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            <span class="small fw-bold text-muted ms-1"><i class="fas fa-building text-primary me-1"></i> {{ __('الطابق:') }}</span>
                            <a href="{{ route('resident-station.index', array_filter(['floor' => 'all', 'search' => $search])) }}" 
                               class="btn btn-sm rounded-pill px-3 py-1 fw-bold {{ empty($selectedFloor) ? 'btn-primary text-white shadow-xs' : 'btn-outline-secondary' }}">
                                {{ __('كافة الطوابق') }}
                            </a>
                            @foreach($availableFloors as $floorName)
                                <a href="{{ route('resident-station.index', array_filter(['floor' => $floorName, 'search' => $search])) }}" 
                                   class="btn btn-sm rounded-pill px-3 py-1 fw-bold {{ $selectedFloor === $floorName ? 'btn-primary text-white shadow-xs' : 'btn-outline-secondary' }}">
                                    {{ $floorName }}
                                </a>
                            @endforeach
                        </div>

                        <!-- Modern Integrated Search Box (بحث مدمج أنيق وفوري) -->
                        <div class="d-flex align-items-center ms-auto">
                            <form action="{{ route('resident-station.index') }}" method="GET" class="m-0">
                                @if($selectedFloor)
                                    <input type="hidden" name="floor" value="{{ $selectedFloor }}">
                                @endif
                                <div class="position-relative" style="min-width: 250px; max-width: 320px;">
                                    <input type="text" name="search" id="residentPatientSearchInput" value="{{ $search ?? '' }}" 
                                           class="form-control form-control-sm rounded-pill ps-4 bg-white border border-secondary-subtle shadow-xs" 
                                           style="padding-right: 34px !important; height: 34px; font-size: 0.85rem;"
                                           placeholder="{{ __('بحث بالمريض، الملف، الغرفة...') }}"
                                           autocomplete="off">
                                    <button type="submit" class="btn btn-link position-absolute top-50 translate-middle-y text-primary p-0 border-0 text-decoration-none" style="right: 12px;" title="{{ __('بحث') }}">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    @if(!empty($search))
                                        <a href="{{ route('resident-station.index', array_filter(['floor' => $selectedFloor])) }}" 
                                           class="position-absolute top-50 translate-middle-y text-danger text-decoration-none" 
                                           style="left: 11px;" title="{{ __('إلغاء وتفريغ البحث') }}">
                                            <i class="fas fa-times-circle"></i>
                                        </a>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                    @if($selectedFloor || !empty($search))
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 px-1 small text-muted">
                            <div class="d-flex align-items-center gap-2">
                                @if($selectedFloor)
                                    <span><i class="fas fa-filter text-primary me-1"></i> {{ __('الطابق النشط:') }} <strong>{{ $selectedFloor }}</strong></span>
                                @endif
                                @if(!empty($search))
                                    <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1">
                                        <i class="fas fa-search me-1"></i> {{ __('البحث عن:') }} {{ $search }}
                                    </span>
                                @endif
                            </div>
                            @if(!empty($search) || $selectedFloor)
                                <a href="{{ route('resident-station.index') }}" class="text-danger text-decoration-none fw-semibold">
                                    <i class="fas fa-redo-alt me-1"></i> {{ __('إعادة ضبط الفلاتر') }}
                                </a>
                            @endif
                        </div>
                    @endif

                    <!-- Tabs Switcher -->
                    <ul class="nav nav-pills mb-4 gap-2 bg-light p-1.5 rounded-pill shadow-sm" id="residentTabs" role="tablist" style="width: fit-content;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill fw-bold px-4 py-2" id="pre-op-patients-tab" data-bs-toggle="tab" data-bs-target="#pre-op-patients" type="button" role="tab" aria-selected="true">
                                <i class="fas fa-notes-medical me-1 text-primary"></i> {{ __('حالات التهيئة قبل الصالة') }}
                                <span class="badge bg-primary text-white rounded-pill ms-1.5">{{ $preOpSurgeries->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold px-4 py-2" id="post-op-patients-tab" data-bs-toggle="tab" data-bs-target="#post-op-patients" type="button" role="tab" aria-selected="false">
                                <i class="fas fa-procedures me-1 text-info"></i> {{ __('حالات المتابعة بعد الصالة') }}
                                <span class="badge bg-info text-white rounded-pill ms-1.5">{{ $postOpSurgeries->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold px-4 py-2" id="completed-patients-tab" data-bs-toggle="tab" data-bs-target="#completed-patients" type="button" role="tab" aria-selected="false">
                                <i class="fas fa-archive me-1 text-success"></i> {{ __('أرشيف الحالات المكتملة') }}
                                <span class="badge bg-success text-white rounded-pill ms-1.5">{{ $completedSurgeries->count() }}</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="residentTabsContent">
                        <!-- TAB 1: PRE-OP PATIENTS -->
                        <div class="tab-pane fade show active" id="pre-op-patients" role="tabpanel" aria-labelledby="pre-op-patients-tab">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="py-3">{{ __('رقم الملف') }}</th>
                                            <th class="py-3">{{ __('اسم المريض') }}</th>
                                            <th class="py-3">{{ __('العملية') }}</th>
                                            <th class="py-3">{{ __('التاريخ والوقت المحدد') }}</th>
                                            <th class="py-3">{{ __('الطبيب المقيم') }}</th>
                                            <th class="py-3">{{ __('حالة المحطة') }}</th>
                                            <th class="py-3 text-center">{{ __('الإجراءات') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($preOpSurgeries as $surgery)
                                        @php
                                            $preOpStation = $surgery->preOpResidentStation;
                                            $currentStation = $preOpStation;
                                            $currentPhase = 'تحضير';
                                        @endphp
                                        <tr>
                                            <td class="fw-bold text-primary">{{ $surgery->patient->file_number }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex justify-content-center align-items-center me-2" style="width: 35px; height: 35px;">
                                                        <i class="fas fa-user-injured fs-6"></i>
                                                    </div>
                                                    <div>
                                                        <span class="fw-semibold text-dark">{{ $surgery->patient->user->full_name }}</span>
                                                        @if($surgery->room)
                                                            <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                                                                <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 0.78rem;">
                                                                    <i class="fas fa-bed text-primary me-1"></i> غرفة {{ $surgery->room->room_number }} ({{ $surgery->room->floor }})
                                                                </span>
                                                                @if($surgery->room_checkin_at)
                                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.72rem;" title="تاريخ ووقت الاستلام: {{ $surgery->room_checkin_at->format('Y-m-d h:i A') }}">
                                                                        <i class="fas fa-check-circle me-1"></i> تم الاستلام ({{ $surgery->room_checkin_at->format('h:i A') }})
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
                                                                        <i class="fas fa-hourglass-start me-1"></i> بانتظار استلام ممرض الطابق
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
                                                @if($currentStation?->resident?->user?->full_name)
                                                    <span class="text-secondary"><i class="fas fa-user-md text-muted me-1"></i> {{ $currentStation->resident->user->full_name }}</span>
                                                @else
                                                    <span class="text-muted italic"><i class="fas fa-user-md text-muted me-1"></i> لم يحدد بعد</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(!$currentStation)
                                                    <span class="badge bg-secondary rounded-pill px-3 py-2">في الانتظار</span>
                                                @elseif($currentStation->status === 'pending')
                                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2">
                                                        <i class="fas fa-hourglass-half me-1"></i> معلقة - {{ $currentPhase }}
                                                    </span>
                                                @elseif($currentStation->status === 'in_progress')
                                                    <span class="badge bg-info text-dark rounded-pill px-3 py-2">
                                                        <i class="fas fa-spinner fa-spin me-1"></i> جارية - {{ $currentPhase }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-success rounded-pill px-3 py-2">
                                                        <i class="fas fa-check-circle me-1"></i> مكتملة
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($surgery->room_id && !$surgery->room_checkin_at)
                                                    <button type="button" class="btn btn-sm btn-secondary opacity-75 rounded-pill px-3 py-1.5 shadow-sm fw-semibold" onclick="alert('تنبيه: لا يمكن فتح المحطة السريرية للمريض.\n\nالمريض مخصص للغرفة ({{ $surgery->room?->room_number ?? '-' }}) ولم يقم ممرض الطابق بتسجيل دخوله واستلامه بعد.')" title="بانتظار استلام ممرض الطابق للغرفة">
                                                        <i class="fas fa-lock me-1"></i> بانتظار استلام الغرفة
                                                    </button>
                                                @else
                                                    <a href="{{ route('resident-station.show', $surgery) }}" class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 shadow-sm">
                                                        <i class="fas fa-eye me-1"></i> عرض وتعديل
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                                                <p class="mb-0 fw-semibold">لا توجد عمليات جارية أو معلقة في محطة المقيم حالياً</p>
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
                                            <th class="py-3">الطبيب المقيم</th>
                                            <th class="py-3">حالة المحطة</th>
                                            <th class="py-3 text-center">الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($postOpSurgeries as $surgery)
                                        @php
                                            $postOpStation = $surgery->postOpResidentStation;
                                            $currentStation = $postOpStation;
                                            $currentPhase = 'متابعة';
                                        @endphp
                                        <tr>
                                            <td class="fw-bold text-primary">{{ $surgery->patient->file_number }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-2 d-flex justify-content-center align-items-center me-2" style="width: 35px; height: 35px;">
                                                        <i class="fas fa-user-injured fs-6"></i>
                                                    </div>
                                                    <div>
                                                        <span class="fw-semibold text-dark">{{ $surgery->patient->user->full_name }}</span>
                                                        @if($surgery->room)
                                                            <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                                                                <span class="badge bg-light text-info border border-info-subtle rounded-pill px-2 py-0.5" style="font-size: 0.78rem;">
                                                                    <i class="fas fa-bed text-info me-1"></i> غرفة {{ $surgery->room->room_number }} ({{ $surgery->room->floor }})
                                                                </span>
                                                                @if($surgery->room_checkin_at)
                                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.72rem;" title="تاريخ ووقت الاستلام: {{ $surgery->room_checkin_at->format('Y-m-d h:i A') }}">
                                                                        <i class="fas fa-check-circle me-1"></i> تم الاستلام ({{ $surgery->room_checkin_at->format('h:i A') }})
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
                                                                        <i class="fas fa-hourglass-start me-1"></i> بانتظار استلام ممرض الطابق
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
                                                @if($currentStation?->resident?->user?->full_name)
                                                    <span class="text-secondary"><i class="fas fa-user-md text-muted me-1"></i> {{ $currentStation->resident->user->full_name }}</span>
                                                @else
                                                    <span class="text-muted italic"><i class="fas fa-user-md text-muted me-1"></i> لم يحدد بعد</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(!$currentStation)
                                                    <span class="badge bg-secondary rounded-pill px-3 py-2">في الانتظار</span>
                                                @elseif($currentStation->status === 'pending')
                                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2">
                                                        <i class="fas fa-hourglass-half me-1"></i> معلقة - {{ $currentPhase }}
                                                    </span>
                                                @elseif($currentStation->status === 'in_progress')
                                                    <span class="badge bg-info text-dark rounded-pill px-3 py-2">
                                                        <i class="fas fa-spinner fa-spin me-1"></i> جارية - {{ $currentPhase }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-success rounded-pill px-3 py-2">
                                                        <i class="fas fa-check-circle me-1"></i> مكتملة
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($surgery->room_id && !$surgery->room_checkin_at)
                                                    <button type="button" class="btn btn-sm btn-secondary opacity-75 rounded-pill px-3 py-1.5 shadow-sm fw-semibold" onclick="alert('تنبيه: لا يمكن فتح المحطة السريرية للمريض.\n\nالمريض مخصص للغرفة ({{ $surgery->room?->room_number ?? '-' }}) ولم يقم ممرض الطابق بتسجيل دخوله واستلامه بعد.')" title="بانتظار استلام ممرض الطابق للغرفة">
                                                        <i class="fas fa-lock me-1"></i> بانتظار استلام الغرفة
                                                    </button>
                                                @else
                                                    <a href="{{ route('resident-station.show', $surgery) }}?phase=post_op" class="btn btn-sm btn-info rounded-pill px-3 py-1.5 shadow-sm text-white">
                                                        <i class="fas fa-eye me-1"></i> عرض وتعديل
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                                                <p class="mb-0 fw-semibold">لا توجد عمليات متابعة بعد الصالة حالياً</p>
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
                                            <th class="py-3">المراحل المكتملة</th>
                                            <th class="py-3 text-center">الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($completedSurgeries as $surgery)
                                        @php
                                            $preOpStation = $surgery->preOpResidentStation;
                                            $postOpStation = $surgery->postOpResidentStation;
                                        @endphp
                                        <tr>
                                            <td class="fw-bold text-primary">{{ $surgery->patient->file_number }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 d-flex justify-content-center align-items-center me-2" style="width: 35px; height: 35px;">
                                                        <i class="fas fa-user-injured fs-6"></i>
                                                    </div>
                                                    <span class="fw-semibold">{{ $surgery->patient->user->full_name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2.5 py-1.5">{{ $surgery->surgery_name }}</span>
                                            </td>
                                            <td>
                                                <span class="text-dark"><i class="far fa-calendar-alt text-muted me-1"></i> {{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '-' }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @if($preOpStation && $preOpStation->status === 'completed')
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1.5 small">
                                                            <i class="fas fa-check-circle me-1"></i> التهيئة (ما قبل)
                                                        </span>
                                                    @endif
                                                    @if($postOpStation && $postOpStation->status === 'completed')
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1.5 small">
                                                            <i class="fas fa-check-circle me-1"></i> المتابعة (ما بعد)
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1.5 justify-content-center">
                                                    @if($preOpStation && $preOpStation->status === 'completed')
                                                        <a href="{{ route('resident-station.show', $surgery) }}?phase=pre_op" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 shadow-sm">
                                                            <i class="fas fa-eye me-1"></i> عرض التهيئة
                                                        </a>
                                                    @endif
                                                    @if($postOpStation && $postOpStation->status === 'completed')
                                                        <a href="{{ route('resident-station.show', $surgery) }}?phase=post_op" class="btn btn-sm btn-outline-info rounded-pill px-3 py-1 text-dark shadow-sm">
                                                            <i class="fas fa-eye me-1"></i> عرض المتابعة
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="fas fa-archive fa-3x mb-3 text-secondary opacity-50"></i>
                                                <p class="mb-0 fw-semibold">لا توجد حالات مكتملة في الأرشيف حالياً</p>
                                            </td>
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
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const patientSearchInput = document.getElementById('residentPatientSearchInput');
        if (patientSearchInput) {
            patientSearchInput.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();
                const activePane = document.querySelector('#residentTabsContent .tab-pane.active');
                if (!activePane) return;
                const rows = activePane.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    if (row.querySelector('.text-muted.py-5') || row.children.length === 1) return;
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });
        }
    });
</script>
@endpush
