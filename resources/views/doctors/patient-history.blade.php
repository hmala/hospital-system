@extends('layouts.app')

@section('content')
<style>
/* خلفية الصفحة الموحدة */
body {
    background: linear-gradient(135deg, #f5f7fa 0%, #e8edf2 100%);
    min-height: 100vh;
}

.page-wrapper {
    background: #f5f7fa;
    min-height: 100vh;
    padding: 2rem 0;
}

/* Patient Header */
.patient-header {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: #fff;
    padding: 2.5rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.patient-avatar {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 50%;
    width: 120px;
    height: 120px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.patient-info-card {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    backdrop-filter: blur(10px);
}

/* Filter Section */
.filter-section {
    background: #fff;
    padding: 1.8rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
    border: 1px solid #e8edf2;
}

.filter-section h5 {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.filter-section .form-select,
.filter-section .form-control {
    border-radius: 10px;
    border: 1px solid #e0e0e0;
    padding: 0.6rem 1rem;
    transition: all 0.2s ease;
}

.filter-section .form-select:focus,
.filter-section .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
}

.filter-section .form-label {
    font-weight: 500;
    color: #6c757d;
}

/* Timeline */
.timeline {
    position: relative;
    padding: 2rem 0;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(to bottom, #3498db, #9b59b6);
    border-radius: 3px;
}

.timeline-item {
    position: relative;
    margin-bottom: 3.5rem;
    display: flex;
    align-items: flex-start;
    gap: 2.5rem;
}

.timeline-item:nth-child(even) {
    flex-direction: row-reverse;
}

.timeline-date {
    flex: 0 0 45%;
    text-align: right;
    padding-right: 2.5rem;
}

.timeline-date .text-muted {
    color: #6c757d !important;
    font-weight: 500;
    font-size: 0.95rem;
}

.timeline-date .badge {
    font-size: 0.75rem;
    padding: 0.4rem 0.8rem;
}

.timeline-item:nth-child(even) .timeline-date {
    text-align: left;
    padding-left: 2.5rem;
    padding-right: 0;
}

.timeline-marker {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid #fff;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    z-index: 10;
}

.timeline-marker i {
    font-size: 1.3rem;
    color: #fff;
}

.timeline-content {
    flex: 0 0 45%;
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #e8edf2;
    transition: all 0.3s ease;
}

.timeline-content:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
}

.timeline-content h5 {
    color: #2c3e50;
    font-weight: 600;
}

.timeline-content .btn {
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    padding: 0.5rem 1.2rem;
    transition: all 0.2s ease;
}

.timeline-content .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.timeline-content .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.timeline-content .btn-outline-success:hover {
    transform: translateY(-2px);
}

.timeline-content .btn-outline-info:hover {
    transform: translateY(-2px);
}

.badge-custom {
    position: absolute;
    top: -12px;
    right: 20px;
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Quick Access Panel */
.quick-access-panel {
    position: fixed;
    left: 20px;
    top: 200px;
    width: 260px;
    z-index: 999;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    border: 1px solid #e8edf2;
}

.quick-access-panel .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border-radius: 16px 16px 0 0;
    padding: 1rem 1.5rem;
    font-weight: 600;
}

.quick-access-panel .list-group-item {
    border: none;
    border-bottom: 1px solid #f0f0f0;
    padding: 1rem 1.5rem;
    transition: all 0.2s ease;
}

.quick-access-panel .list-group-item:hover {
    background: #f8f9fa;
    transform: translateX(4px);
    color: #667eea;
}

.quick-access-panel .list-group-item:last-child {
    border-bottom: none;
    border-radius: 0 0 16px 16px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

/* Buttons */
.btn-custom {
    border-radius: 10px;
    padding: 0.6rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Timeline Title */
.timeline-title {
    background: #fff;
    padding: 1.5rem 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.timeline-title h3 {
    color: #2c3e50;
    font-weight: 700;
    margin: 0;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .quick-access-panel {
        display: none;
    }
}

@media (max-width: 768px) {
    .timeline::before {
        left: 30px;
    }
    
    .timeline-item,
    .timeline-item:nth-child(even) {
        flex-direction: column;
        padding-left: 60px;
    }
    
    .timeline-marker {
        left: 30px;
        width: 40px;
        height: 40px;
    }
    
    .timeline-date,
    .timeline-item:nth-child(even) .timeline-date {
        text-align: left;
        padding: 0;
        margin-bottom: 0.5rem;
    }
    
    .timeline-content {
        flex: 1;
    }
    
    .patient-header .row {
        text-align: center;
    }
}

/* Print Styles */
@media print {
    .quick-access-panel,
    .filter-section,
    .btn,
    button {
        display: none !important;
    }
    
    .page-wrapper {
        background: white !important;
    }
    
    .timeline-content {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        page-break-inside: avoid;
    }
}
</style>

<div class="page-wrapper">
<div class="container-fluid">
    <!-- Patient Header -->
    <div class="patient-header">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <div class="patient-avatar">
                    <i class="fas fa-user fa-4x text-primary"></i>
                </div>
            </div>
            <div class="col-md-7">
                <h2 class="mb-3 fw-bold">{{ $patient->user->name ?? 'المريض' }}</h2>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-id-card me-2"></i>
                            <div>
                                <small class="d-block opacity-75">رقم المريض</small>
                                <strong>P-{{ $patient->id }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-birthday-cake me-2"></i>
                            <div>
                                <small class="d-block opacity-75">العمر</small>
                                <strong>{{ $patient->age ?? 'غير محدد' }} سنة</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-tint me-2"></i>
                            <div>
                                <small class="d-block opacity-75">فصيلة الدم</small>
                                <strong>{{ $patient->blood_type ?? 'غير محدد' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                @if($patient->allergies)
                <div class="alert alert-danger mb-2 py-2 d-inline-block">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>حساسية:</strong> {{ $patient->allergies }}
                </div>
                @endif
            </div>
            <div class="col-md-3">
                <div class="patient-info-card">
                    <div class="card-body">
                        <h6 class="mb-3">
                            <i class="fas fa-heartbeat me-2"></i>
                            الأمراض المزمنة
                        </h6>
                        @if($patient->chronic_diseases)
                            {!! nl2br(e($patient->chronic_diseases)) !!}
                        @else
                            <div class="text-white-50 small">• Diabetes Type 2</div>
                            <div class="text-white-50 small">• Hypertension</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button class="btn btn-light btn-custom" onclick="window.print()">
                <i class="fas fa-print me-2"></i>طباعة
            </button>
            <a href="{{ route('doctor.visits.index') }}" class="btn btn-outline-light btn-custom">
                <i class="fas fa-arrow-right me-2"></i>رجوع
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <h5><i class="fas fa-filter me-2 text-primary"></i>فلتر السجل الطبي</h5>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">
                    <i class="fas fa-calendar me-1"></i>نطاق التاريخ
                </label>
                <select class="form-select" id="dateFilter">
                    <option selected>الكل</option>
                    <option>آخر شهر</option>
                    <option>آخر 3 أشهر</option>
                    <option>آخر 6 أشهر</option>
                    <option>آخر سنة</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">
                    <i class="fas fa-list me-1"></i>نوع الحدث
                </label>
                <select class="form-select" id="typeFilter">
                    <option selected>الكل</option>
                    <option>زيارات</option>
                    <option>مختبر</option>
                    <option>أشعة</option>
                    <option>عمليات جراحية</option>
                    <option>طوارئ</option>
                    <option>تنويم</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">
                    <i class="fas fa-user-md me-1"></i>الطبيب
                </label>
                <select class="form-select" id="physicianFilter">
                    <option selected>الكل</option>
                    @foreach($timeline->pluck('doctor')->unique()->filter() as $doctor)
                        <option>{{ $doctor }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">
                    <i class="fas fa-search me-1"></i>بحث
                </label>
                <input type="text" class="form-control" id="keywordSearch" placeholder="ابحث في الأحداث...">
            </div>
        </div>
    </div>

    <!-- Timeline Section -->
    <div class="timeline-title">
        <h3>
            <i class="fas fa-stream me-2 text-primary"></i>الرحلة الطبية للمريض
        </h3>
    </div>

    <div class="timeline">
        @foreach($timeline as $event)
            <div class="timeline-item">
                <div class="timeline-date">
                    @if($event['date'])
                        <div class="mb-2">
                            <div class="fw-bold text-dark" style="font-size: 1.1rem;">
                                {{ \Carbon\Carbon::parse($event['date'])->format('d') }}
                            </div>
                            <div class="text-muted small">
                                {{ \Carbon\Carbon::parse($event['date'])->format('M Y') }}
                            </div>
                        </div>
                    @endif
                    @if($event['type'] === 'lab')
                        <div class="text-end">
                            <span class="badge bg-info">الأحدث</span>
                        </div>
                    @endif
                </div>
                
                <div class="timeline-marker bg-{{ $event['color'] ?? 'primary' }}">
                    <i class="fas fa-{{ $event['icon'] ?? 'circle' }}"></i>
                </div>
                
                <div class="timeline-content">
                    <span class="badge-custom bg-{{ $event['color'] ?? 'primary' }}">{{ $event['badge'] ?? 'حدث' }}</span>
                    <h5 class="mb-2 mt-2">{{ $event['title'] }}</h5>
                    @if(!empty($event['doctor']))
                        <div class="text-muted small mb-2">
                            <i class="fas fa-user-md me-1"></i>{{ $event['doctor'] }}
                        </div>
                    @endif
                    <p class="mb-3 text-secondary" style="white-space: pre-line;">{{ $event['description'] }}</p>
                    @if(!empty($event['extraLabel']))
                        <div class="alert alert-success mb-2 py-2">
                            <small>{{ $event['extraLabel'] }}</small>
                        </div>
                    @endif
                    <div class="d-flex gap-2">
                        @if($event['link'])
                            <a href="{{ $event['link'] }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye me-1"></i>عرض التفاصيل
                            </a>
                        @endif
                        @if($event['type'] === 'lab')
                            <button class="btn btn-sm btn-outline-success">
                                <i class="fas fa-file-medical me-1"></i>عرض التقرير
                            </button>
                        @elseif($event['type'] === 'radiology')
                            <button class="btn btn-sm btn-outline-info">
                                <i class="fas fa-image me-1"></i>عرض الصورة
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($timeline->isEmpty())
        <div class="empty-state">
            <i class="fas fa-history fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">لا توجد سجلات طبية</h5>
            <p class="text-muted mb-0">لم يتم تسجيل أي أحداث طبية لهذا المريض حتى الآن</p>
        </div>
    @endif

    <!-- Quick Access Section (Side Panel) -->
    <div class="quick-access-panel">
        <div class="card border-0">
            <div class="card-header">
                <i class="fas fa-bolt me-2"></i>الوصول السريع
            </div>
            <div class="list-group list-group-flush">
                <a href="#" class="list-group-item list-group-item-action">
                    <i class="fas fa-prescription text-success me-2"></i>آخر وصفة طبية
                </a>
                <a href="#" class="list-group-item list-group-item-action">
                    <i class="fas fa-flask text-info me-2"></i>آخر نتيجة مختبر
                </a>
                <a href="#" class="list-group-item list-group-item-action">
                    <i class="fas fa-file-medical text-danger me-2"></i>ملخص الخروج
                </a>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
