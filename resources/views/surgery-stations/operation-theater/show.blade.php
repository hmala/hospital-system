@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">محطة صالة العمليات - العملية رقم {{ $surgery->id }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('operation-theater-station.index') }}" class="btn btn-sm btn-secondary">
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
                                    <td>{{ $surgery->scheduled_date?->format('Y-m-d') }} {{ $surgery->scheduled_time }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <!-- نموذج صالة العمليات -->
                    <form action="{{ route('operation-theater-station.update', $surgery) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ممرض/ة صالة العمليات</label>
                                    <select name="or_nurse_id" class="form-control">
                                        <option value="">-- اختر --</option>
                                        @foreach($nurses as $nurse)
                                            <option value="{{ $nurse->id }}" {{ $surgery->operationTheaterStation?->or_nurse_id == $nurse->id ? 'selected' : '' }}>
                                                {{ $nurse->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>طبيب التخدير</label>
                                    <select name="anesthesiologist_id" class="form-control">
                                        <option value="">-- اختر --</option>
                                        @foreach($anesthesiologists as $doctor)
                                            <option value="{{ $doctor->id }}" {{ $surgery->operationTheaterStation?->anesthesiologist_id == $doctor->id ? 'selected' : '' }}>
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
                                    <label>وقت البدء</label>
                                    <input type="time" name="start_time" class="form-control" value="{{ $surgery->operationTheaterStation?->start_time }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>وقت الانتهاء</label>
                                    <input type="time" name="end_time" class="form-control" value="{{ $surgery->operationTheaterStation?->end_time }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ملاحظات الصالة</label>
                            <textarea name="notes" class="form-control" rows="4">{{ $surgery->operationTheaterStation?->notes ?? '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>ملاحظات الإجراء</label>
                            <textarea name="procedure_notes" class="form-control" rows="4">{{ $surgery->operationTheaterStation?->procedure_notes ?? '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ البيانات
                            </button>

                            @if($surgery->operationTheaterStation && $surgery->operationTheaterStation->status !== 'completed')
                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#completeModal">
                                    <i class="fas fa-check"></i> إتمام المحطة
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Complete Modal -->
<div class="modal fade" id="completeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تأكيد الإتمام</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                هل أنت متأكد من إتمام مرحلة صالة العمليات؟ سيتم الانتقال لمحطة الجراح.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                <form action="{{ route('operation-theater-station.complete', $surgery) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('POST')
                    <button type="submit" class="btn btn-success">تأكيد الإتمام</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
