@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">محطة صالة العمليات</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center justify-content-between p-3 mb-3 flex-wrap gap-2" style="background: #f0f9ff; border-radius: 12px;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-info-circle text-primary fs-5"></i>
                            <div>
                                <strong class="text-dark">تنبيه تنظيمي:</strong>
                                <span class="text-muted">تدار صالات العمليات والمواعيد والبدء والإنهاء مباشرة عبر لوحة المتابعة الشاملة.</span>
                            </div>
                        </div>
                        <a href="{{ route('surgeries.waiting') }}" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                            <i class="fas fa-clock me-1"></i> الانتقال لقائمة انتظار العمليات (Flight Board)
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>رقم الملف</th>
                                    <th>اسم المريض</th>
                                    <th>العملية</th>
                                    <th>التاريخ والوقت المحدد</th>
                                    <th>حالة المحطة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($surgeries as $surgery)
                                <tr>
                                    <td>{{ $surgery->patient->file_number }}</td>
                                    <td>{{ $surgery->patient->user->full_name }}</td>
                                    <td>{{ $surgery->surgery_name }}</td>
                                    <td>
                                        {{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '-' }}
                                        <br>
                                        <small>{{ $surgery->scheduled_time ? \Carbon\Carbon::parse($surgery->scheduled_time)->format('h:i A') : '-' }}</small>
                                    </td>
                                    <td>
                                        @if(!$surgery->operationTheaterStation)
                                            <span class="badge badge-secondary">في الانتظار</span>
                                        @elseif($surgery->operationTheaterStation->status === 'pending')
                                            <span class="badge badge-warning">معلقة</span>
                                        @elseif($surgery->operationTheaterStation->status === 'in_progress')
                                            <span class="badge badge-info">جارية</span>
                                        @else
                                            <span class="badge badge-success">مكتملة</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('operation-theater-station.show', $surgery) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> عرض التفاصيل
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">لا توجد عمليات في صالة العمليات</td>
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
@endsection
