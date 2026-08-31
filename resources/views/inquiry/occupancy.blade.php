@extends('layouts.app')

@section('title', 'المرضى المقيمين وإشغال الغرف')

@section('content')
<div class="container-fluid py-3">
    <!-- رأس الصفحة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2 class="mb-1 fw-bold text-dark">
                        <i class="fas fa-bed text-primary me-2"></i>
                        المرضى المقيمين في المستشفى (إشغال الغرف)
                    </h2>
                    <p class="text-muted mb-0">متابعة النزلاء في غرف الرقود ومواعيد العمليات الجراحية</p>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-outline-primary shadow-sm">
                        <i class="fas fa-print me-1"></i> طباعة الكشف
                    </button>
                    <a href="{{ route('inquiry.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> العودة للاستعلامات
                    </a>
                </div>
            </div>
        </div>
    </div>

    @php
        $bedReservations = collect($allOccupancies)->where('type_en', 'bed_reservation');
        $surgeries = collect($allOccupancies)->where('type_en', 'surgery');
    @endphp

    <!-- كروت الإحصائيات السريعة -->
    <div class="row mb-4 g-3">
        <div class="col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm text-white bg-primary bg-gradient h-100" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="small opacity-75 d-block mb-1">إجمالي المقيمين حالياً</span>
                            <h3 class="mb-0 fw-bold">{{ $allOccupancies ? count($allOccupancies) : 0 }}</h3>
                        </div>
                        <i class="fas fa-users-line fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm text-white bg-info bg-gradient h-100" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="small opacity-75 d-block mb-1">حالات الرقود المباشر</span>
                            <h3 class="mb-0 fw-bold">{{ $bedReservations->count() }}</h3>
                        </div>
                        <i class="fas fa-bed fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm text-white bg-danger bg-gradient h-100" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="small opacity-75 d-block mb-1">حالات حجز العمليات</span>
                            <h3 class="mb-0 fw-bold">{{ $surgeries->count() }}</h3>
                        </div>
                        <i class="fas fa-procedures fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- كرت الفلاتر والبحث -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('inquiry.occupancy') }}">
                <div class="row g-2 align-items-end">
                    <!-- البحث النصي -->
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <label class="form-label small fw-bold text-muted mb-1">
                            <i class="fas fa-search me-1"></i>بحث فوري (اسم المريض، الغرفة، الطبيب، الهاتف)
                        </label>
                        <input type="text" name="search" id="liveSearchInput" class="form-control form-control-sm" placeholder="اكتب للبحث..." value="{{ $search ?? '' }}">
                    </div>

                    <!-- نوع الغرفة -->
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">
                            <i class="fas fa-door-open me-1"></i>نوع الغرفة
                        </label>
                        <select name="room_type" class="form-select form-select-sm">
                            <option value="">كافة الغرف</option>
                            <option value="vip" {{ ($roomType ?? '') == 'vip' ? 'selected' : '' }}>غرف VIP</option>
                            <option value="normal" {{ ($roomType ?? '') == 'normal' ? 'selected' : '' }}>غرف عادية</option>
                        </select>
                    </div>

                    <!-- من تاريخ -->
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">
                            <i class="fas fa-calendar-day me-1"></i>من تاريخ
                        </label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate ?? '' }}">
                    </div>

                    <!-- إلى تاريخ -->
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1">
                            <i class="fas fa-calendar-day me-1"></i>إلى تاريخ
                        </label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate ?? '' }}">
                    </div>

                    <!-- أزرار الفلترة -->
                    <div class="col-lg-2 col-md-3 col-sm-6 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="fas fa-filter me-1"></i> تصفية
                        </button>
                        <a href="{{ route('inquiry.occupancy') }}" class="btn btn-outline-secondary btn-sm" title="إعادة تعيين">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- التبويبات وجداول السجلات -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white p-3 border-bottom">
            <ul class="nav nav-pills card-header-pills" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold px-4" id="tab-bed" data-bs-toggle="tab" data-bs-target="#bed" type="button" role="tab" aria-controls="bed" aria-selected="true">
                        <i class="fas fa-bed me-2 text-info"></i>
                        حالات الرقود ({{ $bedReservations->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold px-4 ms-2" id="tab-surgery" data-bs-toggle="tab" data-bs-target="#surgery" type="button" role="tab" aria-controls="surgery" aria-selected="false">
                        <i class="fas fa-procedures me-2 text-danger"></i>
                        العمليات الجراحية ({{ $surgeries->count() }})
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content">
                <!-- تبويب الرقود -->
                <div class="tab-pane fade show active" id="bed" role="tabpanel" aria-labelledby="tab-bed">
                    @if($bedReservations->count())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center filterable-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 45px;">#</th>
                                        <th class="text-start">المريض</th>
                                        <th>الغرفة</th>
                                        <th>نوع الغرفة</th>
                                        <th>تاريخ الدخول</th>
                                        <th>الوقت</th>
                                        <th>الطبيب المعالج</th>
                                        <th>القسم</th>
                                        <th>الحالة</th>
                                        <th>ملاحظات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bedReservations as $reservation)
                                        @php $record = $reservation['data']; @endphp
                                        <tr class="table-row-item">
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="text-start">
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px; font-weight: bold; min-width: 35px;">
                                                        {{ mb_substr($record->patient?->user?->name ?? 'م', 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <strong class="text-dark d-block search-target">{{ $record->patient?->user?->name ?? 'غير معروف' }}</strong>
                                                        <small class="text-muted search-target">{{ $record->patient?->user?->phone ?? 'لا يوجد هاتف' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-primary border border-primary px-2 py-1 fs-6 search-target">
                                                    <i class="fas fa-door-closed me-1"></i>{{ $record->room?->room_number ?? '-' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($record->room && $record->room->room_type === 'vip')
                                                    <span class="badge bg-warning text-dark border border-warning">⭐ VIP</span>
                                                @elseif($record->room)
                                                    <span class="badge bg-light text-dark border">عادية</span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $record->scheduled_date ? $record->scheduled_date->format('Y-m-d') : '-' }}</td>
                                            <td>{{ $record->scheduled_time ? $record->scheduled_time->format('H:i') : '-' }}</td>
                                            <td class="search-target">
                                                @if($record->doctor?->user)
                                                    <span class="fw-semibold text-dark"><i class="fas fa-user-md text-primary me-1"></i>د. {{ $record->doctor->user->name }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="search-target">{{ $record->department?->name ?? '-' }}</td>
                                            <td>
                                                @if($record->status === 'pending')
                                                    <span class="badge bg-warning text-dark">قيد الانتظار</span>
                                                @elseif($record->status === 'confirmed')
                                                    <span class="badge bg-success">مؤكد</span>
                                                @elseif($record->status === 'completed')
                                                    <span class="badge bg-secondary">مكتمل</span>
                                                @elseif($record->status === 'cancelled')
                                                    <span class="badge bg-danger">ملغى</span>
                                                @else
                                                    <span class="badge bg-light text-dark border">{{ $record->status }}</span>
                                                @endif
                                            </td>
                                            <td class="text-muted small search-target">{{ $record->notes ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-bed fa-3x mb-3 text-secondary opacity-50"></i>
                            <h5 class="fw-bold">لا يوجد رقود مسجل حالياً</h5>
                            <p class="small text-muted">جرب تغيير معايير البحث أو الفلترة</p>
                        </div>
                    @endif
                </div>

                <!-- تبويب العمليات -->
                <div class="tab-pane fade" id="surgery" role="tabpanel" aria-labelledby="tab-surgery">
                    @if($surgeries->count())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center filterable-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 45px;">#</th>
                                        <th class="text-start">المريض</th>
                                        <th>الغرفة</th>
                                        <th>نوع الغرفة</th>
                                        <th>تاريخ العملية</th>
                                        <th>الوقت</th>
                                        <th>الطبيب الجراح</th>
                                        <th>القسم</th>
                                        <th>نوع العملية</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($surgeries as $surgery)
                                        @php $record = $surgery['data']; @endphp
                                        <tr class="table-row-item">
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="text-start">
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px; font-weight: bold; min-width: 35px;">
                                                        {{ mb_substr($record->patient?->user?->name ?? 'م', 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <strong class="text-dark d-block search-target">{{ $record->patient?->user?->name ?? 'غير معروف' }}</strong>
                                                        <small class="text-muted search-target">{{ $record->patient?->user?->phone ?? 'لا يوجد هاتف' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-danger border border-danger px-2 py-1 fs-6 search-target">
                                                    <i class="fas fa-door-closed me-1"></i>{{ $record->room?->room_number ?? '-' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($record->room && $record->room->room_type === 'vip')
                                                    <span class="badge bg-warning text-dark border border-warning">⭐ VIP</span>
                                                @elseif($record->room)
                                                    <span class="badge bg-light text-dark border">عادية</span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $record->scheduled_date ? $record->scheduled_date->format('Y-m-d') : '-' }}</td>
                                            <td>{{ $record->scheduled_time ? $record->scheduled_time->format('H:i') : '-' }}</td>
                                            <td class="search-target">
                                                @if($record->doctor?->user)
                                                    <span class="fw-semibold text-dark"><i class="fas fa-user-md text-danger me-1"></i>د. {{ $record->doctor->user->name }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="search-target">{{ $record->department?->name ?? '-' }}</td>
                                            <td class="fw-semibold text-primary search-target">{{ $record->surgery_type ?? '-' }}</td>
                                            <td>
                                                @if($record->status === 'scheduled')
                                                    <span class="badge bg-primary">مجدولة</span>
                                                @elseif($record->status === 'waiting')
                                                    <span class="badge bg-warning text-dark">في الانتظار</span>
                                                @elseif($record->status === 'in_progress')
                                                    <span class="badge bg-info">جارية</span>
                                                @elseif($record->status === 'completed')
                                                    <span class="badge bg-success">مكتملة</span>
                                                @else
                                                    <span class="badge bg-light text-dark border">{{ $record->status }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-procedures fa-3x mb-3 text-secondary opacity-50"></i>
                            <h5 class="fw-bold">لا توجد عمليات مسجلة حالياً</h5>
                            <p class="small text-muted">جرب تغيير معايير البحث أو الفلترة</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// بحث فوري لحظي في الجداول
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('liveSearchInput');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        document.querySelectorAll('.table-row-item').forEach(row => {
            const text = row.innerText.toLowerCase();
            if (text.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>
@endsection