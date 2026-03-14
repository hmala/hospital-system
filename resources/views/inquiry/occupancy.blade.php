@extends('layouts.app')

@section('title', 'المرضى المقيمين في المستشفى')

@section('content')
<div class="container-fluid occupancy-page">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bed"></i>
                        المرضى المقيمين في المستشفى
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $bedReservations = collect($allOccupancies)->where('type_en', 'bed_reservation');
                        $surgeries = collect($allOccupancies)->where('type_en', 'surgery');
                    @endphp

                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-bed" data-bs-toggle="tab" data-bs-target="#bed" type="button" role="tab" aria-controls="bed" aria-selected="true">
                                رقود ({{ $bedReservations->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-surgery" data-bs-toggle="tab" data-bs-target="#surgery" type="button" role="tab" aria-controls="surgery" aria-selected="false">
                                عمليات ({{ $surgeries->count() }})
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="bed" role="tabpanel" aria-labelledby="tab-bed">
                            @if($bedReservations->count())
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>المريض</th>
                                                <th>الغرفة</th>
                                                <th>نوع الغرفة</th>
                                                <th>التاريخ</th>
                                                <th>الوقت</th>
                                                <th>الطبيب</th>
                                                <th>القسم</th>
                                                <th>الحالة</th>
                                                <th>ملاحظات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($bedReservations as $reservation)
                                                @php $record = $reservation['data']; @endphp
                                                <tr>
                                                    <td>{{ optional($record->patient->user)->name ?? 'غير معروف' }}</td>
                                                    <td>{{ $record->room?->room_number ?? '-' }}</td>
                                                    <td>
                                                        @if($record->room && $record->room->room_type === 'vip')
                                                            VIP
                                                        @elseif($record->room)
                                                            عادية
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>{{ $record->scheduled_date->format('Y-m-d') }}</td>
                                                    <td>{{ $record->scheduled_time->format('H:i') }}</td>
                                                    <td>{{ optional($record->doctor->user)->name ?? '-' }}</td>
                                                    <td>{{ optional($record->department)->name ?? '-' }}</td>
                                                    <td>
                                                        @if($record->status === 'pending')
                                                            <span class="badge bg-warning">قيد الانتظار</span>
                                                        @elseif($record->status === 'confirmed')
                                                            <span class="badge bg-success">مؤكد</span>
                                                        @elseif($record->status === 'completed')
                                                            <span class="badge bg-secondary">مكتمل</span>
                                                        @elseif($record->status === 'cancelled')
                                                            <span class="badge bg-danger">ملغى</span>
                                                        @else
                                                            {{ $record->status }}
                                                        @endif
                                                    </td>
                                                    <td>{{ $record->notes ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-bed fa-3x text-muted mb-3"></i>
                                    <h4 class="text-muted">لا يوجد رقود مؤقت حالياً</h4>
                                    <p class="text-muted">يمكنك إنشاء رقود جديد من صفحة الحجز</p>
                                </div>
                            @endif
                        </div>
                        <div class="tab-pane fade" id="surgery" role="tabpanel" aria-labelledby="tab-surgery">
                            @if($surgeries->count())
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>المريض</th>
                                                <th>الغرفة</th>
                                                <th>نوع الغرفة</th>
                                                <th>التاريخ</th>
                                                <th>الوقت</th>
                                                <th>الطبيب</th>
                                                <th>القسم</th>
                                                <th>نوع العملية</th>
                                                <th>الحالة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($surgeries as $surgery)
                                                @php $record = $surgery['data']; @endphp
                                                <tr>
                                                    <td>{{ optional($record->patient->user)->name ?? 'غير معروف' }}</td>
                                                    <td>{{ $record->room?->room_number ?? '-' }}</td>
                                                    <td>
                                                        @if($record->room && $record->room->room_type === 'vip')
                                                            VIP
                                                        @elseif($record->room)
                                                            عادية
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>{{ $record->scheduled_date->format('Y-m-d') }}</td>
                                                    <td>{{ $record->scheduled_time->format('H:i') }}</td>
                                                    <td>{{ optional($record->doctor->user)->name ?? '-' }}</td>
                                                    <td>{{ optional($record->department)->name ?? '-' }}</td>
                                                    <td>{{ $record->surgery_type ?? '-' }}</td>
                                                    <td>
                                                        @if($record->status === 'scheduled')
                                                            مجدولة
                                                        @elseif($record->status === 'waiting')
                                                            في الانتظار
                                                        @elseif($record->status === 'in_progress')
                                                            جارية
                                                        @elseif($record->status === 'completed')
                                                            مكتملة
                                                        @else
                                                            {{ $record->status }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-procedures fa-3x text-muted mb-3"></i>
                                    <h4 class="text-muted">لا توجد عمليات محجوزة حالياً</h4>
                                    <p class="text-muted">يمكنك متابعة عملياتك من صفحة العمليات</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>إجمالي رقود:</strong> {{ $bedReservations->count() }}
                        </div>
                        <div class="col-md-6 text-left">
                            <strong>إجمالي عمليات:</strong> {{ $surgeries->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.occupancy-page {
    background-color: #f0f8ff;
}
.occupancy-page .card {
    border: 2px solid #17a2b8;
}
.occupancy-page table {
    background: #ffffff;
}
.card {
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
.card-header {
    border-bottom: 1px solid #dee2e6;
}
.table th {
    vertical-align: middle;
}
.table td {
    vertical-align: middle;
}
</style>
@endsection