@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 px-lg-4">
    <!-- الشريط العلوي لمحطة صرف الأدوية -->
    <div class="card border-0 shadow-sm rounded-4 mb-3 bg-dark text-white p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-success text-white p-2 rounded-3 fs-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="fas fa-pills"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold text-white d-flex align-items-center gap-2">
                        محطة صرف وتجهيز الأدوية (نقطة بيع وصرف الصيدلية)
                        <span class="badge bg-success-subtle text-success fs-6 rounded-pill px-3 py-1 border border-success">
                            <i class="fas fa-satellite-dish me-1 fa-fade"></i> استقبال مباشر
                        </span>
                    </h5>
                    <small class="text-secondary">استقبال الوصفات الطبية الإلكترونية من العيادات وصرفها فورياً دون تعقيدات</small>
                </div>
            </div>

            <!-- المؤشرات وأزرار التحكم السريعة -->
            <div class="d-flex align-items-center flex-wrap gap-2">
                <div class="bg-secondary bg-opacity-25 px-3 py-2 rounded-3 text-center border border-secondary border-opacity-25">
                    <span class="small text-muted d-block">الوصفات قيد الانتظار</span>
                    <span class="fs-5 fw-bold text-warning font-monospace" id="headerPendingCount">
                        {{ $pendingPrescriptionsCount ?? 0 }}
                    </span>
                </div>

                <button type="button" class="btn btn-outline-info btn-sm px-3 py-2 rounded-3" id="btnRefreshQueue" title="تحديث يدوي">
                    <i class="fas fa-sync-alt me-1" id="refreshIcon"></i> تحديث
                </button>

                <button type="button" class="btn btn-outline-light btn-sm px-3 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#manualSearchModal">
                    <i class="fas fa-search me-1"></i> فحص رصيد / بدائل
                </button>

                <a href="{{ route('pharmacy.pos.sales.history') }}" class="btn btn-outline-secondary btn-sm px-3 py-2 rounded-3 text-white">
                    <i class="fas fa-history me-1"></i> سجل الصرف
                </a>
            </div>
        </div>
    </div>

    <!-- شبكة العمل الرئيسية: طابور الوصفات الواردة (يسار) + لوحة تجهيز وصرف الوصفة المحددة (يمين) -->
    <div class="row g-3">
        <!-- القائمة الجانبية: طابور الوصفات الواردة -->
        <div class="col-lg-4 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 d-flex flex-column" style="min-height: 650px;">
                <div class="card-header bg-white py-3 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-6 fw-bold text-dark"><i class="fas fa-inbox text-primary me-2"></i>الوصفات الواردة</span>
                        <span class="badge bg-danger rounded-pill font-monospace" id="queueBadgeCount">{{ $pendingPrescriptionsCount ?? 0 }}</span>
                    </div>
                    <div class="form-check form-switch small mb-0">
                        <input class="form-check-input" type="checkbox" id="autoRefreshSwitch" checked>
                        <label class="form-check-label text-muted small" for="autoRefreshSwitch">تحديث تلقائي</label>
                    </div>
                </div>

                <!-- حقل الفلترة السريعة بالاسم أو رقم الوصفة -->
                <div class="p-2 border-bottom bg-light">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="queueSearchInput" class="form-control border-start-0" placeholder="بحث باسم المريض أو رقم الوصفة...">
                        <button class="btn btn-outline-secondary" type="button" id="btnClearSearch" style="display:none;"><i class="fas fa-times"></i></button>
                    </div>
                </div>

                <!-- قائمة بطاقات الوصفات -->
                <div class="card-body p-2 flex-grow-1 overflow-auto" id="prescriptionsQueueList" style="max-height: 620px;">
                    @if(isset($pendingPrescriptions) && $pendingPrescriptions->count() > 0)
                        @foreach($pendingPrescriptions as $rx)
                            <div class="prescription-queue-card card border rounded-3 p-3 mb-2 shadow-xs cursor-pointer transition-all" 
                                 data-id="{{ $rx->id }}" 
                                 data-rx-number="{{ $rx->prescription_number }}"
                                 data-patient-name="{{ optional($rx->patient)->user->name ?? 'مريض مباشر' }}"
                                 id="rxCard-{{ $rx->id }}"
                                 onclick="selectPrescription({{ $rx->id }})">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fw-bold px-2 py-1">
                                        <i class="fas fa-hashtag me-1"></i>{{ $rx->prescription_number }}
                                    </span>
                                    <span class="badge bg-light text-muted border font-monospace small">
                                        <i class="far fa-clock me-1 text-primary"></i>{{ $rx->created_at ? $rx->created_at->diffForHumans() : '-' }}
                                    </span>
                                </div>
                                <div class="fw-bold text-dark fs-6 mb-1 text-truncate">
                                    <i class="fas fa-user-injured text-secondary me-1"></i>{{ optional($rx->patient)->user->name ?? 'مريض استشارية' }}
                                </div>
                                <div class="small text-muted d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-user-md text-info me-1"></i>{{ optional($rx->doctor)->user->name ?? 'طبيب استشاري' }}</span>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                                        {{ $rx->items->count() }} أدوية
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-5 text-muted" id="emptyQueueMsg">
                            <div class="mb-3 text-secondary opacity-50">
                                <i class="fas fa-clipboard-check fa-3x"></i>
                            </div>
                            <h6 class="fw-bold text-dark">لا توجد وصفات قيد الانتظار</h6>
                            <p class="small text-muted mb-0">الوصفات المحولة من العيادات ستظهر هنا تلقائياً فور تحويلها.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- اللوحة الرئيسية: تفاصيل الوصفة والصرف الفوري -->
        <div class="col-lg-8 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 h-100 d-flex flex-column" style="min-height: 650px;" id="dispensingWorkstation">
                <!-- حالة عدم اختيار وصفة -->
                <div id="noSelectionState" class="p-5 text-center my-auto">
                    <div class="bg-primary-subtle text-primary d-inline-flex p-4 rounded-circle mb-3">
                        <i class="fas fa-hand-holding-medical fa-3x"></i>
                    </div>
                    <h5 class="fw-bold text-dark">اختر وصفة طبية من الطابور لبدء التجهيز والصرف</h5>
                    <p class="text-muted small mx-auto" style="max-width: 480px;">
                        اضغط على أي وصفة واردة في القائمة الجانبية لعرض قائمة الأدوية الموصوفة والجرعات، وتأكيد صرفها بضغطة زر واحدة مع التحديث الآلي للمخزون.
                    </p>
                </div>

                <!-- شاشة تفاصيل الوصفة المحددة (تظهر عند الاختيار) -->
                <div id="activePrescriptionView" class="d-none flex-grow-1 d-flex flex-column">
                    <!-- الترويسة: بيانات المريض والطبيب والتشخيص -->
                    <div class="card-header bg-white py-3 px-4 border-bottom">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-primary px-3 py-2 fs-6 font-monospace" id="dispRxNumber">RX-0000</span>
                                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1" id="dispRxStatus">جاهزة للصرف</span>
                                    <span class="small text-muted font-monospace" id="dispRxTime">-</span>
                                </div>
                                <h4 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="dispPatientName">
                                    اسم المريض
                                </h4>
                            </div>

                            <div class="text-start text-md-end">
                                <div class="text-dark fw-bold mb-1">
                                    <i class="fas fa-user-md text-primary me-1"></i> <span id="dispDoctorName">د. الطبيب</span>
                                </div>
                                <div class="small text-muted" id="dispClinicSpecialty">
                                    عيادة الاستشارية
                                </div>
                            </div>
                        </div>

                        <!-- ملاحظات / التشخيص إن وجد -->
                        <div class="alert alert-light border border-info-subtle rounded-3 p-2 mt-3 mb-0 d-flex align-items-center gap-2 small" id="diagnosisBox">
                            <i class="fas fa-stethoscope text-info fs-5"></i>
                            <div>
                                <strong class="text-dark">التشخيص / التوصيات:</strong>
                                <span class="text-muted ms-1" id="dispDiagnosisText">لا يوجد تشخيص مسجل</span>
                            </div>
                        </div>

                        <!-- إشعار موافقة / رفض الطبيب على البديل -->
                        <div id="rxSubstitutionFeedbackBanner" class="mt-3 mb-0" style="display: none;"></div>
                    </div>

                    <!-- جدول الأدوية المطلوب صرفها -->
                    <div class="card-body p-3 flex-grow-1 overflow-auto">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark mb-0">
                                <i class="fas fa-tablets text-primary me-2"></i>الأدوية والجرعات المطلوب تجهيزها:
                            </h6>
                            <span class="badge bg-secondary rounded-pill font-monospace" id="dispItemsCount">0 أدوية</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0" id="dispensingItemsTable">
                                <thead class="table-light text-muted small">
                                    <tr>
                                        <th style="width: 5%;" class="text-center">#</th>
                                        <th style="width: 35%;">اسم الدواء والشكل</th>
                                        <th style="width: 15%;" class="text-center">الكمية المطلوبة</th>
                                        <th style="width: 25%;">الجرعة وتعليمات الطبيب</th>
                                        <th style="width: 20%;" class="text-center">حالة التوفر بالمخزون</th>
                                    </tr>
                                </thead>
                                <tbody id="dispensingItemsBody">
                                    <!-- يتم بناؤه بالجافاسكريبت -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- تذييل العمليات: زر الصرف الفوري وطباعة الملصق -->
                    <div class="card-footer bg-light p-3 border-top mt-auto">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="text-muted small">
                                <i class="fas fa-shield-alt text-success me-1"></i> يتم خصم الأرصدة تلقائياً بنظام FEFO (الأقرب انتهاءً أولاً) وإشعار محطة الطبيب فوراً.
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <a href="#" target="_blank" class="btn btn-outline-dark px-3 py-2 fw-bold rounded-3" id="btnPrintPrescriptionBtn">
                                    <i class="fas fa-print me-1"></i> طباعة ملصق / راشيتة
                                </a>

                                <button type="button" class="btn btn-success btn-lg px-4 py-2 fw-bold shadow-sm rounded-3 d-flex align-items-center gap-2" id="btnExecuteDispense" onclick="executeDispense()">
                                    <i class="fas fa-check-circle fa-lg"></i>
                                    <span>تأكيد صرف وتجهيز الدواء</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: فحص رصيد وبدائل دواء سريع -->
<div class="modal fade" id="manualSearchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-search me-2 text-info"></i>فحص سريع لرصيد الأدوية والبدائل المتاحة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="input-group input-group-lg mb-3">
                    <span class="input-group-text bg-primary text-white"><i class="fas fa-barcode"></i></span>
                    <input type="text" id="quickSearchMedInput" class="form-control" placeholder="امسح الباركود أو اكتب اسم الدواء / المادة الفعالة..." autocomplete="off">
                </div>

                <div id="quickSearchResultsList" class="list-group shadow-xs" style="max-height: 380px; overflow-y: auto;">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-search fa-2x mb-2 opacity-50"></i>
                        <p class="small mb-0">اكتب اسم أي دواء أو امسح الباركود للتحقق من الرصيد والبدائل</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: استبدال الدواء ببديل مكافئ متوفر -->
<div class="modal fade" id="alternativeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-exchange-alt me-2"></i>
                    اختيار واقتراح بديل للدواء: <span id="modalTargetMedName" class="text-primary font-monospace">-</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <!-- حقل البحث المباشر في كامل دليل الأدوية -->
                <div class="input-group mb-3">
                    <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="modalAltSearchInput" class="form-control" placeholder="ابحث في دليل الأدوية الكامل بالاسم التجاري أو العلمي أو التركيز...">
                </div>

                <!-- سبب / ملاحظة اقتراح البديل -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted mb-1">
                        <i class="fas fa-comment-medical text-primary me-1"></i>
                        ملاحظة / سبب الاستبدال الموجه للطبيب (اختياري):
                    </label>
                    <input type="text" id="modalAltReasonInput" class="form-control form-control-sm" placeholder="مثال: غير متوفر الصنف الأصلي - متوفر نفس المادة الفعالة بشركة بديلة">
                </div>

                <!-- قائمة البدائل المقترحة والنتائج -->
                <h6 class="fw-bold text-dark mb-2 small d-flex align-items-center justify-content-between">
                    <span><i class="fas fa-list-check text-success me-1"></i> البدائل المتاحة للاختيار:</span>
                    <span class="badge bg-secondary-subtle text-secondary" id="modalAltCountBadge">0 بديل</span>
                </h6>

                <div class="list-group shadow-xs" id="alternativesModalList" style="max-height: 320px; overflow-y: auto;">
                    <!-- Dynamic Alternatives List -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- صوت تنبيه خفيف عند اكتمال الصرف -->
<audio id="dispenseAudio" preload="auto">
    <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
</audio>
@endsection

@push('scripts')
<script>
    let currentSelectedRxId = null;
    let currentPrescriptionData = null;
    let autoRefreshTimer = null;
    let targetItemForAlternative = null;

    document.addEventListener('DOMContentLoaded', function() {
        // تشغيل التحديث التلقائي كل 10 ثوان
        startAutoRefresh();

        // التبديل اليدوي للتحديث التلقائي
        document.getElementById('autoRefreshSwitch').addEventListener('change', function(e) {
            if (e.target.checked) {
                startAutoRefresh();
            } else {
                clearInterval(autoRefreshTimer);
            }
        });

        // زر التحديث اليدوي
        document.getElementById('btnRefreshQueue').addEventListener('click', function() {
            refreshPrescriptionsQueue(true);
        });

        // بحث وتصفية قائمة الوصفات محلياً
        const searchInput = document.getElementById('queueSearchInput');
        const clearBtn = document.getElementById('btnClearSearch');

        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            clearBtn.style.display = query.length > 0 ? 'inline-block' : 'none';

            document.querySelectorAll('.prescription-queue-card').forEach(card => {
                const rxNum = (card.getAttribute('data-rx-number') || '').toLowerCase();
                const patName = (card.getAttribute('data-patient-name') || '').toLowerCase();
                if (rxNum.includes(query) || patName.includes(query)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            clearBtn.style.display = 'none';
            document.querySelectorAll('.prescription-queue-card').forEach(card => card.style.display = '');
        });

        // نافذة فحص الأدوية السريعة
        const quickSearchMedInput = document.getElementById('quickSearchMedInput');
        quickSearchMedInput.addEventListener('input', debounce(function() {
            const q = this.value.trim();
            if (q.length < 1) {
                document.getElementById('quickSearchResultsList').innerHTML = `
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-search fa-2x mb-2 opacity-50"></i>
                        <p class="small mb-0">اكتب اسم أي دواء أو امسح الباركود للتحقق من الرصيد والبدائل</p>
                    </div>`;
                return;
            }

            fetch(`{{ route('pharmacy.pos.search') }}?q=${encodeURIComponent(q)}`)
                .then(res => res.json())
                .then(data => {
                    renderQuickSearchResults(data.medicines || []);
                });
        }, 300));

        // البحث عن بدائل داخل مودال البدائل
        const modalAltSearchInput = document.getElementById('modalAltSearchInput');
        if (modalAltSearchInput) {
            modalAltSearchInput.addEventListener('input', debounce(function() {
                const q = this.value.trim();
                if (q.length < 1) {
                    if (targetItemForAlternative && currentPrescriptionData) {
                        const item = currentPrescriptionData.items.find(i => i.id === targetItemForAlternative);
                        renderAlternativesList(item ? (item.alternatives || []) : []);
                    }
                    return;
                }

                fetch(`{{ route('pharmacy.pos.search') }}?q=${encodeURIComponent(q)}`)
                    .then(res => res.json())
                    .then(data => {
                        renderAlternativesList(data.medicines || []);
                    });
            }, 300));
        }

        // إذا كانت هناك وصفة أولى بالقائمة، اخترها تلقائياً لتسهيل الاستخدام
        const firstCard = document.querySelector('.prescription-queue-card');
        if (firstCard) {
            const firstId = firstCard.getAttribute('data-id');
            selectPrescription(firstId);
        }
    });

    function startAutoRefresh() {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = setInterval(() => {
            refreshPrescriptionsQueue(false);
        }, 10000);
    }

    // تحديث طابور الوصفات بالـ AJAX
    function refreshPrescriptionsQueue(showSpinner = false) {
        const refreshIcon = document.getElementById('refreshIcon');
        if (showSpinner && refreshIcon) refreshIcon.classList.add('fa-spin');

        fetch(`{{ route('pharmacy.pos.pending-prescriptions') }}`)
            .then(res => res.json())
            .then(data => {
                if (showSpinner && refreshIcon) refreshIcon.classList.remove('fa-spin');
                if (data.success) {
                    updateQueueUI(data.prescriptions || [], data.count || 0);
                }
            })
            .catch(err => {
                if (showSpinner && refreshIcon) refreshIcon.classList.remove('fa-spin');
                console.error('Error fetching pending prescriptions:', err);
            });
    }

    // رسم بطاقات الطابور
    function updateQueueUI(prescriptions, count) {
        document.getElementById('headerPendingCount').innerText = count;
        document.getElementById('queueBadgeCount').innerText = count;

        const container = document.getElementById('prescriptionsQueueList');
        if (prescriptions.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5 text-muted" id="emptyQueueMsg">
                    <div class="mb-3 text-secondary opacity-50">
                        <i class="fas fa-clipboard-check fa-3x"></i>
                    </div>
                    <h6 class="fw-bold text-dark">لا توجد وصفات قيد الانتظار</h6>
                    <p class="small text-muted mb-0">الوصفات المحولة من العيادات ستظهر هنا تلقائياً فور تحويلها.</p>
                </div>`;
            
            if (currentSelectedRxId) {
                deselectPrescription();
            }
            return;
        }

        let html = '';
        prescriptions.forEach(rx => {
            const isSelected = currentSelectedRxId == rx.id ? 'border-primary bg-primary-subtle shadow-sm' : '';
            
            let subStatusBadge = '';
            if (rx.has_approved_sub) {
                subStatusBadge = `
                    <div class="mt-2 pt-2 border-top">
                        <span class="badge bg-success text-white w-100 py-1 d-flex align-items-center justify-content-center gap-1 shadow-xs">
                            <i class="fas fa-check-circle fa-bounce"></i> موافقة الطبيب على البديل ✅ جاهز للصرف
                        </span>
                    </div>`;
            } else if (rx.has_pending_sub) {
                subStatusBadge = `
                    <div class="mt-2 pt-2 border-top">
                        <span class="badge bg-warning text-dark w-100 py-1 d-flex align-items-center justify-content-center gap-1 shadow-xs">
                            <i class="fas fa-hourglass-half fa-spin"></i> بانتظار موافقة الطبيب على البديل
                        </span>
                    </div>`;
            } else if (rx.has_rejected_sub) {
                subStatusBadge = `
                    <div class="mt-2 pt-2 border-top">
                        <span class="badge bg-danger text-white w-100 py-1 d-flex align-items-center justify-content-center gap-1 shadow-xs">
                            <i class="fas fa-times-circle"></i> رفض الطبيب البديل ❌
                        </span>
                    </div>`;
            }

            html += `
                <div class="prescription-queue-card card border rounded-3 p-3 mb-2 shadow-xs cursor-pointer transition-all ${isSelected} ${rx.has_approved_sub ? 'border-success' : ''}" 
                     data-id="${rx.id}" 
                     data-rx-number="${rx.prescription_number}"
                     data-patient-name="${rx.patient_name}"
                     id="rxCard-${rx.id}"
                     onclick="selectPrescription(${rx.id})">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fw-bold px-2 py-1">
                            <i class="fas fa-hashtag me-1"></i>${rx.prescription_number}
                        </span>
                        <span class="badge bg-light text-muted border font-monospace small">
                            <i class="far fa-clock me-1 text-primary"></i>${rx.time_ago}
                        </span>
                    </div>
                    <div class="fw-bold text-dark fs-6 mb-1 text-truncate">
                        <i class="fas fa-user-injured text-secondary me-1"></i>${rx.patient_name}
                    </div>
                    <div class="small text-muted d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-md text-info me-1"></i>${rx.doctor_name}</span>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                            ${rx.items_count} أدوية
                        </span>
                    </div>
                    ${subStatusBadge}
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // اختيار وصفة من القائمة وتحميل تفاصيلها الكاملة
    function selectPrescription(rxId) {
        currentSelectedRxId = rxId;

        // تحديث المظهر المرئي للبطاقات
        document.querySelectorAll('.prescription-queue-card').forEach(card => {
            if (card.getAttribute('data-id') == rxId) {
                card.classList.add('border-primary', 'bg-primary-subtle', 'shadow-sm');
            } else {
                card.classList.remove('border-primary', 'bg-primary-subtle', 'shadow-sm');
            }
        });

        // جلب تفاصيل الوصفة بالـ AJAX
        fetch(`{{ url('pharmacy/pos/prescriptions') }}/${rxId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.prescription) {
                    renderActivePrescription(data.prescription);
                }
            })
            .catch(err => {
                console.error('Error loading prescription:', err);
            });
    }

    // تفريغ الشاشة عند عدم وجود وصفة
    function deselectPrescription() {
        currentSelectedRxId = null;
        currentPrescriptionData = null;
        document.getElementById('noSelectionState').classList.remove('d-none');
        document.getElementById('activePrescriptionView').classList.add('d-none');
    }

    // عرض تفاصيل الوصفة في لوحة الصرف
    function renderActivePrescription(rx) {
        currentPrescriptionData = rx;

        document.getElementById('noSelectionState').classList.add('d-none');
        document.getElementById('activePrescriptionView').classList.remove('d-none');

        document.getElementById('dispRxNumber').innerText = rx.prescription_number;
        document.getElementById('dispPatientName').innerText = rx.patient_name || 'مريض';
        document.getElementById('dispDoctorName').innerText = 'د. ' + (rx.doctor_name || 'الاستشاري');
        document.getElementById('dispDiagnosisText').innerText = rx.diagnosis || rx.notes || 'لا يوجد تشخيص إضافي مسجل';
        document.getElementById('dispItemsCount').innerText = (rx.items ? rx.items.length : 0) + ' أدوية';

        // فحص إشعارات موافقة أو رفض الطبيب على البدائل
        const feedbackBanner = document.getElementById('rxSubstitutionFeedbackBanner');
        if (feedbackBanner) {
            const approvedItems = (rx.items || []).filter(i => i.substitution_status === 'approved');
            const pendingItems = (rx.items || []).filter(i => i.substitution_status === 'pending_approval');
            const rejectedItems = (rx.items || []).filter(i => i.substitution_status === 'rejected');

            if (approvedItems.length > 0) {
                feedbackBanner.style.display = 'block';
                feedbackBanner.innerHTML = `
                    <div class="alert alert-success border-2 border-success shadow-sm rounded-3 p-3 d-flex align-items-center gap-3">
                        <div class="bg-success text-white p-2 rounded-circle fs-4 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-check-double fa-bounce"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-success fs-6">✅ وافق الطبيب المعالج على استبدال (${approvedItems.length}) دواء!</strong>
                                <span class="badge bg-success text-white">معتمد وجاهز للصرف</span>
                            </div>
                            <div class="small text-dark mt-1">
                                ${approvedItems.map(ai => `<span class="me-3"><i class="fas fa-pills text-success me-1"></i><strong>${ai.name}</strong> ${ai.substitution_response_notes ? `(${ai.substitution_response_notes})` : ''}</span>`).join('')}
                            </div>
                            <small class="text-muted d-block mt-1">تم تعديل الوصفة الطبية رسمياً، اضغط على <strong>«تأكيد صرف وتجهيز الدواء»</strong> أدناه لإتمام الصرف وخصم المخزون.</small>
                        </div>
                    </div>`;
            } else if (pendingItems.length > 0) {
                feedbackBanner.style.display = 'block';
                feedbackBanner.innerHTML = `
                    <div class="alert alert-warning border-2 border-warning shadow-sm rounded-3 p-3 d-flex align-items-center gap-3">
                        <div class="bg-warning text-dark p-2 rounded-circle fs-4 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-hourglass-half fa-spin"></i>
                        </div>
                        <div>
                            <strong class="text-dark fs-6">⏳ بانتظار موافقة الطبيب على البديل المقترح</strong>
                            <small class="text-muted d-block">تم إرسال المقترح إلى شاشة الطبيب. ستصلك الموافقة هنا تلقائياً فور اعتمادها من قبل الطبيب.</small>
                        </div>
                    </div>`;
            } else if (rejectedItems.length > 0) {
                feedbackBanner.style.display = 'block';
                feedbackBanner.innerHTML = `
                    <div class="alert alert-danger border-2 border-danger shadow-sm rounded-3 p-3 d-flex align-items-center gap-3">
                        <div class="bg-danger text-white p-2 rounded-circle fs-4 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div>
                            <strong class="text-danger fs-6">❌ رفض الطبيب المعالج مقترح البديل:</strong>
                            <small class="text-dark d-block">${rejectedItems.map(ri => ri.substitution_response_notes || 'يرجى البحث عن الصنف الأصلي أو اقتراح بديل آخر').join(', ')}</small>
                        </div>
                    </div>`;
            } else {
                feedbackBanner.style.display = 'none';
            }
        }

        // رابط الطباعة
        const printBtn = document.getElementById('btnPrintPrescriptionBtn');
        printBtn.href = `{{ url('doctor/visits') }}/${rx.patient_id}/prescription/print`;

        // رسم جدول الأدوية
        const tbody = document.getElementById('dispensingItemsBody');
        let html = '';

        rx.items.forEach((item, index) => {
            const hasStock = item.is_in_stock;
            let stockBadge = '';

            if (item.substitution_status === 'pending_approval') {
                stockBadge = `
                    <div class="d-flex flex-column gap-1 align-items-center">
                        <span class="badge bg-warning text-dark border border-warning px-2 py-1 shadow-xs mb-1">
                            <i class="fas fa-hourglass-half fa-spin me-1"></i> بانتظار موافقة الطبيب
                        </span>
                        <small class="text-muted font-monospace mb-1">مقترح: <strong>${item.suggested_medicine_name || 'بديل'}</strong></small>
                        <button type="button" class="btn btn-xs btn-outline-warning text-dark fw-bold py-1 px-2 rounded shadow-xs" onclick="openAlternativesModal(${item.id})">
                            <i class="fas fa-exchange-alt me-1"></i> تغيير البديل المقترح
                        </button>
                    </div>`;
            } else if (item.substitution_status === 'approved') {
                stockBadge = `
                    <div class="d-flex flex-column gap-1 align-items-center">
                        <span class="badge bg-success text-white px-2 py-1 shadow-xs mb-1">
                            <i class="fas fa-check-circle me-1"></i> وافق الطبيب على البديل ✅
                        </span>
                        <small class="text-success font-monospace">معتمد للصرف الفوري</small>
                    </div>`;
            } else if (item.substitution_status === 'rejected') {
                stockBadge = `
                    <div class="d-flex flex-column gap-1 align-items-center">
                        <span class="badge bg-danger text-white px-2 py-1 shadow-xs mb-1">
                            <i class="fas fa-times-circle me-1"></i> رفض الطبيب البديل ❌
                        </span>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-xs btn-outline-success py-1 px-2 rounded shadow-xs" onclick="toggleItemStock(${item.id})">
                                <i class="fas fa-check me-1"></i> توفر الأصلي
                            </button>
                            <button type="button" class="btn btn-xs btn-warning text-dark fw-bold py-1 px-2 rounded shadow-xs" onclick="openAlternativesModal(${item.id})">
                                <i class="fas fa-exchange-alt me-1"></i> اقتراح بديل آخر
                            </button>
                        </div>
                    </div>`;
            } else if (hasStock) {
                stockBadge = `
                    <div class="d-flex flex-column gap-1 align-items-center">
                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1 mb-1">
                            <i class="fas fa-check-circle me-1"></i> متوفر (${item.total_stock > 0 ? item.total_stock + ' علبة' : 'على الرف'})
                        </span>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-xs btn-outline-danger py-1 px-2 rounded shadow-xs" onclick="toggleItemStock(${item.id})" title="تحديد كـ غير متوفر على الرف">
                                <i class="fas fa-times-circle me-1"></i> غير متوفر
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-warning text-dark fw-bold py-1 px-2 rounded shadow-xs" onclick="openAlternativesModal(${item.id})" title="اقتراح بديل مكافئ لهذا الدواء">
                                <i class="fas fa-exchange-alt me-1"></i> اقتراح بديل
                            </button>
                        </div>
                    </div>`;
            } else {
                stockBadge = `
                    <div class="d-flex flex-column gap-1 align-items-center">
                        <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1 mb-1">
                            <i class="fas fa-times-circle me-1"></i> غير متوفر
                        </span>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-xs btn-outline-success py-1 px-2 rounded shadow-xs" onclick="toggleItemStock(${item.id})" title="تحديد كـ متوفر على الرف">
                                <i class="fas fa-check-circle me-1"></i> متوفر على الرف
                            </button>
                            <button type="button" class="btn btn-xs btn-warning text-dark fw-bold py-1 px-2 rounded shadow-xs" onclick="openAlternativesModal(${item.id})" title="فتح نافذة اختيار واقتراح البديل">
                                <i class="fas fa-exchange-alt me-1"></i> اقتراح بديل للطبيب
                            </button>
                        </div>
                    </div>`;
            }

            html += `
                <tr id="dispRow-${item.id}" class="${!hasStock && item.substitution_status !== 'approved' ? 'table-warning' : ''}">
                    <td class="text-center fw-bold text-muted">${index + 1}</td>
                    <td>
                        <div class="fw-bold text-dark fs-6" id="medName-${item.id}">
                            ${item.name}
                            ${item.substitution_status === 'approved' ? '<span class="badge bg-success ms-1">بديل معتمد من الطبيب</span>' : ''}
                        </div>
                        <div class="small text-muted">
                            ${item.generic_name ? `<span class="badge bg-light text-secondary border me-1">${item.generic_name}</span>` : ''}
                            ${item.dosage_form ? `<span class="text-secondary">${item.dosage_form}</span>` : ''}
                            ${item.strength ? `<span class="text-secondary ms-1">(${item.strength})</span>` : ''}
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-primary fs-6 font-monospace px-3 py-1">
                            ${item.quantity} ${item.unit_type === 'sub_unit' ? (item.sub_unit || 'شريط') : (item.main_unit || 'علبة')}
                        </span>
                    </td>
                    <td>
                        <div class="fw-bold text-primary">
                            <i class="fas fa-clock me-1"></i>${item.dosage_frequency || 'حسب إرشادات الطبيب'}
                        </div>
                        ${item.instructions ? `<div class="small text-muted"><i class="fas fa-info-circle me-1"></i>${item.instructions}</div>` : ''}
                        ${item.duration_days ? `<div class="small text-muted"><i class="far fa-calendar-alt me-1"></i>لمدة ${item.duration_days} يوم</div>` : ''}
                    </td>
                    <td class="text-center" id="stockCol-${item.id}">
                        ${stockBadge}
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    // فتح نافذة اختيار البدائل
    function openAlternativesModal(itemId) {
        targetItemForAlternative = itemId;
        const item = currentPrescriptionData.items.find(i => i.id === itemId);
        if (!item) return;

        document.getElementById('modalTargetMedName').innerText = item.name;
        document.getElementById('modalAltReasonInput').value = `عدم توفر دواء (${item.name}) - مقترح البديل`;
        document.getElementById('modalAltSearchInput').value = '';

        renderAlternativesList(item.alternatives || []);

        const modal = new bootstrap.Modal(document.getElementById('alternativeModal'));
        modal.show();
    }

    // رسم قائمة البدائل داخل المودال
    function renderAlternativesList(alternatives) {
        const listContainer = document.getElementById('alternativesModalList');
        const badge = document.getElementById('modalAltCountBadge');
        if (badge) badge.innerText = alternatives.length + ' بديل';

        if (!alternatives || alternatives.length === 0) {
            listContainer.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-search-plus fa-2x mb-2 text-warning"></i>
                    <p class="small mb-1 fw-bold">لا توجد بدائل مكافئة مسبقة</p>
                    <p class="small text-muted mb-0">استخدم شريط البحث أعلاه للبحث عن أي دواء بديل من الدليل الرسمي.</p>
                </div>
            `;
            return;
        }

        let html = '';
        alternatives.forEach(alt => {
            const hasStock = (alt.total_stock > 0 || alt.total_open_sub_units > 0);
            html += `
                <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center p-3 gap-2">
                    <div>
                        <div class="fw-bold text-dark fs-6">${alt.name}</div>
                        <div class="small text-muted">${alt.generic_name || ''} - ${alt.dosage_form || ''} (${alt.strength || ''})</div>
                        <div class="small font-monospace ${hasStock ? 'text-success' : 'text-secondary'}">
                            <i class="fas fa-boxes me-1"></i> ${hasStock ? `رصيد النظام: ${alt.total_stock} ${alt.main_unit || 'علبة'}` : 'متوفر على الرف'}
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-primary fw-bold px-3 py-2 shadow-sm" onclick="sendAlternativeProposalToDoctor(${alt.id}, '${escapeHtml(alt.name)}')">
                            <i class="fas fa-paper-plane me-1"></i> إرسال اقتراح للطبيب
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success fw-bold px-2 py-2" onclick="applyAlternative(${alt.id}, '${escapeHtml(alt.name)}')">
                            <i class="fas fa-check me-1"></i> استبدال مباشر
                        </button>
                    </div>
                </div>
            `;
        });

        listContainer.innerHTML = html;
    }

    // إرسال طلب اقتراح البديل إلى شاشة الطبيب للموافقة
    function sendAlternativeProposalToDoctor(altId, altName) {
        if (!targetItemForAlternative || !currentPrescriptionData) return;

        const reasonInput = document.getElementById('modalAltReasonInput');
        const customReason = reasonInput ? reasonInput.value.trim() : '';
        const finalReason = customReason.length > 0 
            ? customReason 
            : `عدم توفر الصنف الأصلي - مقترح البديل المكافئ (${altName})`;

        fetch(`{{ url('pharmacy/pos/prescription-items') }}/${targetItemForAlternative}/suggest-alternative`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                suggested_medicine_id: altId,
                substitution_reason: finalReason
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // إغلاق المودال
                const modalEl = document.getElementById('alternativeModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                Swal.fire({
                    icon: 'success',
                    title: 'تم إرسال الطلب للطبيب 📨',
                    text: data.message,
                    timer: 3500,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false
                });

                // تحديث حالة البند محلياً
                const item = currentPrescriptionData.items.find(i => i.id === targetItemForAlternative);
                if (item) {
                    item.substitution_status = 'pending_approval';
                    item.suggested_medicine_name = altName;
                }
                renderActivePrescription(currentPrescriptionData);

            } else {
                Swal.fire({ icon: 'error', title: 'خطأ', text: data.message || 'تعذر إرسال الطلب للطبيب.' });
            }
        })
        .catch(err => {
            console.error('Error suggesting alternative:', err);
            Swal.fire({ icon: 'error', title: 'خطأ', text: 'تعذر الاتصال بالخادم.' });
        });
    }

    // تطبيق استبدال الدواء المباشر في شاشة الصرف
    function applyAlternative(altId, altName) {
        if (!targetItemForAlternative || !currentPrescriptionData) return;

        const item = currentPrescriptionData.items.find(i => i.id === targetItemForAlternative);
        if (item) {
            item.medicine_id = altId;
            item.name = altName + ' (بديل)';
            item.is_in_stock = true;
            item.substitution_status = 'approved';

            // تحديث الصف
            document.getElementById(`medName-${item.id}`).innerHTML = `${altName} <span class="badge bg-warning text-dark ms-1">بديل معتمد</span>`;
            document.getElementById(`stockCol-${item.id}`).innerHTML = `<span class="badge bg-success-subtle text-success border border-success px-2 py-1"><i class="fas fa-check-circle me-1"></i> متوفر البديل</span>`;
            document.getElementById(`dispRow-${item.id}`).classList.remove('table-warning');
        }

        // إغلاق المودال
        const modalEl = document.getElementById('alternativeModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }

    // تبديل حالة توفر الصنف يدوياً (متوفر / غير متوفر)
    function toggleItemStock(itemId) {
        if (!currentPrescriptionData) return;
        const item = currentPrescriptionData.items.find(i => i.id === itemId);
        if (item) {
            item.is_in_stock = !item.is_in_stock;
            // إذا كان تم تحديد توفره وكان غير معتمد، إزالة وسم الرفض
            if (item.is_in_stock && item.substitution_status === 'rejected') {
                item.substitution_status = 'none';
            }
            renderActivePrescription(currentPrescriptionData);
        }
    }

    // تنفيذ عملية الصرف الفوري بضغطة زر واحدة
    function executeDispense() {
        if (!currentSelectedRxId || !currentPrescriptionData) {
            Swal.fire({ icon: 'warning', title: 'تنبيه', text: 'يرجى اختيار وصفة أولاً.' });
            return;
        }

        const btn = document.getElementById('btnExecuteDispense');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> جاري الصرف وتحديث المخزون...`;

        // تجهيز بيانات البنود وحالات التوفر
        const payload = {
            _token: '{{ csrf_token() }}',
            items: currentPrescriptionData.items.map(i => ({
                id: i.id,
                medicine_id: i.medicine_id,
                quantity: i.quantity,
                unit_type: i.unit_type || 'main_unit',
                is_available: i.is_in_stock
            }))
        };

        fetch(`{{ url('pharmacy/pos/prescriptions') }}/${currentSelectedRxId}/dispense`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;

            if (data.success) {
                // تشغيل نغمة التأكيد
                try {
                    document.getElementById('dispenseAudio').play();
                } catch(e){}

                // إشعار نجاح فوري
                Swal.fire({
                    icon: 'success',
                    title: 'تم الصرف بنجاح! ✅',
                    text: data.message,
                    timer: 2500,
                    showConfirmButton: false,
                    position: 'top-end',
                    toast: true
                });

                // إزالة الوصفة من الطابور فورياً
                const card = document.getElementById(`rxCard-${currentSelectedRxId}`);
                if (card) {
                    card.style.transition = 'all 0.4s ease';
                    card.style.transform = 'scale(0.9)';
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 400);
                }

                // تحديث الطابور واختيار الوصفة التالية إن وجدت
                setTimeout(() => {
                    refreshPrescriptionsQueue(false);
                    const remainingCards = document.querySelectorAll('.prescription-queue-card');
                    if (remainingCards.length > 0) {
                        const nextId = remainingCards[0].getAttribute('data-id');
                        if (nextId != currentSelectedRxId) {
                            selectPrescription(nextId);
                        } else if (remainingCards.length > 1) {
                            selectPrescription(remainingCards[1].getAttribute('data-id'));
                        } else {
                            deselectPrescription();
                        }
                    } else {
                        deselectPrescription();
                    }
                }, 500);

            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'تعذر الصرف',
                    text: data.message || 'حدث خطأ أثناء صرف الوصفة.'
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            Swal.fire({ icon: 'error', title: 'خطأ في الاتصال', text: 'تعذر الاتصال بالخادم.' });
            console.error('Dispense error:', err);
        });
    }

    // نتائج البحث السريع في المودال
    function renderQuickSearchResults(medicines) {
        const container = document.getElementById('quickSearchResultsList');
        if (medicines.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-exclamation-circle fa-2x mb-2 text-warning"></i>
                    <p class="small mb-0">لم يتم العثور على أدوية مطابقة لبحثك</p>
                </div>`;
            return;
        }

        let html = '';
        medicines.forEach(m => {
            const hasStock = (m.total_stock > 0 || m.total_open_sub_units > 0);
            html += `
                <div class="list-group-item p-3">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div>
                            <span class="fw-bold text-dark fs-6">${m.name}</span>
                            <span class="badge bg-light text-secondary border font-monospace ms-1">${m.national_code || m.barcode || ''}</span>
                        </div>
                        <span class="badge ${hasStock ? 'bg-success' : 'bg-danger'} font-monospace px-2 py-1">
                            ${hasStock ? `متوفر: ${m.total_stock} ${m.main_unit}` : 'نفذ الرصيد'}
                        </span>
                    </div>
                    <div class="small text-muted mb-1">
                        المادة الفعالة: ${m.generic_name || '-'} | الشكل: ${m.dosage_form || '-'} (${m.strength || '-'})
                    </div>
                    ${m.earliest_batch ? `
                        <div class="small text-primary font-monospace">
                            <i class="fas fa-calendar-check me-1"></i> تاريخ الصلاحية الأقرب: ${m.earliest_batch.expiry_date} (متبقي ${m.earliest_batch.days_left} يوم)
                        </div>
                    ` : ''}
                </div>
            `;
        });
        container.innerHTML = html;
    }

    // دوال مساعدة
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    function escapeHtml(string) {
        const entityMap = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '/': '&#x2F;'};
        return String(string).replace(/[&<>"'\/]/g, function (s) { return entityMap[s]; });
    }
</script>

<style>
    .cursor-pointer { cursor: pointer; }
    .transition-all { transition: all 0.2s ease-in-out; }
    .shadow-xs { box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .prescription-queue-card:hover {
        border-color: #0d6efd !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    }
    .btn-xs { padding: 0.15rem 0.4rem; font-size: 0.75rem; }
</style>
@endpush
