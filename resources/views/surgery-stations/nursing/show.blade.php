@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">محطة التمريض - العملية رقم {{ $surgery->id }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('nursing-station.index') }}" class="btn btn-sm btn-secondary">
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
                            <h5>معلومات المريض</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">رقم الملف:</th>
                                    <td>{{ $surgery->patient->file_number }}</td>
                                </tr>
                                <tr>
                                    <th>الاسم:</th>
                                    <td>{{ $surgery->patient->user->full_name }}</td>
                                </tr>
                                <tr>
                                    <th>العمر:</th>
                                    <td>{{ $surgery->patient->age ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>معلومات العملية</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">اسم العملية:</th>
                                    <td>{{ $surgery->surgery_name }}</td>
                                </tr>
                                <tr>
                                    <th>الطبيب الجراح:</th>
                                    <td>{{ $surgery->doctor?->user?->full_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>الطبيب المقيم:</th>
                                    <td>{{ $surgery->residentStation?->resident?->user?->full_name ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <!-- خطة العلاج المحددة من قبل الجراح وإعطاؤها -->
                    <div class="card border border-secondary-subtle shadow-sm rounded-3 mb-4">
                        <div class="card-header bg-light bg-opacity-50 border-bottom py-2 px-3">
                            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-pills text-primary me-1"></i> العلاجات والأدوية المطلوبة وإعطاؤها</h5>
                        </div>
                        <div class="card-body p-3">
                            @if($surgery->surgeryTreatments->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="py-2">الوصف</th>
                                                <th class="py-2">الجرعة</th>
                                                <th class="py-2">التوقيت</th>
                                                <th class="py-2">المدة</th>
                                                <th class="py-2 text-center">حالة العلاج</th>
                                                <th class="py-2 text-center">عدد الجرعات المعطاة</th>
                                                <th class="py-2 text-center" width="22%">الإجراء</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($surgery->surgeryTreatments as $treatment)
                                            <tr>
                                                <td class="text-dark fw-bold">{{ $treatment->description }}</td>
                                                <td class="text-dark">{{ $treatment->dosage ?? '-' }}</td>
                                                <td class="text-dark">{{ $treatment->timing ?? '-' }}</td>
                                                <td class="text-dark">
                                                    @if($treatment->duration_value)
                                                        {{ $treatment->duration_value }} 
                                                        @php $units=['days'=>'يوم','weeks'=>'أسبوع','months'=>'شهر','hours'=>'ساعة','doses'=>'جرعة']; @endphp
                                                        {{ $units[$treatment->duration_unit] ?? $treatment->duration_unit }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($treatment->status === 'cancelled')
                                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2.5 py-1.5"><i class="fas fa-times-circle me-1"></i>تم إيقاف العلاج</span>
                                                    @else
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2.5 py-1.5"><i class="fas fa-play-circle me-1"></i>نشط ومستمر</span>
                                                    @endif
                                                </td>
                                                <td class="text-center fw-bold text-dark">
                                                    {{ $treatment->administrations ? count($treatment->administrations) : 0 }} جرعات
                                                </td>
                                                <td class="text-center">
                                                    @if($treatment->status === 'planned')
                                                        <button type="button" class="btn btn-sm btn-success px-2.5 py-1 me-1" onclick="openAdministerModal({{ $treatment->id }}, '{{ addslashes($treatment->description) }}', 'administered')">
                                                            <i class="fas fa-plus-circle"></i> تسجيل إعطاء جرعة
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger px-2.5 py-1" onclick="openAdministerModal({{ $treatment->id }}, '{{ addslashes($treatment->description) }}', 'cancelled')">
                                                            <i class="fas fa-ban"></i> إيقاف العلاج
                                                        </button>
                                                    @else
                                                        <span class="text-muted small">
                                                            @if($treatment->admin_notes)
                                                                <span class="d-inline-block text-truncate" style="max-width: 150px;" title="{{ $treatment->admin_notes }}">
                                                                    تم الإيقاف: {{ $treatment->admin_notes }}
                                                                </span>
                                                            @else
                                                                تم إيقاف العلاج
                                                            @endif
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @if($treatment->administrations && count($treatment->administrations) > 0)
                                            <tr class="table-light">
                                                <td colspan="7" class="p-2 bg-light">
                                                    <div class="ps-4">
                                                        <span class="fw-bold text-secondary small d-block mb-2"><i class="fas fa-history me-1 text-info"></i> سجل إعطاء الجرعات الدورية:</span>
                                                        <div class="d-flex flex-wrap gap-2">
                                                            @foreach($treatment->administrations as $index => $admin)
                                                                <div class="badge bg-white text-dark border border-secondary-subtle p-2 rounded-2 text-start font-monospace shadow-sm" style="font-size: 0.85rem; font-weight: normal; line-height: 1.4;">
                                                                    <span class="badge bg-success me-1">الجرعة {{ $index + 1 }}</span>
                                                                    <span class="fw-bold text-dark">{{ $admin['administered_by_name'] }}</span>
                                                                    <span class="text-muted mx-1">|</span>
                                                                    <span class="text-secondary">{{ $admin['administered_at'] }}</span>
                                                                    @if(!empty($admin['notes']))
                                                                        <div class="mt-1 text-primary small fw-semibold"><i class="fas fa-comment-alt me-1 text-muted"></i>ملاحظة: {{ $admin['notes'] }}</div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @elseif($surgery->surgeonStation?->treatment_plan)
                                <div class="table-responsive">
                                    <table class="table table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <th class="w-25 text-muted">الخطة المحددة من قبل الجراح</th>
                                                <td class="text-dark">{!! nl2br(e($surgery->surgeonStation->treatment_plan)) !!}</td>
                                            </tr>
                                            @if($surgery->surgeonStation?->notes)
                                            <tr>
                                                <th class="text-muted">ملاحظات الجراح</th>
                                                <td class="text-dark">{!! nl2br(e($surgery->surgeonStation->notes)) !!}</td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-warning border-warning-subtle bg-warning bg-opacity-10 mb-0">
                                    <i class="fas fa-exclamation-triangle me-1"></i> لم يحدد الجراح خطة علاج بعد.
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <!-- نموذج محطة التمريض -->
                    <form action="{{ route('nursing-station.update', $surgery) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="form-group">
                            <label>الممرضة المسؤولة</label>
                            <select name="nurse_id" class="form-control">
                                <option value="">-- اختر الممرضة --</option>
                                @foreach($nurses as $nurse)
                                    <option value="{{ $nurse->id }}" 
                                        {{ $surgery->nursingStation?->nurse_id == $nurse->id ? 'selected' : '' }}>
                                        {{ $nurse->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>ملاحظات التمريض</label>
                            <textarea name="nursing_notes" class="form-control" rows="4">{{ $surgery->nursingStation?->nursing_notes ?? '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>العلامات الحيوية</label>
                            <textarea name="vital_signs" class="form-control" rows="3" 
                                placeholder="مثال: ضغط الدم، النبض، الحرارة، التنفس...">{{ $surgery->nursingStation?->vital_signs ?? '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>ملاحظات الخروج</label>
                            <textarea name="discharge_notes" class="form-control" rows="4">{{ $surgery->nursingStation?->discharge_notes ?? '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ البيانات
                            </button>

                            @if($surgery->nursingStation && $surgery->nursingStation->status !== 'completed')
                                <button type="button" class="btn btn-success" 
                                    onclick="if(confirm('هل أنت متأكد من إتمام هذه المحطة؟ سيتم إنهاء العملية.')) {
                                        event.preventDefault();
                                        document.getElementById('complete-form').submit();
                                    }">
                                    <i class="fas fa-check"></i> إتمام المحطة وإنهاء العملية
                                </button>
                            @endif
                        </div>
                    </form>

                    <!-- نموذج الإتمام -->
                    <form id="complete-form" action="{{ route('nursing-station.complete', $surgery) }}" method="POST" style="display: none;">
                        @csrf
                    </form>

                    <!-- معلومات المحطة -->
                    @if($surgery->nursingStation)
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h5>معلومات الحالة</h5>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="20%">حالة المحطة:</th>
                                    <td>
                                        @if($surgery->nursingStation->status === 'pending')
                                            <span class="badge badge-warning">معلقة</span>
                                        @elseif($surgery->nursingStation->status === 'in_progress')
                                            <span class="badge badge-info">جارية</span>
                                        @else
                                            <span class="badge badge-success">مكتملة</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($surgery->nursingStation->started_at)
                                <tr>
                                    <th>تاريخ البدء:</th>
                                    <td>{{ $surgery->nursingStation->started_at->format('Y-m-d h:i A') }}</td>
                                </tr>
                                @endif
                                @if($surgery->nursingStation->completed_at)
                                <tr>
                                    <th>تاريخ الإتمام:</th>
                                    <td>{{ $surgery->nursingStation->completed_at->format('Y-m-d h:i A') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نافذة تسجيل إعطاء/إلغاء العلاج -->
<div class="modal fade" id="administerTreatmentModal" tabindex="-1" aria-labelledby="administerTreatmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="administerTreatmentForm" action="" method="POST">
                @csrf
                <input type="hidden" name="status" id="modalTreatmentStatus">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="administerTreatmentModalLabel">تسجيل جرعة علاجية</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3 fw-semibold">العلاج: <span id="modalTreatmentDescription" class="text-primary fw-bold"></span></p>
                    
                    <div class="form-group mb-3">
                        <label id="modalNotesLabel" for="modalAdminNotes" class="form-label fw-bold">ملاحظات الإعطاء (اختياري)</label>
                        <textarea class="form-control" name="admin_notes" id="modalAdminNotes" rows="3" placeholder="أدخل أي ملاحظات حول إعطاء الجرعة أو سبب إيقاف العلاج..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="submit" class="btn id-submit-btn">تأكيد</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openAdministerModal(treatmentId, description, status) {
        const modal = new bootstrap.Modal(document.getElementById('administerTreatmentModal'));
        
        // ضبط عنوان النافذة وزر الإرسال بناءً على الإجراء
        const label = document.getElementById('administerTreatmentModalLabel');
        const notesLabel = document.getElementById('modalNotesLabel');
        const submitBtn = document.querySelector('#administerTreatmentForm .id-submit-btn');
        const notesTextarea = document.getElementById('modalAdminNotes');
        
        if (status === 'administered') {
            label.textContent = 'تسجيل إعطاء جرعة علاجية';
            notesLabel.textContent = 'ملاحظات إعطاء الجرعة (اختياري)';
            notesTextarea.placeholder = 'أدخل ملاحظاتك حول إعطاء هذه الجرعة (مثال: تم إعطاؤها وريدياً، تناولها مع الطعام...)';
            submitBtn.textContent = 'تأكيد إعطاء الجرعة';
            submitBtn.className = 'btn btn-success';
        } else {
            label.textContent = 'إيقاف العلاج بالكامل';
            notesLabel.textContent = 'سبب إيقاف العلاج (مطلوب)';
            notesTextarea.placeholder = 'أدخل سبب إيقاف هذا العلاج للمريض (مطلوب)...';
            notesTextarea.required = true;
            submitBtn.textContent = 'تأكيد إيقاف العلاج';
            submitBtn.className = 'btn btn-danger';
        }
        
        // تعبئة البيانات
        document.getElementById('modalTreatmentDescription').textContent = description;
        document.getElementById('modalTreatmentStatus').value = status;
        document.getElementById('modalAdminNotes').value = '';
        
        // ضبط رابط النموذج
        const form = document.getElementById('administerTreatmentForm');
        form.action = `/surgery-stations/treatments/${treatmentId}/administer`;
        
        modal.show();
    }
</script>
@endsection
