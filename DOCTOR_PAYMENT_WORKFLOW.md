# نظام الدفع للتحاليل والأشعة من واجهة الطبيب

## نظرة عامة

تم تطوير نظام دفع متكامل للتحاليل والأشعة التي يطلبها الطبيب للمريض. النظام يضمن أن جميع الطلبات يجب أن تمر بالكاشير للدفع قبل إرسالها للقسم المختص (المختبر أو الأشعة).

---

## سير العمل (Workflow)

### 1️⃣ الطبيب يطلب تحاليل أو أشعة

```
الطبيب → واجهة الزيارة → إضافة طلب → اختيار التحاليل/الأشعة → إرسال الطلب
```

**النتيجة:**
- يتم إنشاء طلب بحالة `status = pending` و `payment_status = pending`
- يظهر للطبيب رسالة: "تم إنشاء الطلب بنجاح - يرجى التوجه للكاشير للدفع"
- يُعرض خيار للانتقال مباشرة لصفحة الكاشير

---

### 2️⃣ الدفع عند الكاشير

```
المريض → محطة الكاشير → دفع رسوم الخدمة → إصدار إيصال
```

**المعلومات المطلوبة:**
- طريقة الدفع (نقدي / بطاقة / تأمين)
- المبلغ
- ملاحظات (اختياري)

**النتيجة:**
- تحديث حالة الطلب: `payment_status = paid`
- تحديث حالة الزيارة: `status = in_progress`
- إصدار رقم إيصال فريد
- إنشاء سجل في جدول `payments`

---

### 3️⃣ إرسال الطلب للقسم المختص

بعد الدفع:
- إذا كان طلب **تحاليل**: يظهر في قائمة طلبات المختبر
- إذا كان طلب **أشعة**: يتم إنشاء سجل في `radiology_requests` ويظهر في قسم الأشعة

---

## التعديلات المنفذة

### 1. DoctorVisitController.php

#### في دالة `storeRequest()`:

```php
$medicalRequest = MedicalRequest::create([
    'visit_id' => $visit->id,
    'type' => $request->type,
    'description' => ...,
    'details' => $details,
    'status' => 'pending',
    'payment_status' => 'pending' // ⭐ إضافة جديدة - يجب الدفع أولاً
]);
```

#### تحديث Response للـ AJAX:

```php
if ($request->expectsJson()) {
    return response()->json([
        'success' => true,
        'message' => 'تم إنشاء الطلب بنجاح - يرجى التوجه للكاشير للدفع',
        'request_count' => $visit->requests()->count(),
        'request_id' => $medicalRequest->id,
        'requires_payment' => true, // ⭐ إشارة للواجهة
        'cashier_url' => route('cashier.request.payment.form', $medicalRequest->id)
    ]);
}
```

---

### 2. show.blade.php (واجهة الطبيب)

#### أ) تنبيه عن الدفع

تم إضافة رسالة تحذيرية في نموذج إضافة الطلبات:

```html
<div class="alert alert-warning border-warning mb-4">
    <div class="d-flex align-items-start">
        <i class="fas fa-exclamation-triangle me-3"></i>
        <div>
            <h6 class="alert-heading mb-2">
                تنبيه مهم - إجراءات الدفع
            </h6>
            <p class="mb-0">
                <strong>يجب تسديد رسوم التحاليل أو الأشعة عند الكاشير 
                قبل إرسالها للقسم المختص.</strong>
                <br>
                بعد إضافة الطلب، سيتم توجيهك إلى صفحة الكاشير 
                لإكمال عملية الدفع.
            </p>
        </div>
    </div>
</div>
```

#### ب) معالجة الاستجابة في JavaScript

```javascript
.then(data => {
    if (data.success) {
        // التحقق إذا كان الطلب يحتاج دفع
        if (data.requires_payment && data.cashier_url) {
            const confirmPayment = confirm(
                data.message + 
                '\n\nهل تريد الانتقال إلى صفحة الكاشير الآن؟'
            );
            if (confirmPayment) {
                window.location.href = data.cashier_url;
            } else {
                location.reload();
            }
        }
    }
})
```

#### ج) عرض حالة الدفع في جدول الطلبات

تم إضافة عمود جديد "حالة الدفع" يعرض:
- ✅ **مدفوع** (badge أخضر) - إذا تم الدفع
- ⚠️ **معلق** (badge أصفر) - إذا لم يتم الدفع بعد
- زر "دفع" للانتقال السريع لصفحة الكاشير

```html
<td>
    @if($paymentStatus == 'paid')
        <span class="badge bg-success">
            <i class="fas fa-check-circle me-1"></i>
            مدفوع
        </span>
    @else
        <span class="badge bg-warning text-dark">
            <i class="fas fa-exclamation-circle me-1"></i>
            معلق
        </span>
        <br>
        <a href="{{ route('cashier.request.payment.form', $request->id) }}" 
           class="btn btn-sm btn-outline-success mt-1">
            <i class="fas fa-money-bill-wave me-1"></i>
            دفع
        </a>
    @endif
</td>
```

---

## الحقول في قاعدة البيانات

### جدول `requests`

| الحقل | النوع | الوصف |
|------|------|-------|
| `payment_status` | enum | حالة الدفع (pending/paid/cancelled) |
| `payment_id` | bigint | معرف عملية الدفع (nullable) |

### جدول `payments`

| الحقل | النوع | الوصف |
|------|------|-------|
| `request_id` | bigint | معرف الطلب الطبي (nullable) |
| `payment_type` | enum | نوع الدفع (lab/radiology/...) |
| `amount` | decimal | المبلغ المدفوع |
| `payment_method` | enum | طريقة الدفع (cash/card/insurance) |

---

## المسارات (Routes)

```php
// عرض صفحة الدفع لطلب معين
GET /cashier/request/{request}/payment

// معالجة الدفع
POST /cashier/request/{request}/payment

// عرض الإيصال
GET /cashier/receipt/{payment}
```

---

## تدفق البيانات (Data Flow)

```
┌─────────────────┐
│   الطبيب       │
│  يطلب تحليل    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ إنشاء طلب       │
│ payment_status  │
│ = pending       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  رسالة للطبيب   │
│ "توجه للكاشير" │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    الكاشير      │
│   يستقبل دفع    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ تحديث الطلب     │
│ payment_status  │
│ = paid          │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ إرسال للقسم     │
│ المختص          │
│ (مختبر/أشعة)   │
└─────────────────┘
```

---

## المزايا

✅ **منع التلاعب**: لا يمكن للقسم المختص رؤية الطلب قبل الدفع  
✅ **تتبع دقيق**: كل عملية دفع موثقة برقم إيصال  
✅ **سهولة الاستخدام**: انتقال سلس من الطبيب → الكاشير → القسم المختص  
✅ **شفافية**: حالة الدفع واضحة في كل مرحلة  
✅ **مرونة**: يمكن الدفع لاحقاً إذا لم يكن المريض جاهزاً  

---

## ملاحظات مهمة

1. **الطلبات غير المدفوعة** لا تظهر في قوائم المختبر أو الأشعة
2. **حالة الزيارة** تبقى `pending_payment` حتى يتم الدفع
3. **يمكن للطبيب** رؤية جميع طلباته بغض النظر عن حالة الدفع
4. **زر الدفع** متاح في واجهة الطبيب للوصول السريع للكاشير

---

## الملفات المعدلة

1. `app/Http/Controllers/DoctorVisitController.php`
2. `resources/views/doctors/visits/show.blade.php`

---

## تاريخ التحديث

**التاريخ**: 2026-01-13  
**الإصدار**: 1.0  
**المطور**: Hospital Management System
