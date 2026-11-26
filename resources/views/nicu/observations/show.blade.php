@extends('layouts.app')

@section('title', 'معاينة المولود (ساعتان) - ' . $observation->mother_name)

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" id="nicuObservationWorkspace">
    {{-- الشريط العلوي والمؤقت الحي --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-3 p-md-4 bg-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <a href="{{ route('nicu.observations.index') }}" class="btn btn-sm btn-outline-primary rounded-3">
                            <i class="fas fa-arrow-right me-1"></i> العودة لمعاينات الوليد الحية
                        </a>
                        <a href="{{ route('nicu.delivery-radar.index') }}" class="btn btn-sm btn-outline-secondary rounded-3">
                            <i class="fas fa-satellite-dish me-1"></i> رادار العمليات
                        </a>
                        <span class="badge {{ $observation->status_badge_class }} px-3 py-2 rounded-pill">
                            {{ $observation->status_label }}
                        </span>
                        @if($observation->is_billable)
                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning px-3 py-2 rounded-pill">
                                <i class="fas fa-coins me-1"></i>
                                أجور المعاينة: {{ number_format($observation->observation_fee, 0) }} د.ع ({{ $observation->payment_status }})
                            </span>
                        @else
                            <span class="badge bg-light text-muted border px-3 py-2 rounded-pill">
                                <i class="fas fa-hand-holding-heart text-success me-1"></i>
                                معاينة بدون أجور (مشمولة مع الولادة)
                            </span>
                        @endif
                    </div>
                    <h4 class="fw-bold text-dark mb-0 mt-2">
                        <i class="fas fa-baby text-danger me-2"></i>
                        ملف معاينة المولود بعد الولادة — الأم: {{ $observation->mother_name }}
                    </h4>
                    <div class="text-muted small mt-1">
                        تاريخ البدء: <strong>{{ $observation->started_at?->format('Y-m-d h:i A') }}</strong>
                        <span class="mx-2">•</span>
                        المدة المستهدفة: <strong>{{ $observation->target_duration_minutes }} دقيقة</strong>
                        @if($observation->surgery)
                            <span class="mx-2">•</span>
                            مربوط بعملية #{{ $observation->surgery->id }} ({{ $observation->operative_description }})
                        @endif
                    </div>
                </div>

                {{-- مؤقت الساعتين الحي وأزرار الطباعة --}}
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="px-4 py-2 rounded-4 border text-center {{ $observation->isDurationCompleted() ? 'bg-success bg-opacity-10 border-success text-success' : 'bg-warning bg-opacity-10 border-warning text-dark' }}"
                         id="liveObservationTimerBox"
                         data-started-at="{{ $observation->started_at?->toIso8601String() }}"
                         data-target-minutes="{{ $observation->target_duration_minutes }}"
                         data-frozen="{{ in_array($observation->status, ['handed_over', 'admitted_to_nicu']) ? '1' : '0' }}"
                         data-elapsed-minutes="{{ $observation->elapsed_minutes }}">
                        <div class="small fw-bold opacity-75">عداد المعاينة المباشر</div>
                        <div class="fs-4 fw-bold font-monospace" id="liveTimerClock">
                            {{ str_pad(intdiv($observation->elapsed_minutes, 60), 2, '0', STR_PAD_LEFT) }}:{{ str_pad($observation->elapsed_minutes % 60, 2, '0', STR_PAD_LEFT) }}:00
                        </div>
                        <div class="small" id="liveTimerHint">
                            @if($observation->isDurationCompleted())
                                <i class="fas fa-check-circle me-1"></i> اكتملت مدة المعاينة
                            @else
                                متبقي {{ max(0, $observation->target_duration_minutes - $observation->elapsed_minutes) }} دقيقة
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('nicu.observations.print', $observation) }}" target="_blank" class="btn btn-dark btn-lg rounded-3 shadow-sm">
                        <i class="fas fa-print me-1"></i>
                        طباعة استمارة A4
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        {{-- العمود الرئيسي: بيانات الاستمارة السريرية والمواليد والعلاجات --}}
        <div class="col-lg-8">
            <form action="{{ route('nicu.observations.update', $observation) }}" method="POST" id="observationMainForm">
                @csrf
                @method('PUT')

                {{-- 1. جدول المواليد والقرار السريري لكل طفل --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">
                                <i class="fas fa-babies text-primary me-2"></i>
                                المواليد في هذه الولادة (Born Name / Gender / Weight / Disposition)
                            </h5>
                            <small class="text-muted">يمكنك تسجيل طفل واحد أو توائم، وتقرير تسليم كل طفل أو تحويله لحاضنة NICU بشكل مستقل</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-3" id="addBabyRowBtn">
                            <i class="fas fa-plus me-1"></i> إضافة مولود (توأم)
                        </button>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th style="min-width: 190px;">اسم المولود (Born Name)</th>
                                        <th style="min-width: 120px;">الحالة (Live Status)</th>
                                        <th style="min-width: 110px;">الجنس (Gender)</th>
                                        <th style="min-width: 110px;">الوزن (Kg)</th>
                                        <th style="min-width: 170px;">القرار / الحالة</th>
                                        <th style="width: 56px;" class="text-center">حذف</th>
                                    </tr>
                                </thead>
                                <tbody id="babiesTableBody">
                                    @foreach($observation->babies as $idx => $baby)
                                        <tr class="baby-row">
                                            <td>
                                                <input type="hidden" name="babies[{{ $idx }}][id]" value="{{ $baby->id }}">
                                                <input type="text" name="babies[{{ $idx }}][born_name]" class="form-control form-control-sm fw-bold" value="{{ old("babies.$idx.born_name", $baby->born_name) }}" required>
                                            </td>
                                            <td>
                                                <input type="text" name="babies[{{ $idx }}][live_status]" class="form-control form-control-sm" value="{{ old("babies.$idx.live_status", $baby->live_status) }}" placeholder="Live Birth">
                                            </td>
                                            <td>
                                                <select name="babies[{{ $idx }}][gender]" class="form-select form-select-sm">
                                                    <option value="">-- اختر --</option>
                                                    <option value="Male" {{ old("babies.$idx.gender", $baby->gender) === 'Male' ? 'selected' : '' }}>Male (ذكر)</option>
                                                    <option value="Female" {{ old("babies.$idx.gender", $baby->gender) === 'Female' ? 'selected' : '' }}>Female (أنثى)</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0.2" max="10" name="babies[{{ $idx }}][weight_kg]" class="form-control form-control-sm" value="{{ old("babies.$idx.weight_kg", $baby->weight_kg) }}" placeholder="3.0">
                                            </td>
                                            <td>
                                                @if($baby->disposition === 'admitted_to_nicu' && $baby->incubatorReservation)
                                                    <a href="{{ route('incubator-reservations.show', $baby->incubatorReservation) }}" class="badge bg-info text-dark text-decoration-none p-2 d-inline-block">
                                                        <i class="fas fa-hospital-user me-1"></i>
                                                        محول لحاضنة #{{ $baby->incubatorReservation->incubator?->incubator_number ?? $baby->incubatorReservation->id }}
                                                    </a>
                                                @elseif($baby->disposition === 'handed_over')
                                                    <span class="badge bg-primary p-2 d-inline-block">
                                                        <i class="fas fa-check me-1"></i> تم التسليم للأهل
                                                    </span>
                                                @else
                                                    <div class="d-flex flex-column gap-1">
                                                        <span class="badge bg-warning text-dark">تحت المعاينة</span>
                                                        <a href="{{ route('incubator-reservations.create', array_filter(['patient_id' => $observation->patient_id, 'observation_baby_id' => $baby->id])) }}"
                                                           class="btn btn-xs btn-danger rounded-2 py-1 px-2 small fw-bold">
                                                            <i class="fas fa-ambulance me-1"></i> تحويل لحاضنة NICU
                                                        </a>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($baby->disposition === 'observing')
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-baby-btn" title="حذف السطر">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                @else
                                                    <i class="fas fa-lock text-muted" title="مقفل بعد الحسم"></i>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- 2. بيانات استمارة Obstetric Case Sheet --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-notes-medical text-danger me-2"></i>
                            بيانات التقرير والاستمارة السريرية (Obstetric Case Sheet)
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">اسم الأم (Patient Name)</label>
                                <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $observation->mother_name) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">العمر والجنس (Age-Gender)</label>
                                <input type="text" name="mother_age_gender" class="form-control" value="{{ old('mother_age_gender', $observation->mother_age_gender) }}" placeholder="34F">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">تاريخ الميلاد (Birth of Date)</label>
                                <input type="date" name="mother_birth_date" class="form-control" value="{{ old('mother_birth_date', $observation->mother_birth_date?->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">القسم (Department)</label>
                                <input type="text" name="department_name" class="form-control" value="{{ old('department_name', $observation->department_name) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">الطبيبة الطالبة / الجراح (Requesting Dr.)</label>
                                <input type="text" name="requesting_doctor_name" class="form-control" value="{{ old('requesting_doctor_name', $observation->requesting_doctor_name) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">حالة الولادة (Case Of Delivery)</label>
                                <select name="case_of_delivery" class="form-select">
                                    <option value="SINGLE" {{ old('case_of_delivery', $observation->case_of_delivery) === 'SINGLE' ? 'selected' : '' }}>SINGLE</option>
                                    <option value="TWIN" {{ old('case_of_delivery', $observation->case_of_delivery) === 'TWIN' ? 'selected' : '' }}>TWIN</option>
                                    <option value="TRIPLET" {{ old('case_of_delivery', $observation->case_of_delivery) === 'TRIPLET' ? 'selected' : '' }}>TRIPLET</option>
                                    <option value="MULTIPLE" {{ old('case_of_delivery', $observation->case_of_delivery) === 'MULTIPLE' ? 'selected' : '' }}>MULTIPLE</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">وصف العملية (Operative Description)</label>
                                <input type="text" name="operative_description" class="form-control" value="{{ old('operative_description', $observation->operative_description) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">تاريخ الولادة (Date Of Delivery)</label>
                                <input type="date" name="delivery_date" class="form-control" value="{{ old('delivery_date', $observation->delivery_date?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">وقت الولادة (Delivery Time)</label>
                                <input type="text" name="delivery_time" class="form-control" value="{{ old('delivery_time', $observation->delivery_time) }}" placeholder="11:45 am">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">تاريخ المرجع (Ref Date)</label>
                                <input type="date" name="reference_date" class="form-control" value="{{ old('reference_date', $observation->reference_date?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">تاريخ الدخول (Date of Admission)</label>
                                <input type="date" name="admission_date" class="form-control" value="{{ old('admission_date', $observation->admission_date?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">تاريخ الخروج (Date of Discharge)</label>
                                <input type="date" name="discharge_date" class="form-control" value="{{ old('discharge_date', $observation->discharge_date?->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">نوع الرقود (Stay Type)</label>
                                <input type="text" name="stay_type" class="form-control" value="{{ old('stay_type', $observation->stay_type) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">الجراح (Surgeon)</label>
                                <input type="text" name="surgeon_name" class="form-control" value="{{ old('surgeon_name', $observation->surgeon_name) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">التخدير (Anesthesia)</label>
                                <input type="text" name="anesthesia_type" class="form-control" value="{{ old('anesthesia_type', $observation->anesthesia_type) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">طبيب الخدج المسؤول</label>
                                <input type="text" name="nicu_doctor_name" class="form-control" list="nicuDoctorsList" value="{{ old('nicu_doctor_name', $observation->nicu_doctor_name) }}" placeholder="اسم طبيب الخدج بالاستمارة">
                                <datalist id="nicuDoctorsList">
                                    @foreach($nicuDoctors as $doc)
                                        <option value="{{ $doc->name }}"></option>
                                    @endforeach
                                </datalist>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">تشخيص قبل الولادة (Pre Delivery Diagnosis)</label>
                                <textarea name="pre_delivery_diagnosis" rows="2" class="form-control">{{ old('pre_delivery_diagnosis', $observation->pre_delivery_diagnosis) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">تشخيص بعد الولادة (Post Delivery Diagnosis)</label>
                                <textarea name="post_delivery_diagnosis" rows="2" class="form-control">{{ old('post_delivery_diagnosis', $observation->post_delivery_diagnosis) }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">الإجراء الجراحي المنفذ (Procedure Performed)</label>
                                <textarea name="procedure_performed" rows="2" class="form-control">{{ old('procedure_performed', $observation->procedure_performed) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">التقييم السريري للوليد (Assessment)</label>
                                <textarea name="assessment" rows="2" class="form-control">{{ old('assessment', $observation->assessment) }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">التاريخ المرضي (History Of Illness)</label>
                                <textarea name="history_of_illness" rows="2" class="form-control">{{ old('history_of_illness', $observation->history_of_illness) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">ملاحظة الولادة (Delivery Note)</label>
                                <textarea name="delivery_note" rows="2" class="form-control">{{ old('delivery_note', $observation->delivery_note) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. العلاجات أثناء الساعتين (اختياري) --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">
                                <i class="fas fa-prescription-bottle-alt text-success me-2"></i>
                                العلاجات الوقائية أثناء المعاينة (Treatment — اختياري)
                            </h5>
                            <small class="text-muted">يمكن تركه فارغاً أو استخدام الإضافة السريعة بضغطة زر</small>
                        </div>
                        <div class="d-flex gap-1 flex-wrap">
                            @foreach($quickTreatments as $qt)
                                <button type="button"
                                        class="btn btn-xs btn-outline-success rounded-pill px-2 py-1 quick-treatment-btn"
                                        data-generic="{{ $qt['generic_name'] }}"
                                        data-brand="{{ $qt['brand_name'] }}"
                                        data-frequency="{{ $qt['frequency'] }}">
                                    + {{ $qt['brand_name'] }}
                                </button>
                            @endforeach
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-3" id="addTreatmentRowBtn">
                                <i class="fas fa-plus me-1"></i> سطر علاج
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Generic Name</th>
                                        <th>Brand Name</th>
                                        <th>Frequency</th>
                                        <th style="width: 56px;" class="text-center">حذف</th>
                                    </tr>
                                </thead>
                                <tbody id="treatmentsTableBody">
                                    @php
                                        $treatments = old('treatments', $observation->treatments ?? []);
                                    @endphp
                                    @forelse($treatments as $tIdx => $tr)
                                        <tr class="treatment-row">
                                            <td><input type="text" name="treatments[{{ $tIdx }}][generic_name]" class="form-control form-control-sm" value="{{ $tr['generic_name'] ?? '' }}"></td>
                                            <td><input type="text" name="treatments[{{ $tIdx }}][brand_name]" class="form-control form-control-sm" value="{{ $tr['brand_name'] ?? '' }}"></td>
                                            <td><input type="text" name="treatments[{{ $tIdx }}][frequency]" class="form-control form-control-sm" value="{{ $tr['frequency'] ?? '' }}"></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-treatment-btn"><i class="fas fa-times"></i></button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="emptyTreatmentsRow">
                                            <td colspan="4" class="text-center text-muted small py-3">
                                                لا توجد علاجات مسجلة (اختياري). يمكنك الضغط على أزرار الإضافة السريعة بالأعلى عند الحاجة.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-lg px-4 rounded-3 shadow-sm">
                            <i class="fas fa-save me-2"></i>
                            حفظ كافة بيانات الاستمارة والمواليد
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- العمود الجانبي: توثيق التسليم القانوني للأهل + أرشفة الاستمارة الموقعة والمبصمة --}}
        <div class="col-lg-4">
            {{-- بطاقة التسليم للأهل --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-primary text-white py-3 rounded-top-4">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-file-signature me-2"></i>
                        توثيق تسليم الطفل للأهل (محضر الاستلام)
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('nicu.observations.handover', $observation) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold">اسم المستلم (الأب / ولي الأمر) <span class="text-danger">*</span></label>
                            <input type="text" name="recipient_name" class="form-control" value="{{ old('recipient_name', $observation->recipient_name) }}" placeholder="الاسم الثلاثي للمستلم" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">صلة القرابة</label>
                                <input type="text" name="recipient_relation" class="form-control" value="{{ old('recipient_relation', $observation->recipient_relation ?? 'الأب') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">رقم الهوية <span class="text-danger">*</span></label>
                                <input type="text" name="recipient_id_number" class="form-control" value="{{ old('recipient_id_number', $observation->recipient_id_number) }}" placeholder="رقم البطاقة الموحدة" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">رقم الهاتف <span class="text-danger">*</span></label>
                            <input type="text" name="recipient_phone" class="form-control" value="{{ old('recipient_phone', $observation->recipient_phone) }}" placeholder="077..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">عنوان السكن</label>
                            <input type="text" name="recipient_address" class="form-control" value="{{ old('recipient_address', $observation->recipient_address) }}" placeholder="المحافظة / الحي / الشارع">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">صيغة التسليم والحالة العامة</label>
                            <textarea name="handover_text" rows="2" class="form-control" placeholder="تم استلام الطفل بصحة جيدة بعد إتمام المعاينة...">{{ old('handover_text', $observation->handover_text) }}</textarea>
                        </div>

                        @php
                            $observingBabies = $observation->babies->where('disposition', 'observing');
                        @endphp
                        @if($observingBabies->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label small fw-bold">المواليد المشمولون بالتسليم:</label>
                                @foreach($observingBabies as $obBaby)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="baby_ids[]" value="{{ $obBaby->id }}" id="handoverBaby{{ $obBaby->id }}" checked>
                                        <label class="form-check-label small" for="handoverBaby{{ $obBaby->id }}">
                                            {{ $obBaby->born_name }} ({{ $obBaby->gender ?? '-' }})
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <button type="submit" class="btn btn-success w-100 rounded-3 fw-bold">
                            <i class="fas fa-check-double me-1"></i>
                            تأكيد التسليم للأهل
                        </button>

                        @if($observation->handed_over_at)
                            <div class="alert alert-light border mt-3 mb-0 small text-muted">
                                <div><i class="fas fa-clock me-1 text-success"></i> سُلّم بتاريخ: {{ $observation->handed_over_at->format('Y-m-d h:i A') }}</div>
                                <div><i class="fas fa-user-check me-1 text-primary"></i> بواسطة: {{ $observation->handedOverBy?->name ?? '-' }}</div>
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            {{-- بطاقة أرشفة النسخة الموقعة والمبصمة --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-dark text-white py-3 rounded-top-4">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-archive me-2"></i>
                        أرشفة الورقة الموقعة وبصمة قدم الطفل
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('nicu.observations.signed-form.upload', $observation) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold">رفع صورة / PDF للورقة المطبوعة بعد التوقيع وبصمة القدم</label>
                            <input type="file" name="signed_form" class="form-control" accept="image/*,application/pdf" required>
                            <small class="text-muted d-block mt-1">الحد الأقصى 10MB (JPG, PNG, WEBP, PDF)</small>
                        </div>
                        <button type="submit" class="btn btn-outline-dark w-100 rounded-3 fw-bold">
                            <i class="fas fa-cloud-upload-alt me-1"></i>
                            حفظ وأرشفة النسخة الموقعة
                        </button>
                    </form>

                    @if($observation->signed_form_path)
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i> نسخة مؤرشفة متوفرة</span>
                            <small class="text-muted">{{ $observation->signed_form_uploaded_at?->format('Y-m-d H:i') }}</small>
                        </div>
                        <a href="{{ route('nicu.observations.signed-form.download', $observation) }}" target="_blank" class="btn btn-sm btn-primary w-100 rounded-3">
                            <i class="fas fa-eye me-1"></i> فتح ومعاينة الورقة المؤرشفة
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. مؤقت الساعتين الحي
    const timerBox = document.getElementById('liveObservationTimerBox');
    const clockEl = document.getElementById('liveTimerClock');
    const hintEl = document.getElementById('liveTimerHint');

    if (timerBox && clockEl && timerBox.dataset.frozen !== '1') {
        const startedAt = new Date(timerBox.dataset.startedAt);
        const targetMins = parseInt(timerBox.dataset.targetMinutes || '120', 10);

        const updateClock = () => {
            const now = new Date();
            const diffSecs = Math.max(0, Math.floor((now - startedAt) / 1000));
            const hrs = String(Math.floor(diffSecs / 3600)).padStart(2, '0');
            const mins = String(Math.floor((diffSecs % 3600) / 60)).padStart(2, '0');
            const secs = String(diffSecs % 60).padStart(2, '0');
            clockEl.textContent = `${hrs}:${mins}:${secs}`;

            const elapsedMins = Math.floor(diffSecs / 60);
            if (elapsedMins >= targetMins) {
                timerBox.classList.remove('bg-warning', 'border-warning', 'text-dark');
                timerBox.classList.add('bg-success', 'border-success', 'text-success');
                if (hintEl) hintEl.innerHTML = '<i class="fas fa-check-circle me-1"></i> اكتملت مدة المعاينة';
            } else {
                const remaining = targetMins - elapsedMins;
                if (hintEl) hintEl.textContent = `متبقي ${remaining} دقيقة`;
            }
        };

        updateClock();
        setInterval(updateClock, 1000);
    }

    // 2. إضافة وحذف أسطر المواليد (التوائم)
    const babiesBody = document.getElementById('babiesTableBody');
    const addBabyBtn = document.getElementById('addBabyRowBtn');
    let babyIndex = babiesBody ? babiesBody.querySelectorAll('.baby-row').length : 0;

    if (addBabyBtn && babiesBody) {
        addBabyBtn.addEventListener('click', () => {
            const idx = babyIndex++;
            const motherName = @json($observation->mother_name);
            const tr = document.createElement('tr');
            tr.className = 'baby-row';
            tr.innerHTML = `
                <td>
                    <input type="text" name="babies[${idx}][born_name]" class="form-control form-control-sm fw-bold" value="طفل ${idx + 1} / ${motherName}" required>
                </td>
                <td>
                    <input type="text" name="babies[${idx}][live_status]" class="form-control form-control-sm" value="Live Birth">
                </td>
                <td>
                    <select name="babies[${idx}][gender]" class="form-select form-select-sm">
                        <option value="">-- اختر --</option>
                        <option value="Male">Male (ذكر)</option>
                        <option value="Female">Female (أنثى)</option>
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" min="0.2" max="10" name="babies[${idx}][weight_kg]" class="form-control form-control-sm" placeholder="3.0">
                </td>
                <td><span class="badge bg-warning text-dark">جديد (احفظ لتفعيل التحويل)</span></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-baby-btn"><i class="fas fa-trash-alt"></i></button>
                </td>
            `;
            babiesBody.appendChild(tr);
        });

        babiesBody.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-baby-btn');
            if (!btn) return;
            if (babiesBody.querySelectorAll('.baby-row').length <= 1) {
                alert('يجب بقاء مولود واحد على الأقل في ملف المعاينة.');
                return;
            }
            btn.closest('tr').remove();
        });
    }

    // 3. إضافة وحذف أسطر العلاجات الاختيارية
    const treatmentsBody = document.getElementById('treatmentsTableBody');
    const addTreatmentBtn = document.getElementById('addTreatmentRowBtn');
    let treatmentIndex = treatmentsBody ? treatmentsBody.querySelectorAll('.treatment-row').length : 0;

    function addTreatmentRow(generic = '', brand = '', frequency = '') {
        if (!treatmentsBody) return;
        const emptyRow = document.getElementById('emptyTreatmentsRow');
        if (emptyRow) emptyRow.remove();

        const idx = treatmentIndex++;
        const tr = document.createElement('tr');
        tr.className = 'treatment-row';
        tr.innerHTML = `
            <td><input type="text" name="treatments[${idx}][generic_name]" class="form-control form-control-sm" value="${generic.replace(/"/g, '&quot;')}"></td>
            <td><input type="text" name="treatments[${idx}][brand_name]" class="form-control form-control-sm" value="${brand.replace(/"/g, '&quot;')}"></td>
            <td><input type="text" name="treatments[${idx}][frequency]" class="form-control form-control-sm" value="${frequency.replace(/"/g, '&quot;')}"></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-treatment-btn"><i class="fas fa-times"></i></button>
            </td>
        `;
        treatmentsBody.appendChild(tr);
    }

    if (addTreatmentBtn) {
        addTreatmentBtn.addEventListener('click', () => addTreatmentRow());
    }

    document.querySelectorAll('.quick-treatment-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            addTreatmentRow(btn.dataset.generic || '', btn.dataset.brand || '', btn.dataset.frequency || '');
        });
    });

    if (treatmentsBody) {
        treatmentsBody.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-treatment-btn');
            if (btn) btn.closest('tr').remove();
        });
    }
});
</script>
@endsection
