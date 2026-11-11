@extends('layouts.app')

@section('title', 'رادار وحدة العناية المركزة (ICU) - المتابعة السريرية الحية')

@section('content')
<div class="container-fluid py-3">
    <!-- رأس الصفحة وأزرار التحكم -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2 class="mb-1 fw-bold text-dark">
                        <i class="fas fa-heartbeat text-danger me-2"></i>
                        رادار وحدة العناية المركزة والحرجة (ICU / CCU)
                    </h2>
                    <p class="text-muted mb-0">المراقبة السريرية الحية، إشغال الأسرة، وأجهزة التنفس الاصطناعي - الطابق الثالث</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @can('admit icu patients')
                    <a href="{{ route('icu.admissions.create') }}" class="btn btn-success shadow-sm">
                        <i class="fas fa-user-plus me-1"></i> دخول وحجز مريض بالعناية
                    </a>
                    @endcan
                    @can('manage icu beds')
                    <a href="{{ route('icu.beds.index') }}" class="btn btn-outline-primary shadow-sm">
                        <i class="fas fa-bed me-1"></i> إدارة وتوزيع الأسرة
                    </a>
                    @endcan
                    <button type="button" class="btn btn-outline-secondary shadow-sm" onclick="window.location.reload();">
                        <i class="fas fa-sync-alt me-1"></i> تحديث فوري
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- التنبيهات -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- كروت الإحصائيات السريعة (KPIs) -->
    <div class="row mb-4 g-3">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm bg-primary text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">إجمالي الأسرة</span>
                        <h3 class="mb-0 fw-bold">{{ $stats['total_beds'] }}</h3>
                        <small class="opacity-75">طاقة الاستيعاب</small>
                    </div>
                    <div class="p-2 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-bed fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm bg-danger text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">الأسرة المشغولة</span>
                        <h3 class="mb-0 fw-bold">{{ $stats['occupied_beds'] }}</h3>
                        <small class="opacity-75">مرضى راقدين</small>
                    </div>
                    <div class="p-2 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-user-injured fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm bg-success text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">الأسرة الشاغرة</span>
                        <h3 class="mb-0 fw-bold">{{ $stats['available_beds'] }}</h3>
                        <small class="opacity-75">جاهز للاستقبال</small>
                    </div>
                    <div class="p-2 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-check-double fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm bg-warning text-dark h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">قيد التعقيم</span>
                        <h3 class="mb-0 fw-bold">{{ $stats['sterilizing_beds'] }}</h3>
                        <small class="opacity-75">تطهير السرير</small>
                    </div>
                    <div class="p-2 bg-dark bg-opacity-10 rounded-circle">
                        <i class="fas fa-pump-soap fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm bg-info text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">تنفس اصطناعي</span>
                        <h3 class="mb-0 fw-bold">{{ $stats['ventilated_count'] }}</h3>
                        <small class="opacity-75">Ventilator Active</small>
                    </div>
                    <div class="p-2 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-lungs fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm bg-dark text-white h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small opacity-75 d-block mb-1">غيبوبة وحرجة</span>
                        <h3 class="mb-0 fw-bold text-danger">{{ $stats['critical_gcs_count'] }}</h3>
                        <small class="opacity-75">GCS &le; 8 حرج جداً</small>
                    </div>
                    <div class="p-2 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-brain fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط الفلاتر والبحث -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('icu.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-0 bg-light" placeholder="بحث باسم المريض أو رقم السرير أو الغرفة..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select border-0 bg-light">
                        <option value="all">كل أنواع الأسرة</option>
                        <option value="general" {{ request('type') == 'general' ? 'selected' : '' }}>عناية مركزة عامة (General ICU)</option>
                        <option value="ccu" {{ request('type') == 'ccu' ? 'selected' : '' }}>عناية قلبية فائقة (CCU)</option>
                        <option value="neuro" {{ request('type') == 'neuro' ? 'selected' : '' }}>عناية عصبية (Neuro-ICU)</option>
                        <option value="isolation" {{ request('type') == 'isolation' ? 'selected' : '' }}>عزل ضغط سالب (Isolation)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select border-0 bg-light">
                        <option value="all">كل الحالات</option>
                        <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>🟢 شاغر ومتاح</option>
                        <option value="occupied" {{ request('status') == 'occupied' ? 'selected' : '' }}>🔴 مشغول بمريض</option>
                        <option value="sterilizing" {{ request('status') == 'sterilizing' ? 'selected' : '' }}>🟡 قيد التعقيم</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">تصفية</button>
                    <a href="{{ route('icu.index') }}" class="btn btn-outline-secondary">إعادة ضبط</a>
                </div>
            </form>
        </div>
    </div>

    <!-- شبكة كروت أسرة العناية (ICU Beds Grid) -->
    <div class="row g-3">
        @forelse($beds as $bed)
            @php
                $admission = $bed->currentAdmission;
                $latestVital = $admission?->latestVital;
                $latestFluid = $admission?->latestFluidBalance;
                $isCriticalGcs = $latestVital && $latestVital->gcs && $latestVital->gcs <= 8;
            @endphp
            <div class="col-xxl-3 col-xl-4 col-md-6">
                <div class="card h-100 shadow-sm border-0 position-relative overflow-hidden" 
                     style="border-radius: 14px; {{ $bed->status === 'occupied' ? 'border-top: 4px solid #dc3545 !important;' : ($bed->status === 'available' ? 'border-top: 4px solid #198754 !important;' : 'border-top: 4px solid #ffc107 !important;') }}">
                    
                    <!-- رأس الكارت -->
                    <div class="card-header bg-white border-bottom-0 pb-1 pt-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge {{ $bed->status_badge_class }} px-2 py-1 fs-6">
                                سرير #{{ $bed->bed_number }}
                            </span>
                            <span class="badge {{ $bed->type_badge_class }} py-1">
                                {{ $bed->type_name }}
                            </span>
                        </div>
                        <span class="badge {{ $bed->status_badge_class }}">
                            {{ $bed->status_name }}
                        </span>
                    </div>

                    <div class="card-body pt-2 pb-3">
                        <div class="d-flex justify-content-between text-muted small mb-2">
                            <span><i class="fas fa-door-open me-1"></i> {{ $bed->room_number ?? 'ردهة العناية' }}</span>
                            <span>{{ number_format($bed->daily_price) }} د.ع / يوم</span>
                        </div>

                        @if($bed->status === 'occupied' && $admission)
                            <!-- بيانات المريض الراقد -->
                            <div class="bg-light p-2 rounded mb-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h6 class="fw-bold mb-1 text-primary">
                                        <i class="fas fa-user-circle me-1"></i>
                                        {{ $admission->patient?->user?->name ?? $admission->patient?->name ?? 'مريض غير محدد' }}
                                    </h6>
                                    @if($admission->is_ventilated)
                                        <span class="badge bg-info text-white" title="جهاز تنفس اصطناعي قيد التشغيل">
                                            <i class="fas fa-lungs me-1"></i> تنفس صناعي
                                        </span>
                                    @endif
                                </div>
                                <div class="small text-muted mb-1">
                                    <span>العمر: {{ $admission->patient?->age ?? 'غير محدد' }}</span> | 
                                    <span>الطبيب: {{ $admission->doctor?->user?->name ?? 'طبيب العناية المناوب' }}</span>
                                </div>
                                <div class="small text-secondary">
                                    <i class="fas fa-stethoscope me-1"></i> {{ Str::limit($admission->primary_diagnosis, 45) }}
                                </div>
                                <div class="small text-muted mt-1 d-flex justify-content-between">
                                    <span><i class="fas fa-sign-in-alt me-1"></i> {{ $admission->admission_source_name }}</span>
                                    <span class="badge bg-secondary">{{ $admission->stay_days_count }} يوم ({{ $admission->stay_duration_hours }} س)</span>
                                </div>
                            </div>

                            <!-- شريط المؤشرات الحيوية السريعة -->
                            <div class="row g-1 text-center mb-3">
                                <div class="col-3">
                                    <div class="p-1 rounded bg-light border {{ $isCriticalGcs ? 'bg-danger text-white' : '' }}">
                                        <small class="d-block text-muted {{ $isCriticalGcs ? 'text-white-50' : '' }}" style="font-size: 0.68rem;">GCS (الوعي)</small>
                                        <strong class="fs-6">{{ $latestVital?->gcs ?? $admission->initial_gcs ?? '--' }}</strong>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-1 rounded bg-light border">
                                        <small class="d-block text-muted" style="font-size: 0.68rem;">النبض HR</small>
                                        <strong class="fs-6 {{ ($latestVital?->heart_rate > 110 || $latestVital?->heart_rate < 55) ? 'text-danger' : 'text-dark' }}">
                                            {{ $latestVital?->heart_rate ?? '--' }}
                                        </strong>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-1 rounded bg-light border">
                                        <small class="d-block text-muted" style="font-size: 0.68rem;">الضغط BP</small>
                                        <strong class="fs-6" style="font-size: 0.78rem;">
                                            {{ $latestVital?->systolic_bp ? $latestVital->systolic_bp.'/'.$latestVital->diastolic_bp : '--' }}
                                        </strong>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-1 rounded bg-light border">
                                        <small class="d-block text-muted" style="font-size: 0.68rem;">SpO2 الأكسجين</small>
                                        <strong class="fs-6 {{ ($latestVital?->spo2 && $latestVital->spo2 < 92) ? 'text-danger' : 'text-success' }}">
                                            {{ $latestVital?->spo2 ? $latestVital->spo2.'%' : '--' }}
                                        </strong>
                                    </div>
                                </div>
                            </div>

                            <!-- أزرار الإجراءات للمريض المشغول -->
                            <div class="d-flex gap-1">
                                <a href="{{ route('icu.show', $admission->id) }}" class="btn btn-sm btn-primary flex-grow-1">
                                    <i class="fas fa-folder-open me-1"></i> فتح الإضبارة
                                </a>
                                @can('record icu vitals')
                                <a href="{{ route('icu.show', $admission->id) }}#vitals" class="btn btn-sm btn-outline-info" title="تدوين قراءة حيوية">
                                    <i class="fas fa-heartbeat"></i>
                                </a>
                                @endcan
                                @can('discharge icu patients')
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#dischargeModal{{ $admission->id }}" 
                                        title="تخريج / تحويل للرقود">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                                @endcan
                            </div>

                            <!-- مودال التخريج السريع -->
                            <div class="modal fade" id="dischargeModal{{ $admission->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('icu.admissions.discharge', $admission->id) }}">
                                            @csrf
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title fs-6">تخريج مريض العناية المركزة - سرير #{{ $bed->bed_number }}</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <p class="mb-2">المريض: <strong>{{ $admission->patient?->user?->name ?? $admission->patient?->name }}</strong></p>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">وجهة التحويل / الخروج:</label>
                                                    <select name="discharge_destination" class="form-select" required>
                                                        <option value="جناح الرقود العادي (Step-down Ward)">تحويل إلى جناح الرقود العادي (Step-down)</option>
                                                        <option value="خروج إلى المنزل بعد استقرار الحالة">خروج إلى المنزل بعد الشفاء والاستقرار</option>
                                                        <option value="تحويل إلى مركز تخصصي خارجي">تحويل إلى مركز تخصصي خارجي</option>
                                                        <option value="وفاة (رحمه الله)">وفاة المريض (رحمه الله)</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">ملخص حالة المريض وتوصيات الخروج:</label>
                                                    <textarea name="discharge_summary" class="form-control" rows="3" required placeholder="اكتب ملخص الخطة العلاجية وحالة المريض عند الخروج..."></textarea>
                                                </div>
                                                <div class="alert alert-warning py-2 small mb-0">
                                                    <i class="fas fa-info-circle me-1"></i> عند تأكيد الخروج سيتحول السرير رقم ({{ $bed->bed_number }}) تلقائياً إلى حالة "قيد التعقيم والتطهير".
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                                                <button type="submit" class="btn btn-danger btn-sm">تأكيد تخريج المريض</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        @elseif($bed->status === 'sterilizing')
                            <!-- السرير قيد التعقيم -->
                            <div class="text-center py-4 bg-light rounded mb-3">
                                <i class="fas fa-pump-soap text-warning fa-3x mb-2"></i>
                                <h6 class="fw-bold text-dark mb-1">السرير قيد التعقيم والتطهير</h6>
                                <p class="text-muted small mb-0">يجري تعقيم أجهزة السرير والشاشات وفق معايير مكافحة العدوى</p>
                            </div>
                            @can('manage icu beds')
                            <form method="POST" action="{{ route('icu.beds.sterilized', $bed->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-warning w-100 btn-sm text-dark fw-bold">
                                    <i class="fas fa-check-circle me-1"></i> تأكيد اكتمال التعقيم وجاهزية السرير
                                </button>
                            </form>
                            @endcan

                        @else
                            <!-- السرير شاغر ومتاح -->
                            <div class="text-center py-4 bg-light rounded mb-3">
                                <i class="fas fa-bed text-success fa-3x mb-2"></i>
                                <h6 class="fw-bold text-success mb-1">السرير متاح وجاهز للاستقبال</h6>
                                <p class="text-muted small mb-0">مجهز بالمراقبة المركزية ومخارج الأكسجين</p>
                            </div>
                            @can('admit icu patients')
                            <a href="{{ route('icu.admissions.create', ['bed_id' => $bed->id]) }}" class="btn btn-success w-100 btn-sm">
                                <i class="fas fa-plus-circle me-1"></i> تسكين مريض على هذا السرير
                            </a>
                            @endcan
                        @endif

                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center py-4">
                    <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                    لا توجد أسرة مطابقة للبحث أو الفلتر المحدد.
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
