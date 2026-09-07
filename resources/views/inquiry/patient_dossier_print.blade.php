<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضبارة المريض الطبية - {{ $patient->user->name ?? 'مريض' }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fff;
            color: #1e293b;
            font-size: 13px;
        }
        .header-box {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .section-header {
            background-color: #f1f5f9;
            border-right: 4px solid #1e3a8a;
            padding: 6px 12px;
            font-weight: bold;
            font-size: 14px;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .table-sm th, .table-sm td {
            padding: 5px 8px;
            font-size: 12px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body class="p-4">

    {{-- أزرار التحكم والطباعة --}}
    <div class="no-print mb-4 d-flex justify-content-between align-items-center bg-light p-3 rounded border">
        <div>
            <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-file-medical-alt me-2"></i> معاينة طباعة الإضبارة الشاملة للمريض</h5>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary px-4 fw-bold shadow-sm">
                <i class="fas fa-print me-1"></i> طباعة الإضبارة
            </button>
            <button onclick="window.close()" class="btn btn-secondary px-3">
                <i class="fas fa-times me-1"></i> إغلاق
            </button>
        </div>
    </div>

    {{-- ترويسة المستشفى الرسمية --}}
    <div class="header-box d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ asset('images/logo.jpeg') }}" alt="شعار المستشفى" style="max-height: 65px;">
            <div>
                <h4 class="fw-bold mb-0 text-primary">مستشفى الكفاءات الأهلي</h4>
                <div class="text-muted small">Al-Kafaat Specialized Hospital - Medical Records Dept.</div>
            </div>
        </div>
        <div class="text-end">
            <h5 class="fw-bold mb-1 text-dark">الملف الطبي الموحد (Medical Dossier)</h5>
            <div class="small text-muted">تاريخ الإصدار: {{ date('Y-m-d H:i') }}</div>
            <div class="badge bg-primary text-white mt-1">رقم الإضبارة: #{{ $patient->national_id ?? $patient->id }}</div>
        </div>
    </div>

    {{-- معلومات المريض الأساسية --}}
    <div class="card border mb-3">
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-4"><strong>اسم المريض:</strong> {{ $patient->user->name ?? '-' }}</div>
                <div class="col-4"><strong>رقم الهاتف:</strong> {{ $patient->user->phone ?? '-' }}</div>
                <div class="col-4"><strong>العمر / الجنس:</strong> {{ $patient->age ? $patient->age . ' سنة' : '-' }} ({{ $patient->user->gender == 'male' ? 'ذكر' : 'أنثى' }})</div>
                
                <div class="col-4"><strong>فصيلة الدم:</strong> <span class="badge bg-danger">{{ $patient->blood_type ?? 'غير محددة' }}</span></div>
                <div class="col-4"><strong>الرقم القومي / الهوية:</strong> {{ $patient->national_id ?? '-' }}</div>
                <div class="col-4"><strong>جهة التأمين:</strong> {{ $patient->covered_by_insurance ? ($patient->insurance_company ?? 'مؤمن') : 'نقد / خاص' }}</div>

                <div class="col-12"><strong>العنوان السكني:</strong> {{ $patient->governorate ?? '' }} {{ $patient->district ? ' - ' . $patient->district : '' }} {{ $patient->neighborhood ? ' - ' . $patient->neighborhood : '' }}</div>
                
                @if($patient->allergies)
                    <div class="col-12 text-danger"><strong><i class="fas fa-exclamation-triangle"></i> الحساسيات المسجلة:</strong> {{ $patient->allergies }}</div>
                @endif
                @if($patient->medical_history)
                    <div class="col-12 text-primary"><strong><i class="fas fa-notes-medical"></i> الأمراض المزمنة / التاريخ الطبي:</strong> {{ $patient->medical_history }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- 1. العيادات والزيارات --}}
    <div class="section-header">1. سجل العيادات والزيارات الطبية</div>
    @if($patient->visits->isEmpty())
        <div class="text-muted small py-1">لا توجد زيارات سابقة مسجلة.</div>
    @else
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>تاريخ الزيارة</th>
                    <th>القسم / العيادة</th>
                    <th>الطبيب المعالج</th>
                    <th>التشخيص / الشكوى</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patient->visits as $v)
                    <tr>
                        <td>{{ $v->visit_date ? \Carbon\Carbon::parse($v->visit_date)->format('Y-m-d') : '-' }}</td>
                        <td>{{ $v->department->name ?? '-' }}</td>
                        <td>{{ $v->doctor->user->name ?? '-' }}</td>
                        <td>
                            @if(is_array($v->diagnosis))
                                {{ implode(', ', array_filter($v->diagnosis)) ?: ($v->chief_complaint ?? '-') }}
                            @elseif(!empty($v->diagnosis))
                                {{ $v->diagnosis }}
                            @elseif(is_array($v->symptoms))
                                {{ implode(', ', array_filter($v->symptoms)) }}
                            @else
                                {{ $v->symptoms ?? ($v->chief_complaint ?? '-') }}
                            @endif
                        </td>
                        <td>{{ $v->status ?? 'مكتمل' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- 2. سجل الطوارئ --}}
    <div class="section-header">2. سجل حالات الطوارئ والحوادث</div>
    @if($patient->emergencies->isEmpty())
        <div class="text-muted small py-1">لا توجد حالات طوارئ مسجلة.</div>
    @else
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>تاريخ الدخول</th>
                    <th>طبيب الطوارئ</th>
                    <th>الشكوى / الأعراض</th>
                    <th>الأولوية</th>
                    <th>الإجراء والتوجيه</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patient->emergencies as $e)
                    <tr>
                        <td>{{ $e->admission_time ? \Carbon\Carbon::parse($e->admission_time)->format('Y-m-d H:i') : $e->created_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $e->doctor->user->name ?? '-' }}</td>
                        <td>
                            @if(is_array($e->symptoms))
                                {{ implode(', ', array_filter($e->symptoms)) }}
                            @elseif(!empty($e->symptoms))
                                {{ $e->symptoms }}
                            @elseif(is_array($e->diagnosis))
                                {{ implode(', ', array_filter($e->diagnosis)) }}
                            @else
                                {{ $e->diagnosis ?? '-' }}
                            @endif
                        </td>
                        <td>{{ $e->priority ?? 'عادي' }}</td>
                        <td>
                            @if($e->requires_surgery) تحويل صالة عمليات @elseif($e->requires_admission) تحويل رقود @else علاج ومغادرة @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- 3. العمليات الجراحية --}}
    <div class="section-header">3. العمليات الجراحية</div>
    @if($patient->surgeries->isEmpty())
        <div class="text-muted small py-1">لا توجد عمليات جراحية مسجلة.</div>
    @else
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>تاريخ العملية</th>
                    <th>اسم العملية الجراحية</th>
                    <th>الجراح المسؤول</th>
                    <th>طبيب التخدير</th>
                    <th>الصالة</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patient->surgeries as $s)
                    <tr>
                        <td>{{ $s->scheduled_date ? \Carbon\Carbon::parse($s->scheduled_date)->format('Y-m-d') : ($s->created_at ? $s->created_at->format('Y-m-d') : '-') }}</td>
                        <td class="fw-bold">{{ $s->surgery_type ?? ($s->surgery_name ?? 'عملية جراحية') }}</td>
                        <td>{{ $s->doctor->user->name ?? ($s->surgeon_name ?? '-') }}</td>
                        <td>{{ $s->anesthesiologist->user->name ?? '-' }}</td>
                        <td>{{ $s->room->name ?? ($s->room->room_number ?? '-') }}</td>
                        <td>{{ $s->status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- 4. التحاليل المخبرية والأشعة --}}
    <div class="row">
        <div class="col-6">
            <div class="section-header">4. التحاليل المخبرية</div>
            @if($patient->requests->isEmpty())
                <div class="text-muted small py-1">لا توجد تحاليل مسجلة.</div>
            @else
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>التحليل</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($patient->requests->take(6) as $r)
                            <tr>
                                <td>{{ $r->created_at->format('Y-m-d') }}</td>
                                <td>{{ $r->type_text ?? ($r->description ?? 'طلب') }}</td>
                                <td>{{ $r->status_text ?? $r->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="col-6">
            <div class="section-header">5. فحوصات الأشعة والسونار</div>
            @if($patient->radiologyRequests->isEmpty())
                <div class="text-muted small py-1">لا توجد فحوصات أشعة مسجلة.</div>
            @else
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>التاريخ</th>
                            <th>نوع الفحص</th>
                            <th>تقرير الأشعة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($patient->radiologyRequests->take(6) as $rad)
                            <tr>
                                <td>{{ $rad->created_at->format('Y-m-d') }}</td>
                                <td>{{ $rad->radiologyType->name ?? 'فحص إشعاعي' }}</td>
                                <td class="small">{{ Str::limit($rad->result && $rad->result->report ? $rad->result->report : ($rad->notes ?? 'لا يوجد تقرير'), 40) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- 6. المستندات المؤرشفة --}}
    <div class="section-header">6. المستندات والوثائق المؤرشفة إلكترونياً</div>
    @if($patient->documents->isEmpty())
        <div class="text-muted small py-1">لا توجد وثائق مؤرشفة إلكترونياً.</div>
    @else
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>نوع الوثيقة</th>
                    <th>عنوان الوثيقة</th>
                    <th>تاريخ الأرشفة</th>
                    <th>الموظف المسؤول</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patient->documents as $dIdx => $doc)
                    <tr>
                        <td>{{ $dIdx + 1 }}</td>
                        <td>{{ $doc->category_name }}</td>
                        <td>{{ $doc->title }}</td>
                        <td>{{ $doc->created_at->format('Y-m-d') }}</td>
                        <td>{{ $doc->uploader->name ?? 'النظام' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- توقيع وختم المستشفى --}}
    <div class="row mt-5 pt-3 border-top">
        <div class="col-4 text-center">
            <strong>مسؤول الاستعلامات والأرشيف</strong>
            <div class="mt-4">________________________</div>
        </div>
        <div class="col-4 text-center">
            <strong>مدير السجلات الطبية</strong>
            <div class="mt-4">________________________</div>
        </div>
        <div class="col-4 text-center">
            <strong>ختم المستشفى الرسمي</strong>
            <div class="mt-4">________________________</div>
        </div>
    </div>

</body>
</html>
