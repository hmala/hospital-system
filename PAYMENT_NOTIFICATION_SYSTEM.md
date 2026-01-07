# نظام الدفع والإشعارات - Payment & Notification System

## نظرة عامة
تم تطوير نظام متكامل لإدارة الدفعات والإشعارات في نظام المستشفى، حيث يتم تنسيق العمل بين موظف الاستعلامات والكاشير.

## سير العمل - Workflow

### 1. حجز الموعد (Appointment Booking)
- **المسؤول**: موظف الاستعلامات (Receptionist)
- **الإجراء**: 
  - يقوم موظف الاستعلامات بحجز موعد للمريض
  - يتم تحديد حالة الدفع على `pending`
  - يتم إرسال إشعار تلقائي للكاشير

### 2. استلام الإشعار (Notification Received)
- **المستلم**: الكاشير/موظف الاستقبال
- **الإشعار**: 
  ```
  💰 موعد جديد بانتظار الدفع
  المريض: [اسم المريض] - الموعد #[رقم الموعد] - المبلغ: [المبلغ] IQD
  ```
- **البيانات المرفقة**:
  - رقم الموعد
  - اسم المريض
  - المبلغ المطلوب
  - اسم موظف الحجز

### 3. الدفع عند الكاشير (Payment Processing)
- **المسؤول**: الكاشير
- **الإجراءات**:
  1. يذهب المريض للكاشير
  2. الكاشير يسجل الدخول ويفتح قائمة "الكاشير"
  3. يظهر عدد المواعيد بانتظار الدفع على القائمة الجانبية
  4. يختار الكاشير الموعد ويقوم بتسجيل الدفع:
     - المبلغ المدفوع
     - طريقة الدفع (نقدي/بطاقة/تحويل بنكي)
     - نوع الدفع (موعد/أشعة/عملية/أخرى)
  5. يتم توليد رقم إيصال تلقائياً (PAY-YYYYMMDD-XXXX)
  6. تتحدث حالة الدفع إلى `paid`

### 4. إصدار الإيصال (Receipt Generation)
- يتم عرض الإيصال تلقائياً بعد إتمام الدفع
- يحتوي على:
  - رقم الإيصال
  - تاريخ ووقت الدفع
  - بيانات المريض
  - بيانات الموعد
  - المبلغ المدفوع
  - طريقة الدفع
  - اسم الكاشير
- يمكن طباعة الإيصال (Ctrl+P)

### 5. إشعار إتمام الدفع (Payment Confirmation)
- **المستلم**: موظف الاستعلامات الذي قام بالحجز
- **الإشعار**:
  ```
  ✅ تم تسديد موعد
  المريض: [اسم المريض] - الموعد #[رقم الموعد] - المبلغ: [المبلغ] IQD - الإيصال: [رقم الإيصال]
  ```
- **البيانات المرفقة**:
  - رقم الموعد
  - رقم الدفع
  - رقم الإيصال
  - اسم المريض
  - المبلغ المدفوع

## المكونات التقنية - Technical Components

### الجداول (Database Tables)

#### 1. payments
```sql
- id (bigint, auto increment, primary key)
- appointment_id (foreign key -> appointments)
- patient_id (foreign key -> patients)
- cashier_id (foreign key -> users)
- amount (decimal 10,2)
- payment_method (enum: cash, card, bank_transfer)
- payment_type (enum: appointment, radiology, surgery, other)
- receipt_number (varchar, unique)
- notes (text, nullable)
- created_at, updated_at
```

#### 2. appointments (إضافة حقول)
```sql
- payment_status (enum: pending, paid) default: pending
- payment_id (foreign key -> payments, nullable)
```

#### 3. notifications (Laravel Default)
```sql
- id (char 36, UUID, primary key)
- type (varchar)
- notifiable_type (varchar) - morphTo relationship
- notifiable_id (bigint)
- data (text, JSON)
- read_at (timestamp, nullable)
- created_at, updated_at
```

### النماذج (Models)

#### 1. Payment
```php
// العلاقات
- patient() -> belongsTo(Patient)
- appointment() -> belongsTo(Appointment)
- cashier() -> belongsTo(User)

// Accessors
- payment_method_name (النقدي، البطاقة، التحويل البنكي)
- payment_type_name (موعد، أشعة، عملية، أخرى)

// Methods
- generateReceiptNumber() - توليد رقم الإيصال
```

#### 2. Notification
```php
// خصائص
- $incrementing = false
- $keyType = 'string' (UUID)

// العلاقات
- notifiable() -> morphTo

// Static Methods
- createForUser($userId, $type, $title, $message, $data = [])
- createForRole($roles, $type, $title, $message, $data = [])
- unreadForUser($userId)
- unreadCountForUser($userId)

// Instance Methods
- markAsRead()
- isUnread()
```

### المتحكمات (Controllers)

#### 1. InquiryController
```php
// يتم تعديل دالة store لإرسال إشعار بعد الحجز
public function store(Request $request)
{
    // ... الحجز
    // إرسال إشعار للكاشير
    Notification::createForRole(['receptionist'], ...);
    // العودة لصفحة الاستعلامات
}
```

#### 2. CashierController
```php
// الدوال الرئيسية
- index() - عرض المواعيد بانتظار الدفع
- showPaymentForm($appointmentId) - عرض نموذج الدفع
- processPayment(Request $request) - معالجة الدفع
- showReceipt($paymentId) - عرض الإيصال
- printReceipt($paymentId) - طباعة الإيصال

// في processPayment يتم:
1. حفظ بيانات الدفع
2. تحديث حالة الموعد
3. إرسال إشعار لموظف الاستعلامات
```

#### 3. NotificationController
```php
- index() - عرض الإشعارات
- markAsRead($id) - تحديد إشعار كمقروء
- markAllAsRead() - تحديد جميع الإشعارات كمقروءة
- destroy($id) - حذف إشعار
- getUnreadCount() - API للحصول على العدد غير المقروء
```

### الواجهات (Views)

#### القائمة الجانبية (Sidebar)
```blade
<li class="nav-item">
    <a href="{{ route('cashier.index') }}" class="nav-link">
        <i class="fas fa-cash-register nav-icon"></i>
        <p>
            الكاشير
            @php $pendingCount = \App\Models\Appointment::where('payment_status', 'pending')->count(); @endphp
            @if($pendingCount > 0)
                <span class="badge badge-warning">{{ $pendingCount }}</span>
            @endif
        </p>
    </a>
</li>
```

#### أيقونة الإشعارات (Navbar)
```blade
<a class="nav-link position-relative" href="{{ route('notifications.index') }}">
    <i class="fas fa-bell fa-lg"></i>
    @php $unreadCount = \App\Models\Notification::unreadCountForUser(Auth::id()); @endphp
    @if($unreadCount > 0)
        <span class="badge rounded-pill bg-danger">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
    @endif
</a>
```

#### صفحات الكاشير
1. **cashier/index.blade.php** - قائمة المواعيد بانتظار الدفع
2. **cashier/payment-form.blade.php** - نموذج الدفع
3. **cashier/receipt.blade.php** - عرض الإيصال
4. **cashier/receipt-print.blade.php** - نسخة الطباعة

#### صفحة الإشعارات
**notifications/index.blade.php** - قائمة الإشعارات مع:
- فلترة حسب المقروء/غير المقروء
- تحديد كمقروء
- حذف
- Pagination

## المسارات (Routes)

### مسارات الكاشير
```php
Route::middleware(['auth'])->prefix('cashier')->name('cashier.')->group(function () {
    Route::get('/', [CashierController::class, 'index'])->name('index');
    Route::get('/payment/{appointment}', [CashierController::class, 'showPaymentForm'])->name('payment-form');
    Route::post('/payment', [CashierController::class, 'processPayment'])->name('process-payment');
    Route::get('/receipt/{payment}', [CashierController::class, 'showReceipt'])->name('receipt');
    Route::get('/receipt/{payment}/print', [CashierController::class, 'printReceipt'])->name('receipt-print');
});
```

### مسارات الإشعارات
```php
Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    Route::get('/unread-count', [NotificationController::class, 'getUnreadCount'])->name('unread-count');
});
```

## الصلاحيات (Permissions)

### الأدوار المطلوبة
- **Admin**: الوصول الكامل
- **Receptionist**: 
  - حجز المواعيد
  - عرض الإشعارات
  - الوصول للكاشير
  - معالجة الدفعات

## اختبار النظام - Testing

### اختبار يدوي
```bash
# تشغيل سكريبت الاختبار
php test_notification_system.php
```

### سيناريو الاختبار الكامل:
1. تسجيل دخول كموظف استعلامات
2. حجز موعد لمريض
3. التحقق من ظهور الإشعار في أيقونة الجرس
4. التحقق من زيادة عدد المواعيد بانتظار الدفع على قائمة الكاشير
5. الانتقال لصفحة الكاشير
6. معالجة الدفع
7. التحقق من إصدار الإيصال
8. التحقق من وصول إشعار إتمام الدفع

## الملفات المضافة/المعدلة

### ملفات جديدة
```
database/migrations/
  - 2026_01_04_100000_create_payments_table.php
  - 2026_01_04_100001_add_payment_fields_to_appointments_table.php

app/Models/
  - Payment.php
  - Notification.php (معدل)

app/Http/Controllers/
  - CashierController.php
  - NotificationController.php (معدل)

resources/views/
  cashier/
    - index.blade.php
    - payment-form.blade.php
    - receipt.blade.php
    - receipt-print.blade.php
  notifications/
    - index.blade.php (معدل)

test_notification_system.php
check_notifications_table.php
PAYMENT_NOTIFICATION_SYSTEM.md (هذا الملف)
```

### ملفات معدلة
```
app/Http/Controllers/InquiryController.php
resources/views/layouts/app.blade.php
routes/web.php
```

## تطويرات مستقبلية - Future Enhancements

1. **تقارير الدفعات**:
   - تقرير يومي بالدفعات
   - تقرير شهري حسب الكاشير
   - إحصائيات طرق الدفع

2. **نظام الإشعارات**:
   - إشعارات بالبريد الإلكتروني
   - إشعارات SMS
   - إشعارات فورية (Real-time) باستخدام WebSockets

3. **طباعة الإيصالات**:
   - دعم PDF باستخدام DomPDF
   - طابعة حرارية مباشرة
   - إرسال الإيصال بالبريد الإلكتروني

4. **الدفعات الجزئية**:
   - إمكانية الدفع على دفعات
   - تتبع المبالغ المتبقية

5. **صندوق الكاشير**:
   - فتح وإغلاق الصندوق
   - تسوية نهاية اليوم
   - تتبع الفروقات

## الدعم والمساعدة

للحصول على المساعدة أو الإبلاغ عن مشاكل:
- مراجعة هذا الملف التوثيقي
- فحص ملفات الـ Logs في `storage/logs`
- استخدام سكريبتات الاختبار المتوفرة

---
**تاريخ الإنشاء**: 2026-01-04  
**الإصدار**: 1.0  
**المطور**: GitHub Copilot
