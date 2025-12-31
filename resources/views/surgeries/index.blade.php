@extends('layouts.app')

@section('styles')
<style>
/* Surgery Treatments Table Styles */
.surgery-treatment-row:hover {
    background-color: #f8f9fa;
}

.frequency-btn {
    padding: 6px 10px;
    border: 2px solid #e9ecef;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    background: white;
    display: inline-block;
    margin: 2px;
}

.frequency-btn:hover {
    border-color: #007bff;
    background-color: #f8f9fa;
}

input[type="radio"]:checked + .frequency-btn {
    border-color: #007bff;
    background-color: #007bff;
    color: white;
}

/* Surgery Treatments Table */
#surgeryTreatmentsTable th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

#surgeryTreatmentsTable td {
    vertical-align: middle;
}

/* Form controls in table */
.form-select-sm, .form-control-sm {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
}

/* Duration input group */
.duration-input-group {
    display: flex;
    gap: 2px;
    align-items: center;
}

.duration-input-group input {
    flex: 1;
    min-width: 50px;
}

.duration-input-group select {
    flex: 1;
    min-width: 70px;
}

/* Timing textarea */
.timing-textarea {
    min-height: 60px;
    resize: vertical;
}

/* Responsive table */
@media (max-width: 768px) {
    #surgeryTreatmentsTable {
        font-size: 0.8rem;
    }

    #surgeryTreatmentsTable .form-select-sm,
    #surgeryTreatmentsTable .form-control-sm {
        font-size: 0.75rem;
        padding: 0.2rem 0.4rem;
    }

    .duration-input-group {
        flex-direction: column;
        gap: 1px;
    }

    .timing-textarea {
        min-height: 40px;
    }
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-procedures me-2"></i>
                    إدارة العمليات الجراحية
                </h2>
                <div>
                    <a href="{{ route('surgeries.waiting') }}" class="btn btn-info text-white me-2">
                        <i class="fas fa-list-ol me-2"></i>قائمة الانتظار
                    </a>
                    @if(!auth()->user()->isDoctor())
                    <a href="{{ route('surgeries.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>حجز عملية جديدة
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- شريط البحث والفلترة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('surgeries.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">البحث</label>
                            <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="ابحث بالمريض أو الطبيب أو نوع العملية...">
                        </div>
                        <div class="col-md-3">
                            <label for="status_filter" class="form-label">الحالة</label>
                            <select class="form-select" id="status_filter" name="status">
                                <option value="">جميع الحالات</option>
                                <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>مجدولة</option>
                                <option value="waiting" {{ request('status') == 'waiting' ? 'selected' : '' }}>في الانتظار</option>
                                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>جارية</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتملة</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_filter" class="form-label">التاريخ</label>
                            <input type="date" class="form-control" id="date_filter" name="date" value="{{ request('date') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <div class="btn-group w-100">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search me-1"></i>بحث
                                    </button>
                                    @if(request('search') || request('status') || request('date'))
                                    <a href="{{ route('surgeries.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>مسح
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- التبويبات -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs" id="surgeriesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab">
                        <i class="fas fa-clock me-1"></i>العمليات النشطة
                        @if($activeSurgeries->count() > 0)
                        <span class="badge bg-success ms-1">{{ $activeSurgeries->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab">
                        <i class="fas fa-check-circle me-1"></i>العمليات المكتملة والملغاة
                        @if($completedSurgeries->count() > 0)
                        <span class="badge bg-secondary ms-1">{{ $completedSurgeries->count() }}</span>
                        @endif
                    </button>
                </li>
            </ul>

            <div class="tab-content mt-3" id="surgeriesTabsContent">
                <!-- تبويب العمليات النشطة -->
                <div class="tab-pane fade show active" id="active" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>العمليات النشطة (المجدولة والمنتظرة والجارية)</h5>
                        </div>
                        <div class="card-body">
                            @if($activeSurgeries->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>المريض</th>
                                            <th>الطبيب</th>
                                            <th>نوع العملية</th>
                                            <th>التاريخ</th>
                                            <th>الوقت</th>
                                            <th>الحالة</th>
                                            <th>الأشعة</th>
                                            <th>المختبر</th>
                                            <th>العلاج</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($activeSurgeries as $surgery)
                                        <tr>
                                            <td>
                                                <i class="fas fa-user-injured text-primary me-1"></i>
                                                {{ $surgery->patient->user->name }}
                                                                {{ $surgery->patient->user->name }}
                                                        </td>
                                                        <td>
                                                                <i class="fas fa-user-md text-success me-1"></i>
                                                                د. {{ $surgery->doctor->user->name }}
                                                        </td>
                                                        <td>{{ $surgery->surgery_type }}</td>
                                                        <td>{{ $surgery->scheduled_date->format('Y-m-d') }}</td>
                                                        <td>{{ $surgery->scheduled_time }}</td>
                                                        <td>
                                                                @if($surgery->status == 'scheduled')
                                                                        <span class="badge bg-secondary">مجدولة</span>
                                                                @elseif($surgery->status == 'waiting')
                                                                        <span class="badge bg-info text-dark">في الانتظار</span>
                                                                @elseif($surgery->status == 'in_progress')
                                                                        <span class="badge bg-warning">جارية</span>
                                                                @elseif($surgery->status == 'completed')
                                                                        <span class="badge bg-success">مكتملة</span>
                                                                @else
                                                                        <span class="badge bg-danger">ملغاة</span>
                                                                @endif
                                                        </td>
                                                        <td>
                                                                @if($surgery->radiologyTests->count())
                                                                    @php
                                                                        $radiologyCompleted = $surgery->radiologyTests->count() > 0 && $surgery->radiologyTests->every(fn($test) => $test->status == 'completed');
                                                                    @endphp
                                                                    <a href="#" data-bs-toggle="modal" data-bs-target="#radiologyModalActive{{ $surgery->id }}">
                                                                        <span class="badge {{ $radiologyCompleted ? 'bg-success' : 'bg-danger' }}">{{ $surgery->radiologyTests->count() }}</span>
                                                                    </a>
                                                                        <!-- Modal -->
                                                                        <div class="modal fade" id="radiologyModalActive{{ $surgery->id }}" tabindex="-1" aria-labelledby="radiologyModalActiveLabel{{ $surgery->id }}" aria-hidden="true">
                                                                            <div class="modal-dialog">
                                                                                <div class="modal-content">
                                                                                    <div class="modal-header">
                                                                                        <h5 class="modal-title" id="radiologyModalActiveLabel{{ $surgery->id }}">تفاصيل الأشعة المطلوبة</h5>
                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                                                                    </div>
                                                                                    <div class="modal-body">
                                                                                        <table class="table table-bordered">
                                                                                            <thead>
                                                                                                <tr>
                                                                                                    <th>نوع الأشعة</th>
                                                                                                    <th>الحالة</th>
                                                                                                    <th>النتيجة</th>
                                                                                                </tr>
                                                                                            </thead>
                                                                                            <tbody>
                                                                                                @foreach($surgery->radiologyTests as $test)
                                                                                                    <tr>
                                                                                                        <td>{{ $test->radiologyType->name ?? '-' }}</td>
                                                                                                        <td>{{ $test->statusText ?? '-' }}</td>
                                                                                                        <td>
                                                                                                            @if($test->result)
                                                                                                                <p class="mb-1">{{ $test->result }}</p>
                                                                                                            @endif
                                                                                                            
                                                                                                            @if($test->result_file)
                                                                                                                <div class="mt-1">
                                                                                                                    <a href="{{ asset('storage/' . $test->result_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                                                                        <i class="fas fa-paperclip me-1"></i>
                                                                                                                        مرفق
                                                                                                                    </a>
                                                                                                                </div>
                                                                                                            @else
                                                                                                                @if(!$test->result)
                                                                                                                    -
                                                                                                                @endif
                                                                                                            @endif
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                @endforeach
                                                                                            </tbody>
                                                                                        </table>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                @else
                                                                        <span class="text-muted">لا يوجد</span>
                                                                @endif
                                                        </td>
                                                        <td>
                                                                @if($surgery->labTests->count())
                                                                    @php
                                                                        $labCompleted = $surgery->labTests->count() > 0 && $surgery->labTests->every(fn($test) => $test->status == 'completed');
                                                                    @endphp
                                                                    <a href="#" data-bs-toggle="modal" data-bs-target="#labModalActive{{ $surgery->id }}">
                                                                        <span class="badge {{ $labCompleted ? 'bg-success' : 'bg-danger' }}">{{ $surgery->labTests->count() }}</span>
                                                                    </a>
                                                                        <!-- Modal -->
                                                                        <div class="modal fade" id="labModalActive{{ $surgery->id }}" tabindex="-1" aria-labelledby="labModalActiveLabel{{ $surgery->id }}" aria-hidden="true">
                                                                            <div class="modal-dialog">
                                                                                <div class="modal-content">
                                                                                    <div class="modal-header">
                                                                                        <h5 class="modal-title" id="labModalActiveLabel{{ $surgery->id }}">تفاصيل التحاليل المخبرية المطلوبة</h5>
                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                                                                    </div>
                                                                                    <div class="modal-body">
                                                                                        <table class="table table-bordered">
                                                                                            <thead>
                                                                                                <tr>
                                                                                                    <th>اسم التحليل</th>
                                                                                                    <th>الحالة</th>
                                                                                                    <th>النتيجة</th>
                                                                                                </tr>
                                                                                            </thead>
                                                                                            <tbody>
                                                                                                @foreach($surgery->labTests as $test)
                                                                                                    <tr>
                                                                                                        <td>{{ $test->labTest->name ?? '-' }}</td>
                                                                                                        <td>{{ $test->statusText ?? '-' }}</td>
                                                                                                        <td>{{ $test->result ?? '-' }}</td>
                                                                                                    </tr>
                                                                                                @endforeach
                                                                                            </tbody>
                                                                                        </table>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                @else
                                                                        <span class="text-muted">لا يوجد</span>
                                                                @endif
                                                        </td>
                                                        <td>
                                                                @if($surgery->surgeryTreatments->count())
                                                                    <a href="#" data-bs-toggle="modal" data-bs-target="#treatmentModalActive{{ $surgery->id }}">
                                                                        <span class="badge bg-info">{{ $surgery->surgeryTreatments->count() }}</span>
                                                                    </a>
                                                                        <!-- Modal -->
                                                                        <div class="modal fade" id="treatmentModalActive{{ $surgery->id }}" tabindex="-1" aria-labelledby="treatmentModalActiveLabel{{ $surgery->id }}" aria-hidden="true">
                                                                            <div class="modal-dialog">
                                                                                <div class="modal-content">
                                                                                    <div class="modal-header">
                                                                                        <h5 class="modal-title" id="treatmentModalActiveLabel{{ $surgery->id }}">تفاصيل العلاج المطلوب</h5>
                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                                                                    </div>
                                                                                    <div class="modal-body">
                                                                                        <table class="table table-bordered">
                                                                                            <thead>
                                                                                                <tr>
                                                                                                    <th>وصف العلاج</th>
                                                                                                    <th>الجرعة</th>
                                                                                                    <th>التوقيت</th>
                                                                                                    <th>المدة</th>
                                                                                                </tr>
                                                                                            </thead>
                                                                                            <tbody>
                                                                                                @foreach($surgery->surgeryTreatments as $treatment)
                                                                                                    <tr>
                                                                                                        <td>{{ $treatment->description }}</td>
                                                                                                        <td>{{ $treatment->dosage ?? '-' }}</td>
                                                                                                        <td>{{ $treatment->timing ?? '-' }}</td>
                                                                                                        <td>
                                                                                                            @if($treatment->duration_value && $treatment->duration_unit)
                                                                                                                {{ $treatment->duration_value }} {{ $treatment->duration_unit == 'days' ? 'يوم' : ($treatment->duration_unit == 'weeks' ? 'أسبوع' : ($treatment->duration_unit == 'months' ? 'شهر' : $treatment->duration_unit)) }}
                                                                                                            @else
                                                                                                                -
                                                                                                            @endif
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                @endforeach
                                                                                            </tbody>
                                                                                        </table>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                @else
                                                                        <span class="text-muted">لا يوجد</span>
                                                                @endif
                                                        </td>
                                                        <td>
                                                                <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-info me-1">
                                                                        <i class="fas fa-eye"></i>
                                                                </a>
                                                                
                                                                @php
                                                                    $canManageSurgery = auth()->user()->hasRole(['surgery_staff', 'admin']) || 
                                                                                       (auth()->user()->isDoctor() && auth()->user()->doctor && auth()->user()->doctor->id == $surgery->doctor_id);
                                                                    $canCheckInPatient = auth()->user()->hasRole(['surgery_staff', 'admin']);
                                                                @endphp
                                                                
                                                                @if($canManageSurgery)
                                                                    <a href="{{ route('surgeries.edit', $surgery) }}" class="btn btn-sm btn-warning me-1">
                                                                            <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    @if($surgery->status == 'completed')
                                                                    <button class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#detailsModal{{ $surgery->id }}">
                                                                            <i class="fas fa-clipboard-check"></i>
                                                                    </button>
                                                                    @endif
                                                                @endif

                                                                @if($canCheckInPatient && $surgery->status == 'scheduled')
                                                                    <form action="{{ route('surgeries.check-in', $surgery) }}" method="POST" class="d-inline me-1">
                                                                            @csrf
                                                                            <button type="submit" class="btn btn-sm btn-primary" title="دخول المريض">
                                                                                    <i class="fas fa-check"></i>
                                                                            </button>
                                                                    </form>
                                                                @endif

                                                                @if($canManageSurgery)
                                                                    @if($surgery->status == 'waiting')
                                                                    <form action="{{ route('surgeries.start', $surgery) }}" method="POST" class="d-inline me-1">
                                                                            @csrf
                                                                            <button type="submit" class="btn btn-sm btn-success" title="بدء العملية">
                                                                                    <i class="fas fa-play"></i>
                                                                            </button>
                                                                    </form>
                                                                    @elseif($surgery->status == 'in_progress')
                                                                    <form action="{{ route('surgeries.complete', $surgery) }}" method="POST" class="d-inline me-1">
                                                                            @csrf
                                                                            <button type="submit" class="btn btn-sm btn-success" title="إكمال العملية">
                                                                                    <i class="fas fa-check-circle"></i>
                                                                            </button>
                                                                    </form>
                                                                    <form action="{{ route('surgeries.return-to-waiting', $surgery) }}" method="POST" class="d-inline me-1">
                                                                            @csrf
                                                                            <button type="submit" class="btn btn-sm btn-warning" title="إعادة للانتظار" onclick="return confirm('هل أنت متأكد من إعادة العملية إلى قائمة الانتظار؟')">
                                                                                    <i class="fas fa-undo"></i>
                                                                            </button>
                                                                    </form>
                                                                    <form action="{{ route('surgeries.cancel', $surgery) }}" method="POST" class="d-inline me-1">
                                                                            @csrf
                                                                            <button type="submit" class="btn btn-sm btn-danger" title="إلغاء العملية" onclick="return confirm('هل أنت متأكد من إلغاء العملية؟')">
                                                                                    <i class="fas fa-times-circle"></i>
                                                                            </button>
                                                                    </form>
                                                                    @endif
                                                                @endif
                                                        </td>
                                                </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if($activeSurgeries->hasPages())
                            <div class="mt-3">
                                {{ $activeSurgeries->links() }}
                            </div>
                            @endif
                            @else
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">لا توجد عمليات نشطة</h5>
                                <p class="text-muted">جميع العمليات المجدولة مكتملة أو ملغاة</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- تبويب العمليات المكتملة والملغاة -->
                <div class="tab-pane fade" id="completed" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>العمليات المكتملة والملغاة</h5>
                        </div>
                        <div class="card-body">
                            @if($completedSurgeries->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>المريض</th>
                                            <th>الطبيب</th>
                                            <th>نوع العملية</th>
                                            <th>التاريخ</th>
                                            <th>الوقت</th>
                                            <th>الحالة</th>
                                            <th>الأشعة</th>
                                            <th>المختبر</th>
                                            <th>العلاج</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($completedSurgeries as $surgery)
                                        <tr>
                                            <td>
                                                <i class="fas fa-user-injured text-primary me-1"></i>
                                                {{ $surgery->patient->user->name }}
                                            </td>
                                            <td>
                                                <i class="fas fa-user-md text-success me-1"></i>
                                                د. {{ $surgery->doctor->user->name }}
                                            </td>
                                            <td>{{ $surgery->surgery_type }}</td>
                                            <td>{{ $surgery->scheduled_date->format('Y-m-d') }}</td>
                                            <td>{{ $surgery->scheduled_time }}</td>
                                            <td>
                                                @if($surgery->status == 'completed')
                                                    <span class="badge bg-success">مكتملة</span>
                                                @else
                                                    <span class="badge bg-danger">ملغاة</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($surgery->radiologyTests->count())
                                                    @php
                                                        $radiologyCompleted = $surgery->radiologyTests->count() > 0 && $surgery->radiologyTests->every(fn($test) => $test->status == 'completed');
                                                    @endphp
                                                    <a href="#" data-bs-toggle="modal" data-bs-target="#radiologyModalCompleted{{ $surgery->id }}">
                                                        <span class="badge {{ $radiologyCompleted ? 'bg-success' : 'bg-danger' }}">{{ $surgery->radiologyTests->count() }}</span>
                                                    </a>
                                                @else
                                                    <span class="text-muted">لا يوجد</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($surgery->labTests->count())
                                                    @php
                                                        $labCompleted = $surgery->labTests->count() > 0 && $surgery->labTests->every(fn($test) => $test->status == 'completed');
                                                    @endphp
                                                    <a href="#" data-bs-toggle="modal" data-bs-target="#labModalCompleted{{ $surgery->id }}">
                                                        <span class="badge {{ $labCompleted ? 'bg-success' : 'bg-danger' }}">{{ $surgery->labTests->count() }}</span>
                                                    </a>
                                                @else
                                                    <span class="text-muted">لا يوجد</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($surgery->surgeryTreatments->count())
                                                    <a href="#" data-bs-toggle="modal" data-bs-target="#treatmentModalCompleted{{ $surgery->id }}">
                                                        <span class="badge bg-info">{{ $surgery->surgeryTreatments->count() }}</span>
                                                    </a>
                                                @else
                                                    <span class="text-muted">لا يوجد</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-info me-1">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                @php
                                                    $canManageSurgery = auth()->user()->hasRole(['surgery_staff', 'admin']) || 
                                                                       (auth()->user()->isDoctor() && auth()->user()->doctor && auth()->user()->doctor->id == $surgery->doctor_id);
                                                @endphp
                                                
                                                @if($canManageSurgery && $surgery->status == 'completed')
                                                <button class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#detailsModalCompleted{{ $surgery->id }}">
                                                    <i class="fas fa-clipboard-check"></i>
                                                </button>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination للعمليات المكتملة -->
                            <div class="d-flex justify-content-center mt-3">
                                {{ $completedSurgeries->links() }}
                            </div>
                            
                            @else
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">لا توجد عمليات مكتملة أو ملغاة</h5>
                                <p class="text-muted">جميع العمليات لا تزال نشطة</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals for Active Surgeries -->
    @foreach($activeSurgeries as $surgery)
    @if($surgery->status == 'completed')
    <div class="modal fade" id="detailsModal{{ $surgery->id }}" tabindex="-1" aria-labelledby="detailsModalLabel{{ $surgery->id }}" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="detailsModalLabel{{ $surgery->id }}">
                        <i class="fas fa-clipboard-check me-2"></i>
                        تفاصيل العملية الجراحية
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form action="{{ route('surgeries.updateDetails', $surgery) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body p-0">
                        <!-- Patient Info Header -->
                        <div class="bg-light p-3 border-bottom">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1">
                                        <i class="fas fa-user-injured text-primary me-2"></i>
                                        المريض: <strong>{{ $surgery->patient->user->name }}</strong>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fas fa-user-md me-1"></i>
                                        الطبيب: د. {{ $surgery->doctor->user->name }} |
                                        <i class="fas fa-procedures me-1"></i>
                                        العملية: {{ $surgery->surgery_type }} |
                                        <i class="fas fa-calendar me-1"></i>
                                        التاريخ: {{ $surgery->scheduled_date->format('Y-m-d') }}
                                    </small>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-success fs-6 px-3 py-2">
                                        <i class="fas fa-check-circle me-1"></i>
                                        العملية مكتملة
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4">
                            <!-- Surgery Details Accordion -->
                            <div class="accordion" id="surgeryDetailsAccordion{{ $surgery->id }}">
                                <!-- Diagnosis Section -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="diagnosisHeading{{ $surgery->id }}">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#diagnosisCollapse{{ $surgery->id }}" aria-expanded="true"
                                                aria-controls="diagnosisCollapse{{ $surgery->id }}">
                                            <i class="fas fa-stethoscope me-2 text-primary"></i>
                                            التشخيص والتخدير
                                        </button>
                                    </h2>
                                    <div id="diagnosisCollapse{{ $surgery->id }}" class="accordion-collapse collapse show"
                                         aria-labelledby="diagnosisHeading{{ $surgery->id }}"
                                         data-bs-parent="#surgeryDetailsAccordion{{ $surgery->id }}">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="diagnosis{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-diagnoses text-primary me-1"></i>
                                                            التشخيص
                                                        </label>
                                                        <textarea class="form-control" id="diagnosis{{ $surgery->id }}" name="diagnosis" rows="3"
                                                                  placeholder="أدخل التشخيص الطبي...">{{ $surgery->diagnosis }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="anesthesia_type{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-syringe text-success me-1"></i>
                                                            نوع التخدير
                                                        </label>
                                                        <select class="form-select" id="anesthesia_type{{ $surgery->id }}" name="anesthesia_type">
                                                            <option value="">اختر نوع التخدير</option>
                                                            <option value="local" {{ $surgery->anesthesia_type == 'local' ? 'selected' : '' }}>تخدير موضعي</option>
                                                            <option value="regional" {{ $surgery->anesthesia_type == 'regional' ? 'selected' : '' }}>تخدير إقليمي</option>
                                                            <option value="general" {{ $surgery->anesthesia_type == 'general' ? 'selected' : '' }}>تخدير عام</option>
                                                            <option value="sedation" {{ $surgery->anesthesia_type == 'sedation' ? 'selected' : '' }}>تخدير إيحائي</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Medical Team Section -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="teamHeading{{ $surgery->id }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#teamCollapse{{ $surgery->id }}" aria-expanded="false"
                                                aria-controls="teamCollapse{{ $surgery->id }}">
                                            <i class="fas fa-users me-2 text-info"></i>
                                            الفريق الطبي
                                        </button>
                                    </h2>
                                    <div id="teamCollapse{{ $surgery->id }}" class="accordion-collapse collapse"
                                         aria-labelledby="teamHeading{{ $surgery->id }}"
                                         data-bs-parent="#surgeryDetailsAccordion{{ $surgery->id }}">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="anesthesiologist_id{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-user-nurse text-info me-1"></i>
                                                            طبيب التخدير
                                                        </label>
                                                        <select class="form-select" id="anesthesiologist_id{{ $surgery->id }}" name="anesthesiologist_id">
                                                            <option value="">اختر طبيب التخدير</option>
                                                            @foreach(\App\Models\Doctor::where('specialization', 'like', '%تخدير%')->orWhere('specialization', 'like', '%anesthesia%')->get() as $doctor)
                                                            <option value="{{ $doctor->id }}" {{ $surgery->anesthesiologist_id == $doctor->id ? 'selected' : '' }}>
                                                                د. {{ $doctor->user->name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="anesthesiologist_2_id{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-user-nurse text-info me-1"></i>
                                                            طبيب تخدير مساعد
                                                        </label>
                                                        <select class="form-select" id="anesthesiologist_2_id{{ $surgery->id }}" name="anesthesiologist_2_id">
                                                            <option value="">اختر طبيب التخدير المساعد</option>
                                                            @foreach(\App\Models\Doctor::where('specialization', 'like', '%تخدير%')->orWhere('specialization', 'like', '%anesthesia%')->get() as $doctor)
                                                            <option value="{{ $doctor->id }}" {{ $surgery->anesthesiologist_2_id == $doctor->id ? 'selected' : '' }}>
                                                                د. {{ $doctor->user->name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="surgical_assistant_name{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-user-friends text-warning me-1"></i>
                                                            اسم المساعد الجراحي
                                                        </label>
                                                        <input type="text" class="form-control" id="surgical_assistant_name{{ $surgery->id }}" name="surgical_assistant_name"
                                                               value="{{ $surgery->surgical_assistant_name }}" placeholder="أدخل اسم المساعد الجراحي">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="supplies{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-tools text-secondary me-1"></i>
                                                            اللوازم المستخدمة
                                                        </label>
                                                        <textarea class="form-control" id="supplies{{ $surgery->id }}" name="supplies" rows="2"
                                                                  placeholder="أدخل اللوازم والأدوات المستخدمة...">{{ $surgery->supplies }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Timing Section -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="timingHeading{{ $surgery->id }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#timingCollapse{{ $surgery->id }}" aria-expanded="false"
                                                aria-controls="timingCollapse{{ $surgery->id }}">
                                            <i class="fas fa-clock me-2 text-warning"></i>
                                            التوقيت والمدة
                                        </button>
                                    </h2>
                                    <div id="timingCollapse{{ $surgery->id }}" class="accordion-collapse collapse"
                                         aria-labelledby="timingHeading{{ $surgery->id }}"
                                         data-bs-parent="#surgeryDetailsAccordion{{ $surgery->id }}">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="start_time{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-play-circle text-success me-1"></i>
                                                            وقت البدء
                                                        </label>
                                                        <input type="time" class="form-control" id="start_time{{ $surgery->id }}" name="start_time"
                                                               value="{{ $surgery->start_time ? (is_string($surgery->start_time) ? \Carbon\Carbon::parse($surgery->start_time)->format('H:i') : $surgery->start_time->format('H:i')) : ($surgery->started_at ? $surgery->started_at->format('H:i') : '') }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="end_time{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-stop-circle text-danger me-1"></i>
                                                            وقت الانتهاء
                                                        </label>
                                                        <input type="time" class="form-control" id="end_time{{ $surgery->id }}" name="end_time"
                                                               value="{{ $surgery->end_time ? (is_string($surgery->end_time) ? \Carbon\Carbon::parse($surgery->end_time)->format('H:i') : $surgery->end_time->format('H:i')) : '' }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="estimated_duration{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-hourglass-half text-primary me-1"></i>
                                                            المدة المقدرة
                                                        </label>
                                                        <input type="text" class="form-control bg-light" id="estimated_duration{{ $surgery->id }}" name="estimated_duration"
                                                               value="{{ $surgery->estimated_duration ? \Carbon\CarbonInterval::minutes($surgery->estimated_duration)->cascade()->format('%H:%I') : '' }}"
                                                               placeholder="س:د (مثال: 02:30)" readonly>
                                                        <small class="form-text text-muted">يتم حسابها تلقائياً من وقت البدء والانتهاء</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Treatment Section -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="treatmentHeading{{ $surgery->id }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#treatmentCollapse{{ $surgery->id }}" aria-expanded="false"
                                                aria-controls="treatmentCollapse{{ $surgery->id }}">
                                            <i class="fas fa-pills me-2 text-info"></i>
                                            خطة العلاج
                                        </button>
                                    </h2>
                                    <div id="treatmentCollapse{{ $surgery->id }}" class="accordion-collapse collapse"
                                         aria-labelledby="treatmentHeading{{ $surgery->id }}"
                                         data-bs-parent="#surgeryDetailsAccordion{{ $surgery->id }}">
                                        <div class="accordion-body">
                                            <!-- جدول علاج العمليات -->
                                            <div class="mb-4">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <label class="form-label">
                                                        <i class="fas fa-table text-primary me-2"></i>
                                                        جدول علاج العمليات
                                                    </label>
                                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addSurgeryTreatment({{ $surgery->id }})">
                                                        <i class="fas fa-plus me-1"></i>
                                                        إضافة علاج
                                                    </button>
                                                </div>

                                                <div class="table-responsive">
                                                    <table class="table table-bordered" id="surgeryTreatmentsTable{{ $surgery->id }}">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th width="5%">الرقم</th>
                                                                <th width="30%">وصف العلاج</th>
                                                                <th width="20%">الجرعة/الكمية</th>
                                                                <th width="20%">التوقيت/التكرار</th>
                                                                <th width="15%">المدة</th>
                                                                <th width="10%">الإجراءات</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="surgeryTreatmentsContainer{{ $surgery->id }}">
                                                            @php
                                                                $savedSurgeryTreatments = $surgery->surgeryTreatments ?? collect();
                                                            @endphp
                                                            @foreach($savedSurgeryTreatments as $index => $treatment)
                                                            <tr class="surgery-treatment-row">
                                                                <td class="text-center">{{ $index + 1 }}</td>
                                                                <td>
                                                                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][description]"
                                                                           value="{{ $treatment->description ?? '' }}"
                                                                           placeholder="اسم الدواء أو وصف العلاج">
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][dosage]"
                                                                           value="{{ $treatment->dosage ?? '' }}"
                                                                           placeholder="مثال: 500mg, 2ml">
                                                                </td>
                                                                <td>
                                                                    <textarea class="form-control form-control-sm timing-textarea" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][timing]" rows="2"
                                                                              placeholder="مثال: كل 6 ساعات، صباحاً ومساءً، قبل العملية بساعة">{{ $treatment->timing ?? '' }}</textarea>
                                                                </td>
                                                                <td>
                                                                    <div class="duration-input-group">
                                                                        <input type="number" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][duration_value]"
                                                                               value="{{ $treatment->duration_value ?? '' }}"
                                                                               placeholder="العدد" min="1">
                                                                        <select class="form-select form-select-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][duration_unit]">
                                                                            <option value="days" {{ ($treatment->duration_unit ?? '') == 'days' ? 'selected' : '' }}>يوم</option>
                                                                            <option value="weeks" {{ ($treatment->duration_unit ?? '') == 'weeks' ? 'selected' : '' }}>أسبوع</option>
                                                                            <option value="months" {{ ($treatment->duration_unit ?? '') == 'months' ? 'selected' : '' }}>شهر</option>
                                                                            <option value="hours" {{ ($treatment->duration_unit ?? '') == 'hours' ? 'selected' : '' }}>ساعة</option>
                                                                            <option value="doses" {{ ($treatment->duration_unit ?? '') == 'doses' ? 'selected' : '' }}>جرعة</option>
                                                                        </select>
                                                                    </div>
                                                                </td>
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSurgeryTreatment(this)">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                            @if($savedSurgeryTreatments->isEmpty())
                                                            <tr id="emptySurgeryTreatmentsRow{{ $surgery->id }}">
                                                                <td colspan="6" class="text-center py-4 text-muted">
                                                                    <i class="fas fa-table fa-2x mb-2"></i>
                                                                    <p>لا توجد علاجات محددة للعملية</p>
                                                                    <small>اضغط على "إضافة علاج" لبدء إضافة علاجات العملية</small>
                                                                </td>
                                                            </tr>
                                                            @endif
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Surgery Classification Section -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="classificationHeading{{ $surgery->id }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#classificationCollapse{{ $surgery->id }}" aria-expanded="false"
                                                aria-controls="classificationCollapse{{ $surgery->id }}">
                                            <i class="fas fa-tags me-2 text-primary"></i>
                                            تصنيف العملية ونوعها
                                        </button>
                                    </h2>
                                    <div id="classificationCollapse{{ $surgery->id }}" class="accordion-collapse collapse"
                                         aria-labelledby="classificationHeading{{ $surgery->id }}"
                                         data-bs-parent="#surgeryDetailsAccordion{{ $surgery->id }}">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="surgery_category{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-layer-group text-primary me-1"></i>
                                                            تصنيف العملية
                                                        </label>
                                                        <select class="form-select" id="surgery_category{{ $surgery->id }}" name="surgery_category">
                                                            <option value="">اختر التصنيف</option>
                                                            <option value="elective" {{ $surgery->surgery_category == 'elective' ? 'selected' : '' }}>اختيارية</option>
                                                            <option value="emergency" {{ $surgery->surgery_category == 'emergency' ? 'selected' : '' }}>طارئة</option>
                                                            <option value="urgent" {{ $surgery->surgery_category == 'urgent' ? 'selected' : '' }}>عاجلة</option>
                                                            <option value="semi_urgent" {{ $surgery->surgery_category == 'semi_urgent' ? 'selected' : '' }}>شبه عاجلة</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="surgery_type_detail{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-procedures text-info me-1"></i>
                                                            نوع العملية
                                                        </label>
                                                        <select class="form-select" id="surgery_type_detail{{ $surgery->id }}" name="surgery_type_detail">
                                                            <option value="">اختر نوع العملية</option>
                                                            <option value="diagnostic" {{ $surgery->surgery_type_detail == 'diagnostic' ? 'selected' : '' }}>تشخيصية</option>
                                                            <option value="therapeutic" {{ $surgery->surgery_category == 'therapeutic' ? 'selected' : '' }}>علاجية</option>
                                                            <option value="preventive" {{ $surgery->surgery_type_detail == 'preventive' ? 'selected' : '' }}>وقائية</option>
                                                            <option value="cosmetic" {{ $surgery->surgery_type_detail == 'cosmetic' ? 'selected' : '' }}>تجميلية</option>
                                                            <option value="reconstructive" {{ $surgery->surgery_type_detail == 'reconstructive' ? 'selected' : '' }}>ترميمية</option>
                                                            <option value="palliative" {{ $surgery->surgery_type_detail == 'palliative' ? 'selected' : '' }}>تخفيفية</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="anesthesia_position{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-bed text-warning me-1"></i>
                                                            نمط الرقود
                                                        </label>
                                                        <select class="form-select" id="anesthesia_position{{ $surgery->id }}" name="anesthesia_position">
                                                            <option value="">اختر نمط الرقود</option>
                                                            <option value="supine" {{ $surgery->anesthesia_position == 'supine' ? 'selected' : '' }}>استلقاء على الظهر</option>
                                                            <option value="prone" {{ $surgery->anesthesia_position == 'prone' ? 'selected' : '' }}>استلقاء على البطن</option>
                                                            <option value="lateral" {{ $surgery->anesthesia_position == 'lateral' ? 'selected' : '' }}>الوضع الجانبي</option>
                                                            <option value="lithotomy" {{ $surgery->anesthesia_position == 'lithotomy' ? 'selected' : '' }}>وضع الولادة</option>
                                                            <option value="fowler" {{ $surgery->anesthesia_position == 'fowler' ? 'selected' : '' }}>وضع فولر</option>
                                                            <option value="trendelenburg" {{ $surgery->anesthesia_position == 'trendelenburg' ? 'selected' : '' }}>وضع تريندلنبرغ</option>
                                                            <option value="sitting" {{ $surgery->anesthesia_position == 'sitting' ? 'selected' : '' }}>الجلوس</option>
                                                            <option value="other" {{ $surgery->anesthesia_position == 'other' ? 'selected' : '' }}>أخرى</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="asa_classification{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-heartbeat text-danger me-1"></i>
                                                            تصنيف ASA
                                                        </label>
                                                        <select class="form-select" id="asa_classification{{ $surgery->id }}" name="asa_classification">
                                                            <option value="">اختر تصنيف ASA</option>
                                                            <option value="asa1" {{ $surgery->asa_classification == 'asa1' ? 'selected' : '' }}>ASA I - مريض سليم</option>
                                                            <option value="asa2" {{ $surgery->asa_classification == 'asa2' ? 'selected' : '' }}>ASA II - مرض خفيف</option>
                                                            <option value="asa3" {{ $surgery->asa_classification == 'asa3' ? 'selected' : '' }}>ASA III - مرض شديد</option>
                                                            <option value="asa4" {{ $surgery->asa_classification == 'asa4' ? 'selected' : '' }}>ASA IV - مرض شديد يهدد الحياة</option>
                                                            <option value="asa5" {{ $surgery->asa_classification == 'asa5' ? 'selected' : '' }}>ASA V - مريض ميت الآن</option>
                                                            <option value="asa6" {{ $surgery->asa_classification == 'asa6' ? 'selected' : '' }}>ASA VI - عضو متبرع</option>
                                                        </select>
                                                        <small class="form-text text-muted">تصنيف الجمعية الأمريكية للتخدير</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="surgical_complexity{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-chart-line text-success me-1"></i>
                                                            درجة تعقيد العملية
                                                        </label>
                                                        <select class="form-select" id="surgical_complexity{{ $surgery->id }}" name="surgical_complexity">
                                                            <option value="">اختر درجة التعقيد</option>
                                                            <option value="minor" {{ $surgery->surgical_complexity == 'minor' ? 'selected' : '' }}>بسيطة</option>
                                                            <option value="intermediate" {{ $surgery->surgical_complexity == 'intermediate' ? 'selected' : '' }}>متوسطة</option>
                                                            <option value="major" {{ $surgery->surgical_complexity == 'major' ? 'selected' : '' }}>كبرى</option>
                                                            <option value="complex" {{ $surgery->surgical_complexity == 'complex' ? 'selected' : '' }}>معقدة</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <label for="surgical_notes{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-file-medical text-secondary me-1"></i>
                                                            ملاحظات تصنيف العملية
                                                        </label>
                                                        <textarea class="form-control" id="surgical_notes{{ $surgery->id }}" name="surgical_notes" rows="3"
                                                                  placeholder="أدخل أي ملاحظات إضافية حول تصنيف العملية ونوعها...">{{ $surgery->surgical_notes }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notes Section -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="notesHeading{{ $surgery->id }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#notesCollapse{{ $surgery->id }}" aria-expanded="false"
                                                aria-controls="notesCollapse{{ $surgery->id }}">
                                            <i class="fas fa-sticky-note me-2 text-secondary"></i>
                                            الملاحظات
                                        </button>
                                    </h2>
                                    <div id="notesCollapse{{ $surgery->id }}" class="accordion-collapse collapse"
                                         aria-labelledby="notesHeading{{ $surgery->id }}"
                                         data-bs-parent="#surgeryDetailsAccordion{{ $surgery->id }}">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="post_op_notes{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-notes-medical text-danger me-1"></i>
                                                            ملاحظات ما بعد العملية
                                                        </label>
                                                        <textarea class="form-control" id="post_op_notes{{ $surgery->id }}" name="post_op_notes" rows="4"
                                                                  placeholder="أدخل ملاحظات ما بعد العملية والتعليمات...">{{ $surgery->post_op_notes }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="notes{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-comment-alt text-info me-1"></i>
                                                            ملاحظات إضافية
                                                        </label>
                                                        <textarea class="form-control" id="notes{{ $surgery->id }}" name="notes" rows="4"
                                                                  placeholder="أدخل أي ملاحظات إضافية...">{{ $surgery->notes }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Treatment Plan Section -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="treatmentHeading{{ $surgery->id }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#treatmentCollapse{{ $surgery->id }}" aria-expanded="false"
                                                aria-controls="treatmentCollapse{{ $surgery->id }}">
                                            <i class="fas fa-clipboard-list me-2 text-primary"></i>
                                            خطة العلاج والمتابعة
                                        </button>
                                    </h2>
                                    <div id="treatmentCollapse{{ $surgery->id }}" class="accordion-collapse collapse"
                                         aria-labelledby="treatmentHeading{{ $surgery->id }}"
                                         data-bs-parent="#surgeryDetailsAccordion{{ $surgery->id }}">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <div class="mb-3">
                                                        <label for="treatment_plan{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-list-check text-primary me-1"></i>
                                                            خطة العلاج
                                                        </label>
                                                        <textarea class="form-control" id="treatment_plan{{ $surgery->id }}" name="treatment_plan" rows="4"
                                                                  placeholder="أدخل خطة العلاج والإرشادات بعد العملية...">{{ $surgery->treatment_plan }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <label for="follow_up_date{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-calendar-check text-success me-1"></i>
                                                            تاريخ المتابعة
                                                        </label>
                                                        <input type="date" class="form-control" id="follow_up_date{{ $surgery->id }}" name="follow_up_date"
                                                               value="{{ $surgery->follow_up_date ? (is_string($surgery->follow_up_date) ? $surgery->follow_up_date : $surgery->follow_up_date->format('Y-m-d')) : '' }}"
                                                               min="{{ date('Y-m-d') }}">
                                                        <small class="form-text text-muted">تاريخ الزيارة التالية</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>إلغاء
                        </button>
                        <button type="submit" class="btn btn-success btn-lg" onclick="return prepareSurgeryData(this)">
                            <i class="fas fa-save me-2"></i>حفظ التفاصيل
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    @endforeach

    <!-- Modals for Completed Surgeries -->
    @foreach($completedSurgeries as $surgery)
    @if($surgery->status == 'completed')
    <div class="modal fade" id="detailsModalCompleted{{ $surgery->id }}" tabindex="-1" aria-labelledby="detailsModalCompletedLabel{{ $surgery->id }}" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="detailsModalCompletedLabel{{ $surgery->id }}">
                        <i class="fas fa-clipboard-check me-2"></i>
                        تفاصيل العملية الجراحية
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form action="{{ route('surgeries.updateDetails', $surgery) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body p-0">
                        <!-- Patient Info Header -->
                        <div class="bg-light p-3 border-bottom">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1">
                                        <i class="fas fa-user-injured text-primary me-2"></i>
                                        المريض: <strong>{{ $surgery->patient->user->name }}</strong>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fas fa-user-md me-1"></i>
                                        الطبيب: د. {{ $surgery->doctor->user->name }} |
                                        <i class="fas fa-procedures me-1"></i>
                                        العملية: {{ $surgery->surgery_type }} |
                                        <i class="fas fa-calendar me-1"></i>
                                        التاريخ: {{ $surgery->scheduled_date->format('Y-m-d') }}
                                    </small>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-success fs-6 px-3 py-2">
                                        <i class="fas fa-check-circle me-1"></i>
                                        العملية مكتملة
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4">
                            <!-- Surgery Details Accordion -->
                            <div class="accordion" id="surgeryDetailsAccordion{{ $surgery->id }}">
                                <!-- Diagnosis Section -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="diagnosisHeading{{ $surgery->id }}">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#diagnosisCollapse{{ $surgery->id }}" aria-expanded="true"
                                                aria-controls="diagnosisCollapse{{ $surgery->id }}">
                                            <i class="fas fa-stethoscope me-2 text-primary"></i>
                                            التشخيص والتخدير
                                        </button>
                                    </h2>
                                    <div id="diagnosisCollapse{{ $surgery->id }}" class="accordion-collapse collapse show"
                                         aria-labelledby="diagnosisHeading{{ $surgery->id }}"
                                         data-bs-parent="#surgeryDetailsAccordion{{ $surgery->id }}">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="diagnosis{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-diagnoses text-primary me-1"></i>
                                                            التشخيص
                                                        </label>
                                                        <textarea class="form-control" id="diagnosis{{ $surgery->id }}" name="diagnosis" rows="3"
                                                                  placeholder="أدخل التشخيص الطبي...">{{ $surgery->diagnosis }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="anesthesia_type{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-syringe text-success me-1"></i>
                                                            نوع التخدير
                                                        </label>
                                                        <select class="form-select" id="anesthesia_type{{ $surgery->id }}" name="anesthesia_type">
                                                            <option value="">اختر نوع التخدير</option>
                                                            <option value="local" {{ $surgery->anesthesia_type == 'local' ? 'selected' : '' }}>تخدير موضعي</option>
                                                            <option value="regional" {{ $surgery->anesthesia_type == 'regional' ? 'selected' : '' }}>تخدير إقليمي</option>
                                                            <option value="general" {{ $surgery->anesthesia_type == 'general' ? 'selected' : '' }}>تخدير عام</option>
                                                            <option value="sedation" {{ $surgery->anesthesia_type == 'sedation' ? 'selected' : '' }}>تخدير إيحائي</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Medical Team Section -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="teamHeading{{ $surgery->id }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#teamCollapse{{ $surgery->id }}" aria-expanded="false"
                                                aria-controls="teamCollapse{{ $surgery->id }}">
                                            <i class="fas fa-users me-2 text-info"></i>
                                            الفريق الطبي
                                        </button>
                                    </h2>
                                    <div id="teamCollapse{{ $surgery->id }}" class="accordion-collapse collapse"
                                         aria-labelledby="teamHeading{{ $surgery->id }}"
                                         data-bs-parent="#surgeryDetailsAccordion{{ $surgery->id }}">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="anesthesiologist_id{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-user-nurse text-info me-1"></i>
                                                            طبيب التخدير
                                                        </label>
                                                        <select class="form-select" id="anesthesiologist_id{{ $surgery->id }}" name="anesthesiologist_id">
                                                            <option value="">اختر طبيب التخدير</option>
                                                            @foreach(\App\Models\Doctor::where('specialization', 'like', '%تخدير%')->orWhere('specialization', 'like', '%anesthesia%')->get() as $doctor)
                                                            <option value="{{ $doctor->id }}" {{ $surgery->anesthesiologist_id == $doctor->id ? 'selected' : '' }}>
                                                                د. {{ $doctor->user->name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="anesthesiologist_2_id{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-user-nurse text-info me-1"></i>
                                                            طبيب تخدير مساعد
                                                        </label>
                                                        <select class="form-select" id="anesthesiologist_2_id{{ $surgery->id }}" name="anesthesiologist_2_id">
                                                            <option value="">اختر طبيب التخدير المساعد</option>
                                                            @foreach(\App\Models\Doctor::where('specialization', 'like', '%تخدير%')->orWhere('specialization', 'like', '%anesthesia%')->get() as $doctor)
                                                            <option value="{{ $doctor->id }}" {{ $surgery->anesthesiologist_2_id == $doctor->id ? 'selected' : '' }}>
                                                                د. {{ $doctor->user->name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="surgical_assistant_name{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-user-friends text-warning me-1"></i>
                                                            مساعد جراح
                                                        </label>
                                                        <input type="text" class="form-control" id="surgical_assistant_name{{ $surgery->id }}" name="surgical_assistant_name"
                                                               value="{{ $surgery->surgical_assistant_name }}" placeholder="اسم المساعد الجراح">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="supplies{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-tools text-secondary me-1"></i>
                                                            المستلزمات المستخدمة
                                                        </label>
                                                        <textarea class="form-control" id="supplies{{ $surgery->id }}" name="supplies" rows="2"
                                                                  placeholder="المستلزمات والأدوات المستخدمة...">{{ $surgery->supplies }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Surgery Details Section -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="surgeryHeading{{ $surgery->id }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#surgeryCollapse{{ $surgery->id }}" aria-expanded="false"
                                                aria-controls="surgeryCollapse{{ $surgery->id }}">
                                            <i class="fas fa-procedures me-2 text-danger"></i>
                                            تفاصيل العملية
                                        </button>
                                    </h2>
                                    <div id="surgeryCollapse{{ $surgery->id }}" class="accordion-collapse collapse"
                                         aria-labelledby="surgeryHeading{{ $surgery->id }}"
                                         data-bs-parent="#surgeryDetailsAccordion{{ $surgery->id }}">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="start_time{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-clock text-success me-1"></i>
                                                            وقت البدء
                                                        </label>
                                                        <input type="time" class="form-control" id="start_time{{ $surgery->id }}" name="start_time"
                                                               value="{{ $surgery->start_time ? $surgery->start_time->format('H:i') : ($surgery->started_at ? $surgery->started_at->format('H:i') : '') }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="end_time{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-clock text-danger me-1"></i>
                                                            وقت الانتهاء
                                                        </label>
                                                        <input type="time" class="form-control" id="end_time{{ $surgery->id }}" name="end_time"
                                                               value="{{ $surgery->end_time ? $surgery->end_time->format('H:i') : '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="estimated_duration{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-hourglass-half text-warning me-1"></i>
                                                            المدة المقدرة
                                                        </label>
                                                        <input type="text" class="form-control" id="estimated_duration{{ $surgery->id }}" name="estimated_duration"
                                                               value="{{ $surgery->estimated_duration }}" placeholder="مثال: 2:30 (ساعات:دقائق)">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="surgery_category{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                                            تصنيف العملية
                                                        </label>
                                                        <select class="form-select" id="surgery_category{{ $surgery->id }}" name="surgery_category">
                                                            <option value="">اختر التصنيف</option>
                                                            <option value="elective" {{ $surgery->surgery_category == 'elective' ? 'selected' : '' }}>اختيارية</option>
                                                            <option value="emergency" {{ $surgery->surgery_category == 'emergency' ? 'selected' : '' }}>طارئة</option>
                                                            <option value="urgent" {{ $surgery->surgery_category == 'urgent' ? 'selected' : '' }}>عاجلة</option>
                                                            <option value="semi_urgent" {{ $surgery->surgery_category == 'semi_urgent' ? 'selected' : '' }}>شبه عاجلة</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="surgery_type_detail{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-info-circle text-info me-1"></i>
                                                            نوع العملية التفصيلي
                                                        </label>
                                                        <select class="form-select" id="surgery_type_detail{{ $surgery->id }}" name="surgery_type_detail">
                                                            <option value="">اختر النوع</option>
                                                            <option value="diagnostic" {{ $surgery->surgery_type_detail == 'diagnostic' ? 'selected' : '' }}>تشخيصية</option>
                                                            <option value="therapeutic" {{ $surgery->surgery_type_detail == 'therapeutic' ? 'selected' : '' }}>علاجية</option>
                                                            <option value="preventive" {{ $surgery->surgery_type_detail == 'preventive' ? 'selected' : '' }}>وقائية</option>
                                                            <option value="cosmetic" {{ $surgery->surgery_type_detail == 'cosmetic' ? 'selected' : '' }}>تجميلية</option>
                                                            <option value="reconstructive" {{ $surgery->surgery_type_detail == 'reconstructive' ? 'selected' : '' }}>ترميمية</option>
                                                            <option value="palliative" {{ $surgery->surgery_type_detail == 'palliative' ? 'selected' : '' }}>تلطيفية</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="anesthesia_position{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-bed text-secondary me-1"></i>
                                                            وضعية التخدير
                                                        </label>
                                                        <select class="form-select" id="anesthesia_position{{ $surgery->id }}" name="anesthesia_position">
                                                            <option value="">اختر الوضعية</option>
                                                            <option value="supine" {{ $surgery->anesthesia_position == 'supine' ? 'selected' : '' }}>استلقاء على الظهر</option>
                                                            <option value="prone" {{ $surgery->anesthesia_position == 'prone' ? 'selected' : '' }}>استلقاء على البطن</option>
                                                            <option value="lateral" {{ $surgery->anesthesia_position == 'lateral' ? 'selected' : '' }}>جانبية</option>
                                                            <option value="lithotomy" {{ $surgery->anesthesia_position == 'lithotomy' ? 'selected' : '' }}>ليثوتومي</option>
                                                            <option value="fowler" {{ $surgery->anesthesia_position == 'fowler' ? 'selected' : '' }}>فاولر</option>
                                                            <option value="trendelenburg" {{ $surgery->anesthesia_position == 'trendelenburg' ? 'selected' : '' }}>تريندلنبرغ</option>
                                                            <option value="sitting" {{ $surgery->anesthesia_position == 'sitting' ? 'selected' : '' }}>جلوس</option>
                                                            <option value="other" {{ $surgery->anesthesia_position == 'other' ? 'selected' : '' }}>أخرى</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="asa_classification{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-heartbeat text-danger me-1"></i>
                                                            تصنيف ASA
                                                        </label>
                                                        <select class="form-select" id="asa_classification{{ $surgery->id }}" name="asa_classification">
                                                            <option value="">اختر التصنيف</option>
                                                            <option value="asa1" {{ $surgery->asa_classification == 'asa1' ? 'selected' : '' }}>ASA 1 - مريض سليم</option>
                                                            <option value="asa2" {{ $surgery->asa_classification == 'asa2' ? 'selected' : '' }}>ASA 2 - مرض خفيف</option>
                                                            <option value="asa3" {{ $surgery->asa_classification == 'asa3' ? 'selected' : '' }}>ASA 3 - مرض شديد</option>
                                                            <option value="asa4" {{ $surgery->asa_classification == 'asa4' ? 'selected' : '' }}>ASA 4 - مرض شديد يهدد الحياة</option>
                                                            <option value="asa5" {{ $surgery->asa_classification == 'asa5' ? 'selected' : '' }}>ASA 5 - مريض ميت لا يُتوقع البقاء</option>
                                                            <option value="asa6" {{ $surgery->asa_classification == 'asa6' ? 'selected' : '' }}>ASA 6 - مريض تم إيقاف قلبه</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="surgical_complexity{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-cogs text-primary me-1"></i>
                                                            تعقيد العملية
                                                        </label>
                                                        <select class="form-select" id="surgical_complexity{{ $surgery->id }}" name="surgical_complexity">
                                                            <option value="">اختر التعقيد</option>
                                                            <option value="minor" {{ $surgery->surgical_complexity == 'minor' ? 'selected' : '' }}>بسيطة</option>
                                                            <option value="intermediate" {{ $surgery->surgical_complexity == 'intermediate' ? 'selected' : '' }}>متوسطة</option>
                                                            <option value="major" {{ $surgery->surgical_complexity == 'major' ? 'selected' : '' }}>كبرى</option>
                                                            <option value="complex" {{ $surgery->surgical_complexity == 'complex' ? 'selected' : '' }}>معقدة</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="surgical_notes{{ $surgery->id }}" class="form-label fw-bold">
                                                    <i class="fas fa-notes-medical text-primary me-1"></i>
                                                    ملاحظات جراحية
                                                </label>
                                                <textarea class="form-control" id="surgical_notes{{ $surgery->id }}" name="surgical_notes" rows="3"
                                                          placeholder="ملاحظات حول العملية الجراحية...">{{ $surgery->surgical_notes }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Post-Op Section -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="postOpHeading{{ $surgery->id }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#postOpCollapse{{ $surgery->id }}" aria-expanded="false"
                                                aria-controls="postOpCollapse{{ $surgery->id }}">
                                            <i class="fas fa-heart me-2 text-danger"></i>
                                            ما بعد العملية
                                        </button>
                                    </h2>
                                    <div id="postOpCollapse{{ $surgery->id }}" class="accordion-collapse collapse"
                                         aria-labelledby="postOpHeading{{ $surgery->id }}"
                                         data-bs-parent="#surgeryDetailsAccordion{{ $surgery->id }}">
                                        <div class="accordion-body">
                                            <div class="mb-3">
                                                <label for="treatment_plan{{ $surgery->id }}" class="form-label fw-bold">
                                                    <i class="fas fa-clipboard-list text-primary me-1"></i>
                                                    خطة العلاج
                                                </label>
                                                <textarea class="form-control" id="treatment_plan{{ $surgery->id }}" name="treatment_plan" rows="3"
                                                          placeholder="خطة العلاج والإرشادات بعد العملية...">{{ $surgery->treatment_plan }}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="post_op_notes{{ $surgery->id }}" class="form-label fw-bold">
                                                    <i class="fas fa-file-medical text-danger me-1"></i>
                                                    ملاحظات ما بعد العملية
                                                </label>
                                                <textarea class="form-control" id="post_op_notes{{ $surgery->id }}" name="post_op_notes" rows="4"
                                                          placeholder="ملاحظات حول فترة ما بعد العملية...">{{ $surgery->post_op_notes }}</textarea>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="follow_up_date{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-calendar-check text-success me-1"></i>
                                                            موعد المتابعة
                                                        </label>
                                                        <input type="date" class="form-control" id="follow_up_date{{ $surgery->id }}" name="follow_up_date"
                                                               value="{{ $surgery->follow_up_date ? (is_string($surgery->follow_up_date) ? $surgery->follow_up_date : $surgery->follow_up_date->format('Y-m-d')) : '' }}" min="{{ date('Y-m-d') }}">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="notes{{ $surgery->id }}" class="form-label fw-bold">
                                                            <i class="fas fa-sticky-note text-warning me-1"></i>
                                                            ملاحظات إضافية
                                                        </label>
                                                        <textarea class="form-control" id="notes{{ $surgery->id }}" name="notes" rows="2"
                                                                  placeholder="ملاحظات إضافية...">{{ $surgery->notes }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Treatment Plan Section -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="treatmentHeading{{ $surgery->id }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#treatmentCollapse{{ $surgery->id }}" aria-expanded="false"
                                                aria-controls="treatmentCollapse{{ $surgery->id }}">
                                            <i class="fas fa-pills me-2 text-success"></i>
                                            خطة العلاج والأدوية
                                        </button>
                                    </h2>
                                    <div id="treatmentCollapse{{ $surgery->id }}" class="accordion-collapse collapse"
                                         aria-labelledby="treatmentHeading{{ $surgery->id }}"
                                         data-bs-parent="#surgeryDetailsAccordion{{ $surgery->id }}">
                                        <div class="accordion-body">
                                            <div class="mb-3">
                                                <label for="treatment_plan{{ $surgery->id }}" class="form-label fw-bold">
                                                    <i class="fas fa-clipboard-list text-success me-1"></i>
                                                    خطة العلاج
                                                </label>
                                                <textarea class="form-control" id="treatment_plan{{ $surgery->id }}" name="treatment_plan" rows="4"
                                                          placeholder="خطة العلاج والرعاية المطلوبة...">{{ $surgery->treatment_plan }}</textarea>
                                            </div>

                                            <!-- Medications Section -->
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">
                                                    <i class="fas fa-pills text-primary me-1"></i>
                                                    الأدوية الموصوفة
                                                </label>
                                                <div id="medicationsContainer{{ $surgery->id }}">
                                                    @if($surgery->prescribed_medications && is_array($surgery->prescribed_medications) && isset($surgery->prescribed_medications['medications']))
                                                        @foreach($surgery->prescribed_medications['medications'] as $index => $medication)
                                                        <div class="medication-item card mb-3 border-success">
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <div class="col-md-3">
                                                                        <label class="form-label">اسم الدواء</label>
                                                                        <input type="text" class="form-control" name="prescribed_medications[medications][{{ $index }}][name]"
                                                                               value="{{ $medication['name'] ?? '' }}" placeholder="اسم الدواء أو اختر من القائمة" list="commonMedications{{ $surgery->id }}">
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <label class="form-label">الجرعة</label>
                                                                        <input type="text" class="form-control" name="prescribed_medications[medications][{{ $index }}][dosage]"
                                                                               value="{{ $medication['dosage'] ?? '' }}" placeholder="مثال: 500mg">
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <label class="form-label">التوقيت</label>
                                                                        <input type="text" class="form-control" name="prescribed_medications[medications][{{ $index }}][timing]"
                                                                               value="{{ $medication['timing'] ?? '' }}" placeholder="مثال: مرتين يومياً">
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <label class="form-label">المدة</label>
                                                                        <input type="number" class="form-control" name="prescribed_medications[medications][{{ $index }}][duration]"
                                                                               value="{{ $medication['duration'] ?? '' }}" placeholder="عدد الأيام" min="1">
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <label class="form-label">الملاحظات</label>
                                                                        <input type="text" class="form-control" name="prescribed_medications[medications][{{ $index }}][notes]"
                                                                               value="{{ $medication['notes'] ?? '' }}" placeholder="ملاحظات إضافية">
                                                                    </div>
                                                                    <div class="col-md-1 d-flex align-items-end">
                                                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeMedication(this)">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                                <button type="button" class="btn btn-outline-success btn-sm" onclick="addMedication({{ $surgery->id }})">
                                                    <i class="fas fa-plus me-1"></i>إضافة دواء
                                                </button>
                                            </div>

                                            <!-- Surgery Treatments Section -->
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">
                                                    <i class="fas fa-procedures text-warning me-1"></i>
                                                    علاجات العملية
                                                </label>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered" id="surgeryTreatmentsTable{{ $surgery->id }}">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>اسم العلاج</th>
                                                                <th>الجرعة</th>
                                                                <th>التوقيت</th>
                                                                <th>المدة</th>
                                                                <th>الوحدة</th>
                                                                <th>الإجراءات</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="surgeryTreatmentsContainer{{ $surgery->id }}">
                                                            @php
                                                                $savedSurgeryTreatments = $surgery->surgeryTreatments ?? collect();
                                                            @endphp
                                                            @foreach($savedSurgeryTreatments as $index => $treatment)
                                                            <tr class="surgery-treatment-row">
                                                                <td>
                                                                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][description]"
                                                                           value="{{ $treatment->description ?? '' }}"
                                                                           placeholder="اسم الدواء أو وصف العلاج">
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][dosage]"
                                                                           value="{{ $treatment->dosage ?? '' }}"
                                                                           placeholder="مثال: 500mg, 2ml">
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][timing]"
                                                                           value="{{ $treatment->timing ?? '' }}"
                                                                           placeholder="مثال: كل 6 ساعات">
                                                                </td>
                                                                <td>
                                                                    <input type="number" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][duration_value]"
                                                                           value="{{ $treatment->duration_value ?? '' }}"
                                                                           placeholder="العدد" min="1">
                                                                </td>
                                                                <td>
                                                                    <select class="form-select form-select-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][duration_unit]">
                                                                        <option value="days" {{ ($treatment->duration_unit ?? '') == 'days' ? 'selected' : '' }}>يوم</option>
                                                                        <option value="weeks" {{ ($treatment->duration_unit ?? '') == 'weeks' ? 'selected' : '' }}>أسبوع</option>
                                                                        <option value="months" {{ ($treatment->duration_unit ?? '') == 'months' ? 'selected' : '' }}>شهر</option>
                                                                        <option value="hours" {{ ($treatment->duration_unit ?? '') == 'hours' ? 'selected' : '' }}>ساعة</option>
                                                                        <option value="doses" {{ ($treatment->duration_unit ?? '') == 'doses' ? 'selected' : '' }}>جرعة</option>
                                                                    </select>
                                                                </td>
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSurgeryTreatment(this)">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                            @if($savedSurgeryTreatments->isEmpty())
                                                            <tr id="emptySurgeryTreatmentsRow{{ $surgery->id }}">
                                                                <td colspan="6" class="text-center py-4 text-muted">
                                                                    <i class="fas fa-table fa-2x mb-2"></i>
                                                                    <p>لا توجد علاجات محددة للعملية</p>
                                                                    <small>اضغط على "إضافة علاج" لبدء إضافة علاجات العملية</small>
                                                                </td>
                                                            </tr>
                                                            @endif
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="addSurgeryTreatment({{ $surgery->id }})">
                                                    <i class="fas fa-plus me-1"></i>إضافة علاج
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>إلغاء
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>حفظ التفاصيل
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    @endforeach

    <!-- Radiology, Lab, and Treatment Modals for Completed Surgeries -->
    @foreach($completedSurgeries as $surgery)
        <!-- Radiology Modal -->
        <div class="modal fade" id="radiologyModalCompleted{{ $surgery->id }}" tabindex="-1" aria-labelledby="radiologyModalCompletedLabel{{ $surgery->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="radiologyModalCompletedLabel{{ $surgery->id }}">تفاصيل الأشعة المطلوبة</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>نوع الأشعة</th>
                                    <th>الحالة</th>
                                    <th>النتيجة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($surgery->radiologyTests as $test)
                                    <tr>
                                        <td>{{ $test->radiologyType->name ?? '-' }}</td>
                                        <td>{{ $test->statusText ?? '-' }}</td>
                                        <td>
                                            @if($test->result)
                                                <p class="mb-1">{{ $test->result }}</p>
                                            @endif
                                            
                                            @if($test->result_file)
                                                <div class="mt-1">
                                                    <a href="{{ asset('storage/' . $test->result_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-paperclip me-1"></i>
                                                        مرفق
                                                    </a>
                                                </div>
                                            @else
                                                @if(!$test->result)
                                                    -
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lab Modal -->
        <div class="modal fade" id="labModalCompleted{{ $surgery->id }}" tabindex="-1" aria-labelledby="labModalCompletedLabel{{ $surgery->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="labModalCompletedLabel{{ $surgery->id }}">تفاصيل التحاليل المخبرية المطلوبة</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>اسم التحليل</th>
                                    <th>الحالة</th>
                                    <th>النتيجة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($surgery->labTests as $test)
                                    <tr>
                                        <td>{{ $test->labTest->name ?? '-' }}</td>
                                        <td>{{ $test->statusText ?? '-' }}</td>
                                        <td>{{ $test->result ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Treatment Modal -->
        <div class="modal fade" id="treatmentModalCompleted{{ $surgery->id }}" tabindex="-1" aria-labelledby="treatmentModalCompletedLabel{{ $surgery->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="treatmentModalCompletedLabel{{ $surgery->id }}">تفاصيل العلاج المطلوب</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>وصف العلاج</th>
                                    <th>الجرعة</th>
                                    <th>التوقيت</th>
                                    <th>المدة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($surgery->surgeryTreatments as $treatment)
                                    <tr>
                                        <td>{{ $treatment->description }}</td>
                                        <td>{{ $treatment->dosage ?? '-' }}</td>
                                        <td>{{ $treatment->timing ?? '-' }}</td>
                                        <td>
                                            @if($treatment->duration_value && $treatment->duration_unit)
                                                {{ $treatment->duration_value }} {{ $treatment->duration_unit == 'days' ? 'يوم' : ($treatment->duration_unit == 'weeks' ? 'أسبوع' : ($treatment->duration_unit == 'months' ? 'شهر' : $treatment->duration_unit)) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

@section('scripts')
<script>
// Function to prepare surgery data before form submission
function prepareSurgeryData(button) {
    console.log('prepareSurgeryData called - form will submit normally');
    return true; // Allow form submission
}

document.addEventListener('DOMContentLoaded', function() {
    function reloadSurgeriesTable() {
        console.log('Reloading surgeries table...');
        fetch(window.location.href, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            }
        })
        .then(response => response.text())
        .then(data => {
            console.log('AJAX success, extracting table...');
            const parser = new DOMParser();
            const doc = parser.parseFromString(data, 'text/html');
            const newTable = doc.querySelector('.table-responsive');
            if (newTable) {
                document.querySelector('.table-responsive').innerHTML = newTable.innerHTML;
                console.log('Table updated successfully');
            } else {
                console.error('Could not find .table-responsive in response');
            }
        })
        .catch(error => {
            console.error('Error reloading surgeries table:', error);
        });
    }

    // Real-time updates
    if (window.Echo) {
        window.Echo.channel('surgeries')
            .listen('.surgery.updated', (e) => {
                console.log('Surgery updated event received', e);
                reloadSurgeriesTable();
            });
    }

    // Fallback polling every 30 seconds
    setInterval(reloadSurgeriesTable, 30000); // كل 30 ثانية

    // Auto-calculate estimated duration
    document.addEventListener('input', function(e) {
        if (e.target.name === 'start_time' || e.target.name === 'end_time') {
            const modal = e.target.closest('.modal');
            const startTimeInput = modal.querySelector('input[name="start_time"]');
            const endTimeInput = modal.querySelector('input[name="end_time"]');
            const durationField = modal.querySelector('input[name="estimated_duration"]');

            const startTime = startTimeInput.value;
            const endTime = endTimeInput.value;

            if (startTime && endTime && durationField) {
                // Parse times
                const start = new Date('1970-01-01T' + startTime + ':00');
                const end = new Date('1970-01-01T' + endTime + ':00');

                // Handle cases where end time is next day
                if (end < start) {
                    end.setDate(end.getDate() + 1);
                }

                // Calculate difference in minutes
                const diffMs = end - start;
                const diffMins = Math.round(diffMs / 60000);

                // Convert to hours and minutes format
                const hours = Math.floor(diffMins / 60);
                const minutes = diffMins % 60;

                // Format as HH:MM
                const formattedDuration = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');

                // Set the duration
                durationField.value = formattedDuration;

                // Also store the total minutes in a hidden field for backend processing
                let hiddenMinutesField = modal.querySelector('input[name="estimated_duration_minutes"]');
                if (!hiddenMinutesField) {
                    hiddenMinutesField = document.createElement('input');
                    hiddenMinutesField.type = 'hidden';
                    hiddenMinutesField.name = 'estimated_duration_minutes';
                    modal.querySelector('form').appendChild(hiddenMinutesField);
                }
                hiddenMinutesField.value = diffMins;
            }
        }
    });
});

// Medication management functions - Global functions that accept surgery ID
window.addSurgeryTreatment = function(surgeryId) {
    try {
        const container = document.getElementById('surgeryTreatmentsContainer' + surgeryId);
        const emptyRow = document.getElementById('emptySurgeryTreatmentsRow' + surgeryId);
        if (!container) return;

        // Remove empty row if it exists
        if (emptyRow) {
            emptyRow.remove();
        }

        const treatmentIndex = container.querySelectorAll('.surgery-treatment-row').length;
        const treatmentHtml = `
            <tr class="surgery-treatment-row">
                <td class="text-center">${treatmentIndex + 1}</td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][${surgeryId}][${treatmentIndex}][description]"
                           placeholder="اسم الدواء أو وصف العلاج">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][${surgeryId}][${treatmentIndex}][dosage]"
                           placeholder="مثال: 500mg, 2ml">
                </td>
                <td>
                    <textarea class="form-control form-control-sm timing-textarea" name="prescribed_medications[surgery_treatments][${surgeryId}][${treatmentIndex}][timing]" rows="2"
                              placeholder="مثال: كل 6 ساعات، صباحاً ومساءً، قبل العملية بساعة"></textarea>
                </td>
                <td>
                    <div class="duration-input-group">
                        <input type="number" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][${surgeryId}][${treatmentIndex}][duration_value]"
                               placeholder="العدد" min="1">
                        <select class="form-select form-select-sm" name="prescribed_medications[surgery_treatments][${surgeryId}][${treatmentIndex}][duration_unit]">
                            <option value="days">يوم</option>
                            <option value="weeks">أسبوع</option>
                            <option value="months">شهر</option>
                            <option value="hours">ساعة</option>
                            <option value="doses">جرعة</option>
                        </select>
                    </div>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSurgeryTreatment(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        container.insertAdjacentHTML('beforeend', treatmentHtml);
    } catch (error) {
        console.error('Error in addSurgeryTreatment:', error);
    }
};

// Medication management functions for each surgery modal
@foreach($activeSurgeries as $surgery)
window.addMedication{{ $surgery->id }} = function() {
    try {
        const container = document.getElementById('medicationsContainer{{ $surgery->id }}');
        if (!container) return;

        const medicationIndex = container.querySelectorAll('.medication-item').length;
        const medicationHtml = `
            <div class="medication-item card mb-3 border-success">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">اسم الدواء</label>
                            <input type="text" class="form-control" name="prescribed_medications[{{ $surgery->id }}][${medicationIndex}][name]"
                                   placeholder="اسم الدواء أو اختر من القائمة" list="commonMedications{{ $surgery->id }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">نوع العلاج</label>
                            <select class="form-select" name="prescribed_medications[{{ $surgery->id }}][${medicationIndex}][type]">
                                <option value="tablet">حبوب</option>
                                <option value="injection">إبرة</option>
                                <option value="syrup">شراب</option>
                                <option value="cream">كريم</option>
                                <option value="drops">قطرات</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">الجرعة</label>
                            <input type="text" class="form-control" name="prescribed_medications[{{ $surgery->id }}][${medicationIndex}][dosage]"
                                   placeholder="مثال: 500mg">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label d-block mb-2">عدد المرات</label>
                            <div class="frequency-selector" style="display: flex; gap: 5px; flex-wrap: wrap;">
                                <input type="radio" id="freq_{{ $surgery->id }}_${medicationIndex}_1" name="prescribed_medications[{{ $surgery->id }}][${medicationIndex}][frequency]" value="1" style="display: none;">
                                <label for="freq_{{ $surgery->id }}_${medicationIndex}_1" class="frequency-btn" style="padding: 6px 10px; border: 2px solid #e9ecef; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s ease; background: white;">مرة</label>

                                <input type="radio" id="freq_{{ $surgery->id }}_${medicationIndex}_2" name="prescribed_medications[{{ $surgery->id }}][${medicationIndex}][frequency]" value="2" style="display: none;">
                                <label for="freq_{{ $surgery->id }}_${medicationIndex}_2" class="frequency-btn" style="padding: 6px 10px; border: 2px solid #e9ecef; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s ease; background: white;">مرتين</label>

                                <input type="radio" id="freq_{{ $surgery->id }}_${medicationIndex}_3" name="prescribed_medications[{{ $surgery->id }}][${medicationIndex}][frequency]" value="3" style="display: none;">
                                <label for="freq_{{ $surgery->id }}_${medicationIndex}_3" class="frequency-btn" style="padding: 6px 10px; border: 2px solid #e9ecef; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s ease; background: white;">ثلاث</label>

                                <input type="radio" id="freq_{{ $surgery->id }}_${medicationIndex}_4" name="prescribed_medications[{{ $surgery->id }}][${medicationIndex}][frequency]" value="4" style="display: none;">
                                <label for="freq_{{ $surgery->id }}_${medicationIndex}_4" class="frequency-btn" style="padding: 6px 10px; border: 2px solid #e9ecef; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s ease; background: white;">أربع</label>

                                <input type="radio" id="freq_{{ $surgery->id }}_${medicationIndex}_needed" name="prescribed_medications[{{ $surgery->id }}][${medicationIndex}][frequency]" value="as_needed" style="display: none;">
                                <label for="freq_{{ $surgery->id }}_${medicationIndex}_needed" class="frequency-btn" style="padding: 6px 10px; border: 2px solid #e9ecef; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s ease; background: white;">عند الحاجة</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">الأوقات</label>
                            <input type="text" class="form-control" name="prescribed_medications[{{ $surgery->id }}][${medicationIndex}][times]"
                                   placeholder="صباح، مساء">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeMedication(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-3">
                            <label class="form-label">المدة</label>
                            <input type="text" class="form-control" name="prescribed_medications[{{ $surgery->id }}][${medicationIndex}][duration]"
                                   placeholder="أيام">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">تعليمات خاصة</label>
                            <input type="text" class="form-control" name="prescribed_medications[{{ $surgery->id }}][${medicationIndex}][instructions]"
                                   placeholder="تعليمات خاصة للمريض">
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', medicationHtml);
    } catch (error) {
        console.error('Error in addMedication:', error);
    }
};

window.addOtherTreatment{{ $surgery->id }} = function() {
    try {
        const container = document.getElementById('otherTreatmentsContainer{{ $surgery->id }}');
        if (!container) return;

        const treatmentIndex = container.querySelectorAll('.treatment-item').length;
        const treatmentHtml = `
            <div class="treatment-item card mb-3 border-info">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">نوع العلاج</label>
                            <select class="form-select" name="prescribed_medications[other_treatments][{{ $surgery->id }}][${treatmentIndex}][type]">
                                <option value="">اختر النوع</option>
                                <option value="physical_therapy">علاج فيزيائي</option>
                                <option value="occupational_therapy">علاج وظيفي</option>
                                <option value="speech_therapy">علاج نطقي</option>
                                <option value="surgery">جراحة</option>
                                <option value="radiotherapy">علاج إشعاعي</option>
                                <option value="chemotherapy">علاج كيميائي</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">وصف العلاج</label>
                            <input type="text" class="form-control" name="prescribed_medications[other_treatments][{{ $surgery->id }}][${treatmentIndex}][description]"
                                   placeholder="وصف العلاج المطلوب">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">المدة</label>
                            <input type="text" class="form-control" name="prescribed_medications[other_treatments][{{ $surgery->id }}][${treatmentIndex}][duration]"
                                   placeholder="عدد الجلسات/الأيام">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">التكرار</label>
                            <input type="text" class="form-control" name="prescribed_medications[other_treatments][{{ $surgery->id }}][${treatmentIndex}][frequency]"
                                   placeholder="يومياً، أسبوعياً">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTreatment(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', treatmentHtml);
    } catch (error) {
        console.error('Error in addOtherTreatment:', error);
    }
};
@endforeach

window.removeSurgeryTreatment = function(button) {
    const row = button.closest('.surgery-treatment-row');
    const container = row.parentElement;
    row.remove();

    // Re-number remaining rows
    const rows = container.querySelectorAll('.surgery-treatment-row');
    const surgeryId = container.id.replace('surgeryTreatmentsContainer', '');
    
    rows.forEach((row, index) => {
        // Update row number
        row.cells[0].textContent = index + 1;
        
        // Update input names
        const descInput = row.querySelector('input[name*="description"]');
        const dosageInput = row.querySelector('input[name*="dosage"]');
        const timingTextarea = row.querySelector('textarea[name*="timing"]');
        const durationValueInput = row.querySelector('input[name*="duration_value"]');
        const durationUnitSelect = row.querySelector('select[name*="duration_unit"]');
        
        if (descInput) descInput.name = `prescribed_medications[surgery_treatments][${surgeryId}][${index}][description]`;
        if (dosageInput) dosageInput.name = `prescribed_medications[surgery_treatments][${surgeryId}][${index}][dosage]`;
        if (timingTextarea) timingTextarea.name = `prescribed_medications[surgery_treatments][${surgeryId}][${index}][timing]`;
        if (durationValueInput) durationValueInput.name = `prescribed_medications[surgery_treatments][${surgeryId}][${index}][duration_value]`;
        if (durationUnitSelect) durationUnitSelect.name = `prescribed_medications[surgery_treatments][${surgeryId}][${index}][duration_unit]`;
    });

    // Add empty row if no treatments left
    if (rows.length === 0) {
        const emptyRowHtml = `
            <tr id="emptySurgeryTreatmentsRow${surgeryId}">
                <td colspan="6" class="text-center py-4 text-muted">
                    <i class="fas fa-table fa-2x mb-2"></i>
                    <p>لا توجد علاجات محددة للعملية</p>
                    <small>اضغط على "إضافة علاج" لبدء إضافة علاجات العملية</small>
                </td>
            </tr>
        `;
        container.insertAdjacentHTML('beforeend', emptyRowHtml);
    }
};

window.removeMedication = function(button) {
    button.closest('.medication-item').remove();
};

window.removeTreatment = function(button) {
    button.closest('.treatment-item').remove();
};
</script>

<!-- Common Medications DataList for Active Surgeries -->
@foreach($activeSurgeries as $surgery)
@if($surgery->status == 'completed')
<datalist id="commonMedications{{ $surgery->id }}">
    <option value="أموكسيسيلين (Amoxicillin)">
    <option value="أزيثروميسين (Azithromycin)">
    <option value="أموكسيكلاف (Amoxicillin-Clavulanate)">
    <option value="سيفالكسين (Cephalexin)">
    <option value="سيفازولين (Cefazolin)">
    <option value="ميترونيدازول (Metronidazole)">
    <option value="سيبروفلوكساسين (Ciprofloxacin)">
    <option value="تريميثوبريم-سلفاميثوكسازول (Trimethoprim-Sulfamethoxazole)">
    <option value="إيبوبروفين (Ibuprofen)">
    <option value="باراسيتامول (Paracetamol)">
    <option value="ديكلوفيناك (Diclofenac)">
    <option value="ترامادول (Tramadol)">
    <option value="مورفين (Morphine)">
    <option value="أسبرين (Aspirin)">
    <option value="وارفارين (Warfarin)">
    <option value="إنسولين (Insulin)">
    <option value="ميتفورمين (Metformin)">
    <option value="أتورفاستاتين (Atorvastatin)">
    <option value="لوسارتان (Losartan)">
    <option value="أملوديبين (Amlodipine)">
    <option value="فوروسيميد (Furosemide)">
    <option value="ديجوكسين (Digoxin)">
    <option value="بريدنيزون (Prednisone)">
    <option value="أوميبرازول (Omeprazole)">
    <option value="رانيتيدين (Ranitidine)">
    <option value="ألبرازولام (Alprazolam)">
    <option value="ديازيبام (Diazepam)">
    <option value="فلوكسيتين (Fluoxetine)">
    <option value="سيرترالين (Sertraline)">
    <option value="أميتريبتيلين (Amitriptyline)">
    <option value="كلونازيبام (Clonazepam)">
    <option value="فينيتوين (Phenytoin)">
    <option value="كاربامازيبين (Carbamazepine)">
    <option value="فالبروات (Valproate)">
    <option value="ليفوثيروكسين (Levothyroxine)">
    <option value="بروبيل ثيوراسيل (Propylthiouracil)">
    <option value="ميثيمازول (Methimazole)">
    <option value="هيبارين (Heparin)">
    <option value="إينوكسابارين (Enoxaparin)">
    <option value="كلوبيدوغريل (Clopidogrel)">
    <option value="تيكاغريلور (Ticagrelor)">
    <option value="ريفامبيسين (Rifampicin)">
    <option value="إيزونيازيد (Isoniazid)">
    <option value="إيثامبوتول (Ethambutol)">
    <option value="بيرازيناميد (Pyrazinamide)">
    <option value="فيتامين D">
    <option value="كالسيوم">
    <option value="حديد">
    <option value="فيتامين B12">
    <option value="فولات">
    <option value="زنك">
    <option value="مغنيسيوم">
    <option value="بوتاسيوم">
    <option value="صوديوم">
    <option value="كلوريد">
    <option value="بيكربونات">
</datalist>
@endif
@endforeach

<!-- Common Medications DataList for Completed Surgeries -->
@foreach($completedSurgeries as $surgery)
@if($surgery->status == 'completed')
<datalist id="commonMedications{{ $surgery->id }}">
    <option value="أموكسيسيلين (Amoxicillin)">
    <option value="أزيثروميسين (Azithromycin)">
    <option value="أموكسيكلاف (Amoxicillin-Clavulanate)">
    <option value="سيفالكسين (Cephalexin)">
    <option value="سيفازولين (Cefazolin)">
    <option value="ميترونيدازول (Metronidazole)">
    <option value="سيبروفلوكساسين (Ciprofloxacin)">
    <option value="تريميثوبريم-سلفاميثوكسازول (Trimethoprim-Sulfamethoxazole)">
    <option value="إيبوبروفين (Ibuprofen)">
    <option value="باراسيتامول (Paracetamol)">
    <option value="ديكلوفيناك (Diclofenac)">
    <option value="ترامادول (Tramadol)">
    <option value="مورفين (Morphine)">
    <option value="أسبرين (Aspirin)">
    <option value="وارفارين (Warfarin)">
    <option value="إنسولين (Insulin)">
    <option value="ميتفورمين (Metformin)">
    <option value="أتورفاستاتين (Atorvastatin)">
    <option value="لوسارتان (Losartan)">
    <option value="أملوديبين (Amlodipine)">
    <option value="فوروسيميد (Furosemide)">
    <option value="ديجوكسين (Digoxin)">
    <option value="بريدنيزون (Prednisone)">
    <option value="أوميبرازول (Omeprazole)">
    <option value="رانيتيدين (Ranitidine)">
    <option value="ألبرازولام (Alprazolam)">
    <option value="ديازيبام (Diazepam)">
    <option value="فلوكسيتين (Fluoxetine)">
    <option value="سيرترالين (Sertraline)">
    <option value="أميتريبتيلين (Amitriptyline)">
    <option value="كلونازيبام (Clonazepam)">
    <option value="فينيتوين (Phenytoin)">
    <option value="كاربامازيبين (Carbamazepine)">
    <option value="فالبروات (Valproate)">
    <option value="ليفوثيروكسين (Levothyroxine)">
    <option value="بروبيل ثيوراسيل (Propylthiouracil)">
    <option value="ميثيمازول (Methimazole)">
    <option value="هيبارين (Heparin)">
    <option value="إينوكسابارين (Enoxaparin)">
    <option value="كلوبيدوغريل (Clopidogrel)">
    <option value="تيكاغريلور (Ticagrelor)">
    <option value="ريفامبيسين (Rifampicin)">
    <option value="إيزونيازيد (Isoniazid)">
    <option value="إيثامبوتول (Ethambutol)">
    <option value="بيرازيناميد (Pyrazinamide)">
    <option value="فيتامين D">
    <option value="كالسيوم">
    <option value="حديد">
    <option value="فيتامين B12">
    <option value="فولات">
    <option value="زنك">
    <option value="مغنيسيوم">
    <option value="بوتاسيوم">
    <option value="صوديوم">
    <option value="كلوريد">
    <option value="بيكربونات">
</datalist>
@endif
@endforeach

@endsection
