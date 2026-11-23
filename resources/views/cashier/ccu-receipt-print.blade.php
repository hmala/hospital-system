<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ __('طباعة إيصال CCU') }} - {{ $ccuPayment->receipt_number }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; direction: rtl; margin: 20px; color: #111; }
        .receipt-box { max-width: 720px; margin: auto; border: 2px solid #222; padding: 20px; border-radius: 8px; }
        .header { text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 12px; margin-bottom: 16px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .amount-box { text-align: center; background: #f8f9fa; border: 1px solid #ddd; padding: 14px; margin: 16px 0; border-radius: 6px; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt-box">
        <div class="header">
            <h2 style="margin:0 0 6px 0;">{{ __('وحدة العناية المركزة للكبار (CCU)') }}</h2>
            <div>{{ __('إيصال مالي رسمي — رقم:') }} <strong>{{ $ccuPayment->receipt_number }}</strong></div>
        </div>

        <div class="row"><span><strong>{{ __('المريض:') }}</strong> {{ $ccuPayment->admission?->patient?->user?->name ?? __('مريض') }}</span><span><strong>{{ __('الإضبارة:') }}</strong> #{{ $ccuPayment->icu_admission_id }}</span></div>
        <div class="row"><span><strong>{{ __('السرير:') }}</strong> {{ $ccuPayment->admission?->bed?->bed_number }}</span><span><strong>{{ __('التاريخ:') }}</strong> {{ optional($ccuPayment->paid_at)->format('Y-m-d H:i') }}</span></div>
        <div class="row"><span><strong>{{ __('نوع العملية:') }}</strong> {{ \App\Models\CcuPayment::PAYMENT_TYPES[$ccuPayment->payment_type] ?? $ccuPayment->payment_type }}</span><span><strong>{{ __('طريقة الدفع:') }}</strong> {{ $ccuPayment->payment_method }}</span></div>

        <div class="amount-box">
            <div style="font-size:13px;color:#555;">المبلغ</div>
            <div style="font-size:24px;font-weight:bold;">{{ number_format($ccuPayment->amount) }} {{ __('د.ع') }}</div>
            <div style="font-size:12px;margin-top:4px;">{{ $ccuPayment->notes }}</div>
        </div>

        <div class="row"><span>{{ __('الصافي على المريض:') }} {{ number_format($stayDetails['patient_grand_total']) }} {{ __('د.ع') }}</span><span>{{ __('المتبقي بذمة المريض:') }} {{ number_format($stayDetails['net_balance']) }} {{ __('د.ع') }}</span></div>
        <div style="margin-top:24px;display:flex;justify-content:space-between;font-size:13px;">
            <span>{{ __('أمين الصندوق:') }} {{ $ccuPayment->cashier?->name ?? __('النظام') }}</span>
            <span>{{ __('التوقيع والختم:') }} __________________</span>
        </div>
        <div class="no-print" style="margin-top:20px;text-align:center;">
            <button onclick="window.print()" style="padding:8px 20px;cursor:pointer;">{{ __('طباعة') }}</button>
        </div>
    </div>
</body>
</html>
