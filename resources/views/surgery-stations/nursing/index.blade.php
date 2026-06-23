@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-gradient bg-primary text-white p-3 rounded-top-4 d-flex align-items-center">
                    <h5 class="mb-0 fs-5"><i class="fas fa-user-nurse me-2"></i> محطة التمريض</h5>
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Tabs Switcher -->
                    <ul class="nav nav-pills mb-4 gap-2 bg-light p-1.5 rounded-pill shadow-sm" id="nursingTabs" role="tablist" style="width: fit-content;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill fw-bold px-4 py-2" id="pre-op-patients-tab" data-bs-toggle="tab" data-bs-target="#pre-op-patients" type="button" role="tab" aria-selected="true">
                                <i class="fas fa-notes-medical me-1 text-primary"></i> حالات التهيئة قبل الصالة
                                <span class="badge bg-primary text-white rounded-pill ms-1.5">{{ $preOpSurgeries->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold px-4 py-2" id="post-op-patients-tab" data-bs-toggle="tab" data-bs-target="#post-op-patients" type="button" role="tab" aria-selected="false">
                                <i class="fas fa-procedures me-1 text-info"></i> حالات المتابعة بعد الصالة
                                <span class="badge bg-info text-white rounded-pill ms-1.5">{{ $postOpSurgeries->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold px-4 py-2" id="completed-patients-tab" data-bs-toggle="tab" data-bs-target="#completed-patients" type="button" role="tab" aria-selected="false">
                                <i class="fas fa-archive me-1 text-success"></i> أرشيف الحالات المكتملة
                                <span class="badge bg-success text-white rounded-pill ms-1.5">{{ $completedSurgeries->count() }}</span>
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
                                            <td class="fw-bold text-primary">{{ $surgery->patient->file_number }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex justify-content-center align-items-center me-2" style="width: 35px; height: 35px;">
                                                        <i class="fas fa-user-injured fs-6"></i>
                                                    </div>
                                                    <span class="fw-semibold">{{ $surgery->patient->user->full_name }}</span>
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
                                                @if(!$surgery->nursingStation)
                                                    <span class="badge bg-secondary rounded-pill px-3 py-2">في الانتظار</span>
                                                @elseif($surgery->nursingStation->status === 'pending')
                                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2">
                                                        <i class="fas fa-hourglass-half me-1"></i> معلقة
                                                    </span>
                                                @elseif($surgery->nursingStation->status === 'in_progress')
                                                    <span class="badge bg-info text-dark rounded-pill px-3 py-2">
                                                        <i class="fas fa-spinner fa-spin me-1"></i> جارية
                                                    </span>
                                                @else
                                                    <span class="badge bg-success rounded-pill px-3 py-2">
                                                        <i class="fas fa-check-circle me-1"></i> مكتملة
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('nursing-station.show', $surgery) }}" class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 shadow-sm">
                                                    <i class="fas fa-eye me-1"></i> عرض وتعديل
                                                </a>
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
                                            <td class="fw-bold text-primary">{{ $surgery->patient->file_number }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-2 d-flex justify-content-center align-items-center me-2" style="width: 35px; height: 35px;">
                                                        <i class="fas fa-user-injured fs-6"></i>
                                                    </div>
                                                    <span class="fw-semibold">{{ $surgery->patient->user->full_name }}</span>
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
                                                @if(!$surgery->nursingStation)
                                                    <span class="badge bg-secondary rounded-pill px-3 py-2">في الانتظار</span>
                                                @elseif($surgery->nursingStation->status === 'pending')
                                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2">
                                                        <i class="fas fa-hourglass-half me-1"></i> معلقة
                                                    </span>
                                                @elseif($surgery->nursingStation->status === 'in_progress')
                                                    <span class="badge bg-info text-dark rounded-pill px-3 py-2">
                                                        <i class="fas fa-spinner fa-spin me-1"></i> جارية
                                                    </span>
                                                @else
                                                    <span class="badge bg-success rounded-pill px-3 py-2">
                                                        <i class="fas fa-check-circle me-1"></i> مكتملة
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('nursing-station.show', $surgery) }}" class="btn btn-sm btn-info rounded-pill px-3 py-1.5 text-white shadow-sm">
                                                    <i class="fas fa-eye me-1"></i> عرض وتعديل
                                                </a>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
