@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-bed me-2 text-primary"></i>
                        تفاصيل الغرفة: {{ $room->room_number }}
                    </h2>
                    <p class="text-muted mb-0">عرض تفاصيل الغرفة والحجوزات</p>
                </div>
                <div>
                    @if(auth()->user()->hasRole('admin'))
                    <a href="{{ route('rooms.edit', $room) }}" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-1"></i>
                        تعديل
                    </a>
                    @endif
                    <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- بيانات الغرفة -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        بيانات الغرفة
                    </h5>
                    <span class="badge bg-{{ $room->status_color }} fs-6">{{ $room->status_name }}</span>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="display-1 text-{{ $room->room_type == 'vip' ? 'warning' : 'primary' }}">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <h3>{{ $room->room_number }}</h3>
                        <span class="badge bg-{{ $room->room_type_color }} fs-5">
                            {{ $room->room_type_name }}
                        </span>
                    </div>

                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted"><i class="fas fa-layer-group me-2"></i>الطابق:</td>
                            <td class="fw-bold">{{ $room->floor ?? 'غير محدد' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="fas fa-money-bill me-2"></i>الأجرة اليومية:</td>
                            <td class="fw-bold text-success">{{ number_format($room->daily_fee) }} د.ع</td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="fas fa-bed me-2"></i>عدد الأسرّة:</td>
                            <td class="fw-bold">{{ $room->beds_count }}</td>
                        </tr>
                    </table>

                    <hr>

                    <h6>المزايا:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @if($room->has_bathroom)
                            <span class="badge bg-info"><i class="fas fa-bath me-1"></i> حمام خاص</span>
                        @endif
                        @if($room->has_tv)
                            <span class="badge bg-primary"><i class="fas fa-tv me-1"></i> تلفزيون</span>
                        @endif
                        @if($room->has_ac)
                            <span class="badge bg-info"><i class="fas fa-snowflake me-1"></i> تكييف</span>
                        @endif
                    </div>

                    @if($room->description)
                    <hr>
                    <h6>الوصف:</h6>
                    <p class="text-muted">{{ $room->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- العمليات المرتبطة -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-procedures me-2"></i>
                        العمليات المرتبطة بهذه الغرفة
                    </h5>
                </div>
                <div class="card-body">
                    @if($surgeries->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>المريض</th>
                                    <th>الطبيب</th>
                                    <th>نوع العملية</th>
                                    <th>التاريخ</th>
                                    <th>الحالة</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($surgeries as $surgery)
                                <tr>
                                    <td>
                                        <i class="fas fa-user me-1 text-muted"></i>
                                        {{ $surgery->patient->user->name ?? 'غير محدد' }}
                                    </td>
                                    <td>
                                        <i class="fas fa-user-md me-1 text-muted"></i>
                                        {{ $surgery->doctor->user->name ?? 'غير محدد' }}
                                    </td>
                                    <td>{{ $surgery->surgery_type }}</td>
                                    <td>{{ $surgery->scheduled_date->format('Y-m-d') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $surgery->status == 'completed' ? 'success' : ($surgery->status == 'scheduled' ? 'info' : 'warning') }}">
                                            {{ $surgery->status_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">لا توجد عمليات</h5>
                        <p class="text-muted">لم يتم حجز أي عمليات لهذه الغرفة بعد</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
