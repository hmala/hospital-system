@extends('layouts.app')

@section('title', 'إدارة أسرة وحدة العناية المركزة (ICU Beds)')

@section('content')
<div class="container-fluid py-3">
    <!-- رأس الصفحة -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fas fa-bed text-primary me-2"></i>
                إدارة وتوزيع أسرة العناية المركزة والحرجة (ICU / CCU)
            </h3>
            <p class="text-muted mb-0">تهيئة أسرّة العناية العامة والقلبية والعزل، متابعة التعقيم، وتحديث الأسعار اليومية</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('icu.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i> العودة للرادار
            </a>
            @can('manage icu beds')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBedModal">
                <i class="fas fa-plus-circle me-1"></i> إضافة سرير عناية جديد
            </button>
            @endcan
        </div>
    </div>

    <!-- التنبيهات -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- جدول الأسرة -->
    <div class="card border-0 shadow-sm" style="border-radius: 14px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th>رقم السرير</th>
                            <th>نوع السرير</th>
                            <th>الغرفة / الردهة</th>
                            <th>الطابق</th>
                            <th>الحالة التشغيلية</th>
                            <th>السعر اليومي</th>
                            <th>المريض الحالي</th>
                            <th>ملاحظات وتجهيزات</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($beds as $bed)
                            @php
                                $admission = $bed->currentAdmission;
                            @endphp
                            <tr>
                                <td class="fw-bold fs-6">
                                    <span class="badge {{ $bed->status_badge_class }} px-2 py-1">
                                        سرير #{{ $bed->bed_number }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $bed->type_badge_class }} px-2 py-1">
                                        {{ $bed->type_name }}
                                    </span>
                                </td>
                                <td>{{ $bed->room_number ?? 'ردهة العناية' }}</td>
                                <td class="small text-muted">{{ $bed->floor }}</td>
                                <td>
                                    <span class="badge {{ $bed->status_badge_class }}">
                                        {{ $bed->status_name }}
                                    </span>
                                </td>
                                <td class="fw-bold text-success">{{ number_format($bed->daily_price) }} د.ع</td>
                                <td>
                                    @if($admission)
                                        <a href="{{ route('icu.show', $admission->id) }}" class="fw-bold text-primary text-decoration-none">
                                            <i class="fas fa-user-injured me-1"></i>
                                            {{ $admission->patient?->user?->name ?? $admission->patient?->name }}
                                        </a>
                                        <small class="d-block text-muted">منذ: {{ $admission->admission_date->format('Y-m-d') }}</small>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td class="small text-start">{{ Str::limit($bed->notes, 40) ?? '--' }}</td>
                                <td>
                                    @if($bed->status === 'sterilizing')
                                        <form method="POST" action="{{ route('icu.beds.sterilized', $bed->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold" title="تأكيد اكتمال التعقيم">
                                                <i class="fas fa-pump-soap me-1"></i> جاهز للاستقبال
                                            </button>
                                        </form>
                                    @elseif($bed->status === 'available')
                                        <form method="POST" action="{{ route('icu.beds.status', $bed->id) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="maintenance">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="تحويل إلى صيانة">
                                                <i class="fas fa-tools"></i>
                                            </button>
                                        </form>
                                    @elseif($bed->status === 'maintenance')
                                        <form method="POST" action="{{ route('icu.beds.status', $bed->id) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="available">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="إعادة تشغيل وإتاحة">
                                                <i class="fas fa-check"></i> إتاحة
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('icu.show', $admission->id) }}" class="btn btn-sm btn-outline-primary" title="عرض الإضبارة">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-muted py-4">لا توجد أسرة مسجلة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- مودال إضافة سرير جديد -->
<div class="modal fade" id="addBedModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('icu.beds.store') }}">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fs-6">إضافة سرير عناية مركزة جديد</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">رقم السرير: <span class="text-danger">*</span></label>
                        <input type="text" name="bed_number" class="form-control" required placeholder="مثال: 13, 14, ICU-A...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">نوع السرير: <span class="text-danger">*</span></label>
                        <select name="bed_type" class="form-select" required>
                            <option value="general">عناية مركزة عامة (General ICU)</option>
                            <option value="ccu">عناية قلبية فائقة (CCU)</option>
                            <option value="neuro">عناية عصبية (Neuro-ICU)</option>
                            <option value="isolation">غرفة عزل ضغط سالب (Isolation)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">رقم الغرفة / الردهة:</label>
                        <input type="text" name="room_number" class="form-control" placeholder="مثال: ICU-301">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">السعر اليومي (د.ع): <span class="text-danger">*</span></label>
                        <input type="number" name="daily_price" class="form-control" required value="150000" step="1000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ملاحظات وتجهيزات السرير:</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="أجهزة التنفس أو مضخات المحاليل الملحقة بالسرير..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm">حفظ وإضافة السرير</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
