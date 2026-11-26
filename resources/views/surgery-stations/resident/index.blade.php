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
                            <button class="nav-link active rounded-pill fw-bold px-4 py-2" id="active-surgeries-tab" data-bs-toggle="tab" data-bs-target="#active-surgeries" type="button" role="tab" aria-selected="true">
                                <i class="fas fa-procedures me-1 text-primary"></i> {{ __('مرضى الرقود والعمليات النشطة') }}
                                <span class="badge bg-primary text-white rounded-pill ms-1.5">{{ $activeSurgeries->count() }}</span>
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
                        <!-- TAB 1: UNIFIED ACTIVE PATIENTS (قبل الصالة، داخل الصالة، بعد الصالة) -->
                        <div class="tab-pane fade show active" id="active-surgeries" role="tabpanel" aria-labelledby="active-surgeries-tab">
                            {{-- أزرار التصفية السريعة اللحظية لأطوار المرضى --}}
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 p-2 bg-white rounded-4 border shadow-xs">
                                <div class="d-flex align-items-center gap-1.5 flex-wrap" id="residentPhaseFilterButtons">
                                    <span class="small fw-bold text-muted ms-1"><i class="fas fa-filter text-primary me-1"></i> {{ __('تصنيف الطور:') }}</span>
                                    <button type="button" class="btn btn-sm btn-dark rounded-pill px-3 py-1 fw-bold active resident-phase-btn" data-phase="all">
                                        {{ __('كافة الحالات النشطة') }} ({{ $activeSurgeries->count() }})
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 fw-bold resident-phase-btn" data-phase="pre_op">
                                        <i class="fas fa-hourglass-start me-1"></i> {{ __('قبل الصالة') }} ({{ $preOpSurgeries->count() }})
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 fw-bold resident-phase-btn" data-phase="in_theater">
                                        <i class="fas fa-spinner fa-spin me-1"></i> {{ __('داخل صالة العمليات') }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 fw-bold resident-phase-btn" data-phase="post_op">
                                        <i class="fas fa-procedures me-1"></i> {{ __('بعد الصالة / الرقود') }} ({{ $postOpSurgeries->count() }})
                                    </button>
                                </div>
                                <div class="small text-muted">
                                    <i class="fas fa-info-circle text-primary me-1"></i> {{ __('فرز فوري لحالات المقيم السريرية') }}
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="residentActiveTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="py-3" style="width: 36%;">{{ __('اسم المريض') }}</th>
                                            <th class="py-3" style="width: 22%;">{{ __('المرحلة') }}</th>
                                            <th class="py-3" style="width: 24%;">{{ __('الحالة السريرية') }}</th>
                                            <th class="py-3 text-center" style="width: 18%;">{{ __('الإجراءات') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="residentActiveTableBody">
                                        @forelse($activeSurgeries as $surgery)
                                        @php
                                            $isPostOp = ($surgery->operationTheaterStation && $surgery->operationTheaterStation->status === 'completed')
                                                || ($surgery->anesthesiaStation && $surgery->anesthesiaStation->status === 'completed')
                                                || $surgery->status === 'completed'
                                                || ($surgery->residentStations && $surgery->residentStations->where('phase', 'post_op')->count() > 0);
                                            $isInTheater = !$isPostOp && ($surgery->status === 'in_progress' || ($surgery->operationTheaterStation && $surgery->operationTheaterStation->status === 'in_progress'));
                                            $dataPhase = $isPostOp ? 'post_op' : ($isInTheater ? 'in_theater' : 'pre_op');

                                            $preOpStation = $surgery->preOpResidentStation;
                                            $postOpStation = $surgery->postOpResidentStation;
                                            $currentStation = $isPostOp ? $postOpStation : $preOpStation;
                                            $currentPhase = $isPostOp ? 'متابعة' : 'تحضير';
                                        @endphp
                                        <tr class="resident-active-row" data-phase="{{ $dataPhase }}" data-search="{{ $surgery->patient?->user?->full_name }} {{ $surgery->patient?->file_number }} {{ $surgery->surgery_name }} {{ $surgery->room?->room_number }}">
                                            {{-- 1. اسم المريض --}}
                                            <td>
                                                <div class="d-flex align-items-start gap-2.5">
                                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex justify-content-center align-items-center mt-0.5 flex-shrink-0" style="width: 38px; height: 38px;">
                                                        <i class="fas fa-user-injured fs-6"></i>
                                                    </div>
                                                    <div class="d-flex flex-column gap-1">
                                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                                            <span class="fw-bold text-dark fs-6">{{ $surgery->patient->user->full_name }}</span>
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5 small" title="{{ __('رقم الملف الطبي') }}">
                                                                <i class="fas fa-id-card me-1"></i> {{ $surgery->patient->file_number }}
                                                            </span>
                                                        </div>
                                                        <div class="d-flex flex-wrap gap-1.5 align-items-center">
                                                            <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2.5 py-0.5" style="font-size: 0.78rem;">
                                                                <i class="fas fa-scalpel me-1"></i> {{ $surgery->surgery_name }}
                                                            </span>
                                                            @if($surgery->room)
                                                                <span class="badge bg-light text-secondary border rounded-pill px-2 py-0.5" style="font-size: 0.75rem;">
                                                                    <i class="fas fa-bed me-1 text-primary"></i> غرفة {{ $surgery->room->room_number }} ({{ $surgery->room->floor }})
                                                                </span>
                                                            @endif
                                                            <span class="badge bg-light text-muted border rounded-pill px-2 py-0.5" style="font-size: 0.73rem;">
                                                                <i class="far fa-calendar-alt me-1"></i> {{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '-' }} | {{ $surgery->scheduled_time ? \Carbon\Carbon::parse($surgery->scheduled_time)->format('h:i A') : '-' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            {{-- 2. المرحلة --}}
                                            <td>
                                                @if($isPostOp)
                                                    <div class="d-flex flex-column gap-1">
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold shadow-xs d-inline-flex align-items-center gap-1.5" style="width: fit-content;">
                                                            <i class="fas fa-procedures text-success"></i> {{ __('بعد الصالة (عناية الرقود)') }}
                                                        </span>
                                                        <small class="text-muted" style="font-size: 0.72rem;">
                                                            <i class="fas fa-check-circle text-success me-1"></i> {{ __('اكتملت الجراحة وجاري المتابعة') }}
                                                        </small>
                                                    </div>
                                                @elseif($isInTheater)
                                                    <div class="d-flex flex-column gap-1">
                                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-3 py-1.5 fw-bold shadow-xs d-inline-flex align-items-center gap-1.5" style="width: fit-content;">
                                                            <i class="fas fa-spinner fa-spin text-primary"></i> {{ __('داخل صالة العمليات') }}
                                                        </span>
                                                        <small class="text-muted" style="font-size: 0.72rem;"><i class="fas fa-door-closed me-1"></i> {{ $surgery->operationTheater?->name ?? __('الصالة الجراحية') }}</small>
                                                    </div>
                                                @else
                                                    <div class="d-flex flex-column gap-1">
                                                        <span class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1.5 fw-bold shadow-xs d-inline-flex align-items-center gap-1.5" style="width: fit-content;">
                                                            <i class="fas fa-hourglass-start text-warning"></i> {{ __('قبل الصالة (تهيئة)') }}
                                                        </span>
                                                        <small class="text-muted" style="font-size: 0.72rem;">
                                                            <i class="fas fa-clipboard-check me-1"></i> {{ __('فحوصات وتهيئة ما قبل الجراحة') }}
                                                        </small>
                                                    </div>
                                                @endif
                                            </td>

                                            {{-- 3. الحالة السريرية --}}
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    {{-- الطبيب المقيم المكلف --}}
                                                    <div>
                                                        @if($currentStation?->resident?->user?->full_name)
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-0.5" style="font-size: 0.75rem;">
                                                                <i class="fas fa-user-md me-1"></i> {{ $currentStation->resident->user->full_name }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-light text-muted border rounded-pill px-2.5 py-0.5" style="font-size: 0.75rem;">
                                                                <i class="fas fa-user-md me-1 text-muted"></i> {{ __('لم يحدد مقيم بعد') }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                    {{-- حالة محطة المقيم --}}
                                                    <div>
                                                        @if(!$currentStation)
                                                            <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2.5 py-0.5" style="font-size: 0.73rem;">
                                                                {{ __('في الانتظار') }}
                                                            </span>
                                                        @elseif($currentStation->status === 'pending')
                                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-0.5" style="font-size: 0.73rem;">
                                                                <i class="fas fa-hourglass-half me-1"></i> {{ __('معلقة') }} - {{ $currentPhase }}
                                                            </span>
                                                        @elseif($currentStation->status === 'in_progress')
                                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2.5 py-0.5" style="font-size: 0.73rem;">
                                                                <i class="fas fa-spinner fa-spin me-1"></i> {{ __('جارية') }} - {{ $currentPhase }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-0.5" style="font-size: 0.73rem;">
                                                                <i class="fas fa-check-circle me-1"></i> {{ __('مكتملة') }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                    {{-- حالة استلام الغرفة --}}
                                                    <div>
                                                        @if($surgery->discharged_at)
                                                            <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
                                                                <i class="fas fa-sign-out-alt me-1"></i> {{ __('تم التخريج') }}
                                                            </span>
                                                        @elseif($surgery->room_checkin_at)
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.72rem;" title="تاريخ ووقت الاستلام: {{ $surgery->room_checkin_at->format('Y-m-d h:i A') }}">
                                                                <i class="fas fa-check-circle me-1"></i> {{ __('تم الاستلام') }} ({{ $surgery->room_checkin_at->format('h:i A') }})
                                                            </span>
                                                        @elseif($surgery->room_id)
                                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
                                                                <i class="fas fa-hourglass-start me-1"></i> {{ __('بانتظار استلام ممرض الطابق') }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-light text-muted border rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
                                                                <i class="fas fa-bed me-1"></i> {{ __('بدون غرفة رقود') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>

                                            {{-- 4. الإجراءات --}}
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center gap-1.5 flex-wrap">
                                                    @if($surgery->room_id && !$surgery->room_checkin_at)
                                                        <button type="button" class="btn btn-sm btn-secondary opacity-75 rounded-pill px-3 py-1.5 shadow-sm fw-semibold" onclick="alert('تنبيه: لا يمكن فتح المحطة السريرية للمريض.\n\nالمريض مخصص للغرفة ({{ $surgery->room?->room_number ?? '-' }}) ولم يقم ممرض الطابق بتسجيل دخوله واستلامه بعد.')" title="{{ __('بانتظار استلام ممرض الطابق للغرفة') }}">
                                                            <i class="fas fa-lock me-1"></i> {{ __('بانتظار استلام الغرفة') }}
                                                        </button>
                                                    @else
                                                        <a href="{{ route('resident-station.show', $surgery) }}{{ $isPostOp ? '?phase=post_op' : '' }}" class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 shadow-sm fw-bold">
                                                            <i class="fas fa-user-md me-1"></i> {{ __('عرض وتعديل') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted">
                                                <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                                                <p class="mb-0 fw-semibold">{{ __('لا توجد عمليات جارية أو نشطة في محطة المقيم حالياً') }}</p>
                                            </td>
                                        </tr>
                                        @endforelse
                                        <tr id="noActiveMatchResidentRow" style="display: none;">
                                            <td colspan="4" class="text-center py-4 text-muted bg-light">
                                                <i class="fas fa-search fa-2x mb-2 text-secondary opacity-50"></i>
                                                <p class="mb-0 small fw-bold">{{ __('لا توجد حالات تطابق الفلتر أو البحث المحدد') }}</p>
                                            </td>
                                        </tr>
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
        const phaseBtns = document.querySelectorAll('.resident-phase-btn');
        const activeRows = document.querySelectorAll('#residentActiveTableBody .resident-active-row');
        const noMatchRow = document.getElementById('noActiveMatchResidentRow');
        let currentActivePhase = 'all';

        function filterResidentActiveTable() {
            const query = (patientSearchInput ? patientSearchInput.value : '').trim().toLowerCase();
            let visibleCount = 0;

            activeRows.forEach(row => {
                const phase = row.getAttribute('data-phase') || '';
                const searchData = (row.getAttribute('data-search') || row.innerText || '').toLowerCase();

                const matchesPhase = (currentActivePhase === 'all') || (phase === currentActivePhase);
                const matchesQuery = !query || searchData.includes(query);

                if (matchesPhase && matchesQuery) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (noMatchRow) {
                noMatchRow.style.display = (visibleCount === 0 && activeRows.length > 0) ? '' : 'none';
            }
        }

        if (patientSearchInput) {
            patientSearchInput.addEventListener('input', function() {
                const activePane = document.querySelector('#residentTabsContent .tab-pane.active');
                if (activePane && activePane.id === 'active-surgeries') {
                    filterResidentActiveTable();
                } else if (activePane) {
                    const query = this.value.trim().toLowerCase();
                    const rows = activePane.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        if (row.querySelector('.text-muted.py-5') || row.children.length === 1) return;
                        const text = row.innerText.toLowerCase();
                        row.style.display = text.includes(query) ? '' : 'none';
                    });
                }
            });
        }

        phaseBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                phaseBtns.forEach(b => {
                    b.classList.remove('active', 'btn-dark');
                    b.classList.add('btn-outline-secondary');
                });

                this.classList.add('active');
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-dark');

                currentActivePhase = this.getAttribute('data-phase');
                filterResidentActiveTable();
            });
        });
    });
</script>
@endpush
