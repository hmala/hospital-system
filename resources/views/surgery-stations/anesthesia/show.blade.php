@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">محطة التخدير - العملية رقم {{ $surgery->id }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('anesthesia-station.index') }}" class="btn btn-sm btn-secondary">
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

                    <!-- نموذج محطة التخدير -->
                    <form action="{{ route('anesthesia-station.update', $surgery) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>طبيب التخدير الأول</label>
                                    <select name="anesthesiologist_id" class="form-control">
                                        <option value="">-- اختر طبيب التخدير --</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}" 
                                                {{ (($surgery->anesthesiaStation?->anesthesiologist_id ?? $surgery->anesthesiologist_id) == $doctor->id) ? 'selected' : '' }}>
                                                {{ $doctor->user->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>طبيب التخدير الثاني (اختياري)</label>
                                    <select name="anesthesiologist_2_id" class="form-control">
                                        <option value="">-- اختر طبيب التخدير --</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}" 
                                                {{ (($surgery->anesthesiaStation?->anesthesiologist_2_id ?? $surgery->anesthesiologist_2_id) == $doctor->id) ? 'selected' : '' }}>
                                                {{ $doctor->user->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>نوع التخدير</label>
                                    <input type="text" name="anesthesia_type" class="form-control" list="anesthesiaTypeSuggestions" value="{{ $surgery->anesthesiaStation?->anesthesia_type ?? $surgery->anesthesia_type ?? '' }}" placeholder="أدخل نوع التخدير...">
                                    <datalist id="anesthesiaTypeSuggestions">
                                        <option value="تخدير عام"></option>
                                        <option value="تخدير إقليمي"></option>
                                        <option value="تخدير موضعي"></option>
                                        <option value="تهدئة"></option>
                                        <option value="سباينال"></option>
                                        <option value="إبيديورال"></option>
                                        <option value="تخدير فوق الجافية"></option>
                                    </datalist>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>اسم المساعد الجراحي</label>
                                    <input type="text" name="surgical_assistant_name" class="form-control" 
                                        value="{{ $surgery->anesthesiaStation?->surgical_assistant_name ?? $surgery->surgical_assistant_name ?? '' }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>وقت البدء</label>
                                    <input type="time" name="start_time" class="form-control" 
                                        value="{{ $surgery->anesthesiaStation?->start_time ?? '' }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>وقت الانتهاء</label>
                                    <input type="time" name="end_time" class="form-control" 
                                        value="{{ $surgery->anesthesiaStation?->end_time ?? '' }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ملاحظات التخدير</label>
                            <textarea name="notes" class="form-control" rows="4">{{ $surgery->anesthesiaStation?->notes ?? '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ البيانات
                            </button>

                            @if($surgery->anesthesiaStation && $surgery->anesthesiaStation->status !== 'completed')
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
                    <form id="complete-form" action="{{ route('anesthesia-station.complete', $surgery) }}" method="POST" style="display: none;">
                        @csrf
                    </form>

                    <!-- معلومات المحطة -->
                    @if($surgery->anesthesiaStation)
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h5>معلومات الحالة</h5>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="20%">حالة المحطة:</th>
                                    <td>
                                        @if($surgery->anesthesiaStation->status === 'pending')
                                            <span class="badge badge-warning">معلقة</span>
                                        @elseif($surgery->anesthesiaStation->status === 'in_progress')
                                            <span class="badge badge-info">جارية</span>
                                        @else
                                            <span class="badge badge-success">مكتملة</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($surgery->anesthesiaStation->started_at)
                                <tr>
                                    <th>تاريخ البدء:</th>
                                    <td>{{ $surgery->anesthesiaStation->started_at->format('Y-m-d h:i A') }}</td>
                                </tr>
                                @endif
                                @if($surgery->anesthesiaStation->completed_at)
                                <tr>
                                    <th>تاريخ الإتمام:</th>
                                    <td>{{ $surgery->anesthesiaStation->completed_at->format('Y-m-d h:i A') }}</td>
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
