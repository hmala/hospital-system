@extends('layouts.app')
@section('title', 'جدول الدوام الأسبوعي - HR')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="fas fa-calendar-alt text-success me-2"></i>جدول الدوام (Roster)</h4>
            <p class="text-muted small mb-0">بناء وتوزيع الشفتات على الموظفين للأيام القادمة</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- الفلتر والتحكم -->
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3">
            <form action="{{ route('hr.schedules.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">القسم</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">جميع الأقسام</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">تاريخ البداية (يعرض 7 أيام)</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-filter"></i> عرض الجدول</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .roster-select {
            font-size: 0.8rem;
            padding: 2px 4px;
            height: auto;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>

    <!-- شبكة بناء الجدول -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">
                الجدول من {{ $startDate->format('Y-m-d') }} إلى {{ $endDate->format('Y-m-d') }}
            </h6>
            <div>
                <form action="{{ route('hr.schedules.copy-last-week') }}" method="POST" class="d-inline-block me-2">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                    <input type="hidden" name="department_id" value="{{ $departmentId }}">
                    <button type="submit" class="btn btn-sm btn-outline-info" onclick="return confirm('هل أنت متأكد من جلب جدول الأسبوع الماضي؟ سيتم استبدال جدول هذا الأسبوع بالبيانات المستنسخة.')"><i class="fas fa-copy"></i> نسخ جدول الأسبوع الماضي</button>
                </form>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('rosterForm').reset();"><i class="fas fa-undo"></i> تصفير التعديلات</button>
                <button type="submit" form="rosterForm" class="btn btn-sm btn-success fw-bold px-4"><i class="fas fa-save"></i> حفظ الجدول</button>
            </div>
        </div>
        <div class="table-responsive" style="max-height: 65vh;">
            <form action="{{ route('hr.schedules.store') }}" method="POST" id="rosterForm">
                @csrf
                <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                
                <table class="table table-bordered table-hover align-middle mb-0 text-center" style="min-width: 1000px;">
                    <thead class="bg-light sticky-top shadow-sm" style="z-index: 10;">
                        <tr>
                            <th class="align-middle text-start" style="width: 200px; min-width: 200px; position: sticky; right: 0; background-color: #f8f9fa; z-index: 11;">الموظف / القسم</th>
                            @foreach($dates as $date)
                                <th class="align-middle" style="width: 120px;">
                                    <div class="fw-bold">{{ $date->locale('ar')->translatedFormat('l') }}</div>
                                    <div class="small text-muted">{{ $date->format('m/d') }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            @php
                                $empSchedules = $existingSchedules->get($emp->id, collect());
                            @endphp
                            <tr>
                                <td class="text-start" style="position: sticky; right: 0; background-color: white; z-index: 5;">
                                    <div class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $emp->full_name }}</div>
                                    <div class="small text-muted" style="font-size: 0.75rem;">{{ $emp->department->name ?? '—' }} | {{ $emp->job_title }}</div>
                                </td>
                                @foreach($dates as $date)
                                    @php
                                        $dateStr = $date->format('Y-m-d');
                                        $daySchedule = $empSchedules->first(function($item) use ($dateStr) { 
                                            return $item->shift_date->format('Y-m-d') === $dateStr; 
                                        });
                                        $currentVal = '';
                                        $currentColor = '';
                                        if ($daySchedule) {
                                            if ($daySchedule->is_off_day) {
                                                $currentVal = 'off';
                                                $currentColor = '#6c757d'; // gray
                                            } else if ($daySchedule->hr_shift_id) {
                                                $currentVal = $daySchedule->hr_shift_id;
                                                $currentColor = $daySchedule->shift->color_code ?? '';
                                            }
                                        }
                                    @endphp
                                    <td class="p-1">
                                        <select name="schedules[{{ $emp->id }}][{{ $dateStr }}]" 
                                                class="form-select roster-select text-center" 
                                                style="color: {{ $currentColor ? '#fff' : 'inherit' }}; background-color: {{ $currentColor ? $currentColor : 'transparent' }}; border-color: {{ $currentColor ? $currentColor : '#dee2e6' }};"
                                                onchange="updateSelectColor(this)">
                                            <option value="" style="color: black; background: white;">— فراغ —</option>
                                            <option value="off" data-color="#6c757d" style="color: white; background: #6c757d;" {{ $currentVal === 'off' ? 'selected' : '' }}>يوم راحة (Off)</option>
                                            @foreach($shifts as $shift)
                                                <option value="{{ $shift->id }}" data-color="{{ $shift->color_code }}" style="color: white; background: {{ $shift->color_code }};" {{ $currentVal == $shift->id ? 'selected' : '' }}>
                                                    {{ $shift->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($dates) + 1 }}" class="text-center py-5 text-muted">
                                    لا يوجد موظفين لعرضهم (جرب تغيير فلتر القسم).
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </form>
        </div>
    </div>
</div>

<script>
    
    <script>
        function checkFatigue() {
            let table = document.querySelector("table");
            let rows = table.querySelectorAll("tbody tr");
            rows.forEach(row => {
                let selects = row.querySelectorAll("select");
                let workDaysCount = 0;
                selects.forEach(select => {
                    if (select.value !== "" && select.value !== "off") {
                        workDaysCount++;
                    }
                });
                
                let nameCell = row.querySelector("td");
                let existingBadge = nameCell.querySelector(".fatigue-badge");
                if (workDaysCount >= 6) {
                    if (!existingBadge) {
                        nameCell.innerHTML += '<div class="fatigue-badge mt-1"><span class="badge bg-danger" style="font-size: 0.65rem;" title="تنبيه إرهاق: يعمل الموظف 6 أيام متواصلة أو أكثر"><i class="fas fa-exclamation-triangle"></i> تنبيه إرهاق</span></div>';
                    }
                } else {
                    if (existingBadge) {
                        existingBadge.remove();
                    }
                }
            });
        }
        
        document.addEventListener("DOMContentLoaded", function() {
            checkFatigue();
        });
    </script>
    
    function updateSelectColor(selectObj) {
        checkFatigue();
        var selectedOption = selectObj.options[selectObj.selectedIndex];
        var color = selectedOption.getAttribute('data-color');
        if (color) {
            selectObj.style.backgroundColor = color;
            selectObj.style.color = 'white';
            selectObj.style.borderColor = color;
        } else {
            selectObj.style.backgroundColor = 'transparent';
            selectObj.style.color = 'inherit';
            selectObj.style.borderColor = '#dee2e6';
        }
    }
</script>
@endsection
