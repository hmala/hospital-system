@extends('layouts.app')

@section('content')
<style>
/* Beautiful Unified Table Styles */

.unified-table {
    border-radius: 10px;
    background: #fff;
    border: 1px solid #e5e7eb;
    margin-bottom: 2rem;
    box-shadow: none;
}


.unified-table thead th {
    background: #f3f4f6;
    color: #2563eb;
    border: none;
    padding: 1rem 0.75rem;
    font-weight: 600;
    font-size: 0.95rem;
    text-transform: none;
    letter-spacing: 0.2px;
    position: relative;
    border-bottom: 1px solid #e5e7eb;
}

.unified-table thead th i {
    color: #60a5fa;
    margin-left: 0.5rem;
}

/* Row color coding based on type and status - Professional Medical Colors */

.unified-table tbody tr.today-visit {
    background: #f0f9ff;
    border-left: 3px solid #60a5fa;
}
.unified-table tbody tr.medical-request {
    background: #f3f4f6;
    border-left: 3px solid #a3a3a3;
}
.unified-table tbody tr.completed-visit {
    background: #f0fdf4;
    border-left: 3px solid #34d399;
}
.unified-table tbody tr.incomplete-visit {
    background: #fff7ed;
    border-left: 3px solid #fbbf24;
}

.unified-table tbody tr.scheduled-appointment {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(37, 99, 235, 0.08) 100%);
    border-left: 4px solid #3b82f6;
}

.unified-table tbody tr:hover {
    transform: translateX(3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    z-index: 1;
    position: relative;
}

.unified-table tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    border: none;
    font-size: 0.85rem;
}


.unified-table .type-badge {
    padding: 0.3rem 0.6rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: #e0e7ff;
    color: #2563eb;
    text-transform: none;
    letter-spacing: 0.1px;
    border: none;
}


.unified-table .status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 14px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
}

/* Status badge variations - calm colors */
.unified-table .status-badge.status-completed {
    background: #f0fdf4;
    color: #166534;
    border-color: #bbf7d0;
}

.unified-table .status-badge.status-pending {
    background: #fefce8;
    color: #92400e;
    border-color: #fde68a;
}

.unified-table .status-badge.status-cancelled {
    background: #fef2f2;
    color: #991b1b;
    border-color: #fecaca;
}

.unified-table .action-btn {
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.unified-table .action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Avatar circles */

.avatar-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: bold;
    color: #2563eb;
    background: #e0e7ff;
    box-shadow: none;
    border: 1px solid #c7d2fe;
}

/* Enhanced hover effects */
.unified-table tbody tr:hover {
    transform: translateX(5px) scale(1.01);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    z-index: 1;
    position: relative;
}

/* Beautiful type badges with professional medical colors */

.unified-table .type-badge.today-visit {
    background: #e0f2fe;
    color: #2563eb;
}
.unified-table .type-badge.medical-request {
    background: #f3f4f6;
    color: #64748b;
}
.unified-table .type-badge.completed-visit {
    background: #f0fdf4;
    color: #059669;
}
.unified-table .type-badge.incomplete-visit {
    background: #fff7ed;
    color: #d97706;
}
.unified-table .type-badge.scheduled-appointment {
    background: #e0f2fe;
    color: #0ea5e9;
}

/* Responsive design */
@media (max-width: 768px) {
    .unified-table {
        font-size: 0.75rem;
    }

    .unified-table thead th,
    .unified-table tbody td {
        padding: 0.5rem 0.3rem;
    }

    .unified-table tbody tr:hover {
        transform: none;
    }
}

/* Type indicators */
.type-indicator {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    border-radius: 0 4px 4px 0;
}
</style>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-stethoscope me-2"></i>
                    لوحة تحكم الطبيب
                </h2>
                <small class="text-muted">مرحباً د. {{ auth()->user()->name }}</small>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(isset($error))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(auth()->user()->isDoctor())
    
    <!-- تنبيه الزيارات غير المكتملة -->
    @if(isset($incompleteVisits) && is_countable($incompleteVisits) && $incompleteVisits->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning border-warning shadow-sm" role="alert" style="border-left: 4px solid #f59e0b;">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="alert-heading mb-1">
                            <i class="fas fa-clipboard-list me-2"></i>
                            لديك {{ $incompleteVisits->count() }} زيارة غير مكتملة تحتاج متابعة
                        </h5>
                        <p class="mb-0">
                            <small>هذه الزيارات من أيام سابقة ولم يتم إكمالها بعد. يرجى المتابعة وإنهاء الفحوصات المطلوبة.</small>
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="#incomplete-visits-section" class="btn btn-warning btn-sm">
                            <i class="fas fa-arrow-down me-1"></i>
                            عرض الزيارات
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <!-- زيارات مختبرية سريعة -->
    @if(auth()->user()->hasRole('receptionist') || auth()->user()->hasRole('admin'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-flask me-2"></i>
                        زيارات مختبرية سريعة
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p class="mb-0">للمرضى الذين يحتاجون تحاليل فقط دون زيارة طبية كاملة</p>
                            <small class="text-white-50">سيتم تحديد التحاليل المطلوبة من قبل فني المختبر</small>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="{{ route('staff.lab-visits.create') }}" class="btn btn-light">
                                <i class="fas fa-plus me-1"></i>
                                إنشاء زيارة مختبرية
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- جدول جميع الزيارات -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-clipboard-list me-3"></i>
                        جميع الزيارات - الحالية والسابقة
                        <span class="badge bg-light text-primary ms-2">
                            {{ isset($allVisits) && is_countable($allVisits) ? $allVisits->count() : 0 }}
                        </span>
                    </h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table unified-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag me-2"></i>#</th>
                                    <th><i class="fas fa-calendar me-2"></i>التاريخ</th>
                                    <th><i class="fas fa-clock me-2"></i>الوقت</th>
                                    <th><i class="fas fa-user-injured me-2"></i>المريض</th>
                                    <th><i class="fas fa-notes-medical me-2"></i>الشكوى</th>
                                    <th><i class="fas fa-tasks me-2"></i>الحالة</th>
                                    <th><i class="fas fa-cogs me-2"></i>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($allVisits) && is_countable($allVisits) && $allVisits->count() > 0)
                                    @foreach($allVisits as $index => $visit)
                                    @php
                                        $isPast = $visit->visit_date && $visit->visit_date->isPast();
                                        $isToday = $visit->visit_date && $visit->visit_date->isToday();
                                        $isIncomplete = $visit->status != 'completed' && $visit->status != 'cancelled';
                                        $isCompleted = $visit->status == 'completed';
                                        $isCancelled = $visit->status == 'cancelled';
                                        
                                        // تحديد نوع الصف
                                        if ($isIncomplete && $isPast) {
                                            $rowClass = 'incomplete-visit';
                                            $rowStyle = 'background: #fef3c7; border-left: 5px solid #f59e0b;';
                                        } elseif ($isToday) {
                                            $rowClass = 'today-visit';
                                            $rowStyle = 'background: #f0f9ff; border-left: 3px solid #60a5fa;';
                                        } elseif ($isCompleted) {
                                            $rowClass = 'completed-visit';
                                            $rowStyle = 'background: #f0fdf4; border-left: 3px solid #34d399;';
                                        } elseif ($isCancelled) {
                                            $rowClass = 'cancelled-visit';
                                            $rowStyle = 'background: #fef2f2; border-left: 3px solid #ef4444;';
                                        } else {
                                            $rowClass = '';
                                            $rowStyle = '';
                                        }
                                    @endphp
                                    <tr class="{{ $rowClass }}" style="{{ $rowStyle }}">
                                        <td>
                                            <strong class="text-muted">#{{ $visit->id }}</strong>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-calendar {{ $isIncomplete && $isPast ? 'text-danger' : ($isToday ? 'text-primary' : 'text-muted') }} me-2"></i>
                                                <div>
                                                    <span class="{{ $isIncomplete && $isPast ? 'fw-bold text-danger' : '' }}">
                                                        {{ $visit->visit_date ? $visit->visit_date->format('Y-m-d') : 'غير محدد' }}
                                                    </span>
                                                    @if($isToday)
                                                        <span class="badge bg-primary ms-2">اليوم</span>
                                                    @elseif($isPast && $isIncomplete && $visit->visit_date)
                                                        <span class="badge bg-danger ms-2">منذ {{ $visit->visit_date->diffInDays(today()) }} يوم</span>
                                                    @elseif($visit->visit_date && $visit->visit_date->isFuture())
                                                        <span class="badge bg-info ms-2">قادمة</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-clock text-info me-2"></i>
                                                <span>{{ $visit->visit_time ?: 'غير محدد' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle">
                                                    {{ substr($visit->patient->user->name, 0, 1) }}
                                                </div>
                                                <div class="ms-2">
                                                    <strong>{{ $visit->patient->user->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $visit->visit_type_text ?? 'زيارة عامة' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 250px;" title="{{ $visit->chief_complaint }}">
                                                <small class="text-muted">{{ Str::limit($visit->chief_complaint, 50) }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($isCompleted)
                                                <span class="status-badge status-completed">
                                                    <i class="fas fa-check-circle"></i>
                                                    مكتملة
                                                </span>
                                            @elseif($isCancelled)
                                                <span class="status-badge status-cancelled">
                                                    <i class="fas fa-times-circle"></i>
                                                    ملغية
                                                </span>
                                            @elseif($visit->status == 'in_progress')
                                                <span class="status-badge" style="background: #e0f2fe; color: #0369a1; border-color: #7dd3fc;">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                    قيد الفحص
                                                </span>
                                            @elseif($visit->status == 'waiting')
                                                <span class="status-badge status-pending">
                                                    <i class="fas fa-hourglass-half"></i>
                                                    في الانتظار
                                                </span>
                                            @else
                                                <span class="status-badge">
                                                    <i class="fas fa-question-circle"></i>
                                                    {{ $visit->status }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($isIncomplete)
                                                <a href="{{ route('doctor.visits.show', $visit) }}" class="action-btn btn-warning">
                                                    <i class="fas fa-clipboard-check"></i>
                                                    إكمال الفحص
                                                </a>
                                            @else
                                                <a href="{{ route('doctor.visits.show', $visit) }}" class="action-btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                    عرض
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                        <h5 class="text-muted">لا توجد زيارات</h5>
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection