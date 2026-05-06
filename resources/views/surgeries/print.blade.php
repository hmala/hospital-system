<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة تفاصيل العملية - {{ $surgery->surgery_type }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Arial', 'Tahoma', sans-serif; margin: 0; padding: 20px; color: #222; background: #fff; }
        .page { max-width: 210mm; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { font-size: 24px; color: #0d6efd; margin: 0; }
        .header .meta { text-align: right; }
        .section { margin-bottom: 22px; }
        .section h2 { font-size: 18px; color: #0d6efd; margin-bottom: 12px; border-bottom: 2px solid #0d6efd; padding-bottom: 6px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .table th, .table td { border: 1px solid #ddd; padding: 10px; vertical-align: top; }
        .table th { background: #f8f9fa; text-align: left; width: 220px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 0.85rem; color: #fff; }
        .badge-success { background: #198754; }
        .badge-warning { background: #ffc107; color: #212529; }
        .badge-secondary { background: #6c757d; }
        .badge-danger { background: #dc3545; }
        .result-box { background: #f8f9fa; padding: 14px; border-radius: 8px; border: 1px solid #dde2e7; }
        .print-actions { margin-bottom: 20px; }
        .print-actions button { padding: 10px 18px; border: none; border-radius: 8px; background: #0d6efd; color: #fff; cursor: pointer; font-size: 14px; }
        .print-actions button:hover { background: #0b5ed7; }
        @media print { .print-actions { display: none; } }
    </style>
</head>
<body>
    <div class="page">
        <div class="print-actions">
            <button onclick="window.print()">🖨️ طباعة</button>
        </div>

        <div class="header">
            <h1>تفاصيل العملية الجراحية</h1>
            <div class="meta">
                <div>المريض: <strong>{{ $surgery->patient->user->name ?? '-' }}</strong></div>
                <div>الطبيب: <strong>{{ $surgery->doctor ? 'د. ' . $surgery->doctor->user->name : ($surgery->surgeon_name ? $surgery->surgeon_name : '-') }}</strong></div>
            </div>
        </div>

        <div class="section">
            <h2>معلومات أساسية</h2>
            <table class="table">
                <tr>
                    <th>نوع العملية</th>
                    <td>{{ $surgery->surgery_type ?? '-' }}</td>
                </tr>
                <tr>
                    <th>الحالة</th>
                    <td>
                        @if($surgery->status == 'scheduled')
                            <span class="badge badge-secondary">مجدولة</span>
                        @elseif($surgery->status == 'waiting')
                            <span class="badge badge-warning">في الانتظار</span>
                        @elseif($surgery->status == 'in_progress')
                            <span class="badge badge-warning">جارية</span>
                        @elseif($surgery->status == 'completed')
                            <span class="badge badge-success">مكتملة</span>
                        @else
                            <span class="badge badge-danger">ملغاة</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>التاريخ</th>
                    <td>{{ optional($surgery->scheduled_date)->format('Y-m-d') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>الوقت</th>
                    <td>{{ $surgery->scheduled_time ?? '-' }}</td>
                </tr>
                <tr>
                    <th>الغرفة</th>
                    <td>{{ $surgery->room ? $surgery->room->room_number : '-' }}</td>
                </tr>
                <tr>
                    <th>وصف العملية</th>
                    <td>{{ $surgery->description ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>معلومات المريض والطبيب</h2>
            <table class="table">
                <tr>
                    <th>المريض</th>
                    <td>{{ $surgery->patient->user->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>الهاتف</th>
                    <td>{{ $surgery->patient->user->phone ?? '-' }}</td>
                </tr>
                <tr>
                    <th>الطبيب الجراح</th>
                    <td>{{ $surgery->doctor ? 'د. ' . $surgery->doctor->user->name : ($surgery->surgeon_name ? $surgery->surgeon_name : '-') }}</td>
                </tr>
                <tr>
                    <th>نوع التخدير</th>
                    <td>{{ $surgery->anesthesia_type ?? '-' }}</td>
                </tr>
                <tr>
                    <th>التصنيف</th>
                    <td>{{ $surgery->surgery_classification ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>نتائج الأشعة</h2>
            @if($surgery->radiologyTests->count())
                <table class="table">
                    <thead>
                        <tr>
                            <th>نوع الأشعة</th>
                            <th>الحالة</th>
                            <th>النتيجة</th>
                            <th>مرفق</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($surgery->radiologyTests as $test)
                        <tr>
                            <td>{{ $test->radiologyType->name ?? '-' }}</td>
                            <td>
                                @if($test->status == 'pending')
                                    <span class="badge badge-secondary">في الانتظار</span>
                                @elseif($test->status == 'completed')
                                    <span class="badge badge-success">مكتملة</span>
                                @elseif($test->status == 'in_progress')
                                    <span class="badge badge-warning">قيد التنفيذ</span>
                                @else
                                    <span class="badge badge-danger">{{ $test->status }}</span>
                                @endif
                            </td>
                            <td>{{ $test->result ?? '-' }}</td>
                            <td>
                                @if($test->result_file)
                                    <a href="{{ asset('storage/' . $test->result_file) }}" target="_blank">عرض الملف</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="result-box">لا توجد نتائج أشعة مرتبطة بهذه العملية.</div>
            @endif
        </div>

        @if($surgery->visit && $surgery->visit->radiologyRequests->count())
        <div class="section">
            <h2>نتائج الأشعة السابقة من الزيارة</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>نوع الأشعة</th>
                        <th>تاريخ الطلب</th>
                        <th>الحالة</th>
                        <th>النتائج</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($surgery->visit->radiologyRequests as $request)
                    <tr>
                        <td>{{ $request->radiologyType->name ?? '-' }}</td>
                        <td>{{ optional($request->created_at)->format('Y-m-d H:i') }}</td>
                        <td>
                            <span class="badge badge-{{ $request->status == 'completed' ? 'success' : ($request->status == 'pending' ? 'secondary' : 'danger') }}">
                                {{ $request->status_text ?? $request->status }}
                            </span>
                        </td>
                        <td>
                            @if($request->result)
                                {{ $request->result->findings ?? '-' }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</body>
</html>
