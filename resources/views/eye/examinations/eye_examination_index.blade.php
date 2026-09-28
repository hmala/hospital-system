@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h3 fw-bold text-primary mb-1">
                <i class="fas fa-stethoscope me-2"></i>سجل كشوفات وفحوصات العيون السريرية
            </h2>
            <p class="text-muted mb-0 small">استعراض ملفات الفحص السريري، حدة الإبصار، قياسات النظارات، وضغط العين</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('eye.reception.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-users me-1"></i>طابور العيون
            </a>
            <a href="{{ route('eye.examinations.create') }}" class="btn btn-primary">
                <i class="fas fa-eye me-1"></i>فتح محطة كشف جديدة
            </a>
        </div>
    </div>

    <!-- جدول الفحوصات السريرية -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-clipboard-list text-primary me-2"></i>سجل الكشوفات المنجزة</h5>
            <span class="badge bg-light text-dark border">العدد: {{ $examinations->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>التاريخ والوقت</th>
                        <th>المريض</th>
                        <th>الطبيب الفاحص</th>
                        <th>حدة البصر (OD / OS)</th>
                        <th>ضغط العين (IOP)</th>
                        <th>التشخيص المعتمد</th>
                        <th class="text-center" style="width: 160px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($examinations as $exam)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $exam->created_at->format('Y-m-d') }}</div>
                            <div class="small text-muted">{{ $exam->created_at->format('h:i A') }}</div>
                        </td>
                        <td>
                            <div class="fw-bold text-primary">{{ $exam->patient->name }}</div>
                            <div class="small text-muted">{{ $exam->patient->phone ?? 'بدون هاتف' }} | {{ $exam->patient->age ?? '-' }} سنة</div>
                        </td>
                        <td>
                            <span class="fw-semibold text-dark">{{ $exam->doctor->user->name ?? 'طبيب العيون' }}</span>
                        </td>
                        <td>
                            <div class="small">
                                <span class="badge bg-success-subtle text-success">OD: {{ $exam->va_od_corrected ?? $exam->va_od_unaided ?? '-' }}</span>
                                <span class="badge bg-info-subtle text-info">OS: {{ $exam->va_os_corrected ?? $exam->va_os_unaided ?? '-' }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="small">
                                <span class="fw-bold text-dark">R: {{ $exam->iop_od ?? '-' }}</span> / 
                                <span class="fw-bold text-dark">L: {{ $exam->iop_os ?? '-' }}</span> 
                                <span class="text-muted">mmHg</span>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold text-truncate" style="max-width: 250px;" title="{{ $exam->diagnosis }}">
                                {{ $exam->diagnosis ?? 'كشف عام' }}
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('eye.examinations.show', $exam) }}" class="btn btn-outline-primary" title="عرض الفحص المقارن">
                                    <i class="fas fa-eye me-1"></i>عرض الملف
                                </a>
                                @if($exam->has_glasses_prescription || $exam->ref_od_sphere || $exam->ref_os_sphere)
                                <a href="{{ route('eye.examinations.printGlasses', $exam) }}" class="btn btn-outline-secondary" target="_blank" title="طباعة راشيتة النظارة">
                                    <i class="fas fa-glasses"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-stethoscope fa-3x mb-3 text-secondary opacity-50"></i>
                            <div class="h5">لا توجد سجلات فحص عيون بعد</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($examinations->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $examinations->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
