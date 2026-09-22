@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-file-excel me-2 text-success"></i>استيراد وتحديث الأدوية والمستلزمات من Excel / CSV
            </h2>
            <p class="text-muted small mb-0">استيراد جماعي لقوائم الأدوية أو التسعيرات الرسمية لهيئة الضمان الصحي بملف واحد</p>
        </div>
        <div>
            <a href="{{ route('pharmacy.medicines.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> العودة لدليل الأدوية
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
        </div>
    @endif

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <!-- بطاقة الرفع -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-cloud-upload-alt me-2 text-primary"></i>رفع ملف البيانات
                    </h6>
                    <a href="{{ route('pharmacy.medicines.template') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download me-1"></i> تحميل نموذج جاهز (Template CSV)
                    </a>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('pharmacy.medicines.import.process') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4 text-center p-4 border border-2 border-dashed rounded-3 bg-light">
                            <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                            <h5>اختر ملف Excel (.xlsx, .xls) أو CSV</h5>
                            <p class="text-muted small mb-3">الحد الأقصى لحجم الملف: 10 ميغابايت</p>
                            <input type="file" name="file" class="form-control w-75 mx-auto" accept=".xlsx,.xls,.csv,.txt" required>
                        </div>

                        <div class="text-end">
                            <a href="{{ route('pharmacy.medicines.index') }}" class="btn btn-light border px-4 me-2">إلغاء</a>
                            <button type="submit" class="btn btn-success px-5 shadow-sm">
                                <i class="fas fa-upload me-1"></i> بدء الاستيراد والمعالجة
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- بطاقة الإرشادات وهيكل الأعمدة -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-info-circle me-2 text-info"></i>ترتيب أعمدة الملف المعتمدة
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>اسم الحقل</th>
                                    <th>الوصف والتنسيق</th>
                                    <th>مثال</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td class="fw-bold">الرمز الوطني (National Code)</td>
                                    <td>رمز الدواء الرسمي بهيئة الضمان (إن وجد)</td>
                                    <td><code>01-C00-038</code></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td class="fw-bold">الاسم التجاري (Name)</td>
                                    <td>اسم الدواء أو المستلزم (إجباري)</td>
                                    <td>Amoxicillin 500mg Cap</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td class="fw-bold">الاسم العلمي (Generic Name)</td>
                                    <td>المادة الفعالة والتكافؤ</td>
                                    <td>Amoxicillin Trihydrate</td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td class="fw-bold">الشكل الصيدلاني (Dosage Form)</td>
                                    <td>حبوب، كبسول، شراب، فيال، أمبول، مستلزم</td>
                                    <td>كبسول</td>
                                </tr>
                                <tr>
                                    <td>5</td>
                                    <td class="fw-bold">العيار / التركيز (Strength)</td>
                                    <td>عيار الصنف</td>
                                    <td>500mg</td>
                                </tr>
                                <tr>
                                    <td>6</td>
                                    <td class="fw-bold">الباركود (Barcode)</td>
                                    <td>باركود العلبة أو القطعة</td>
                                    <td>628100123456</td>
                                </tr>
                                <tr>
                                    <td>7</td>
                                    <td class="fw-bold">الوحدة الكبرى (Main Unit)</td>
                                    <td>علبة / قطعة / كرتونة (افتراضي: علبة)</td>
                                    <td>علبة</td>
                                </tr>
                                <tr>
                                    <td>8</td>
                                    <td class="fw-bold">الوحدة الصغرى (Sub Unit)</td>
                                    <td>شريط / حبة / أمبول (افتراضي: شريط)</td>
                                    <td>شريط</td>
                                </tr>
                                <tr>
                                    <td>9</td>
                                    <td class="fw-bold">معامل التحويل (Sub Units Count)</td>
                                    <td>عدد الأشرطة أو الوحدات بالعلبة (افتراضي: 1)</td>
                                    <td>2</td>
                                </tr>
                                <tr>
                                    <td>10</td>
                                    <td class="fw-bold">سعر التكلفة (Cost Price)</td>
                                    <td>سعر شراء العلبة</td>
                                    <td>2000</td>
                                </tr>
                                <tr>
                                    <td>11</td>
                                    <td class="fw-bold">سعر البيع كاش (Sale Price)</td>
                                    <td>سعر بيع العلبة للجمهور بالدينار العراقي</td>
                                    <td>3000</td>
                                </tr>
                                <tr>
                                    <td>12</td>
                                    <td class="fw-bold">سعر بيع الشريط (Sub Unit Sale Price)</td>
                                    <td>سعر بيع الوحدة الصغرى (إن ترك فارغاً يحسب آلياً)</td>
                                    <td>1500</td>
                                </tr>
                                <tr>
                                    <td>13</td>
                                    <td class="fw-bold">سعر الضمان الصحي (HI Price)</td>
                                    <td>السعر المعتمد بهيئة الضمان</td>
                                    <td>2500</td>
                                </tr>
                                <tr>
                                    <td>14</td>
                                    <td class="fw-bold">مشمول بالضمان (Covered)</td>
                                    <td>1 للمشمول، 0 لغير المشمول</td>
                                    <td>1</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
