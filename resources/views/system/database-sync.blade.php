@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- رأس الصفحة -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1">
                    <i class="fas fa-cloud text-primary me-1"></i> هوستنجر ⬅️ اللوكل هوست
                </span>
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1">
                    <i class="fas fa-shield-alt text-success me-1"></i> حماية وتطابق 100%
                </span>
            </div>
            <h2 class="fw-bold mb-1 text-dark">
                <i class="fas fa-sync-alt text-primary me-2"></i> مركز سحب ومزامنة قاعدة البيانات
            </h2>
            <p class="text-muted small mb-0">
                سحب أحدث بيانات المستشفى من سيرفر هوستنجر الأونلاين وتحديث نسختك المحلية في المنزل أو العمل بنقرة زر واحدة.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-3" id="btnTestConnection">
                <i class="fas fa-satellite-dish me-1"></i> فحص الاتصال بسيرفر هوستنجر
            </button>
        </div>
    </div>

    <!-- بطاقات تفاصيل الاتصال والبيئة -->
    <div class="row g-3 mb-4">
        <!-- سيرفر هوستنجر -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                            🌐 السيرفر الأونلاين (المصدر)
                        </span>
                        <span id="remoteStatusBadge" class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-50 rounded-pill px-2 py-1">
                            <i class="fas fa-check-circle me-1"></i> جاهز ومتصل
                        </span>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Hostinger MySQL</h5>
                    <div class="d-flex flex-column gap-1 text-muted small">
                        <div><strong class="text-dark">السيرفر:</strong> <code class="text-primary">{{ $connectionInfo['host'] }}</code></div>
                        <div><strong class="text-dark">القاعدة:</strong> <code class="text-primary">{{ $connectionInfo['database'] }}</code></div>
                        <div><strong class="text-dark">المستخدم:</strong> <code class="text-muted">{{ $connectionInfo['username'] }}</code></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- قاعدة اللوكل الحالية -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                            💻 جهازك المحلي (الهدف)
                        </span>
                        <span class="badge bg-light text-dark border rounded-pill px-2 py-1">
                            WAMP64 Localhost
                        </span>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">MySQL المحلي</h5>
                    <div class="d-flex flex-column gap-1 text-muted small">
                        <div><strong class="text-dark">القاعدة المحلية:</strong> <code class="text-success">{{ $connectionInfo['local_database'] }}</code></div>
                        <div><strong class="text-dark">المنفذ:</strong> <code>3306</code></div>
                        <div><strong class="text-dark">الحالة:</strong> <span class="text-success fw-bold">سيتم تحديثها بالكامل</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الأمان والنسخ الاحتياطي -->
        <div class="col-md-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-success border-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-2 text-success">
                        <i class="fas fa-shield-alt fs-4"></i>
                        <h6 class="fw-bold mb-0">أمان وحماية البيانات التلقائية</h6>
                    </div>
                    <p class="small text-muted mb-0">
                        قبل أن يبدأ النظام بسحب أي بيانات، يقوم تلقائياً بإنشاء نسخة احتياطية كاملة من نسختك المحلية وحفظها في مجلد <code>storage/app/backups</code>، حتى يمكنك الرجوع إليها في أي وقت.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- لوحة السحب الرئيسية (Hero Card) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 text-center p-4 p-md-5 bg-white position-relative overflow-hidden">
        <div class="position-relative z-1 py-3">
            <div class="mb-3">
                <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-2 shadow-sm">
                    <i class="fas fa-cloud-download-alt fa-3x"></i>
                </div>
            </div>
            <h3 class="fw-black text-dark mb-2">سحب وتحديث قاعدة البيانات من هوستنجر</h3>
            <p class="text-muted mx-auto mb-4" style="max-width: 650px;">
                بنقرة واحدة سيتم استيراد كافة الجداول (106 جدول) بكافة بيانات المرضى، الأطباء، الوصولات، الفحوصات والتشخيصات لتطابق السيرفر الحي تماماً.
            </p>

            <button type="button" class="btn btn-primary btn-lg rounded-pill px-5 py-3 shadow-lg fw-bold" id="btnStartPull">
                <i class="fas fa-download me-2"></i> بدء السحب وتحديث اللوكل هوست الآن
            </button>

            <!-- شريط التحميل والتقدم -->
            <div id="pullProgressSection" class="d-none mt-4 mx-auto" style="max-width: 500px;">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">جارٍ السحب...</span>
                </div>
                <h5 class="fw-bold text-primary mb-1" id="pullProgressTitle">جارٍ سحب وتحديث البيانات...</h5>
                <p class="text-muted small mb-3" id="pullProgressSubtitle">
                    يتم أولاً حفظ نسخة احتياطية محلية ثم سحب الجداول وتحديثها. يرجى الانتظار ثوانٍ معدودة وعدم إغلاق الصفحة.
                </p>
                <div class="progress rounded-pill" style="height: 12px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 100%"></div>
                </div>
            </div>

            <!-- رسالة النتيجة -->
            <div id="pullResultAlert" class="alert d-none mt-4 mx-auto rounded-4 text-start shadow-sm" style="max-width: 650px;" role="alert">
                <div class="d-flex align-items-center gap-3">
                    <div id="pullResultIcon" class="fs-2"></div>
                    <div>
                        <h6 class="fw-bold mb-1" id="pullResultTitle"></h6>
                        <div class="small" id="pullResultBody"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول النسخ الاحتياطية المحلية المحفوظة -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-header bg-transparent border-0 p-3 pb-0 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fas fa-history text-secondary me-2"></i> سجل النسخ الاحتياطية المحلية المحفوظة تلقائياً
            </h5>
            <span class="badge bg-light text-secondary border rounded-pill">{{ count($backups) }} نسخ</span>
        </div>
        <div class="card-body p-0 mt-3">
            @if(empty($backups))
                <div class="text-center py-4 text-muted small">
                    <i class="fas fa-folder-open fa-2x mb-2 text-secondary opacity-50"></i>
                    <div>لم يتم إنشاء أي نسخ احتياطية بعد. سيتم أخذ نسخة تلقائياً عند أول عملية سحب.</div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>اسم ملف النسخة</th>
                                <th>الحجم</th>
                                <th>تاريخ ووقت النسخ</th>
                                <th>المسار على جهازك</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($backups as $b)
                                <tr>
                                    <td class="fw-bold text-muted">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="font-monospace fw-bold text-dark">
                                            <i class="fas fa-file-code text-primary me-1"></i>{{ $b['name'] }}
                                        </span>
                                    </td>
                                    <td><span class="badge bg-light text-dark border">{{ $b['size'] }}</span></td>
                                    <td><span class="text-muted"><i class="fas fa-clock me-1"></i>{{ $b['date'] }}</span></td>
                                    <td><small class="text-muted font-monospace">storage/app/backups/{{ $b['name'] }}</small></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnTest = document.getElementById('btnTestConnection');
    const btnPull = document.getElementById('btnStartPull');
    const progressSection = document.getElementById('pullProgressSection');
    const resultAlert = document.getElementById('pullResultAlert');
    const resultIcon = document.getElementById('pullResultIcon');
    const resultTitle = document.getElementById('pullResultTitle');
    const resultBody = document.getElementById('pullResultBody');
    const remoteBadge = document.getElementById('remoteStatusBadge');

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    // 1. فحص الاتصال
    btnTest.addEventListener('click', async function () {
        btnTest.disabled = true;
        btnTest.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جارٍ الفحص...';

        try {
            const res = await fetch('{{ route("system.database-sync.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();

            if (data.success) {
                remoteBadge.className = 'badge bg-success bg-opacity-15 text-success border border-success border-opacity-50 rounded-pill px-2 py-1';
                remoteBadge.innerHTML = `<i class="fas fa-check-circle me-1"></i> متصل بنجاح (${data.latency_ms}ms)`;
                alert(`✅ تم الاتصال بسيرفر هوستنجر بنجاح تام!\n• زمن الاستجابة: ${data.latency_ms} ms\n• عدد الجداول بالسيرفر: ${data.tables_count} جدول\n• عدد المرضى: ${data.patients_count}`);
            } else {
                remoteBadge.className = 'badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-50 rounded-pill px-2 py-1';
                remoteBadge.innerHTML = '<i class="fas fa-times-circle me-1"></i> فشل الاتصال';
                alert('❌ فشل الاتصال بسيرفر هوستنجر: ' + data.message);
            }
        } catch (err) {
            alert('حدث خطأ في الاتصال بالشبكة.');
        } finally {
            btnTest.disabled = false;
            btnTest.innerHTML = '<i class="fas fa-satellite-dish me-1"></i> فحص الاتصال بسيرفر هوستنجر';
        }
    });

    // 2. بدء السحب
    btnPull.addEventListener('click', async function () {
        if (!confirm('⚠️ تأكيد السحب والتحديث:\n\nسيقوم النظام بسحب أحدث نسخة كاملة من قاعدة بيانات هوستنجر واستبدال نسختك المحلية بها.\n(سيتم حفظ نسخة احتياطية من نسختك اللوكل الحالية أولاً).\n\nهل تريد المتابعة الآن؟')) {
            return;
        }

        btnPull.disabled = true;
        btnPull.classList.add('d-none');
        progressSection.classList.remove('d-none');
        resultAlert.classList.add('d-none');

        try {
            const res = await fetch('{{ route("system.database-sync.pull") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await res.json();
            progressSection.classList.add('d-none');
            btnPull.classList.remove('d-none');
            btnPull.disabled = false;

            if (data.success) {
                resultAlert.className = 'alert alert-success mt-4 mx-auto rounded-4 text-start shadow-sm d-block';
                resultIcon.innerHTML = '<i class="fas fa-check-circle text-success"></i>';
                resultTitle.textContent = 'تم سحب وتحديث قاعدة البيانات بنجاح تام! 🎉';
                resultBody.innerHTML = `
                    <div class="mt-1">
                        • تم تحديث <strong>${data.synced_tables}</strong> جدول من أصل ${data.total_tables}.<br>
                        • إجمالي السجلات المستوردة: <strong>${data.total_rows.toLocaleString()}</strong> سجل.<br>
                        • الوقت المستغرق: <strong>${data.elapsed_seconds}</strong> ثانية.<br>
                        • تم حفظ نسخة احتياطية محلية باسم: <code>${data.backup_file || '-'}</code>
                    </div>
                `;
            } else {
                resultAlert.className = 'alert alert-danger mt-4 mx-auto rounded-4 text-start shadow-sm d-block';
                resultIcon.innerHTML = '<i class="fas fa-exclamation-triangle text-danger"></i>';
                resultTitle.textContent = 'فشلت عملية السحب!';
                resultBody.textContent = data.message || 'حدث خطأ غير متوقع.';
            }
        } catch (err) {
            progressSection.classList.add('d-none');
            btnPull.classList.remove('d-none');
            btnPull.disabled = false;
            alert('حدث خطأ في الاتصال بالخادم أثناء السحب: ' + err.message);
        }
    });
});
</script>
@endsection
