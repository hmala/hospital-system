@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">محطة الجراح - العملية رقم {{ $surgery->id }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('surgeon-station.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-right"></i> العودة للقائمة
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <!-- معلومات المريض والعملية -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>معلومات المريض</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">رقم الملف:</th>
                                    <td>{{ $surgery->patient->file_number }}</td>
                                </tr>
                                <tr>
                                    <th>الاسم:</th>
                                    <td>{{ $surgery->patient->user->full_name }}</td>
                                </tr>
                                <tr>
                                    <th>العمر:</th>
                                    <td>{{ $surgery->patient->age ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>معلومات العملية</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">اسم العملية:</th>
                                    <td>{{ $surgery->surgery_name }}</td>
                                </tr>
                                <tr>
                                    <th>الطبيب الجراح:</th>
                                    <td>{{ $surgery->doctor?->user?->full_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>التاريخ المحدد:</th>
                                    <td>{{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <!-- نموذج محطة الجراح -->
                    <form action="{{ route('surgeon-station.update', $surgery) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>تعيين طبيب مقيم</label>
                                    <select name="resident_assigned_id" class="form-control">
                                        <option value="">-- اختر طبيب مقيم --</option>
                                        @foreach($residents as $resident)
                                            <option value="{{ $resident->id }}" 
                                                {{ $surgery->surgeonStation?->resident_assigned_id == $resident->id ? 'selected' : '' }}>
                                                {{ $resident->user->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ملاحظات الجراح</label>
                            <textarea name="notes" class="form-control" rows="4">{{ $surgery->surgeonStation?->notes ?? '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>خطة العلاج</label>
                            <textarea name="treatment_plan" class="form-control" rows="4">{{ $surgery->surgeonStation?->treatment_plan ?? '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ البيانات
                            </button>

                            @if($surgery->surgeonStation && $surgery->surgeonStation->status !== 'completed')
                                <button type="button" class="btn btn-success" 
                                    onclick="if(confirm('هل أنت متأكد من إتمام هذه المحطة؟')) {
                                        event.preventDefault();
                                        document.getElementById('complete-form').submit();
                                    }">
                                    <i class="fas fa-check"></i> إتمام المحطة
                                </button>
                            @endif
                        </div>
                    </form>

                    <!-- نموذج الإتمام -->
                    <form id="complete-form" action="{{ route('surgeon-station.complete', $surgery) }}" method="POST" style="display: none;">
                        @csrf
                    </form>

                    <!-- معلومات المحطة -->
                    @if($surgery->surgeonStation)
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h5>معلومات الحالة</h5>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="20%">حالة المحطة:</th>
                                    <td>
                                        @if($surgery->surgeonStation->status === 'pending')
                                            <span class="badge badge-warning">معلقة</span>
                                        @elseif($surgery->surgeonStation->status === 'in_progress')
                                            <span class="badge badge-info">جارية</span>
                                        @else
                                            <span class="badge badge-success">مكتملة</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($surgery->surgeonStation->started_at)
                                <tr>
                                    <th>تاريخ البدء:</th>
                                    <td>{{ $surgery->surgeonStation->started_at->format('Y-m-d h:i A') }}</td>
                                </tr>
                                @endif
                                @if($surgery->surgeonStation->completed_at)
                                <tr>
                                    <th>تاريخ الإتمام:</th>
                                    <td>{{ $surgery->surgeonStation->completed_at->format('Y-m-d h:i A') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
