@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">محطة المقيم - العملية رقم {{ $surgery->id }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('resident-station.index') }}" class="btn btn-sm btn-secondary">
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
                            <div class="card shadow-sm border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-user-injured"></i> معلومات المريض</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <th width="40%"><i class="fas fa-folder-open text-info"></i> رقم الملف:</th>
                                            <td><strong>{{ $surgery->patient->file_number }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-user text-success"></i> الاسم:</th>
                                            <td><strong>{{ $surgery->patient->user->full_name }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-birthday-cake text-warning"></i> العمر:</th>
                                            <td><strong>{{ $surgery->patient->age ?? '-' }}</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card shadow-sm border-success">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-procedures"></i> معلومات العملية</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <th width="40%"><i class="fas fa-syringe text-danger"></i> اسم العملية:</th>
                                            <td><strong>{{ $surgery->surgery_name }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-user-md text-primary"></i> الطبيب الجراح:</th>
                                            <td><strong>{{ $surgery->doctor?->user?->full_name ?? '-' }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-stethoscope text-info"></i> الطبيب المقيم:</th>
                                            <td><strong>{{ $station?->resident?->user?->full_name ?? 'لم يحدد بعد' }}</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- نموذج محطة المقيم -->
                    <form action="{{ route('resident-station.update', $surgery) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <!-- اختيار الطبيب المقيم -->
                        <div class="card shadow-sm border-info mb-4">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-user-md"></i> الطبيب المقيم المسؤول</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-0">
                                    <select name="resident_id" class="form-control form-control-lg">
                                        <option value="">-- اختر الطبيب المقيم --</option>
                                        @foreach($residents as $resident)
                                            <option value="{{ $resident->id }}" 
                                                {{ ($station?->resident_id ?? old('resident_id')) == $resident->id ? 'selected' : '' }}>
                                                {{ $resident->user->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- قسم التاريخ المرضي -->
                        <div class="card shadow-sm border-info mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-history"></i> التاريخ المرضي</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label><i class="fas fa-comment-medical text-danger"></i> الشكاية الرئيسية</label>
                                    <textarea name="chief_complaint" class="form-control" rows="3" placeholder="اكتب الشكاية الرئيسية...">{{ $station?->chief_complaint ?? '' }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-notes-medical text-info"></i> تاريخ المرض الحالي</label>
                                    <textarea name="history_present_illness" class="form-control" rows="3" placeholder="اكتب تاريخ المرض الحالي...">{{ $station?->history_present_illness ?? '' }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-file-medical text-primary"></i> التاريخ الطبي السابق</label>
                                    <textarea name="past_medical_hx" class="form-control" rows="3" placeholder="اكتب التاريخ الطبي السابق...">{{ $station?->past_medical_hx ?? '' }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-cut text-warning"></i> التاريخ الجراحي السابق</label>
                                    <textarea name="past_surgical_hx" class="form-control" rows="3" placeholder="اكتب التاريخ الجراحي السابق...">{{ $station?->past_surgical_hx ?? '' }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-pills text-success"></i> تاريخ الأدوية</label>
                                    <textarea name="drug_hx" class="form-control" rows="3" placeholder="اكتب تاريخ الأدوية...">{{ $station?->drug_hx ?? '' }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-exclamation-triangle text-danger"></i> حساسية دوائية</label>
                                    <textarea name="drug_allergy" class="form-control" rows="3" placeholder="اكتب الحساسية الدوائية إن وجدت...">{{ $station?->drug_allergy ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- قسم الفحص السريري -->
                        <div class="card shadow-sm border-success mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-user-md"></i> الفحص السريري</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label><i class="fas fa-stethoscope text-primary"></i> الفحص السريري</label>
                                    <textarea name="clinical_examination" class="form-control" rows="3" placeholder="اكتب نتائج الفحص السريري...">{{ $station?->clinical_examination ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- العلامات الحيوية -->
                        <div class="card shadow-sm border-danger mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-heartbeat"></i> العلامات الحيوية</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label><i class="fas fa-tachometer-alt text-danger"></i> BP</label>
                                            <input type="text" name="bp" class="form-control" placeholder="120/80" value="{{ $station?->bp ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label><i class="fas fa-thermometer-half text-warning"></i> Temp</label>
                                            <input type="text" name="temp" class="form-control" placeholder="37°C" value="{{ $station?->temp ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label><i class="fas fa-heart text-danger"></i> PR</label>
                                            <input type="text" name="pr" class="form-control" placeholder="80" value="{{ $station?->pr ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label><i class="fas fa-lungs text-info"></i> R.R</label>
                                            <input type="text" name="rr" class="form-control" placeholder="16" value="{{ $station?->rr ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label><i class="fas fa-wind text-primary"></i> SPo2</label>
                                            <input type="text" name="spo2" class="form-control" placeholder="98%" value="{{ $station?->spo2 ?? '' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-notes-medical text-secondary"></i> مراجعة الأنظمة الأخرى</label>
                            <textarea name="review_of_other_systems" class="form-control" rows="3" placeholder="اكتب مراجعة الأنظمة الأخرى...">{{ $station?->review_of_other_systems ?? '' }}</textarea>
                        </div>

                        <!-- نتائج الفحوصات المخبرية من المختبر -->
                        @if($surgery->labTests && $surgery->labTests->count() > 0)
                        <div class="form-group">
                            <div class="card shadow-sm border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-flask"></i> نتائج الفحوصات المخبرية</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th><i class="fas fa-vial"></i> الفحص</th>
                                                    <th><i class="fas fa-info-circle"></i> الحالة</th>
                                                    <th><i class="fas fa-file-medical-alt"></i> النتيجة</th>
                                                    <th><i class="far fa-calendar-alt"></i> التاريخ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($surgery->labTests as $labTest)
                                                <tr>
                                                    <td><strong>{{ $labTest->labTest?->name ?? '-' }}</strong></td>
                                                    <td>
                                                        @if($labTest->status === 'completed')
                                                            <span class="badge badge-success"><i class="fas fa-check"></i> مكتمل</span>
                                                        @elseif($labTest->status === 'pending')
                                                            <span class="badge badge-warning"><i class="fas fa-clock"></i> معلق</span>
                                                        @else
                                                            <span class="badge badge-secondary">{{ $labTest->status }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $labTest->result ?? '-' }}</td>
                                                    <td>{{ $labTest->completed_at?->format('Y-m-d') ?? '-' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- نتائج الفحوصات التصويرية من الأشعة -->
                        @if($surgery->radiologyTests && $surgery->radiologyTests->count() > 0)
                        <div class="form-group">
                            <div class="card shadow-sm border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-x-ray"></i> نتائج الفحوصات التصويرية</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th><i class="fas fa-camera"></i> الفحص</th>
                                                    <th><i class="fas fa-info-circle"></i> الحالة</th>
                                                    <th><i class="fas fa-file-medical-alt"></i> النتيجة</th>
                                                    <th><i class="far fa-calendar-alt"></i> التاريخ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($surgery->radiologyTests as $radiologyTest)
                                                <tr>
                                                    <td><strong>{{ $radiologyTest->radiologyType?->name ?? '-' }}</strong></td>
                                                    <td>
                                                        @if($radiologyTest->status === 'completed')
                                                            <span class="badge badge-success"><i class="fas fa-check"></i> مكتمل</span>
                                                        @elseif($radiologyTest->status === 'pending')
                                                            <span class="badge badge-warning"><i class="fas fa-clock"></i> معلق</span>
                                                        @else
                                                            <span class="badge badge-secondary">{{ $radiologyTest->status }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $radiologyTest->result ?? '-' }}</td>
                                                    <td>{{ $radiologyTest->completed_at?->format('Y-m-d') ?? '-' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <input type="hidden" name="phase" value="{{ $currentPhase ?? 'pre_op' }}">

                        <!-- ملاحظات المقيم -->
                        <div class="card shadow-sm border-primary mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-clipboard"></i> ملاحظات المقيم</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-0">
                                    <textarea name="notes" class="form-control" rows="4" placeholder="اكتب ملاحظاتك...">{{ $station?->notes ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>

                        @if(($currentPhase ?? 'pre_op') === 'post_op')
                            <!-- حقول ما بعد العملية -->
                            <div class="card shadow-sm border-warning mb-3">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-notes-medical"></i> بيانات ما بعد العملية</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label><i class="fas fa-comment-dots text-info"></i> ملاحظات ما بعد العملية</label>
                                        <textarea name="post_op_notes" class="form-control" rows="4" placeholder="اكتب ملاحظات ما بعد العملية...">{{ $station?->post_op_notes ?? '' }}</textarea>
                                    </div>

                                    <div class="form-group">
                                        <label><i class="fas fa-prescription text-success"></i> خطة العلاج</label>
                                        <textarea name="treatment_plan" class="form-control" rows="4" placeholder="اكتب خطة العلاج...">{{ $station?->treatment_plan ?? '' }}</textarea>
                                    </div>

                                    <div class="form-group">
                                        <label><i class="far fa-calendar-check text-primary"></i> موعد المراجعة</label>
                                        <input type="date" name="follow_up_date" class="form-control"
                                            value="{{ $station?->follow_up_date ? $station->follow_up_date->format('Y-m-d') : '' }}">
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> حفظ البيانات
                            </button>

                            @if($station && $station->status !== 'completed')
                                <button type="button" class="btn btn-success btn-lg" 
                                    onclick="if(confirm('هل أنت متأكد من إتمام هذه المحطة؟')) {
                                        event.preventDefault();
                                        document.getElementById('complete-form').submit();
                                    }">
                                    <i class="fas fa-check-circle"></i> إتمام المحطة
                                </button>
                            @endif
                        </div>
                    </form>

                    <!-- نموذج الإتمام -->
                    <form id="complete-form" action="{{ route('resident-station.complete', $surgery) }}" method="POST" style="display: none;">
                        @csrf
                    </form>

                    <!-- معلومات المحطة -->
                    @if($station)
                    <div class="card shadow-sm border-secondary mt-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> معلومات الحالة</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="alert alert-light border text-center mb-0">
                                        <h6 class="mb-2"><i class="fas fa-tasks"></i> حالة المحطة</h6>
                                        @if($station->status === 'pending')
                                            <span class="badge badge-warning badge-pill px-4 py-2" style="font-size: 1rem;">
                                                <i class="fas fa-hourglass-half"></i> معلقة
                                            </span>
                                        @elseif($station->status === 'in_progress')
                                            <span class="badge badge-info badge-pill px-4 py-2" style="font-size: 1rem;">
                                                <i class="fas fa-spinner"></i> جارية
                                            </span>
                                        @else
                                            <span class="badge badge-success badge-pill px-4 py-2" style="font-size: 1rem;">
                                                <i class="fas fa-check-circle"></i> مكتملة
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                @if($station->started_at)
                                <div class="col-md-4 mb-3">
                                    <div class="alert alert-light border text-center mb-0">
                                        <h6 class="mb-2"><i class="fas fa-play-circle"></i> تاريخ البدء</h6>
                                        <p class="mb-0"><strong>{{ $station->started_at->format('Y-m-d h:i A') }}</strong></p>
                                    </div>
                                </div>
                                @endif
                                @if($station->completed_at)
                                <div class="col-md-4 mb-3">
                                    <div class="alert alert-light border text-center mb-0">
                                        <h6 class="mb-2"><i class="fas fa-flag-checkered"></i> تاريخ الإتمام</h6>
                                        <p class="mb-0"><strong>{{ $station->completed_at->format('Y-m-d h:i A') }}</strong></p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
    }
    
    .form-control:focus {
        border-color: #17a2b8;
        box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
    }
    
    .table thead th {
        border-bottom: 2px solid #dee2e6;
    }
    
    .badge {
        font-weight: 500;
    }
    
    .alert-light {
        background-color: #fafbfc;
    }
    
    label i {
        margin-right: 5px;
    }
    
    .card-header h5 i,
    .card-header h6 i {
        margin-right: 8px;
    }
</style>
@endpush
