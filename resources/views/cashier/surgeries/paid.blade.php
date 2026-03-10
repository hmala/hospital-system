@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-file-invoice-dollar me-2 text-success"></i>
                        سجل مدفوعات العمليات الجراحية
                    </h2>
                    <p class="text-muted">
                        عرض جميع المدفوعات مع إمكانية طباعة الإيصالات
                    </p>
                </div>
                <div>
                    <a href="{{ route('cashier.surgeries.index') }}" class="btn btn-danger me-2">
                        <i class="fas fa-clock me-2"></i>
                        العمليات المعلقة
                    </a>
                    <a href="{{ route('cashier.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-right me-2"></i>
                        العودة للكاشير
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- إحصائيات -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 opacity-75">إيصالات اليوم</p>
                            <h3 class="mb-0">{{ $stats['today_count'] }}</h3>
                        </div>
                        <div class="opacity-75">
                            <i class="fas fa-receipt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 opacity-75">إيرادات اليوم</p>
                            <h3 class="mb-0">{{ number_format($stats['today_amount'], 0) }}</h3>
                            <small>IQD</small>
                        </div>
                        <div class="opacity-75">
                            <i class="fas fa-money-bill-wave fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 opacity-75">إيصالات الشهر</p>
                            <h3 class="mb-0">{{ $stats['month_count'] }}</h3>
                        </div>
                        <div class="opacity-75">
                            <i class="fas fa-calendar-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1">إيرادات الشهر</p>
                            <h3 class="mb-0">{{ number_format($stats['month_amount'], 0) }}</h3>
                            <small>IQD</small>
                        </div>
                        <div class="opacity-75">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- فلاتر البحث -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0">
                <i class="fas fa-filter me-2"></i>
                بحث وفلترة
            </h6>
        </div>
        <div class="card-body">
            <form action="{{ route('cashier.surgeries.paid') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="patient_name" class="form-label">اسم المريض</label>
                    <input type="text" class="form-control" id="patient_name" name="patient_name" 
                           value="{{ request('patient_name') }}" placeholder="ابحث باسم المريض...">
                </div>
                <div class="col-md-2">
                    <label for="from_date" class="form-label">من تاريخ</label>
                    <input type="date" class="form-control" id="from_date" name="from_date" 
                           value="{{ request('from_date') }}">
                </div>
                <div class="col-md-2">
                    <label for="to_date" class="form-label">إلى تاريخ</label>
                    <input type="date" class="form-control" id="to_date" name="to_date" 
                           value="{{ request('to_date') }}">
                </div>
                <div class="col-md-2">
                    <label for="payment_method" class="form-label">طريقة الدفع</label>
                    <select class="form-select" id="payment_method" name="payment_method">
                        <option value="">الكل</option>
                        <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>نقدي</option>
                        <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>بطاقة</option>
                        <option value="insurance" {{ request('payment_method') == 'insurance' ? 'selected' : '' }}>تأمين</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>
                        بحث
                    </button>
                    <a href="{{ route('cashier.surgeries.paid') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>
                        مسح
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول المدفوعات -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                قائمة الإيصالات
                <span class="badge bg-light text-success ms-2">{{ $payments->total() }} إيصال</span>
            </h5>
        </div>
        <div class="card-body p-0">
            @if($payments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>رقم الإيصال</th>
                                <th>المريض</th>
                                <th>التفاصيل</th>
                                <th>المبلغ</th>
                                <th>طريقة الدفع</th>
                                <th>الكاشير</th>
                                <th>التاريخ والوقت</th>
                                <th class="text-center" style="width: 150px;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $index => $payment)
                                @php
                                    // استخراج رقم العملية من الوصف
                                    $surgeryId = null;
                                    if (preg_match('/ID: #(\d+)/', $payment->description, $matches)) {
                                        $surgeryId = $matches[1];
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $payments->firstItem() + $index }}</td>
                                    <td>
                                        <span class="badge bg-success">
                                            {{ $payment->receipt_number }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-2">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                            <div>
                                                <strong>{{ $payment->patient->user->name ?? 'غير محدد' }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    {{ $payment->patient->national_id ?? '-' }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($surgeryId)
                                            <span class="badge bg-danger me-1">عملية #{{ $surgeryId }}</span>
                                        @endif
                                        @php
                                            // عدد العناصر المدفوعة
                                            $itemCount = 0;
                                            if (preg_match_all('/\n- /', $payment->description, $itemMatches)) {
                                                $itemCount = count($itemMatches[0]);
                                            }
                                        @endphp
                                        @if($itemCount > 0)
                                            <small class="text-muted d-block">{{ $itemCount }} عنصر مدفوع</small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="text-success" style="font-size: 1.1rem;">
                                            {{ number_format($payment->amount, 0) }}
                                        </strong>
                                        <small class="text-muted">IQD</small>
                                    </td>
                                    <td>
                                        @switch($payment->payment_method)
                                            @case('cash')
                                                <span class="badge bg-success">
                                                    <i class="fas fa-money-bill-wave me-1"></i>نقدي
                                                </span>
                                                @break
                                            @case('card')
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-credit-card me-1"></i>بطاقة
                                                </span>
                                                @break
                                            @case('insurance')
                                                <span class="badge bg-info">
                                                    <i class="fas fa-shield-alt me-1"></i>تأمين
                                                </span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $payment->payment_method }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <small>{{ $payment->cashier->name ?? 'غير محدد' }}</small>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="fas fa-calendar text-muted me-1"></i>
                                            {{ $payment->paid_at->format('Y-m-d') }}
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            {{ $payment->paid_at->format('H:i') }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('cashier.receipt', $payment->id) }}" 
                                               class="btn btn-sm btn-outline-success" 
                                               title="عرض الإيصال">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('cashier.receipt.print', $payment->id) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               target="_blank"
                                               title="طباعة">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            عرض {{ $payments->firstItem() }} - {{ $payments->lastItem() }} من {{ $payments->total() }} إيصال
                        </div>
                        {{ $payments->appends(request()->query())->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">لا توجد مدفوعات مطابقة للبحث</p>
                    <a href="{{ route('cashier.surgeries.paid') }}" class="btn btn-outline-primary">
                        عرض الكل
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
