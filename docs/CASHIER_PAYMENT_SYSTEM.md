# نظام الدفع عند الكاشير - دليل الاستخدام

## نظرة عامة

تم تطوير نظام الدفع الكامل لإدارة المدفوعات عند محطة الكاشير قبل الذهاب للقسم المختص. النظام يوفر:

- ✅ تسجيل المدفوعات
- ✅ إصدار إيصالات رسمية
- ✅ تتبع حالة الدفع للمواعيد
- ✅ تقارير المدفوعات
- ✅ طباعة الإيصالات PDF

---

## سير العمل (Workflow)

### 1️⃣ حجز موعد من الاستعلامات

```
موظف الاستعلامات → اختيار المريض → تحديد الطبيب والقسم → إنشاء موعد
```

**النتيجة:**
- يتم إنشاء موعد بحالة `payment_status = pending`
- يتم توجيه المريض تلقائياً إلى صفحة الدفع

---

### 2️⃣ الدفع عند الكاشير

```
المريض → محطة الكاشير → إدخال بيانات الدفع → تأكيد الدفع
```

**المعلومات المطلوبة:**
- طريقة الدفع (نقدي / بطاقة / تأمين)
- المبلغ
- ملاحظات (اختياري)

**النتيجة:**
- تسجيل عملية الدفع في جدول `payments`
- تحديث حالة الموعد: `payment_status = paid`
- إصدار رقم إيصال فريد: `REC-YYYYMMDD-####`

---

### 3️⃣ إصدار الإيصال

```
إيصال فوري → عرض التفاصيل → طباعة PDF
```

**محتويات الإيصال:**
- رقم الإيصال
- معلومات المريض
- تفاصيل الموعد
- المبلغ المدفوع
- طريقة الدفع
- اسم الكاشير
- التوقيع

---

## الجداول والحقول

### جدول `payments`

| الحقل | النوع | الوصف |
|------|------|-------|
| `id` | bigint | المعرف الفريد |
| `appointment_id` | bigint | رقم الموعد (nullable) |
| `patient_id` | bigint | رقم المريض |
| `cashier_id` | bigint | رقم الكاشير |
| `receipt_number` | string | رقم الإيصال (فريد) |
| `amount` | decimal(10,2) | المبلغ المدفوع |
| `payment_method` | enum | طريقة الدفع (cash/card/insurance) |
| `payment_type` | enum | نوع الدفع (appointment/lab/radiology/pharmacy/surgery/other) |
| `description` | text | وصف الدفعة |
| `notes` | text | ملاحظات |
| `paid_at` | timestamp | تاريخ ووقت الدفع |

### تحديثات جدول `appointments`

| الحقل الجديد | النوع | الوصف |
|-------------|------|-------|
| `payment_status` | enum | حالة الدفع (pending/paid/refunded) - افتراضي: pending |
| `payment_id` | bigint | معرف عملية الدفع (nullable) |

---

## المسارات (Routes)

### مسارات الكاشير

```php
GET  /cashier                        - قائمة المواعيد المعلقة
GET  /cashier/payment/{appointment}  - صفحة الدفع
POST /cashier/payment/{appointment}  - معالجة الدفع
GET  /cashier/receipt/{payment}      - عرض الإيصال
GET  /cashier/receipt/{payment}/print - طباعة PDF
GET  /cashier/report                 - تقرير المدفوعات
```

---

## واجهات المستخدم

### 1. لوحة الكاشير الرئيسية
**المسار:** `/cashier`

**المحتوى:**
- إحصائيات اليوم (المبالغ المحصلة، عدد المدفوعات، المواعيد المعلقة)
- جدول المواعيد المعلقة بانتظار الدفع
- زر "تسديد" لكل موعد

### 2. صفحة الدفع
**المسار:** `/cashier/payment/{appointment_id}`

**المحتوى:**
- نموذج إدخال بيانات الدفع
- تفاصيل الموعد
- معلومات المريض
- معلومات الطبيب

### 3. إيصال الدفع
**المسار:** `/cashier/receipt/{payment_id}`

**المحتوى:**
- رقم الإيصال وتاريخ الدفع
- معلومات المريض والموعد
- جدول تفاصيل الدفع
- معلومات الكاشير
- زر الطباعة

### 4. طباعة PDF
**المسار:** `/cashier/receipt/{payment_id}/print`

**المحتوى:**
- إيصال منسق للطباعة بصيغة PDF
- جاهز للطباعة على ورق A4

---

## Model: Payment

```php
use App\Models\Payment;

// إنشاء دفعة جديدة
$payment = Payment::create([
    'appointment_id' => $appointment->id,
    'patient_id' => $patient->id,
    'cashier_id' => auth()->id(),
    'receipt_number' => Payment::generateReceiptNumber(),
    'amount' => 50000.00,
    'payment_method' => 'cash',
    'payment_type' => 'appointment',
    'description' => 'دفع رسوم موعد',
    'paid_at' => now()
]);

// العلاقات
$payment->patient;      // المريض
$payment->appointment;  // الموعد
$payment->cashier;      // الكاشير

// Attributes مخصصة
$payment->payment_method_name;  // "نقدي"
$payment->payment_type_name;    // "موعد"
```

---

## Controller: CashierController

### الوظائف المتاحة:

#### 1. `index()`
عرض لوحة الكاشير مع المواعيد المعلقة والإحصائيات

#### 2. `showPaymentForm(Appointment $appointment)`
عرض نموذج الدفع لموعد محدد

#### 3. `processPayment(Request $request, Appointment $appointment)`
معالجة عملية الدفع:
- التحقق من البيانات
- إنشاء سجل الدفع
- تحديث حالة الموعد
- إصدار رقم إيصال

#### 4. `showReceipt(Payment $payment)`
عرض إيصال الدفع

#### 5. `printReceipt(Payment $payment)`
تحميل الإيصال بصيغة PDF

#### 6. `paymentsReport(Request $request)`
عرض تقرير المدفوعات مع الفلاتر

---

## أمثلة الاستخدام

### مثال 1: حجز موعد من الاستعلامات

```php
// في InquiryController@store
$appointment = Appointment::create([
    'patient_id' => $patient->id,
    'doctor_id' => $doctor->id,
    'department_id' => $department->id,
    'appointment_date' => now(),
    'consultation_fee' => 50000,
    'status' => 'scheduled',
    'payment_status' => 'pending'  // ⭐ مهم
]);

// توجيه إلى صفحة الدفع
return redirect()->route('cashier.payment.form', $appointment->id);
```

### مثال 2: معالجة الدفع

```php
// في CashierController@processPayment
$payment = Payment::create([
    'appointment_id' => $appointment->id,
    'patient_id' => $appointment->patient_id,
    'cashier_id' => auth()->id(),
    'receipt_number' => Payment::generateReceiptNumber(),
    'amount' => $request->amount,
    'payment_method' => $request->payment_method,
    'payment_type' => 'appointment',
    'paid_at' => now()
]);

$appointment->update([
    'payment_status' => 'paid',
    'payment_id' => $payment->id
]);
```

---

## التحقق من حالة الدفع

### في الكود

```php
// التحقق من أن الموعد تم دفعه
if ($appointment->payment_status === 'paid') {
    // السماح بالذهاب للقسم
}

// الحصول على تفاصيل الدفع
$payment = $appointment->payment;
echo $payment->receipt_number;
echo $payment->amount;
```

### في Blade Views

```blade
@if($appointment->payment_status === 'paid')
    <span class="badge bg-success">تم الدفع</span>
@else
    <span class="badge bg-warning">معلق</span>
@endif
```

---

## الإحصائيات والتقارير

### إحصائيات اليوم

```php
$todayStats = [
    'total_collected' => Payment::whereDate('paid_at', today())->sum('amount'),
    'total_payments' => Payment::whereDate('paid_at', today())->count(),
    'pending_count' => Appointment::where('payment_status', 'pending')->count()
];
```

### تقرير المدفوعات

يمكن فلترة التقرير حسب:
- التاريخ (من - إلى)
- طريقة الدفع
- الكاشير

---

## الأمان والصلاحيات

### الأدوار المصرح لها:
- `admin` - صلاحية كاملة
- `receptionist` - إدارة الدفع
- `staff` - إدارة الدفع

### الحماية:
```php
if (!$user->hasRole(['admin', 'receptionist', 'staff'])) {
    abort(403, 'غير مصرح لك بالوصول');
}
```

---

## المكونات المستخدمة

### Packages
- **Laravel PDF**: `barryvdh/laravel-dompdf` - لتوليد PDF

### تثبيت (إذا لم يكن مثبتاً):
```bash
composer require barryvdh/laravel-dompdf
```

---

## الملفات المُنشأة

### Migrations
- `2026_01_04_100000_create_payments_table.php`
- `2026_01_04_100001_add_payment_fields_to_appointments_table.php`

### Models
- `app/Models/Payment.php`

### Controllers
- `app/Http/Controllers/CashierController.php`

### Views
- `resources/views/cashier/index.blade.php`
- `resources/views/cashier/payment-form.blade.php`
- `resources/views/cashier/receipt.blade.php`
- `resources/views/cashier/receipt-pdf.blade.php`

### Routes
- مسارات في `routes/web.php` تحت prefix `cashier`

---

## الخطوات التالية (اختياري)

### 1. إضافة إشعارات
- إشعار للمريض عند إتمام الدفع
- إشعار للطبيب عند جاهزية المريض

### 2. طباعة ملصقات
- ملصق رقم الموعد للمريض
- ملصق توجيه للقسم

### 3. تكامل مع نظام المواعيد
- تحديث حالة الموعد تلقائياً
- إرسال رسائل SMS

### 4. تقارير متقدمة
- تقرير يومي بالمبالغ
- تقرير شهري حسب الأقسام
- تقرير أداء الكاشيرات

---

## الاختبار

### اختبار سير العمل الكامل:

1. **تسجيل الدخول كموظف استعلامات**
2. **إنشاء موعد جديد** → `/inquiry/search`
3. **التوجه لصفحة الدفع** (تلقائي)
4. **إدخال بيانات الدفع** → طريقة الدفع + المبلغ
5. **تأكيد الدفع** → إصدار إيصال
6. **طباعة الإيصال** → PDF

---

## الدعم والمساعدة

للاستفسارات أو المشاكل:
- مراجعة ملف الـ logs: `storage/logs/laravel.log`
- التحقق من حالة قاعدة البيانات
- التأكد من تشغيل الـ migrations

---

## ملاحظات مهمة

⚠️ **تذكير:**
- يجب الدفع قبل الذهاب للقسم
- الإيصال دليل الدفع الرسمي
- رقم الإيصال فريد ولا يتكرر
- يتم حفظ جميع العمليات في قاعدة البيانات

✅ **تم التطوير:**
- نظام دفع كامل ومتكامل
- إيصالات احترافية
- واجهات سهلة الاستخدام
- نظام تتبع متقدم

---

**تاريخ التطوير:** 2026-01-04  
**الإصدار:** 1.0  
**المطور:** نظام إدارة المستشفى
