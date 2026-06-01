@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">محطة الطبيب المقيم</h3>
                </div>
                <div class="card-body">
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
                                    <th>الطبيب المقيم</th>
                                    <th>حالة المحطة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($surgeries as $surgery)
                                @php
                                    $preOpStation = $surgery->preOpResidentStation;
                                    $postOpStation = $surgery->postOpResidentStation;
                                    $currentStation = null;
                                    $currentPhase = '';
                                    
                                    if (!$preOpStation || $preOpStation->status !== 'completed') {
                                        $currentStation = $preOpStation;
                                        $currentPhase = 'تحضير';
                                    } elseif ($surgery->anesthesiaStation && $surgery->anesthesiaStation->status === 'completed') {
                                        $currentStation = $postOpStation;
                                        $currentPhase = 'متابعة';
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $surgery->patient->file_number }}</td>
                                    <td>{{ $surgery->patient->user->full_name }}</td>
                                    <td>{{ $surgery->surgery_name }}</td>
                                    <td>
                                        {{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '-' }}
                                        <br>
                                        <small>{{ $surgery->scheduled_time ? \Carbon\Carbon::parse($surgery->scheduled_time)->format('h:i A') : '-' }}</small>
                                    </td>
                                    <td>{{ $currentStation?->resident?->user?->full_name ?? '-' }}</td>
                                    <td>
                                        @if(!$currentStation)
                                            <span class="badge badge-secondary">في الانتظار</span>
                                        @elseif($currentStation->status === 'pending')
                                            <span class="badge badge-warning">معلقة - {{ $currentPhase }}</span>
                                        @elseif($currentStation->status === 'in_progress')
                                            <span class="badge badge-info">جارية - {{ $currentPhase }}</span>
                                        @else
                                            <span class="badge badge-success">مكتملة</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('resident-station.show', $surgery) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> عرض التفاصيل
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد عمليات في محطة المقيم</td>
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
