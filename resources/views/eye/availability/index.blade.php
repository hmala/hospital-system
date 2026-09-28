@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" style="background-color: #f8fafc; min-height: 100vh;">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-6 fw-bold text-primary mb-2">
                <i class="fas fa-eye me-2"></i>
                توفر أطباء واستشاريي العيون
            </h1>
            <p class="lead text-muted small">متابعة دوام أطباء العيون، تحديث التوفر اليومي، وإدارة طابور المراجعين المباشر</p>
        </div>
    </div>

    <!-- Quick Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mx-auto mb-4" style="max-width: 750px;" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mx-auto mb-4" style="max-width: 750px;" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4 g-3">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100 rounded-3 border-start border-4 border-primary">
                <div class="card-body text-center py-3">
                    <div class="display-5 fw-bold text-primary mb-1">{{ $stats['total'] }}</div>
                    <div class="text-muted fw-semibold small">إجمالي أطباء العيون</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100 rounded-3 border-start border-4 border-success">
                <div class="card-body text-center py-3">
                    <div class="display-5 fw-bold text-success mb-1">{{ $stats['available_today'] }}</div>
                    <div class="text-muted fw-semibold small">متاح اليوم بالمركز</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100 rounded-3 border-start border-4 border-danger">
                <div class="card-body text-center py-3">
                    <div class="display-5 fw-bold text-danger mb-1">{{ $stats['unavailable_today'] }}</div>
                    <div class="text-muted fw-semibold small">غير متاح حالياً</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100 rounded-3 border-start border-4 border-info">
                <div class="card-body text-center py-3">
                    <div class="display-5 fw-bold text-info mb-1">{{ $stats['today_queue'] }}</div>
                    <div class="text-muted fw-semibold small">طابور وحالات اليوم</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Day Selector Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills justify-content-center bg-white p-2 rounded-3 shadow-sm border" role="tablist">
                @foreach($weekDays as $day)
                    <li class="nav-item me-1" role="presentation">
                        <a href="?day={{ urlencode($day) }}" class="nav-link px-4 fw-bold {{ $day === $selectedDay ? 'active bg-primary' : 'text-dark' }}">
                            {{ $day }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- Action Toolbar Buttons -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <!-- Bulk Enable -->
                <form method="POST" action="{{ route('eye.availability.bulkUpdate') }}" style="display: inline;">
                    @csrf
                    <input type="hidden" name="is_available_today" value="1">
                    <button type="submit" class="btn btn-success px-4 py-2 shadow-sm fw-bold" onclick="return confirm('هل أنت متأكد من تفعيل التوفر لجميع أطباء واستشاريي العيون؟')">
                        <i class="fas fa-toggle-on me-1"></i> تفعيل الكل
                    </button>
                </form>

                <!-- Bulk Disable -->
                <form method="POST" action="{{ route('eye.availability.bulkUpdate') }}" style="display: inline;">
                    @csrf
                    <input type="hidden" name="is_available_today" value="0">
                    <button type="submit" class="btn btn-outline-danger px-4 py-2 shadow-sm fw-bold" onclick="return confirm('هل أنت متأكد من إلغاء التوفر لجميع أطباء العيون؟')">
                        <i class="fas fa-toggle-off me-1"></i> إلغاء الكل
                    </button>
                </form>

                <!-- Add Eye Doctor Button -->
                <a href="{{ route('eye.availability.doctors.create') }}" class="btn btn-success px-4 py-2 shadow-sm fw-bold">
                    <i class="fas fa-user-md me-1"></i> إضافة طبيب عيون جديد
                </a>

                <!-- Eye Reception Link -->
                <a href="{{ route('eye.reception.index') }}" class="btn btn-outline-primary px-4 py-2 shadow-sm fw-bold">
                    <i class="fas fa-users me-1"></i> طابور الاستعلامات
                </a>

                <!-- New Appointment Button -->
                <a href="{{ route('eye.reception.create') }}" class="btn btn-primary px-4 py-2 shadow-sm fw-bold">
                    <i class="fas fa-calendar-plus me-1"></i> حجز موعد عيون جديد
                </a>

                <!-- Public Queue TV Screen Link -->
                <a href="{{ route('queue.all.display') }}" target="_blank" class="btn btn-outline-info px-4 py-2 shadow-sm fw-bold" title="شاشة العرض العامة للتلفاز">
                    <i class="fas fa-tv me-1"></i> شاشة صالة الانتظار العامة
                </a>
            </div>
        </div>
    </div>

    <!-- Live Toast / Alert for Call Patient -->
    <div id="callAlertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1100;"></div>

    <!-- Today's Eye Appointments & Live Queue Section -->
    @if(isset($todayAppointments) && $todayAppointments->count() > 0)
    <div class="row mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-calendar-day me-2"></i>
                        طابور ومراجعي العيون المباشر لليوم
                        <span class="badge bg-white text-primary ms-2">{{ $todayAppointments->count() }} مراجع</span>
                    </h5>
                    <span class="badge bg-success-subtle text-success border border-success px-3 py-1">
                        <i class="fas fa-circle fa-xs me-1"></i> طابور حي
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 70px;" class="text-center"># الدور</th>
                                <th style="width: 110px;">الوقت</th>
                                <th>المريض</th>
                                <th>طبيب العيون المختص</th>
                                <th>نوع المراجعة</th>
                                <th class="text-center">توسيع الحدقة</th>
                                <th>كاشير العيون</th>
                                <th>حالة المراجع</th>
                                <th class="text-center" style="width: 220px;">الإجراءات السريعة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($todayAppointments as $appointment)
                            <tr class="{{ $appointment->status === 'in_clinic' ? 'table-success' : ($appointment->status === 'dilated' ? 'table-warning' : '') }}">
                                <td class="text-center">
                                    <span class="badge bg-dark fs-6 px-3 py-2">{{ $appointment->queue_number ?: $appointment->id }}</span>
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        <i class="fas fa-clock me-1 text-primary"></i>
                                        {{ $appointment->created_at->format('h:i A') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary">{{ $appointment->patient->name }}</div>
                                    <div class="small text-muted">
                                        <span>MRN: {{ $appointment->patient->medical_record_number ?? 'N/A' }}</span>
                                        @if($appointment->patient->phone)
                                            <span> • {{ $appointment->patient->phone }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        @if($appointment->insurance_type === 'health_insurance')
                                            <span class="badge bg-info-subtle text-info small"><i class="fas fa-heartbeat me-1"></i>ضمان صحي</span>
                                        @elseif($appointment->insurance_type === 'moi')
                                            <span class="badge bg-primary-subtle text-primary small"><i class="fas fa-shield-alt me-1"></i>قوى الأمن</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary small">نقدي (Cash)</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">د. {{ $appointment->doctor->user->name ?? 'طبيب العيون' }}</div>
                                    <small class="text-muted">{{ $appointment->doctor->specialization ?? 'جراحة عيون' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $appointment->visit_type_arabic }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($appointment->status === 'dilated')
                                        <span class="badge bg-warning text-dark px-2 py-1" title="تم وضع قطرات الميدرياسيل">
                                            <i class="fas fa-eye me-1"></i>موسعة 👁️
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($appointment->latestInvoice && $appointment->latestInvoice->status === 'paid')
                                        <span class="badge bg-success-subtle text-success border border-success"><i class="fas fa-check-circle me-1"></i>مدفوع</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning"><i class="fas fa-clock me-1"></i>بانتظار الدفع</span>
                                    @endif
                                </td>
                                <td>
                                    @if($appointment->status === 'waiting')
                                        <span class="badge bg-warning text-dark">في الانتظار</span>
                                    @elseif($appointment->status === 'dilated')
                                        <span class="badge bg-warning text-dark">توسيع الحدقة</span>
                                    @elseif($appointment->status === 'in_clinic')
                                        <span class="badge bg-primary text-white">في العيادة 🩺</span>
                                    @elseif($appointment->status === 'in_investigation')
                                        <span class="badge bg-info text-white">في غرفة الأجهزة</span>
                                    @else
                                        <span class="badge bg-success text-white">مكتمل</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                                        <!-- Call / Recall Button -->
                                        <button type="button" class="btn btn-sm btn-primary call-patient-btn" data-id="{{ $appointment->id }}" data-name="{{ $appointment->patient->name }}" data-queue="{{ $appointment->queue_number }}" title="مناداة واستدعاء المريض للشاشة الخارجية">
                                            <i class="fas fa-bullhorn me-1"></i>استدعاء 📢
                                        </button>

                                        <!-- Admit to Clinic Button -->
                                        <form method="POST" action="{{ route('eye.availability.admitPatient', $appointment) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="إدخال المريض لعيادة الفحص">
                                                <i class="fas fa-sign-in-alt me-1"></i>إدخال 🩺
                                            </button>
                                        </form>

                                        <!-- Dilate Pupil Button -->
                                        @if($appointment->status !== 'dilated')
                                        <form method="POST" action="{{ route('eye.availability.dilatePatient', $appointment) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning text-dark" title="توثيق توسيع الحدقة بالقطرات">
                                                <i class="fas fa-tint me-1"></i>توسيع 💧
                                            </button>
                                        </form>
                                        @endif

                                        <!-- Print Ticket -->
                                        <a href="{{ route('eye.reception.printTicket', $appointment) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="طباعة كارت وتذكرة الطابور">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Eye Doctors Availability Directory Table -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-user-md text-primary me-2"></i>
                        دليل وجدول أطباء واستشاريي العيون (يوم {{ $selectedDay }})
                    </h5>
                    <div class="d-flex gap-2 align-items-center">
                        <a href="{{ route('eye.availability.doctors.create') }}" class="btn btn-sm btn-success fw-bold shadow-sm">
                            <i class="fas fa-plus-circle me-1"></i> إضافة طبيب عيون جديد
                        </a>
                        <span class="badge bg-light text-muted border">العدد: {{ $eyeDoctors->count() }} طبيب</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase">
                                <th style="width: 50px;">#</th>
                                <th>طبيب العيون</th>
                                <th>التخصص الدقيق بالمركز</th>
                                <th>أيام الدوام المعتمدة</th>
                                <th>تسعيرة الكشف</th>
                                <th class="text-center" style="width: 140px;">الحالة اليوم</th>
                                <th class="text-center" style="width: 110px;">تبديل التوفر</th>
                                <th class="text-center" style="width: 180px;">إدارة وحجز</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($eyeDoctors as $index => $doctor)
                            <tr>
                                <td class="text-muted small fw-bold">{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 42px; height: 42px;">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">د. {{ $doctor->user->name ?? 'طبيب عيون' }}</div>
                                            <small class="text-muted">{{ $doctor->qualification ?? 'استشاري عيون' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border px-2 py-1 fs-6">
                                        <i class="fas fa-stethoscope me-1"></i>{{ $doctor->specialization ?: 'جراحة عيون عامة' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="small text-muted">
                                        @if(is_array($doctor->working_days) && count($doctor->working_days) > 0)
                                            {{ implode(' • ', $doctor->working_days) }}
                                        @else
                                            <span>كافة أيام الأسبوع</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark">{{ number_format($doctor->consultation_fee) }}</span>
                                    <small class="text-muted">د.ع</small>
                                </td>
                                <td class="text-center">
                                    @if($doctor->is_available_today)
                                        <span class="badge bg-success fs-6 px-3 py-2 shadow-sm">
                                            <i class="fas fa-check-circle me-1"></i>متاح اليوم
                                        </span>
                                    @else
                                        <span class="badge bg-danger fs-6 px-3 py-2 shadow-sm">
                                            <i class="fas fa-times-circle me-1"></i>غير متاح
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('eye.availability.update', $doctor) }}" style="display: inline;">
                                        @csrf
                                        <input type="hidden" name="is_available_today" value="{{ $doctor->is_available_today ? '0' : '1' }}">
                                        @if($doctor->is_available_today)
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="تعطيل التوفر اليوم">
                                                <i class="fas fa-toggle-off me-1"></i>تعطيل
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="تفعيل التوفر اليوم">
                                                <i class="fas fa-toggle-on me-1"></i>تفعيل
                                            </button>
                                        @endif
                                    </form>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="{{ route('eye.availability.doctors.edit', $doctor) }}" class="btn btn-sm btn-outline-secondary" title="تعديل بيانات وجدول دوام الطبيب">
                                            <i class="fas fa-cog me-1"></i>تعديل
                                        </a>
                                        <a href="{{ route('eye.reception.create', ['doctor_id' => $doctor->id]) }}" class="btn btn-sm btn-outline-primary shadow-sm" title="حجز موعد عند هذا الطبيب">
                                            <i class="fas fa-calendar-plus me-1"></i>حجز
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-user-md fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h5>لا يوجد أطباء عيون مسجلين في هذا اليوم</h5>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const callButtons = document.querySelectorAll('.call-patient-btn');
    const alertContainer = document.getElementById('callAlertContainer');

    callButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const patientId = this.dataset.id;
            const patientName = this.dataset.name;
            const queueNumber = this.dataset.queue;

            // تشغيل تنبيه صوتي افتراضي للمناداة
            try {
                const utterance = new SpeechSynthesisUtterance(`المريض ${patientName}، يرجى التوجه لعيادة الفحص`);
                utterance.lang = 'ar-SA';
                window.speechSynthesis.speak(utterance);
            } catch (e) {
                console.log('Voice synthesis not supported');
            }

            // إرسال طلب النداء
            fetch(`{{ url('eye/availability/call-patient') }}/${patientId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                const toast = document.createElement('div');
                toast.className = 'alert alert-info alert-dismissible fade show shadow-lg';
                toast.setAttribute('role', 'alert');
                toast.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-bullhorn fa-2x me-3 text-primary"></i>
                        <div>
                            <div class="fw-bold fs-6">تمت مناداة المريض: ${patientName}</div>
                            <small class="text-muted">رقم الدور (#${queueNumber}) تم إرساله لشاشة العيادة الخارجية</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                alertContainer.appendChild(toast);

                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            })
            .catch(err => console.error(err));
        });
    });
});
</script>
@endpush
@endsection
