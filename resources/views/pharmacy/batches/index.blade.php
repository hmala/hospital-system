@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- عنوان الصفحة وزر التوريد -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-boxes me-2"></i>لوحة متابعة الشحنات والصلاحيات (FEFO Dashboard)
            </h2>
            <p class="text-muted small mb-0">نظام الصرف الذكي للأقرب انتهاءً أولاً (First Expired, First Out) ومراقبة الأدوية المعرضة للتلف</p>
        </div>
        <div>
            <a href="{{ route('pharmacy.batches.create') }}" class="btn btn-primary shadow-sm px-4">
                <i class="fas fa-plus-circle me-1"></i> توريد وجبة / شحنة جديدة
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

    <!-- بطاقات الإحصائيات الذكية -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body">
                    <div class="text-muted small">إجمالي الوجبات</div>
                    <h3 class="fw-bold mb-0 text-dark">{{ number_format($stats['total_batches']) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 border-start border-success border-4">
                <div class="card-body">
                    <div class="text-muted small">وجبات نشطة وسارية</div>
                    <h3 class="fw-bold mb-0 text-success">{{ number_format($stats['active_batches']) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 border-start border-warning border-4">
                <div class="card-body">
                    <div class="text-muted small">تنبيه: قريبة الانتهاء (&le; 90 يوم)</div>
                    <h3 class="fw-bold mb-0 text-warning">{{ number_format($stats['expiring_soon']) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 border-start border-danger border-4">
                <div class="card-body">
                    <div class="text-muted small">منتهية الصلاحية</div>
                    <h3 class="fw-bold mb-0 text-danger">{{ number_format($stats['expired']) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 bg-warning bg-opacity-10">
                <div class="card-body">
                    <div class="text-muted small">قيمة المخزون المعرض للتلف</div>
                    <h4 class="fw-bold mb-0 text-dark">{{ number_format($stats['at_risk_value']) }} <small class="fs-6">د.ع</small></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط البحث والتبويبات -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <!-- تبويبات سريعة للحالة -->
                <ul class="nav nav-pills small fw-bold">
                    <li class="nav-item">
                        <a class="nav-link {{ request('status', 'all') === 'all' ? 'active' : '' }}" href="{{ route('pharmacy.batches.index') }}">
                            الكل ({{ $stats['total_batches'] }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') === 'expiring_soon' ? 'active bg-warning text-dark' : 'text-warning' }}" href="{{ route('pharmacy.batches.index', ['status' => 'expiring_soon']) }}">
                            <i class="fas fa-exclamation-triangle me-1"></i> قريبة الانتهاء ({{ $stats['expiring_soon'] }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') === 'active' ? 'active bg-success' : 'text-success' }}" href="{{ route('pharmacy.batches.index', ['status' => 'active']) }}">
                            نشطة وسارية ({{ $stats['active_batches'] }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') === 'expired' ? 'active bg-danger' : 'text-danger' }}" href="{{ route('pharmacy.batches.index', ['status' => 'expired']) }}">
                            منتهية الصلاحية ({{ $stats['expired'] }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('status') === 'quarantined' ? 'active bg-secondary' : 'text-secondary' }}" href="{{ route('pharmacy.batches.index', ['status' => 'quarantined']) }}">
                            محجورة ({{ $stats['quarantined'] }})
                        </a>
                    </li>
                </ul>

                <!-- نموذج البحث -->
                <form method="GET" action="{{ route('pharmacy.batches.index') }}" class="d-flex gap-2">
                    <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                    <select name="medicine_id" class="form-select form-select-sm" style="max-width: 220px;">
                        <option value="">كل الأدوية</option>
                        @foreach($medicinesList as $med)
                            <option value="{{ $med->id }}" {{ request('medicine_id') == $med->id ? 'selected' : '' }}>{{ $med->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="رقم الوجبة أو المورد..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                    @if(request()->anyFilled(['search', 'medicine_id', 'status']))
                        <a href="{{ route('pharmacy.batches.index') }}" class="btn btn-outline-secondary btn-sm" title="إلغاء الفلاتر">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <!-- جدول الوجبات والشحنات -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-muted small">
                            <th>الدواء والصنف</th>
                            <th>رقم الوجبة (Lot)</th>
                            <th>تاريخ الانتهاء</th>
                            <th>مؤشر الصلاحية</th>
                            <th>الرصيد المتوفر</th>
                            <th>سعر الشراء</th>
                            <th>المورد</th>
                            <th>الحالة</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $batch)
                            <tr class="{{ $batch->is_expired ? 'table-danger bg-opacity-10' : ($batch->is_expiring_soon ? 'table-warning bg-opacity-10' : '') }}">
                                <td>
                                    @if($batch->medicine)
                                        <a href="{{ route('pharmacy.medicines.show', $batch->medicine->id) }}" class="fw-bold text-decoration-none text-primary">
                                            {{ $batch->medicine->name }}
                                        </a>
                                        <div class="text-muted small">
                                            {{ $batch->medicine->dosage_form }} - {{ $batch->medicine->strength }}
                                            @if($batch->medicine->national_code)
                                                | <span class="font-monospace">{{ $batch->medicine->national_code }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">صنف محذوف</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="font-monospace fw-bold text-dark fs-6">{{ $batch->batch_number }}</span>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : '-' }}</span>
                                </td>
                                <td>
                                    @if($batch->is_expired)
                                        <span class="badge bg-danger"><i class="fas fa-ban me-1"></i>منتهي الصلاحية</span>
                                    @elseif($batch->is_expiring_soon)
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>{{ $batch->days_until_expiry }} يوم متبقي</span>
                                    @else
                                        <span class="badge bg-success bg-opacity-10 text-success">{{ $batch->days_until_expiry }} يوم</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-bold text-dark">{{ $batch->current_quantity }} {{ $batch->medicine->main_unit ?? 'علبة' }}</span>
                                    @if($batch->current_sub_units > 0)
                                        <span class="badge bg-light text-dark border ms-1">+{{ $batch->current_sub_units }} {{ $batch->medicine->sub_unit ?? 'شريط' }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-dark small">{{ number_format($batch->purchase_price) }} د.ع</span>
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $batch->supplier_name ?? 'غير محدد' }}</span>
                                </td>
                                <td>
                                    @if($batch->status === 'active')
                                        <span class="badge bg-success">نشطة</span>
                                    @elseif($batch->status === 'quarantined')
                                        <span class="badge bg-danger"><i class="fas fa-lock me-1"></i>محجورة</span>
                                    @elseif($batch->status === 'expired')
                                        <span class="badge bg-dark">منتهية</span>
                                    @elseif($batch->status === 'depleted')
                                        <span class="badge bg-secondary">نافدة</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <!-- زر الحجر / إلغاء الحجر -->
                                        @if($batch->status === 'quarantined')
                                            <form action="{{ route('pharmacy.batches.status', $batch->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="btn btn-outline-success" title="إلغاء الحجر وتفعيل الوجبة">
                                                    <i class="fas fa-lock-open"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('pharmacy.batches.status', $batch->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="quarantined">
                                                <button type="submit" class="btn btn-outline-warning" title="حجر الوجبة ومنع صرفها">
                                                    <i class="fas fa-shield-alt"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <a href="{{ route('pharmacy.batches.edit', $batch->id) }}" class="btn btn-outline-primary" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <form action="{{ route('pharmacy.batches.destroy', $batch->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الوجبة؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="حذف">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-boxes fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-2">لا توجد وجبات أو شحنات مطابقة لمعايير البحث.</p>
                                    <a href="{{ route('pharmacy.batches.create') }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus-circle me-1"></i> توريد وجبة جديدة الآن
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($batches->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $batches->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
