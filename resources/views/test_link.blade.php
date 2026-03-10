<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>اختبار الرابط</title>
</head>
<body>
    <h1>اختبار ظهور الرابط</h1>
    
    @hasrole('consultation_receptionist')
        <p style="color: green; font-weight: bold;">✅ الرابط سيظهر للمستخدم</p>
        <a href="/consultant-availability">توفر الأطباء الاستشاريين</a>
    @endhasrole
    
    @hasrole('admin')
        <p style="color: red;">❌ هذا للأدمن فقط</p>
    @endhasrole
    
    <p>المستخدم الحالي: {{ Auth::user()->name ?? 'غير محدد' }}</p>
    <p>الأدوار: {{ Auth::user() ? implode(', ', Auth::user()->getRoleNames()->toArray()) : 'غير محدد' }}</p>
</body>
</html>