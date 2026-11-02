@extends('layouts.app')

@section('content')
<div class="container-fluid py-3" id="surgery-lab-tests-content">
    
    <!-- 1. الهيدر العلوي وشريط الإحصاءات السريعة (مطابق لـ /lab) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary text-white p-2 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 46px; height: 46px;">
                <i class="fas fa-flask fs-4"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    تحاليل وفحوصات العمليات الجراحية
                    <span class="badge bg-success shadow-sm" style="font-size: 0.72rem;">
                        <i class="fas fa-circle fa-xs me-1"></i> مباشر
                    </span>
                </h4>
                <small class="text-muted">
                    مرحباً <strong>{{ auth()->user()->name }}</strong> • قائمة موحدة لإدارة واختيار فحوصات مرضى العمليات واعتماد نتائجها
                </small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('lab.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold rounded-pill px-3 shadow-xs">
                <i class="fas fa-arrow-left me-1"></i> الانتقال للمختبر العام
            </a>
            @can('create lab tests')
                <a href="{{ route('lab-tests.index') }}" class="btn btn-outline-primary btn-sm fw-semibold rounded-pill px-3 shadow-xs">
                    <i class="fas fa-vial me-1"></i> دليل وأسعار التحاليل
                </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- 2. بطاقات الإحصاءات السريعة الأربعة الموحدة -->
    <div class="row g-2 mb-3">
        <!-- أ. كافة العمليات -->
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('staff.surgery-lab-tests.index', ['status' => 'all', 'date' => request('date', 'all')]) }}" 
               class="card shadow-sm border-0 text-decoration-none h-100 {{ ($statusFilter ?? 'all') == 'all' ? 'border-bottom border-primary border-4 shadow' : '' }}" 
               style="background: #ffffff; transition: all 0.2s ease;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1 fw-semibold">كافة عمليات اليوم والجدول</span>
                        <h4 class="mb-0 fw-bold text-primary font-monospace">{{ $counts['all'] ?? 0 }}</h4>
                        <small class="text-muted" style="font-size: 0.7rem;">إجمالي مرضى العمليات المسجلين</small>
                    </div>
                    <div class="bg-primary-subtle text-primary p-2 rounded-circle text-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-procedures fs-5"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- ب. بانتظار تحديد الفحوصات -->
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('staff.surgery-lab-tests.index', ['status' => 'pending_selection', 'date' => request('date', 'all')]) }}" 
               class="card shadow-sm border-0 text-decoration-none h-100 {{ ($statusFilter ?? '') == 'pending_selection' ? 'border-bottom border-warning border-4 shadow' : '' }}" 
               style="background: #ffffff; transition: all 0.2s ease;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1 fw-semibold">بانتظار تحديد التحاليل</span>
                        <h4 class="mb-0 fw-bold text-warning font-monospace">{{ $counts['pending_selection'] ?? 0 }}</h4>
                        <small class="text-muted" style="font-size: 0.7rem;">محجوزة بحاجة لاختيار الفحوصات</small>
                    </div>
                    <div class="bg-warning-subtle text-warning p-2 rounded-circle text-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-hourglass-half fs-5"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- ج. بانتظار الفحص / معلق -->
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('staff.surgery-lab-tests.index', ['status' => 'pending', 'date' => request('date', 'all')]) }}" 
               class="card shadow-sm border-0 text-decoration-none h-100 {{ ($statusFilter ?? '') == 'pending' ? 'border-bottom border-info border-4 shadow' : '' }}" 
               style="background: #ffffff; transition: all 0.2s ease;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1 fw-semibold">بانتظار الفحص / جارية</span>
                        <h4 class="mb-0 fw-bold text-info font-monospace">{{ $counts['pending'] ?? 0 }}</h4>
                        <small class="text-muted" style="font-size: 0.7rem;">تحاليل محددة قيد سحب العينات والعمل</small>
                    </div>
                    <div class="bg-info-subtle text-info p-2 rounded-circle text-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-vial fs-5"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- د. المكتملة والمعتمدة -->
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('staff.surgery-lab-tests.index', ['status' => 'completed', 'date' => request('date', 'all')]) }}" 
               class="card shadow-sm border-0 text-decoration-none h-100 {{ ($statusFilter ?? '') == 'completed' ? 'border-bottom border-success border-4 shadow' : '' }}" 
               style="background: #ffffff; transition: all 0.2s ease;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1 fw-semibold">المكتملة والمعتمدة</span>
                        <h4 class="mb-0 fw-bold text-success font-monospace">{{ $counts['completed'] ?? 0 }}</h4>
                        <small class="text-muted" style="font-size: 0.7rem;">جاهزة ومتاحة لصالة العمليات</small>
                    </div>
                    <div class="bg-success-subtle text-success p-2 rounded-circle text-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-check-circle fs-5"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- 3. شريط الفلترة والبحث السريع الموحد (مطابق لـ /lab) -->
    <div class="card shadow-sm border-0 bg-white mb-3 rounded-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center justify-content-between">
                <!-- أزرار الفلترة السريعة لحالة الفحص -->
                <div class="col-lg-7 col-12">
                    <div class="d-flex flex-wrap align-items-center gap-1.5">
                        <span class="text-muted small fw-bold me-1"><i class="fas fa-filter text-secondary me-1"></i> فلترة:</span>
                        
                        <a href="{{ route('staff.surgery-lab-tests.index', ['status' => 'all', 'date' => request('date', 'all'), 'search' => request('search')]) }}" 
                           class="btn btn-sm py-1 px-3 rounded-pill {{ ($statusFilter ?? 'all') == 'all' ? 'btn-secondary text-white fw-bold shadow-sm' : 'btn-outline-secondary' }}">
                            الكل <span class="badge bg-white text-dark ms-1 rounded-pill font-monospace">{{ $counts['all'] ?? 0 }}</span>
                        </a>

                        <a href="{{ route('staff.surgery-lab-tests.index', ['status' => 'pending_selection', 'date' => request('date', 'all'), 'search' => request('search')]) }}" 
                           class="btn btn-sm py-1 px-3 rounded-pill {{ ($statusFilter ?? '') == 'pending_selection' ? 'btn-warning text-dark fw-bold shadow-sm' : 'btn-outline-warning text-dark' }}">
                            <i class="fas fa-hourglass-half me-1"></i> بانتظار تحديد الفحوصات 
                            <span class="badge bg-dark text-white ms-1 rounded-pill font-monospace">{{ $counts['pending_selection'] ?? 0 }}</span>
                        </a>

                        <a href="{{ route('staff.surgery-lab-tests.index', ['status' => 'pending', 'date' => request('date', 'all'), 'search' => request('search')]) }}" 
                           class="btn btn-sm py-1 px-3 rounded-pill {{ ($statusFilter ?? '') == 'pending' ? 'btn-primary text-white fw-bold shadow-sm' : 'btn-outline-primary' }}">
                            <i class="fas fa-vial me-1"></i> بانتظار الفحص / معلق 
                            <span class="badge bg-white text-primary ms-1 rounded-pill font-monospace">{{ $counts['pending'] ?? 0 }}</span>
                        </a>

                        <a href="{{ route('staff.surgery-lab-tests.index', ['status' => 'completed', 'date' => request('date', 'all'), 'search' => request('search')]) }}" 
                           class="btn btn-sm py-1 px-3 rounded-pill {{ ($statusFilter ?? '') == 'completed' ? 'btn-success text-white fw-bold shadow-sm' : 'btn-outline-success' }}">
                            <i class="fas fa-check-circle me-1"></i> المكتملة والمعتمدة 
                            <span class="badge bg-white text-success ms-1 rounded-pill font-monospace">{{ $counts['completed'] ?? 0 }}</span>
                        </a>
                    </div>
                </div>

                <!-- شريط البحث وفلترة التاريخ -->
                <div class="col-lg-5 col-12">
                    <form method="GET" action="{{ route('staff.surgery-lab-tests.index') }}" class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                        <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                        
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="{{ route('staff.surgery-lab-tests.index', ['status' => request('status', 'all'), 'date' => 'today', 'search' => request('search')]) }}" 
                               class="btn {{ ($dateFilter ?? '') == 'today' ? 'btn-primary fw-bold' : 'btn-outline-secondary' }}">
                                اليوم
                            </a>
                            <a href="{{ route('staff.surgery-lab-tests.index', ['status' => request('status', 'all'), 'date' => 'all', 'search' => request('search')]) }}" 
                               class="btn {{ ($dateFilter ?? 'all') == 'all' ? 'btn-primary fw-bold' : 'btn-outline-secondary' }}">
                                كافة التواريخ
                            </a>
                        </div>

                        <div class="input-group input-group-sm" style="max-width: 250px;">
                            <input type="text" name="search" id="surgery-search-input" class="form-control" 
                                   placeholder="بحث باسم المريض، رقم، عملية..." 
                                   value="{{ request('search') }}">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request('search'))
                                <a href="{{ route('staff.surgery-lab-tests.index', ['status' => request('status', 'all'), 'date' => request('date', 'all')]) }}" class="btn btn-outline-secondary" title="إلغاء البحث">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. جدول طلبات العمليات الموحد والوحيد في الشاشة (قائمة واحدة مطابقة للمختبر العام) -->
    <div class="card shadow-sm border-0 bg-white rounded-3 overflow-hidden">
        <div class="card-body p-0">
            @if($surgeries->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="unifiedSurgeriesTable">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="text-center" style="width: 80px;"># العملية</th>
                                <th>بيانات المريض والموقع</th>
                                <th>المصدر / الجراح</th>
                                <th>العملية والتحاليل المطلوبة</th>
                                <th style="width: 145px;">موعد وتاريخ العملية</th>
                                <th style="width: 110px;" class="text-center">حالة الدفع</th>
                                <th style="width: 155px;" class="text-center">حالة الفحص</th>
                                <th style="width: 180px;" class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($surgeries as $surgery)
                                @php
                                    $patient = $surgery->patient;
                                    $patientUser = $patient?->user;
                                    $patientName = $patientUser?->name ?? 'مريض غير معروف';
                                    $doctorUser = $surgery->doctor?->user;
                                    $doctorName = $doctorUser?->name ?? 'غير محدد';
                                    $validTests = $surgery->labTests->whereNotNull('lab_test_id');
                                    $hasTests = $validTests->count() > 0;
                                    $firstTest = $validTests->first() ?: $surgery->labTests->first();
                                    $completedTestsCount = $validTests->where('status', 'completed')->count();
                                    $isAllCompleted = $hasTests && ($completedTestsCount === $validTests->count());
                                    $isFeePaid = ($surgery->surgery_fee_paid === 'paid') || ($validTests->where('payment_status', 'paid')->count() > 0);

                                    // تحديد لون الحافة وحالة السطر
                                    if (!$hasTests) {
                                        $rowBorderColor = '#f59e0b'; // أصفر برتقالي - بانتظار تحديد الفحوصات
                                        $rowBg = '#ffffff';
                                    } elseif ($isAllCompleted) {
                                        $rowBorderColor = '#10b981'; // أخضر - مكتمل ومعتمد
                                        $rowBg = '#f0fdf4';
                                    } else {
                                        $rowBorderColor = '#0284c7'; // أزرق - بانتظار الفحص والنتائج
                                        $rowBg = '#ffffff';
                                    }
                                @endphp
                                <tr style="border-right: 4px solid {{ $rowBorderColor }}; background-color: {{ $rowBg }};">
                                    <!-- 1. رقم العملية / الطلب -->
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-dark font-monospace fw-bold px-2 py-1">
                                            #{{ $surgery->id }}
                                        </span>
                                        <div class="text-muted" style="font-size: 0.65rem;">
                                            {{ $surgery->created_at ? $surgery->created_at->format('Y-m-d') : '' }}
                                        </div>
                                    </td>

                                    <!-- 2. بيانات المريض والموقع السريري (الغرفة والطابق) -->
                                    <td>
                                        <div class="fw-bold text-dark d-flex align-items-center gap-1">
                                            <span>{{ $patientName }}</span>
                                            @if($patientUser?->gender)
                                                <span class="text-muted small" style="font-size: 0.72rem;">
                                                    ({{ $patientUser->gender === 'male' ? 'ذكر' : 'أنثى' }})
                                                </span>
                                            @endif
                                        </div>
                                        <div class="mt-1 d-flex flex-wrap align-items-center gap-1">
                                            @if($surgery->room)
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-0.5 px-2 font-monospace" style="font-size: 0.75rem;">
                                                    <i class="fas fa-bed me-1"></i> غرفة {{ $surgery->room->room_number }} ({{ $surgery->room->floor ?? 'الطابق' }})
                                                </span>
                                            @else
                                                <span class="badge bg-light text-muted border py-0.5 px-2" style="font-size: 0.7rem;">
                                                    <i class="fas fa-hospital me-1"></i> لم تسند غرفة
                                                </span>
                                            @endif

                                            @if($patient?->national_id)
                                                <small class="text-muted font-monospace" style="font-size: 0.7rem;">
                                                    هوية: {{ $patient->national_id }}
                                                </small>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- 3. المصدر / الجراح -->
                                    <td>
                                        <div class="fw-semibold text-dark small">
                                            <i class="fas fa-user-md text-primary me-1"></i> د. {{ $doctorName }}
                                        </div>
                                        <small class="text-muted d-block" style="font-size: 0.72rem;">
                                            {{ $surgery->department?->name ?? 'قسم العمليات الجراحية' }}
                                        </small>
                                    </td>

                                    <!-- 4. العملية والتحاليل المطلوبة -->
                                    <td>
                                        <div class="fw-bold text-dark small mb-1">
                                            <i class="fas fa-scalpel text-secondary me-1"></i> {{ $surgery->surgery_type }}
                                        </div>
                                        @if($hasTests)
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($validTests->take(4) as $sTest)
                                                    <span class="badge {{ $sTest->status === 'completed' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-dark border' }} py-0.5 px-2" style="font-size: 0.72rem;">
                                                        @if($sTest->status === 'completed')
                                                            <i class="fas fa-check-circle me-1"></i>
                                                        @else
                                                            <i class="fas fa-vial me-1 text-primary"></i>
                                                        @endif
                                                        {{ $sTest->labTest->name ?? 'تحليل' }}
                                                    </span>
                                                @endforeach
                                                @if($validTests->count() > 4)
                                                    <span class="badge bg-secondary-subtle text-dark border py-0.5 px-1.5" style="font-size: 0.7rem;">
                                                        +{{ $validTests->count() - 4 }} تحاليل أخرى
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle py-1 px-2" style="font-size: 0.75rem;">
                                                <i class="fas fa-exclamation-triangle text-warning me-1"></i> لم تحدد التحاليل بعد (طلب جديد)
                                            </span>
                                        @endif
                                    </td>

                                    <!-- 5. موعد وتاريخ العملية -->
                                    <td>
                                        <div class="small fw-semibold text-dark">
                                            <i class="far fa-calendar-alt text-secondary me-1"></i>
                                            {{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : 'غير محدد' }}
                                        </div>
                                        <small class="text-muted d-block font-monospace" style="font-size: 0.72rem;">
                                            <i class="far fa-clock me-1"></i>
                                            {{ $surgery->scheduled_time ? date('h:i A', strtotime($surgery->scheduled_time)) : 'وقت غير محدد' }}
                                        </small>
                                    </td>

                                    <!-- 6. حالة الدفع -->
                                    <td class="text-center">
                                        @if($isFeePaid)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size: 0.72rem;">
                                                <i class="fas fa-check-circle me-1"></i> مدفوع
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-1 px-2" style="font-size: 0.72rem;">
                                                <i class="fas fa-clock me-1"></i> غير مدفوع
                                            </span>
                                        @endif
                                    </td>

                                    <!-- 7. حالة الفحص -->
                                    <td class="text-center">
                                        @if(!$hasTests)
                                            <span class="badge bg-warning text-dark py-1.5 px-2.5 rounded-pill shadow-xs" style="font-size: 0.72rem;">
                                                <i class="fas fa-hourglass-half me-1"></i> بانتظار تحديد الفحوصات
                                            </span>
                                        @elseif($isAllCompleted)
                                            <span class="badge bg-success text-white py-1.5 px-2.5 rounded-pill shadow-xs" style="font-size: 0.72rem;">
                                                <i class="fas fa-check-circle me-1"></i> مكتمل ومعتمد ({{ $completedTestsCount }}/{{ $validTests->count() }})
                                            </span>
                                        @else
                                            <span class="badge bg-primary text-white py-1.5 px-2.5 rounded-pill shadow-xs" style="font-size: 0.72rem;">
                                                <i class="fas fa-vial me-1"></i> بانتظار الفحص / جاري ({{ $completedTestsCount }}/{{ $validTests->count() }})
                                            </span>
                                        @endif
                                    </td>

                                    <!-- 8. الإجراءات -->
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            @if(!$hasTests)
                                                <!-- زر تحديد الباقة للعمليات المحجوزة حديثاً -->
                                                <form action="{{ route('staff.surgery-lab-tests.create-selection', $surgery) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-warning text-dark btn-sm fw-bold px-3 py-1.5 rounded-pill shadow-xs d-inline-flex align-items-center gap-1">
                                                        <i class="fas fa-vial"></i>
                                                        <span>تحديد باقة التحاليل</span>
                                                    </button>
                                                </form>
                                            @else
                                                <!-- زر إدخال / معاينة النتائج للعمليات التي لها تحاليل -->
                                                <a href="{{ route('staff.surgery-lab-tests.show', $firstTest) }}" 
                                                   class="btn {{ $isAllCompleted ? 'btn-outline-primary' : 'btn-primary' }} btn-sm fw-semibold rounded-pill px-3 shadow-xs d-inline-flex align-items-center gap-1">
                                                    <i class="fas {{ $isAllCompleted ? 'fa-eye' : 'fa-microscope' }}"></i>
                                                    <span>{{ $isAllCompleted ? 'معاينة النتائج' : 'إدخال النتائج' }}</span>
                                                </a>

                                                @if($completedTestsCount > 0)
                                                    <a href="{{ route('staff.surgery-lab-tests.print', $firstTest) }}" target="_blank" 
                                                       class="btn btn-outline-dark btn-sm rounded-circle p-1 shadow-xs" 
                                                       title="طباعة تقرير التحاليل المعتمدة" 
                                                       style="width: 31px; height: 31px; display: inline-flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($surgeries->hasPages())
                    <div class="p-3 border-top d-flex justify-content-center">
                        {{ $surgeries->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <div class="text-muted mb-3">
                        <i class="fas fa-procedures fa-3x" style="color: #cbd5e1;"></i>
                    </div>
                    <h5 class="text-dark fw-bold mb-1">لا توجد عمليات جراحية مطابقة لخيارات الفرز الحالية</h5>
                    <p class="text-muted small mb-3">يمكنك تعديل البحث أو الضغط على خيار "الكل" لعرض كافة العمليات.</p>
                    <a href="{{ route('staff.surgery-lab-tests.index') }}" class="btn btn-outline-primary btn-sm px-3 rounded-pill">
                        <i class="fas fa-redo me-1"></i> إعادة ضبط الفلاتر
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
