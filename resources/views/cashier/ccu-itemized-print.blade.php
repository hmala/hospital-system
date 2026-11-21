<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ __('كشف الحساب والمطالبة التفصيلي CCU') }} #{{ $admission->id }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; direction: rtl; margin: 20px; color: #111; font-size: 13px; }
        .doc { max-width: 960px; margin: auto; }
        .header { text-align: center; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; margin-bottom: 16px; }
        .grid { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; background: #f8f9fa; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; }
        .grid div { flex: 1 1 220px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: center; }
        th { background: #f1f5f9; font-weight: bold; }
        .text-start { text-align: right; }
        .summary-box { display: flex; gap: 12px; margin-top: 12px; }
        .summary-box > div { flex: 1; border: 1px solid #94a3b8; padding: 10px; border-radius: 6px; }
        @media print { .no-print { display: none; } body { margin: 8px; } }
    </style>
</head>
<body>
<div class="doc">
    <div class="no-print" style="text-align: left; margin-bottom: 10px;">
        <button onclick="window.print()" style="padding: 8px 18px; background: #0d6efd; color: #fff; border: none; border-radius: 5px; cursor: pointer;">طباعة الكشف الرسمي</button>
    </div>

    <div class="header">
        <h2 style="margin:0;">{{ __('كشف حساب ومطالبة ردهات العناية المركزة للكبار (CCU / ICU)') }}</h2>
        <div>{{ __('إضبارة رقم') }} #{{ $admission->id }} — تاريخ الإصدار: {{ now()->format('Y-m-d H:i') }}</div>
    </div>

    <div class="grid">
        <div><strong>{{ __('المريض:') }}</strong> {{ $admission->patient?->user?->name ?? __('مريض') }}</div>
        <div><strong>{{ __('التغطية:') }}</strong> {{ $stayDetails['is_insured'] ? __('مشمول بالضمان الصحي') . ' (' . __('فئة') . ' ' . $stayDetails['policy_category'] . ')' : __('نقدي (كاش)') }}</div>
        <div><strong>{{ __('السرير:') }}</strong> {{ $admission->bed?->bed_number }} ({{ $admission->bed?->type_name }})</div>
        <div><strong>{{ __('التشخيص الأولي:') }}</strong> {{ $admission->primary_diagnosis ?: __('قيد التحديد') }} {{ $admission->icd10_code ? '(' . $admission->icd10_code . ')' : '' }}</div>
        <div><strong>{{ __('تاريخ الدخول:') }}</strong> {{ optional($admission->admission_date)->format('Y-m-d H:i') }}</div>
        <div><strong>{{ __('عدد الأيام المحتسبة:') }}</strong> {{ $stayDetails['days_count'] }} {{ __('يوم') }} @if($stayDetails['is_insured']) ({{ __('السنوي التراكمي:') }} {{ $stayDetails['total_annual_days_with_current'] }}/{{ $stayDetails['annual_max_days'] }}) @endif</div>
    </div>

    <h4>{{ __('1. تفصيل الإقامة اليومية وشرائح التغطية المعتمدة') }}</h4>
    <table>
        <thead>
            <tr>
                <th>{{ __('اليوم') }}</th>
                <th>{{ __('السجل السنوي') }}</th>
                <th class="text-start">{{ __('الباقة / نظام التسعير') }}</th>
                <th>{{ __('اليومية') }}</th>
                <th>{{ __('الشريحة') }}</th>
                <th>{{ __('حصة الضمان') }}</th>
                <th>{{ __('حصة المريض') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stayDetails['daily_coverage_details'] as $row)
                <tr>
                    <td>{{ $row['stay_day_number'] }}</td>
                    <td>{{ $row['annual_day_number'] }} / {{ $stayDetails['annual_max_days'] }}</td>
                    <td class="text-start">{{ $row['service_name'] }}</td>
                    <td>{{ number_format($row['daily_rate']) }}</td>
                    <td>{{ $row['tier_label'] }}</td>
                    <td>{{ number_format($row['hia_share']) }}</td>
                    <td>{{ number_format($row['patient_share']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(count($stayDetails['addon_breakdown']) > 0)
        <h4>{{ __('2. الخدمات المخصصة المنفصلة (كاش / أجهزة إضافية)') }}</h4>
        <table>
            <thead>
                <tr>
                    <th class="text-start">{{ __('الخدمة') }}</th>
                    <th>{{ __('السعر') }}</th>
                    <th>{{ __('الكمية') }}</th>
                    <th>{{ __('حصة الضمان') }}</th>
                    <th>{{ __('حصة المريض') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stayDetails['addon_breakdown'] as $addon)
                    <tr>
                        <td class="text-start">{{ $addon['service_name'] }}</td>
                        <td>{{ number_format($addon['unit_fee']) }}</td>
                        <td>{{ $addon['quantity'] }}</td>
                        <td>{{ number_format($addon['insurance_share']) }}</td>
                        <td>{{ number_format($addon['patient_share']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h4>{{ __('3. الفحوصات المختبرية والشعاعية (توضيح القيمة الأصلية مقابل المشمول 0 د.ع)') }}</h4>
    <table>
        <thead>
            <tr>
                <th>{{ __('النوع') }}</th>
                <th class="text-start">{{ __('اسم الفحص') }}</th>
                <th>{{ __('القيمة الأصلية (للمطالبة)') }}</th>
                <th>{{ __('حالة الشمول') }}</th>
                <th>{{ __('حصة الضمان الإضافية') }}</th>
                <th>{{ __('حصة المريض') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stayDetails['medical_orders'] as $ord)
                <tr>
                    <td>{{ $ord->order_type === 'lab' ? __('مختبر') : __('أشعة') }}</td>
                    <td class="text-start">{{ $ord->labTest?->name ?? $ord->radiologyType?->name ?? $ord->item_name }}</td>
                    <td>{{ number_format($ord->original_price_display ?? $ord->original_price ?? 0) }}</td>
                    <td>{{ $ord->is_bundled ? __('مشمول بالباقة اليومية (0 د.ع على المريض)') : __('محتسب منفرداً') }}</td>
                    <td>{{ number_format($ord->insurance_share ?? 0) }}</td>
                    <td>{{ number_format($ord->patient_share ?? 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">{{ __('لا توجد طلبات مختبر أو أشعة.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-box">
        <div>
            <strong>{{ __('ملخص حساب المريض') }}</strong><br>
            {{ __('صافي المستحق على المريض:') }} <strong>{{ number_format($stayDetails['patient_grand_total']) }} {{ __('د.ع') }}</strong><br>
            {{ __('المدفوع حتى الآن:') }} <strong>{{ number_format($stayDetails['total_paid']) }} {{ __('د.ع') }}</strong><br>
            {{ __('المتبقي بذمة المريض:') }} <strong>{{ number_format($stayDetails['net_balance']) }} {{ __('د.ع') }}</strong>
        </div>
        <div>
            <strong>{{ __('ملخص مطالبة هيئة الضمان الصحي') }}</strong><br>
            {{ __('القيمة الأصلية للفحوصات المشمولة:') }} <strong>{{ number_format($stayDetails['bundled_reference_total']) }} {{ __('د.ع') }}</strong><br>
            {{ __('صافي المطالبة الفعلية للضمان:') }} <strong>{{ number_format($stayDetails['insurance_grand_total']) }} {{ __('د.ع') }}</strong><br>
            {{ __('المستلم من الضمان:') }} <strong>{{ number_format($stayDetails['hia_collected_amount']) }} {{ __('د.ع') }}</strong><br>
            {{ __('الباقي بذمة الضمان الصحي:') }} <strong>{{ number_format($stayDetails['hia_remaining_balance']) }} {{ __('د.ع') }}</strong>
        </div>
    </div>
</div>
</body>
</html>
