@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-x-ray me-2"></i>
                    تفاصيل طلب الإشعة
                </h2>
                <div>
                    @if($radiology->status === 'completed' && $radiology->result)
                        <a href="{{ route('radiology.print', $radiology) }}" target="_blank" class="btn btn-success me-2">
                            <i class="fas fa-print me-1"></i>
                            طباعة النتيجة
                        </a>
                    @endif
                    @if(Auth::user()->isAdmin() || Auth::user()->isReceptionist())
                        @if($radiology->status === 'pending')
                        <a href="{{ route('radiology.edit', $radiology) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit me-1"></i>
                            تعديل
                        </a>
                        @endif
                    @endif
                    <a href="{{ route('radiology.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة للقائمة
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- معلومات الطلب -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        معلومات الطلب
                    </h5>
                </div>
                <div class="card-body">
                    <!-- نوع الأشعة - في الأعلى -->
                    <div class="alert alert-primary mb-3">
                        <h6 class="mb-2"><i class="fas fa-x-ray me-2"></i><strong>نوع الإشعة المطلوبة:</strong></h6>
                        @if($radiology->radiologyType)
                            <h4 class="mb-1">{{ $radiology->radiologyType->name }}</h4>
                            <p class="mb-0"><small><strong>الرمز:</strong> {{ $radiology->radiologyType->code }}</small></p>
                            @if(isset($radiology->radiologyType->description) && $radiology->radiologyType->description)
                                <p class="mb-0 mt-2"><small>{{ $radiology->radiologyType->description }}</small></p>
                            @endif
                        @else
                            <p class="text-danger mb-0">⚠️ لم يتم تحديد نوع الأشعة</p>
                        @endif
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>رقم الطلب:</strong></p>
                            <p class="text-muted fs-5">#{{ $radiology->id }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>الحالة:</strong></p>
                            <span class="badge bg-{{ $radiology->status_color }} fs-5">
                                {{ $radiology->status_text }}
                            </span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>الأولوية:</strong></p>
                            <span class="badge bg-{{ $radiology->priority_color }} fs-6">
                                {{ $radiology->priority_text }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            @if($radiology->total_cost)
                            <p class="mb-2"><strong>التكلفة:</strong></p>
                            <p class="text-success fs-5 mb-0">{{ number_format($radiology->total_cost, 2) }} ج.م</p>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>تاريخ الطلب:</strong></p>
                            <p class="text-muted">{{ $radiology->requested_date->format('Y-m-d H:i') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>الموعد المجدول:</strong></p>
                            <p class="text-muted">
                                {{ $radiology->scheduled_date ? $radiology->scheduled_date->format('Y-m-d H:i') : 'لم يحدد بعد' }}
                            </p>
                        </div>
                    </div>

                    @if($radiology->performed_at)
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>تاريخ التنفيذ:</strong></p>
                            <p class="text-muted">{{ $radiology->performed_at->format('Y-m-d H:i') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>المنفذ:</strong></p>
                            <p class="text-muted">{{ $radiology->performer ? $radiology->performer->name : '-' }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- معلومات المريض والطبيب -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-injured me-2"></i>
                        معلومات المريض
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>الاسم:</strong></p>
                    <p class="text-muted fs-5">{{ $radiology->patient->user->name }}</p>

                    <p class="mb-2 mt-3"><strong>رقم الهاتف:</strong></p>
                    <p class="text-muted">{{ $radiology->patient->user->phone ?? 'لا يوجد' }}</p>

                    <p class="mb-2 mt-3"><strong>البريد الإلكتروني:</strong></p>
                    <p class="text-muted">{{ $radiology->patient->user->email ?? 'لا يوجد' }}</p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-md me-2"></i>
                        الطبيب المطلب
                    </h5>
                </div>
                <div class="card-body">
                    @if($radiology->doctor)
                        <p class="mb-2"><strong>الاسم:</strong></p>
                        <p class="text-muted fs-5">د. {{ $radiology->doctor->user->name }}</p>

                        @if($radiology->doctor->department)
                        <p class="mb-2 mt-3"><strong>القسم:</strong></p>
                        <p class="text-muted">{{ $radiology->doctor->department->name }}</p>
                        @endif
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>طلب من الاستعلامات</strong>
                            <p class="mb-0 mt-2">هذا الطلب تم إنشاؤه من خدمة الاستعلامات ولا يرتبط بطبيب محدد.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- التفاصيل الطبية -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-notes-medical me-2"></i>
                        التفاصيل الطبية
                    </h5>
                </div>
                <div class="card-body">
                    @if($radiology->clinical_indication)
                    <div class="mb-3">
                        <p class="mb-2"><strong>المؤشرات السريرية:</strong></p>
                        <p class="text-muted">{{ $radiology->clinical_indication }}</p>
                    </div>
                    @endif

                    @if($radiology->specific_instructions)
                    <div class="mb-3">
                        <p class="mb-2"><strong>تعليمات خاصة:</strong></p>
                        <p class="text-muted">{{ $radiology->specific_instructions }}</p>
                    </div>
                    @endif

                    @if($radiology->notes)
                    <div class="mb-3">
                        <p class="mb-2"><strong>ملاحظات:</strong></p>
                        <p class="text-muted">{{ $radiology->notes }}</p>
                    </div>
                    @endif

                    @if(!$radiology->clinical_indication && !$radiology->specific_instructions && !$radiology->notes)
                    <p class="text-center text-muted">لا توجد تفاصيل طبية إضافية</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- النتائج -->
    @if($radiology->result)
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-medical me-2"></i>
                        نتيجة الإشعة
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>التشخيص:</strong></p>
                            <p class="text-muted">{{ $radiology->result->diagnosis ?? 'لا يوجد' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>الطبيب المختص:</strong></p>
                            <p class="text-muted">{{ $radiology->result->radiologist ? $radiology->result->radiologist->name : '-' }}</p>
                        </div>
                    </div>

                    @if($radiology->result->findings)
                    <div class="mb-3">
                        <p class="mb-2"><strong>النتائج:</strong></p>
                        <p class="text-muted">{{ $radiology->result->findings }}</p>
                    </div>
                    @endif

                    @if($radiology->result->impression)
                    <div class="mb-3">
                        <p class="mb-2"><strong>الانطباع:</strong></p>
                        <p class="text-muted">{{ $radiology->result->impression }}</p>
                    </div>
                    @endif

                    @if($radiology->result->recommendations)
                    <div class="mb-3">
                        <p class="mb-2"><strong>التوصيات:</strong></p>
                        <p class="text-muted">{{ $radiology->result->recommendations }}</p>
                    </div>
                    @endif

                    @if($radiology->result->result_file)
                    <div class="mb-3">
                        <p class="mb-2"><strong>ملف النتيجة:</strong></p>
                        <a href="{{ asset('storage/' . $radiology->result->result_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download me-1"></i>
                            تحميل الملف
                        </a>
                    </div>
                    @endif

                    <div class="mt-3">
                        <p class="mb-2"><strong>تاريخ النتيجة:</strong></p>
                        <p class="text-muted">{{ $radiology->result->created_at->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- الإجراءات -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        الإجراءات
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if(Auth::user()->hasRole('radiology_staff') && $radiology->canBePerformed())
                        <div class="col-md-3 mb-2">
                            <form action="{{ route('radiology.start', $radiology) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-play me-1"></i>
                                    بدء الإجراء
                                </button>
                            </form>
                        </div>
                        @endif

                        @if(Auth::user()->hasRole('radiology_staff') && in_array($radiology->status, ['scheduled', 'in_progress', 'completed']))
                        <div class="col-md-3 mb-2">
                            <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#resultsModal">
                                <i class="fas fa-file-medical me-1"></i>
                                {{ $radiology->result ? 'تعديل النتائج' : 'إضافة النتائج' }}
                            </button>
                        </div>
                        @endif

                        @if($radiology->status === 'in_progress' && Auth::user()->hasRole('radiology_staff') && !$radiology->result)
                        <div class="col-md-3 mb-2">
                            <form action="{{ route('radiology.complete', $radiology) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-info w-100">
                                    <i class="fas fa-check me-1"></i>
                                    إكمال بدون نتائج
                                </button>
                            </form>
                        </div>
                        @endif

                        @if($radiology->status === 'pending' && (Auth::user()->isAdmin() || Auth::user()->isReceptionist()))
                        <div class="col-md-3 mb-2">
                            <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                                <i class="fas fa-calendar-alt me-1"></i>
                                جدولة الموعد
                            </button>
                        </div>
                        @endif

                        @if($radiology->status === 'pending' && (Auth::user()->isAdmin() || Auth::user()->isReceptionist()))
                        <div class="col-md-3 mb-2">
                            <form action="{{ route('radiology.destroy', $radiology) }}" method="POST"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا الطلب؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="fas fa-trash me-1"></i>
                                    حذف الطلب
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal لجدولة الموعد -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalLabel">جدولة موعد الإشعة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('radiology.schedule', $radiology) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="scheduled_date" class="form-label">تاريخ ووقت الموعد</label>
                        <input type="datetime-local" class="form-control" id="scheduled_date" name="scheduled_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ الموعد</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal لإضافة/تعديل النتائج -->
<div class="modal fade" id="resultsModal" tabindex="-1" aria-labelledby="resultsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="resultsModalLabel">
                    <i class="fas fa-file-medical me-2"></i>
                    {{ $radiology->result ? 'تعديل نتائج الأشعة' : 'إضافة نتائج الأشعة' }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('radiology.saveResults', $radiology) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="findings" class="form-label"><strong>النتائج (Findings):</strong></label>
                        <textarea class="form-control" id="findings" name="findings" rows="4" required>{{ $radiology->result->findings ?? '' }}</textarea>
                        <small class="text-muted">وصف تفصيلي للنتائج المرئية في الأشعة</small>
                    </div>

                    <div class="mb-3">
                        <label for="impression" class="form-label"><strong>الانطباع (Impression):</strong></label>
                        <textarea class="form-control" id="impression" name="impression" rows="3" required>{{ $radiology->result->impression ?? '' }}</textarea>
                        <small class="text-muted">التشخيص أو الانطباع الطبي</small>
                    </div>

                    <div class="mb-3">
                        <label for="recommendations" class="form-label">التوصيات (Recommendations):</label>
                        <textarea class="form-control" id="recommendations" name="recommendations" rows="3">{{ $radiology->result->recommendations ?? '' }}</textarea>
                        <small class="text-muted">التوصيات للمتابعة أو الفحوصات الإضافية</small>
                    </div>

                    <div class="mb-3">
                        <label for="images" class="form-label"><strong>صور الأشعة:</strong></label>
                        <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*,.pdf,.dcm">
                        <small class="text-muted">يمكنك رفع صور متعددة (JPEG, PNG, PDF, DICOM)</small>
                        
                        @if($radiology->result && $radiology->result->images)
                        <div class="mt-2">
                            <p class="mb-1"><strong>الصور المرفوعة:</strong></p>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($radiology->result->images as $index => $image)
                                <div class="position-relative">
                                    <img src="{{ asset('storage/' . $image) }}" alt="صورة {{ $index + 1 }}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                    <a href="{{ asset('storage/' . $image) }}" target="_blank" class="btn btn-sm btn-primary position-absolute top-0 end-0">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_preliminary" name="is_preliminary" value="1" {{ ($radiology->result && $radiology->result->is_preliminary) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_preliminary">
                                تقرير أولي (Preliminary Report)
                            </label>
                            <br>
                            <small class="text-muted">إذا كان هذا تقريراً أولياً، يمكن تعديله لاحقاً</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>
                        حفظ النتائج
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
