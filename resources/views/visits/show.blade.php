<!-- resources/views/visits/show.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-file-medical-alt me-2"></i>
                    تفاصيل الزيارة
                </h2>
                <div>
                    <a href="{{ route('visits.edit', $visit) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>تعديل
                    </a>
                    <a href="{{ route('visits.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-right me-2"></i>العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">التقرير الطبي</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th>نوع الزيارة:</th>
                                    <td>
                                        <span class="badge bg-info">{{ $visit->visit_type_text }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>حالة الزيارة:</th>
                                    <td>
                                        <span class="badge bg-{{ $visit->status == 'completed' ? 'success' : ($visit->status == 'in_progress' ? 'warning' : ($visit->status == 'cancelled' ? 'danger' : 'secondary')) }}">
                                            {{ $visit->status_text }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>تاريخ الزيارة:</th>
                                    <td>{{ $visit->visit_date ? $visit->visit_date->format('Y-m-d') : 'غير محدد' }}</td>
                                </tr>
                                <tr>
                                    <th>المريض:</th>
                                    <td>
                                        <a href="{{ route('patients.show', $visit->patient) }}" class="text-decoration-none">
                                            {{ $visit->patient->user->name }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>الطبيب:</th>
                                    <td>د. {{ $visit->doctor?->user?->name ?? 'غير محدد' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">العيادة:</th>
                                    <td>{{ $visit->department->name }}</td>
                                </tr>
                                <tr>
                                    <th>رقم الغرفة:</th>
                                    <td>{{ $visit->department->room_number }}</td>
                                </tr>
                                @if($visit->next_visit_date)
                                <tr>
                                    <th>موعد المتابعة:</th>
                                    <td class="text-success">
                                        <strong>{{ $visit->next_visit_date ? $visit->next_visit_date->format('Y-m-d') : 'غير محدد' }}</strong>
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <hr>

                    <!-- العلامات الحيوية -->
                    @if($visit->vital_signs_formatted)
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>العلامات الحيوية:</h6>
                            <div class="bg-light p-3 rounded">
                                {{ $visit->vital_signs_formatted }}
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- الشكوى الرئيسية -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>الشكوى الرئيسية:</h6>
                            <div class="bg-light p-3 rounded">
                                {{ $visit->chief_complaint }}
                            </div>
                        </div>
                    </div>

                    <!-- التشخيص -->
                    @if($visit->diagnosis)
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>التشخيص:</h6>
                            <div class="bg-light p-3 rounded">
                                @php $diag = is_string($visit->diagnosis) ? json_decode($visit->diagnosis, true) : $visit->diagnosis; @endphp
                                @if($diag['code'] ?? false)
                                    @if($diag['code'] === 'other' && isset($diag['custom_code']))
                                        <strong>رمز ICD:</strong> {{ $diag['custom_code'] }}<br>
                                    @elseif($diag['code'] !== 'other')
                                        <strong>رمز ICD-10:</strong> {{ $diag['code'] }}<br>
                                    @endif
                                @endif
                                <strong>الوصف:</strong> {{ $diag['description'] ?? $visit->diagnosis }}
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- العلاج -->
                    @if($visit->treatment)
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>العلاج:</h6>
                            <div class="bg-light p-3 rounded">
                                {{ $visit->treatment }}
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- الوصفة الطبية -->
                    @if($visit->prescription)
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>الوصفة الطبية:</h6>
                            <div class="bg-light p-3 rounded">
                                {{ $visit->prescription }}
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- ملاحظات -->
                    @if($visit->notes)
                    <div class="row">
                        <div class="col-12">
                            <h6>ملاحظات:</h6>
                            <div class="bg-light p-3 rounded">
                                {{ $visit->notes }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- معلومات سريعة -->
        <div class="col-lg-4">
            <!-- معلومات المريض -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-user-injured me-2"></i>معلومات المريض</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar-sm bg-primary rounded-circle mx-auto d-flex align-items-center justify-content-center mb-2">
                            <span class="text-white fw-bold">
                                {{ substr($visit->patient->user->name, 0, 1) }}
                            </span>
                        </div>
                        <h6>{{ $visit->patient->user->name }}</h6>
                    </div>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th>العمر:</th>
                            <td>{{ $visit->patient->age }} سنة</td>
                        </tr>
                        <tr>
                            <th>الهاتف:</th>
                            <td>{{ $visit->patient->user->phone }}</td>
                        </tr>
                        <tr>
                            <th>فصيلة الدم:</th>
                            <td>
                                @if($visit->patient->blood_type)
                                    <span class="badge bg-danger">{{ $visit->patient->blood_type }}</span>
                                @else
                                    <span class="text-muted">---</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>رقم الطوارئ:</th>
                            <td>{{ $visit->patient->emergency_contact ?? '---' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- إجراءات سريعة -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>إجراءات سريعة</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('patients.show', $visit->patient) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user me-2"></i>عرض ملف المريض
                        </a>
                        <a href="{{ route('visits.create', ['patient_id' => $visit->patient->id]) }}" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-plus me-2"></i>زيارة جديدة لنفس المريض
                        </a>
                        @if($visit->next_visit_date)
                        <a href="{{ route('appointments.create', ['patient_id' => $visit->patient->id, 'doctor_id' => $visit->doctor->id]) }}" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-calendar-plus me-2"></i>حجز موعد متابعة
                        </a>
                        @endif
                        @if($visit->is_completed)
                        <form action="{{ route('visits.request-surgery', $visit) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('هل أنت متأكد من طلب عملية لهذا المريض؟')">
                                <i class="fas fa-procedures me-2"></i>طلب عملية جراحية
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-sm {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}
</style>
@endsection