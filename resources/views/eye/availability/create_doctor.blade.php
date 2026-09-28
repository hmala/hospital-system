@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" style="background-color: #f8fafc; min-height: 100vh;">
    <div class="row justify-content-center">
        <div class="col-xl-9 col-lg-10">

            <!-- Breadcrumb & Back -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-primary mb-1">
                        <i class="fas fa-user-md me-2"></i>إضافة طبيب واستشاري عيون جديد
                    </h3>
                    <p class="text-muted small mb-0">تسجيل طبيب عيون وتحديد اختصاصه الدقيق وأيام وساعات دوامه وأجور الكشفية بمركز العيون</p>
                </div>
                <a href="{{ route('eye.availability.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i> العودة لدليل توفر الأطباء
                </a>
            </div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('eye.availability.doctors.store') }}" method="POST" id="createEyeDoctorForm">
                @csrf

                <!-- بطاقة 1: البيانات الأساسية وحساب النظام -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-id-card text-primary me-2"></i>بيانات الطبيب وحساب النظام
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">اسم الطبيب الكامل <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted">د.</span>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="مثال: فراس حميد العبيدي" required>
                                </div>
                                @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">البريد الإلكتروني (لتسجيل الدخول) <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="doctor@hospital.iq" required>
                                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">رقم الهاتف <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="07701234567" required>
                                @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">كلمة المرور (اختياري)</label>
                                <input type="password" name="password" class="form-control" placeholder="افتراضياً: password">
                                <div class="form-text small text-muted">اتركها فارغة لاستخدام كلمة المرور الافتراضية (password)</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">الدرجة / الرتبة المهنية <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="consultant" {{ old('type') == 'consultant' ? 'selected' : '' }}>استشاري طب وجراحة عيون (Consultant)</option>
                                    <option value="surgeon" {{ old('type') == 'surgeon' ? 'selected' : '' }}>أخصائي / جراح عيون (Specialist Surgeon)</option>
                                    <option value="resident" {{ old('type') == 'resident' ? 'selected' : '' }}>مقيم أقدم عيون (Senior Resident)</option>
                                    <option value="optometrist" {{ old('type') == 'optometrist' ? 'selected' : '' }}>أخصائي بصريات وقياس نظر (Optometrist)</option>
                                </select>
                                @error('type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">القسم والمركز الطبي</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success-subtle text-success"><i class="fas fa-check-circle"></i></span>
                                    <input type="text" class="form-control bg-light text-success fw-bold" value="مركز وجراحة العيون التخصصي (Ophthalmology Center)" readonly>
                                </div>
                                <div class="form-text small text-muted">مثبت ومخصص تلقائياً لقسم العيون</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- بطاقة 2: التخصص العيني الدقيق -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-eye text-primary me-2"></i>التخصص الدقيق في طب وجراحة العيون
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">اختر التخصص الدقيق <span class="text-danger">*</span></label>
                                <select name="specialization" id="specializationSelect" class="form-select @error('specialization') is-invalid @enderror" required>
                                    <option value="">-- اختر من تخصصات العيون المعتمدة --</option>
                                    @foreach($eyeSpecializations as $spec)
                                        <option value="{{ $spec }}" {{ old('specialization') == $spec ? 'selected' : '' }}>{{ $spec }}</option>
                                    @endforeach
                                    <option value="other" {{ old('specialization') == 'other' ? 'selected' : '' }}>-- تخصص دقيق آخر (كتابة يدوية) --</option>
                                </select>
                                @error('specialization')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12 d-none" id="customSpecContainer">
                                <label class="form-label fw-bold text-primary">اكتب التخصص الدقيق يدوياً <span class="text-danger">*</span></label>
                                <input type="text" name="custom_specialization" id="customSpecInput" class="form-control" value="{{ old('custom_specialization') }}" placeholder="مثال: تصحيح البصر والقرنية المخروطية...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- بطاقة 3: أجور الكشفية والتأمين الصحي -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-coins text-warning me-2"></i>أجور كشفية العيون وتغطية التأمين
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">أجر الكشف كاش (د.ع) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="consultation_fee" id="feeInput" class="form-control fw-bold fs-6 @error('consultation_fee') is-invalid @enderror" value="{{ old('consultation_fee', 25000) }}" min="0" step="500" required>
                                    <span class="input-group-text bg-light">د.ع</span>
                                </div>
                                <div class="form-text small text-muted">سعر الكشفية العادية للمراجع النقدي</div>
                                @error('consultation_fee')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold mb-0 text-success">
                                            <i class="fas fa-shield-alt me-1"></i>الضمان الصحي
                                        </label>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" name="is_hi_active" value="1" id="hiToggle" {{ old('is_hi_active', '1') ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="hi_price" id="hiPriceInput" class="form-control" value="{{ old('hi_price', 25000) }}" min="0" step="500">
                                        <span class="input-group-text">د.ع</span>
                                    </div>
                                    <small class="text-muted d-block mt-1">نسبة التحمل 10% والباقي يغطيه الضمان</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold mb-0 text-primary">
                                            <i class="fas fa-user-shield me-1"></i>قوى الأمن الداخلي
                                        </label>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" name="is_moi_active" value="1" id="moiToggle" {{ old('is_moi_active', '1') ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="moi_price" id="moiPriceInput" class="form-control" value="{{ old('moi_price', 20000) }}" min="0" step="500">
                                        <span class="input-group-text">د.ع</span>
                                    </div>
                                    <small class="text-muted d-block mt-1">تسعيرة منتسبي وزارة الداخلية</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- بطاقة 4: جدول الدوام وساعات العيادة والتواجد اليومي -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-calendar-alt text-primary me-2"></i>جدول الدوام وساعات العيادة
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <!-- أيام الدوام -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold mb-0">أيام الدوام المعتمدة في مركز العيون <span class="text-danger">*</span></label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" id="btnSelectAllDays">تحديد كل الأيام</button>
                                    <button type="button" class="btn btn-outline-secondary" id="btnSelectWeekdays">أيام الأسبوع (أحد - خميس)</button>
                                    <button type="button" class="btn btn-outline-danger" id="btnClearDays">مسح</button>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 p-3 bg-light rounded-3 border">
                                @foreach($weekDays as $day)
                                    <div class="form-check form-check-inline m-0 me-3">
                                        <input class="form-check-input day-checkbox" type="checkbox" name="working_days[]" value="{{ $day }}" id="day_{{ $loop->index }}" {{ in_array($day, old('working_days', ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس'])) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="day_{{ $loop->index }}">{{ $day }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('working_days')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <!-- ساعات الدوام والتواجد -->
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">وقت بدء دوام العيادة <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control" value="{{ old('start_time', '08:30') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">وقت انتهاء دوام العيادة <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control" value="{{ old('end_time', '14:30') }}" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-center">
                                <div class="form-check form-switch p-3 bg-success-subtle rounded-3 border border-success-subtle w-100 mt-md-4">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="is_available_today" value="1" id="availableTodaySwitch" {{ old('is_available_today', '1') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold text-success" for="availableTodaySwitch">
                                        تفعيل التوفر اليومي فوراً 🟢
                                    </label>
                                    <div class="small text-muted">يظهر الطبيب فوراً كمتاح لاستقبال المراجعين اليوم</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- أزرار الحفظ -->
                <div class="d-flex justify-content-end gap-3 mb-5">
                    <a href="{{ route('eye.availability.index') }}" class="btn btn-light px-4 py-2 border">إلغاء</a>
                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm fw-bold">
                        <i class="fas fa-check-circle me-2"></i>حفظ واعتماد طبيب العيون
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // إظهار حقل التخصص المخصص عند اختيار "تخصص آخر"
    const specSelect = document.getElementById('specializationSelect');
    const customContainer = document.getElementById('customSpecContainer');
    const customInput = document.getElementById('customSpecInput');

    function checkSpec() {
        if (specSelect.value === 'other') {
            customContainer.classList.remove('d-none');
            customInput.required = true;
            customInput.focus();
        } else {
            customContainer.classList.add('d-none');
            customInput.required = false;
        }
    }

    specSelect.addEventListener('change', checkSpec);
    checkSpec();

    // التحكم السريع بأيام الدوام
    const dayCheckboxes = document.querySelectorAll('.day-checkbox');
    const btnAll = document.getElementById('btnSelectAllDays');
    const btnWeekdays = document.getElementById('btnSelectWeekdays');
    const btnClear = document.getElementById('btnClearDays');

    btnAll.addEventListener('click', () => {
        dayCheckboxes.forEach(cb => cb.checked = true);
    });

    btnWeekdays.addEventListener('click', () => {
        const weekdaysList = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس'];
        dayCheckboxes.forEach(cb => {
            cb.checked = weekdaysList.includes(cb.value);
        });
    });

    btnClear.addEventListener('click', () => {
        dayCheckboxes.forEach(cb => cb.checked = false);
    });

    // تحديث تسعيرة الضمان تلقائياً بناءً على الكشف كاش إن لم يتم تعديلها
    const feeInput = document.getElementById('feeInput');
    const hiInput = document.getElementById('hiPriceInput');
    feeInput.addEventListener('input', function() {
        if (hiInput && !hiInput.dataset.manual) {
            hiInput.value = this.value;
        }
    });
    hiInput.addEventListener('input', function() {
        this.dataset.manual = 'true';
    });
});
</script>
@endpush
@endsection
