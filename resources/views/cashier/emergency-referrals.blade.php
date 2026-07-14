@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" dir="rtl">
    {{-- رأس الصفحة والتحكم --}}
    <div class="d-flex justify-content-between align-items-center mb-4 text-end">
        <div>
            <h3 class="mb-1 text-dark"><i class="fas fa-file-invoice-dollar me-2 text-danger"></i>تقرير حوافز الطبيب وتفاصيل الإحالات</h3>
            <p class="text-muted mb-0">كشف الحالات المحولة والخدمات المتولدة عنها</p>
        </div>
        <div class="d-print-none">
            <button onclick="window.print()" class="btn btn-primary px-4 me-2">
                <i class="fas fa-print me-1"></i> طباعة التقرير
            </button>
            <a href="{{ route('cashier.emergency.statements', ['from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-outline-secondary px-4">
                <i class="fas fa-arrow-left me-1"></i> العودة للكشوفات
            </a>
        </div>
    </div>

    {{-- معلومات الطبيب والتقرير --}}
    <div class="row g-4 mb-4 text-end">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="avatar-lg bg-light-danger rounded-circle p-3 text-center">
                                <i class="fas fa-user-md fa-3x text-danger"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h4 class="mb-1 text-dark">د. {{ optional($doctor->user)->name ?? 'طبيب طوارئ' }}</h4>
                            <p class="text-muted mb-2"><i class="fas fa-stethoscope me-1 text-secondary"></i> قسم الطوارئ</p>
                            <div class="d-flex flex-wrap gap-2 justify-content-start">
                                <span class="badge bg-light text-dark border p-2"><i class="fas fa-calendar-alt me-1 text-primary"></i> الفترة: {{ $fromDate ?? 'من البداية' }} إلى {{ $toDate ?? 'اليوم' }}</span>
                                <span class="badge bg-light text-dark border p-2"><i class="fas fa-tags me-1 text-success"></i> فئة الإحالة: {{ $typeLabel }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 bg-danger text-white shadow-sm h-100">
                <div class="card-body d-flex flex-column justify-content-between p-4">
                    <div>
                        <h6 class="text-uppercase text-white-50 mb-1">إجمالي الإيراد المتولد من الإحالات</h6>
                        <h2 class="mb-0 fw-bold">{{ number_format($totalAmount, 0) }} د.ع</h2>
                    </div>
                    <div class="mt-3">
                        <small class="text-white-50"><i class="fas fa-info-circle me-1"></i> الإيراد المحسوب يشمل الخدمات المدفوعة فقط الناتجة عن إحالة الطبيب</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول تفاصيل الحالات --}}
    <div class="row text-end">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 text-dark fw-bold"><i class="fas fa-list me-2 text-danger"></i> تفاصيل الحالات المرضية والفحوصات</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 25%" class="text-end">اسم المريض</th>
                                    <th style="width: 15%">تاريخ ووقت الإحالة</th>
                                    <th style="width: 40%" class="text-end">الخدمات/الفحوصات المطلوبة بالتفصيل</th>
                                    <th style="width: 15%">القيمة المالية (IQD)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($details as $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="text-end fw-bold text-dark">{{ $row->patient_name }}</td>
                                        <td>{{ $row->date }}</td>
                                        <td class="text-end"><span class="text-muted small">{{ $row->items }}</span></td>
                                        <td class="fw-bold text-success">{{ number_format($row->amount, 0) }} د.ع</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="fas fa-folder-open fa-3x mb-3 text-secondary"></i>
                                            <p class="mb-0">لا توجد تفاصيل إحالات مدفوعة لهذه الفئة للطبيب في الفترة المحددة.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(count($details) > 0)
                                <tfoot class="table-light">
                                    <tr class="fw-bold fs-5">
                                        <th colspan="4" class="text-end">المجموع الكلي للإيرادات المتولدة:</th>
                                        <th class="text-danger">{{ number_format($totalAmount, 0) }} د.ع</th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-light-danger {
        background-color: rgba(220, 53, 69, 0.1);
    }
    @media print {
        body {
            background: white !important;
        }
        .card {
            box-shadow: none !important;
            border: 1px solid #dee2e6 !important;
        }
        .d-print-none {
            display: none !important;
        }
    }
</style>
@endsection
