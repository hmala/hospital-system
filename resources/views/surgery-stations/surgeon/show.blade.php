@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">محطة الجراح - العملية رقم {{ $surgery->id }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('surgeon-station.index') }}" class="btn btn-sm btn-secondary">
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
                                    <th>التاريخ المحدد:</th>
                                    <td>{{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <!-- نموذج محطة الجراح -->
                    <form action="{{ route('surgeon-station.update', $surgery) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>تعيين طبيب مقيم</label>
                                    <select name="resident_assigned_id" class="form-control">
                                        <option value="">-- اختر طبيب مقيم --</option>
                                        @foreach($residents as $resident)
                                            <option value="{{ $resident->id }}" 
                                                {{ $surgery->surgeonStation?->resident_assigned_id == $resident->id ? 'selected' : '' }}>
                                                {{ $resident->user->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>بروتوكول المراقبة</label>
                                    <select name="monitoring_protocol" class="form-control">
                                        <option value="standard" {{ ($surgery->surgeonStation?->monitoring_protocol ?? 'standard') == 'standard' ? 'selected' : '' }}>قياسي (علامات حيوية فقط)</option>
                                        <option value="fluid_monitoring" {{ ($surgery->surgeonStation?->monitoring_protocol ?? '') == 'fluid_monitoring' ? 'selected' : '' }}>مراقبة السوائل (حيوية + سوائل)</option>
                                        <option value="intensive" {{ ($surgery->surgeonStation?->monitoring_protocol ?? '') == 'intensive' ? 'selected' : '' }}>مكثف (حيوية + سوائل + متابعة دقيقة)</option>
                                    </select>
                                    <small class="text-muted">البروتوكول الموصى به من الجراح بناءً على نوع العملية</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ملاحظات الجراح</label>
                            <textarea name="notes" class="form-control" rows="4">{{ $surgery->surgeonStation?->notes ?? '' }}</textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label>خطة العلاج العامة (نصية)</label>
                            <textarea name="treatment_plan" class="form-control" rows="3" placeholder="أدخل خطة العلاج العامة أو أي ملاحظات إضافية...">{{ $surgery->surgeonStation?->treatment_plan ?? '' }}</textarea>
                        </div>

                        <!-- العلاجات والأدوية المهيكلة -->
                        <div class="card border border-secondary-subtle shadow-sm rounded-3 mb-4">
                            <div class="card-header bg-light bg-opacity-50 border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-pills text-primary me-1"></i> العلاجات والأدوية المطلوبة</h6>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="addSurgeryTreatment()">
                                    <i class="fas fa-plus me-1"></i> إضافة علاج
                                </button>
                            </div>
                            <div class="card-body p-3">
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0" id="surgeryTreatmentsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="5%" class="text-center">#</th>
                                                <th width="35%">وصف العلاج/الدواء</th>
                                                <th width="20%">الجرعة</th>
                                                <th width="20%">التوقيت/التكرار</th>
                                                <th width="15%">المدة</th>
                                                <th width="5%" class="text-center">حذف</th>
                                            </tr>
                                        </thead>
                                        <tbody id="surgeryTreatmentsContainer">
                                            @php $savedSurgeryTreatments = $surgery->surgeryTreatments ?? collect(); @endphp
                                            @foreach($savedSurgeryTreatments as $index => $treatment)
                                            <tr class="treatment-item">
                                                <td class="text-center row-number">{{ $index + 1 }}</td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][description]"
                                                           value="{{ $treatment->description ?? '' }}" placeholder="اسم الدواء أو وصف العلاج" required>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][dosage]"
                                                           value="{{ $treatment->dosage ?? '' }}" placeholder="مثال: 500mg, 2ml">
                                                </td>
                                                <td>
                                                    <textarea class="form-control form-control-sm timing-textarea" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][timing]" rows="1"
                                                              placeholder="مثال: كل 6 ساعات">{{ $treatment->timing ?? '' }}</textarea>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <input type="number" class="form-control form-control-sm w-50" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][duration_value]"
                                                               value="{{ $treatment->duration_value ?? '' }}" placeholder="العدد" min="1">
                                                        <select class="form-select form-select-sm w-50" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][duration_unit]">
                                                            <option value="days" {{ ($treatment->duration_unit ?? 'days') == 'days' ? 'selected' : '' }}>يوم</option>
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
                                            <tr id="emptySurgeryTreatmentsRow">
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    <i class="fas fa-table fa-2x mb-2"></i>
                                                    <p class="mb-1">لا توجد علاجات مسجلة لهذه العملية</p>
                                                    <small>اضغط على "إضافة علاج" للبدء</small>
                                                </td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ البيانات
                            </button>

                            @if($surgery->surgeonStation && $surgery->surgeonStation->status !== 'completed')
                                <button type="button" class="btn btn-success" 
                                    onclick="if(confirm('هل أنت متأكد من إتمام هذه المحطة؟')) {
                                        event.preventDefault();
                                        document.getElementById('complete-form').submit();
                                    }">
                                    <i class="fas fa-check"></i> إتمام المحطة
                                </button>
                            @endif
                        </div>
                    </form>

                    <!-- نموذج الإتمام -->
                    <form id="complete-form" action="{{ route('surgeon-station.complete', $surgery) }}" method="POST" style="display: none;">
                        @csrf
                    </form>

                    <!-- متابعة المقيم للجراح -->
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">متابعات الطبيب المقيم</h5>
                        </div>
                        <div class="card-body">
                            @php
                                $surgeonFollowUps = $surgery->residentStationFollowUps->sortByDesc('created_at');
                            @endphp
                            @if($surgeonFollowUps->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>تاريخ المتابعة</th>
                                                <th>الوردية</th>
                                                <th>المقيم</th>
                                                <th>الملاحظات</th>
                                                <th>توقيت التسجيل</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($surgeonFollowUps as $followUp)
                                            <tr>
                                                <td>{{ $followUp->follow_up_date->format('Y-m-d') }}</td>
                                                <td>{{ $followUp->session === 'morning' ? 'صباحاً' : 'مساءً' }}</td>
                                                <td>{{ $followUp->resident?->user?->full_name ?? $followUp->resident_name ?? 'غير محدد' }}</td>
                                                <td>{!! nl2br(e($followUp->notes)) !!}</td>
                                                <td>{{ $followUp->created_at->format('Y-m-d H:i') }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-1"></i> لا توجد حتى الآن متابعات مسجلة من الطبيب المقيم.
                                </div>
                            @endif
                        </div>
                        @if(auth()->user()->can('view resident station'))
                        <div class="card-footer bg-white text-end">
                            <a href="{{ route('resident-station.show', $surgery) }}?phase=post_op" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i> عرض تفاصيل متابعة المقيم
                            </a>
                        </div>
                        @endif
                    </div>

                    <!-- معلومات المحطة -->
                    @if($surgery->surgeonStation)
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h5>معلومات الحالة</h5>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="20%">حالة المحطة:</th>
                                    <td>
                                        @if($surgery->surgeonStation->status === 'pending')
                                            <span class="badge badge-warning">معلقة</span>
                                        @elseif($surgery->surgeonStation->status === 'in_progress')
                                            <span class="badge badge-info">جارية</span>
                                        @else
                                            <span class="badge badge-success">مكتملة</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($surgery->surgeonStation->started_at)
                                <tr>
                                    <th>تاريخ البدء:</th>
                                    <td>{{ $surgery->surgeonStation->started_at->format('Y-m-d h:i A') }}</td>
                                </tr>
                                @endif
                                @if($surgery->surgeonStation->completed_at)
                                <tr>
                                    <th>تاريخ الإتمام:</th>
                                    <td>{{ $surgery->surgeonStation->completed_at->format('Y-m-d h:i A') }}</td>
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

<script>
    // Dynamic Treatments Table Logic
    window.addSurgeryTreatment = function() {
        const container = document.getElementById('surgeryTreatmentsContainer');
        const emptyRow = document.getElementById('emptySurgeryTreatmentsRow');
        if (!container) return;

        if (emptyRow) {
            emptyRow.remove();
        }

        const treatmentIndex = container.querySelectorAll('.treatment-item').length;
        const treatmentHtml = `
            <tr class="treatment-item">
                <td class="text-center row-number">${treatmentIndex + 1}</td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][description]"
                           placeholder="اسم الدواء أو وصف العلاج" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][dosage]"
                           placeholder="مثال: 500mg, 2ml">
                </td>
                <td>
                    <textarea class="form-control form-control-sm timing-textarea" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][timing]" rows="1"
                              placeholder="مثال: كل 6 ساعات"></textarea>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <input type="number" class="form-control form-control-sm w-50" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][duration_value]"
                               placeholder="العدد" min="1">
                        <select class="form-select form-select-sm w-50" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][duration_unit]">
                            <option value="days" selected>يوم</option>
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
        reNumberRows();
    };

    window.removeSurgeryTreatment = function(button) {
        const row = button.closest('.treatment-item');
        if (!row) return;
        const container = row.parentElement;
        row.remove();

        reNumberRows();

        const remainingRows = container.querySelectorAll('.treatment-item');
        if (remainingRows.length === 0) {
            const emptyRowHtml = `
                <tr id="emptySurgeryTreatmentsRow">
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="fas fa-table fa-2x mb-2"></i>
                        <p class="mb-1">لا توجد علاجات مسجلة لهذه العملية</p>
                        <small>اضغط على "إضافة علاج" للبدء</small>
                    </td>
                </tr>
            `;
            container.insertAdjacentHTML('beforeend', emptyRowHtml);
        }
    };

    function reNumberRows() {
        const rows = document.querySelectorAll('#surgeryTreatmentsContainer .treatment-item');
        rows.forEach((row, index) => {
            // Update row number display
            const numCell = row.querySelector('.row-number');
            if (numCell) numCell.textContent = index + 1;

            // Re-index names to maintain standard contiguous sequence arrays for PHP backend validation
            const inputs = row.querySelectorAll('[name]');
            inputs.forEach(input => {
                const oldName = input.name;
                const newName = oldName.replace(/(\[surgery_treatments\]\[\d+\])\[\d+\]/, `$1[${index}]`);
                input.name = newName;
            });
        });
    }
</script>
@endsection
