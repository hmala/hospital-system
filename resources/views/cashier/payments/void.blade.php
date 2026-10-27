@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- رأس الصفحة الرقابي الفاخر -->
    <div class="rounded-4 p-4 mb-4 text-white shadow-sm" style="background: linear-gradient(135deg, #b02a37 0%, #842029 100%);">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-white text-danger fw-bold px-3 py-2 rounded-pill mb-2 shadow-sm">
                    <i class="fas fa-shield-alt me-1"></i> وحدة الرقابة المالية والتدقيق الإداري
                </span>
                <h3 class="fw-bold mb-1">إبطال الوصل المالي وإعادة فتحه للتصحيح</h3>
                <p class="mb-0 text-white-50 small">
                    يتيح هذا الإجراء تصحيح أخطاء فئات الضمان أو التكرار وإعادة فتح القيد في الكاشير للدفع بالرقم والنسبة الصحيحين.
                </p>
            </div>
            <div>
                <a href="{{ route('cashier.receipt', $payment->id) }}" class="btn btn-outline-light px-4 rounded-pill">
                    <i class="fas fa-arrow-right me-1"></i> تراجع والعودة للوصل
                </a>
            </div>
        </div>
    </div>

    <!-- بطاقات المعلومات الثلاثية -->
    <div class="row g-3 mb-4">
        <!-- 1. بيانات الوصل -->
        <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100 p-3 bg-white border-top border-4 border-danger">
                <span class="text-muted small d-block mb-1">
                    <i class="fas fa-receipt text-danger me-1"></i> بيانات الوصل الحالي
                </span>
                <div class="fs-4 fw-bold text-dark mb-1">{{ $payment->receipt_number }}</div>
                <div class="small text-muted">
                    تاريخ القبض: <strong class="text-dark">{{ $payment->paid_at?->format('Y-m-d H:i') }}</strong><br>
                    أمين الصندوق: <strong class="text-dark">{{ $payment->cashier?->name ?? 'غير محدد' }}</strong>
                </div>
            </div>
        </div>

        <!-- 2. بيانات المريض -->
        <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100 p-3 bg-white border-top border-4 border-primary">
                <span class="text-muted small d-block mb-1">
                    <i class="fas fa-user-injured text-primary me-1"></i> المريض والتغطية
                </span>
                <div class="fs-5 fw-bold text-primary mb-1 text-truncate">{{ $pname }}</div>
                <div class="small text-muted">
                    رقم الهوية: <strong class="text-dark">{{ $pid }}</strong><br>
                    نوع الدفع: <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $payment->payment_type_name }}</span>
                    @if($payment->insurance_type && $payment->insurance_type !== 'none')
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            ضمان (تحمل {{ (float)$payment->copay_percentage }}%)
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- 3. المبالغ المالية -->
        <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100 p-3 bg-white border-top border-4 border-success">
                <span class="text-muted small d-block mb-1">
                    <i class="fas fa-money-bill-wave text-success me-1"></i> المبلغ المقبوض نقداً
                </span>
                <div class="fs-3 fw-bold text-success mb-1">{{ number_format($totalPatientSum) }} <small class="fs-6">د.ع</small></div>
                <div class="small text-muted">
                    @if($payment->insurance_type && $payment->insurance_type !== 'none')
                        حصة الضمان: <strong class="text-primary">{{ number_format($payment->insurance_share ?? 0) }} د.ع</strong>
                    @else
                        تسديد نقدي كامل 100%
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- استمارة الإبطال والتصحيح -->
    <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
        <div class="p-3 px-4 border-bottom bg-light d-flex align-items-center gap-2">
            <i class="fas fa-cogs text-danger fs-5"></i>
            <h5 class="fw-bold mb-0 text-dark">خيارات الإبطال والتوثيق المالي الإلزامي</h5>
        </div>

        <form action="{{ route('cashier.payments.void', $payment->id) }}" method="POST" id="voidPaymentForm" class="p-4">
            @csrf

            @if($errors->any())
                <div class="alert alert-danger rounded-3 mb-4">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- تحديد نوع الإجراء -->
            <label class="form-label fw-bold text-dark fs-6 mb-3">
                <i class="fas fa-tasks text-primary me-1"></i> اختر نوع الإجراء الرقابي المطلوب تنفيذه:
            </label>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="card border-2 p-3 rounded-4 cursor-pointer h-100 transition-all option-card" id="card_rebill" style="border-color: #198754 !important; background-color: #f8fff9;">
                        <div class="d-flex gap-3 align-items-start">
                            <input type="radio" name="void_type" value="rebill" checked class="form-check-input mt-1 fs-5" onchange="highlightOption('rebill')">
                            <div>
                                <h6 class="fw-bold text-success mb-1">
                                    <i class="fas fa-sync-alt me-1"></i> إعادة فتح القيد للتسديد والتصحيح (Re-bill) — موصى به
                                </h6>
                                <p class="text-muted small mb-0">
                                    يتم تصفير حالة الدفع للوصل وتفريغ رقم الوصل وتاريخ القبض، وإعادة القيد إلى <strong>طابور الكاشير فوراً</strong> لتعديل فئة الضمان أو النسبة وسداد المبلغ الصحيح وإصدار وصل رسمي جديد.
                                </p>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="col-md-6">
                    <label class="card border p-3 rounded-4 cursor-pointer h-100 transition-all option-card" id="card_refund" style="border-color: #dee2e6; background-color: #ffffff;">
                        <div class="d-flex gap-3 align-items-start">
                            <input type="radio" name="void_type" value="refund" class="form-check-input mt-1 fs-5" onchange="highlightOption('refund')">
                            <div>
                                <h6 class="fw-bold text-danger mb-1">
                                    <i class="fas fa-hand-holding-usd me-1"></i> إبطال نهائي واسترداد للمريض (Refund & Cancel)
                                </h6>
                                <p class="text-muted small mb-0">
                                    تسجيل سند صرف استرجاع نقدي للمريض من الصندوق وإلغاء الطلب الطبي أو الموعد المرتبط نهائياً مع إغلاق قيد المطالبة.
                                </p>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- سبب الإلغاء -->
            <div class="mb-4">
                <label for="voidQuickReason" class="form-label fw-bold text-dark fs-6 mb-2">
                    <i class="fas fa-comment-dots text-danger me-1"></i> سبب الإلغاء والتصحيح (إلزامي للرقابة والتدقيق المالي):
                </label>
                
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="applyReason('خطأ في تحديد فئة الضمان الصحي أو نسبة التحمل الرسمية')">
                        خطأ في فئة الضمان ونسبة التحمل
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="applyReason('تكرار طلب الفحص أو الخدمة بالخطأ')">
                        تكرار الفحص بالخطأ
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="applyReason('رغبة المريض في إلغاء الخدمة واسترداد المبلغ')">
                        رغبة المريض بالإلغاء
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="applyReason('طلب تصحيح إداري صادر من الطبيب المعالج أو الإدارة')">
                        تصحيح إداري / طبي
                    </button>
                </div>

                <textarea name="void_reason" id="void_reason" class="form-control rounded-4 p-3 fs-6" rows="3" required minlength="5" placeholder="اكتب سبب الإلغاء والتصحيح بالتفصيل هنا (إلزامي، 5 أحرف على الأقل)...">{{ old('void_reason') }}</textarea>
                <small class="text-muted">يرجى توثيق السبب بدقة حيث يظهر هذا البيان في كشف الحسابات الختامية وسجل التدقيق الإداري.</small>
            </div>

            <!-- تنبيه رقابي أمني -->
            <div class="alert alert-warning border-2 border-warning rounded-4 p-3 mb-4 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning text-dark p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fas fa-user-shield fs-5"></i>
                </div>
                <div>
                    <strong class="d-block text-dark">مسؤولية التدقيق والرقابة المالية:</strong>
                    <span class="small text-muted">
                        هذه العملية مسجلة بالثانية باسمك الرسمي (<strong>{{ auth()->user()->name }}</strong>) في سجل الرقابة المالي (Audit Trail) غير القابل للحذف.
                    </span>
                </div>
            </div>

            <!-- أزرار الإجراء -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-3 border-top">
                <a href="{{ route('cashier.receipt', $payment->id) }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                    <i class="fas fa-times me-1"></i> إلغاء والتراجع
                </a>
                <button type="submit" class="btn btn-danger px-5 py-2 fs-6 fw-bold rounded-pill shadow" id="submitVoidBtn">
                    <i class="fas fa-check-circle me-1"></i> تأكيد إبطال الوصل وتنفيذ الإجراء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function applyReason(text) {
        const textarea = document.getElementById('void_reason');
        textarea.value = text;
        textarea.focus();
    }

    function highlightOption(type) {
        const cardRebill = document.getElementById('card_rebill');
        const cardRefund = document.getElementById('card_refund');

        if (type === 'rebill') {
            cardRebill.style.borderColor = '#198754';
            cardRebill.style.backgroundColor = '#f8fff9';
            cardRefund.style.borderColor = '#dee2e6';
            cardRefund.style.backgroundColor = '#ffffff';
        } else {
            cardRefund.style.borderColor = '#dc3545';
            cardRefund.style.backgroundColor = '#fff8f8';
            cardRebill.style.borderColor = '#dee2e6';
            cardRebill.style.backgroundColor = '#ffffff';
        }
    }

    document.getElementById('voidPaymentForm')?.addEventListener('submit', function(e) {
        const btn = document.getElementById('submitVoidBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري تنفيذ الإبطال...';
    });
</script>
@endsection
