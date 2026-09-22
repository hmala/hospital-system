@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- عنوان الصفحة وزر نقطة البيع -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-history me-2"></i>سجل مبيعات وفواتير الصيدلية
            </h2>
            <p class="text-muted small mb-0">أرشيف الفواتير الصادرة، تتبع الإيرادات وحصص الضمان الصحي، وإعادة طباعة الوصولات</p>
        </div>
        <div>
            <a href="{{ route('pharmacy.pos.index') }}" class="btn btn-success shadow-sm px-4">
                <i class="fas fa-cash-register me-1"></i> نقطة البيع (POS)
            </a>
        </div>
    </div>

    <!-- بطاقات الإحصائيات لليوم -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body">
                    <div class="text-muted small">إجمالي مبيعات اليوم</div>
                    <h3 class="fw-bold mb-0 text-dark">{{ number_format($stats['today_sales']) }} <small class="fs-6">د.ع</small></h3>
                    <div class="text-muted small mt-1">عدد الفواتير: {{ $stats['today_count'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 border-start border-success border-4">
                <div class="card-body">
                    <div class="text-muted small">المقبوض نقداً اليوم (حصة المرضى)</div>
                    <h3 class="fw-bold mb-0 text-success">{{ number_format($stats['today_patient_share']) }} <small class="fs-6">د.ع</small></h3>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-muted small">مطالبات الضمان لليوم</div>
                    <h3 class="fw-bold mb-0 text-primary">{{ number_format($stats['today_insurance_share']) }} <small class="fs-6">د.ع</small></h3>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body">
                    <div class="text-muted small">متوسط قيمة الفاتورة</div>
                    @php
                        $avg = $stats['today_count'] > 0 ? round($stats['today_sales'] / $stats['today_count']) : 0;
                    @endphp
                    <h3 class="fw-bold mb-0 text-info">{{ number_format($avg) }} <small class="fs-6">د.ع</small></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط الفلاتر والبحث -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('pharmacy.pos.sales.history') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">بحث برقم الفاتورة أو اسم المريض</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="ابحث..." value="{{ request('search') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">نوع البيع</label>
                    <select name="sale_type" class="form-select form-select-sm">
                        <option value="">كل الأنواع</option>
                        <option value="direct_otc" {{ request('sale_type') == 'direct_otc' ? 'selected' : '' }}>مباشر OTC</option>
                        <option value="prescription" {{ request('sale_type') == 'prescription' ? 'selected' : '' }}>وصفة استشارية</option>
                        <option value="emergency" {{ request('sale_type') == 'emergency' ? 'selected' : '' }}>طوارئ</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">جهة التأمين</label>
                    <select name="insurance_type" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        <option value="none" {{ request('insurance_type') == 'none' ? 'selected' : '' }}>نقدي (كاش)</option>
                        <option value="health_insurance" {{ request('insurance_type') == 'health_insurance' ? 'selected' : '' }}>الضمان الصحي</option>
                        <option value="interior_ministry" {{ request('insurance_type') == 'interior_ministry' ? 'selected' : '' }}>وزارة الداخلية</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">التاريخ</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter me-1"></i> تصفية
                    </button>
                    @if(request()->anyFilled(['search', 'sale_type', 'insurance_type', 'date']))
                        <a href="{{ route('pharmacy.pos.sales.history') }}" class="btn btn-outline-secondary btn-sm" title="إلغاء الفلاتر">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- جدول الفواتير -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small">
                        <tr>
                            <th>رقم الفاتورة</th>
                            <th>المريض</th>
                            <th>النوع</th>
                            <th>جهة التأمين</th>
                            <th>المبلغ الإجمالي</th>
                            <th>حصة المريض</th>
                            <th>حصة الضمان</th>
                            <th>حالة الدفع</th>
                            <th>التوقيت والصيدلي</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                            <tr>
                                <td>
                                    <a href="{{ route('pharmacy.pos.sales.show', $sale->id) }}" class="font-monospace fw-bold text-decoration-none text-primary">
                                        {{ $sale->invoice_number }}
                                    </a>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $sale->patient_name ?? 'مريض مباشر' }}</span>
                                </td>
                                <td>
                                    @if($sale->sale_type === 'direct_otc')
                                        <span class="badge bg-light text-dark border">مباشر OTC</span>
                                    @elseif($sale->sale_type === 'prescription')
                                        <span class="badge bg-info text-dark">استشارية</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $sale->sale_type }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sale->insurance_type === 'health_insurance')
                                        <span class="badge bg-success"><i class="fas fa-shield-alt me-1"></i>ضمان ({{ $sale->copay_percentage }}%)</span>
                                    @elseif($sale->insurance_type === 'interior_ministry')
                                        <span class="badge bg-primary">داخلية</span>
                                    @else
                                        <span class="badge bg-light text-muted border">نقدي</span>
                                    @endif
                                </td>
                                <td class="fw-bold text-dark">{{ number_format($sale->total_amount) }} د.ع</td>
                                <td class="fw-bold text-success">{{ number_format($sale->patient_share) }} د.ع</td>
                                <td class="fw-semibold text-primary">{{ number_format($sale->insurance_share) }} د.ع</td>
                                <td>
                                    @if($sale->payment_status === 'paid')
                                        <span class="badge bg-success">مسددة</span>
                                    @elseif($sale->payment_status === 'pending_cashier')
                                        <span class="badge bg-warning text-dark">بانتظار الكاشير</span>
                                    @elseif($sale->payment_status === 'refunded')
                                        <span class="badge bg-danger">مسترجعة</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small text-dark">{{ $sale->created_at->format('Y-m-d h:i A') }}</div>
                                    <small class="text-muted">{{ $sale->dispenser->name ?? ($sale->user->name ?? 'صيدلي') }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('pharmacy.pos.sales.show', $sale->id) }}" class="btn btn-outline-info" title="عرض التفاصيل">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('pharmacy.pos.sales.print', $sale->id) }}" target="_blank" class="btn btn-outline-secondary" title="طباعة الوصل 80mm">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fas fa-receipt fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-0">لا توجد فواتير مبيعات مسجلة في هذا النطاق.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($sales->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
