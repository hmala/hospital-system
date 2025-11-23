@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-procedures me-2"></i>
                    تفاصيل العملية الجراحية
                </h2>
                <div>
                    <a href="{{ route('surgeries.edit', $surgery) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>تعديل
                    </a>
                    <a href="{{ route('surgeries.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-right me-2"></i>العودة
                    </a>
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

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">معلومات العملية</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">نوع العملية:</th>
                            <td><strong>{{ $surgery->surgery_type }}</strong></td>
                        </tr>
                        <tr>
                            <th>الحالة:</th>
                            <td>
                                @if($surgery->status == 'scheduled')
                                    <span class="badge bg-secondary">مجدولة</span>
                                @elseif($surgery->status == 'in_progress')
                                    <span class="badge bg-warning">جارية</span>
                                @elseif($surgery->status == 'completed')
                                    <span class="badge bg-success">مكتملة</span>
                                @else
                                    <span class="badge bg-danger">ملغاة</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>التاريخ:</th>
                            <td>{{ $surgery->scheduled_date->format('Y-m-d') }}</td>
                        </tr>
                        <tr>
                            <th>الوقت:</th>
                            <td>{{ $surgery->scheduled_time }}</td>
                        </tr>
                        <tr>
                            <th>المريض:</th>
                            <td>
                                <a href="{{ route('patients.show', $surgery->patient) }}" class="text-decoration-none">
                                    {{ $surgery->patient->user->name }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>الطبيب الجراح:</th>
                            <td>د. {{ $surgery->doctor->user->name }}</td>
                        </tr>
                        <tr>
                            <th>القسم:</th>
                            <td>{{ $surgery->department->name }}</td>
                        </tr>
                        @if($surgery->visit)
                        <tr>
                            <th>الزيارة المرتبطة:</th>
                            <td>
                                <a href="{{ route('visits.show', $surgery->visit) }}" class="text-decoration-none">
                                    زيارة رقم {{ $surgery->visit->id }}
                                </a>
                            </td>
                        </tr>
                        @endif
                    </table>

                    @if($surgery->description)
                    <hr>
                    <h6>وصف العملية:</h6>
                    <div class="bg-light p-3 rounded">
                        {{ $surgery->description }}
                    </div>
                    @endif

                    @if($surgery->notes)
                    <hr>
                    <h6>ملاحظات:</h6>
                    <div class="bg-light p-3 rounded">
                        {{ $surgery->notes }}
                    </div>
                    @endif

                    @if($surgery->post_op_notes)
                    <hr>
                    <h6>ملاحظات ما بعد العملية:</h6>
                    <div class="bg-light p-3 rounded">
                        {{ $surgery->post_op_notes }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- التحاليل والأشعة المطلوبة -->
            @if($surgery->labTests->count() > 0 || $surgery->radiologyTests->count() > 0)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-flask me-2"></i>الفحوصات المطلوبة قبل العملية</h5>
                </div>
                <div class="card-body">
                    @if($surgery->labTests->count() > 0)
                    <h6 class="text-primary mb-3"><i class="fas fa-vial me-2"></i>التحاليل المخبرية</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>اسم التحليل</th>
                                    <th>الفئة</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الإكمال</th>
                                    <th>النتيجة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($surgery->labTests as $labTest)
                                <tr>
                                    <td>{{ $labTest->labTest->name }}</td>
                                    <td>{{ $labTest->labTest->category ?? 'غير محدد' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $labTest->status_color }}">
                                            {{ $labTest->status_text }}
                                        </span>
                                    </td>
                                    <td>{{ $labTest->completed_at ? $labTest->completed_at->format('Y-m-d H:i') : '-' }}</td>
                                    <td>
                                        @if($labTest->result)
                                            <span class="text-success">{{ $labTest->result }}</span>
                                        @elseif($labTest->result_file)
                                            <a href="{{ asset('storage/' . $labTest->result_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-file-download me-1"></i>عرض الملف
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    @if($surgery->radiologyTests->count() > 0)
                    <h6 class="text-success mb-3"><i class="fas fa-x-ray me-2"></i>الأشعة والتصوير</h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>نوع التصوير</th>
                                    <th>الكود</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الإكمال</th>
                                    <th>النتيجة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($surgery->radiologyTests as $radiologyTest)
                                <tr>
                                    <td>{{ $radiologyTest->radiologyType->name }}</td>
                                    <td>{{ $radiologyTest->radiologyType->code }}</td>
                                    <td>
                                        <span class="badge bg-{{ $radiologyTest->status_color }}">
                                            {{ $radiologyTest->status_text }}
                                        </span>
                                    </td>
                                    <td>{{ $radiologyTest->completed_at ? $radiologyTest->completed_at->format('Y-m-d H:i') : '-' }}</td>
                                    <td>
                                        @if($radiologyTest->result)
                                            <span class="text-success">{{ $radiologyTest->result }}</span>
                                        @elseif($radiologyTest->result_file)
                                            <a href="{{ asset('storage/' . $radiologyTest->result_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-file-download me-1"></i>عرض الملف
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>إجراءات سريعة</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($surgery->status == 'scheduled')
                        <form action="{{ route('surgeries.start', $surgery) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100" onclick="return confirm('هل تريد بدء العملية؟')">
                                <i class="fas fa-play me-2"></i>بدء العملية
                            </button>
                        </form>
                        @endif

                        @if($surgery->status == 'in_progress')
                        <form action="{{ route('surgeries.complete', $surgery) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('هل تم إكمال العملية؟')">
                                <i class="fas fa-check me-2"></i>إكمال العملية
                            </button>
                        </form>
                        @endif

                        @if($surgery->status != 'cancelled' && $surgery->status != 'completed')
                        <form action="{{ route('surgeries.cancel', $surgery) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100" onclick="return confirm('هل تريد إلغاء العملية؟')">
                                <i class="fas fa-times me-2"></i>إلغاء العملية
                            </button>
                        </form>
                        @endif

                        <a href="{{ route('patients.show', $surgery->patient) }}" class="btn btn-outline-primary">
                            <i class="fas fa-user me-2"></i>عرض ملف المريض
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
