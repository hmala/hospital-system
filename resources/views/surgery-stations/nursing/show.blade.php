@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">محطة التمريض - العملية رقم {{ $surgery->id }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('nursing-station.index') }}" class="btn btn-sm btn-secondary">
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
                                    <th>الطبيب المقيم:</th>
                                    <td>{{ $surgery->residentStation?->resident?->user?->full_name ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <!-- نموذج محطة التمريض -->
                    <form action="{{ route('nursing-station.update', $surgery) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="form-group">
                            <label>الممرضة المسؤولة</label>
                            <select name="nurse_id" class="form-control">
                                <option value="">-- اختر الممرضة --</option>
                                @foreach($nurses as $nurse)
                                    <option value="{{ $nurse->id }}" 
                                        {{ $surgery->nursingStation?->nurse_id == $nurse->id ? 'selected' : '' }}>
                                        {{ $nurse->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>ملاحظات التمريض</label>
                            <textarea name="nursing_notes" class="form-control" rows="4">{{ $surgery->nursingStation?->nursing_notes ?? '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>العلامات الحيوية</label>
                            <textarea name="vital_signs" class="form-control" rows="3" 
                                placeholder="مثال: ضغط الدم، النبض، الحرارة، التنفس...">{{ $surgery->nursingStation?->vital_signs ?? '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>ملاحظات الخروج</label>
                            <textarea name="discharge_notes" class="form-control" rows="4">{{ $surgery->nursingStation?->discharge_notes ?? '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ البيانات
                            </button>

                            @if($surgery->nursingStation && $surgery->nursingStation->status !== 'completed')
                                <button type="button" class="btn btn-success" 
                                    onclick="if(confirm('هل أنت متأكد من إتمام هذه المحطة؟ سيتم إنهاء العملية.')) {
                                        event.preventDefault();
                                        document.getElementById('complete-form').submit();
                                    }">
                                    <i class="fas fa-check"></i> إتمام المحطة وإنهاء العملية
                                </button>
                            @endif
                        </div>
                    </form>

                    <!-- نموذج الإتمام -->
                    <form id="complete-form" action="{{ route('nursing-station.complete', $surgery) }}" method="POST" style="display: none;">
                        @csrf
                    </form>

                    <!-- معلومات المحطة -->
                    @if($surgery->nursingStation)
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h5>معلومات الحالة</h5>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="20%">حالة المحطة:</th>
                                    <td>
                                        @if($surgery->nursingStation->status === 'pending')
                                            <span class="badge badge-warning">معلقة</span>
                                        @elseif($surgery->nursingStation->status === 'in_progress')
                                            <span class="badge badge-info">جارية</span>
                                        @else
                                            <span class="badge badge-success">مكتملة</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($surgery->nursingStation->started_at)
                                <tr>
                                    <th>تاريخ البدء:</th>
                                    <td>{{ $surgery->nursingStation->started_at->format('Y-m-d h:i A') }}</td>
                                </tr>
                                @endif
                                @if($surgery->nursingStation->completed_at)
                                <tr>
                                    <th>تاريخ الإتمام:</th>
                                    <td>{{ $surgery->nursingStation->completed_at->format('Y-m-d h:i A') }}</td>
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
