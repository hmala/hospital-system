@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-receipt me-2 text-danger"></i>
                        إدارة المصروفات
                    </h2>
                    <p class="text-muted">تتبع وإدارة مصروفات المستشفى</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('accounting.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right me-1"></i> لوحة التحكم
                    </a>
                    @can('create expenses')
                    <a href="{{ route('accounting.expenses.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> إضافة مصروف
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- فلاتر البحث -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('accounting.expenses.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">الفئة</label>
                    <select name="category" class="form-select">
                        <option value="">الكل</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="">الكل</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-search me-1"></i> بحث
                    </button>
                    <a href="{{ route('accounting.expenses.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- إجمالي المصروفات الموافق عليها -->
    <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
        <i class="fas fa-info-circle fs-5"></i>
        <span>إجمالي المصروفات الموافق عليها في هذه الفترة: <strong>{{ number_format($totalAmount, 0) }} دينار</strong></span>
    </div>

    <!-- جدول المصروفات -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العنوان</th>
                            <th>الفئة</th>
                            <th>المبلغ</th>
                            <th>طريقة الدفع</th>
                            <th>التاريخ</th>
                            <th>المورد/الجهة</th>
                            <th>أضيف بواسطة</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                        <tr>
                            <td class="text-muted small">{{ $expense->id }}</td>
                            <td>
                                <div class="fw-bold">{{ $expense->title }}</div>
                                @if($expense->description)
                                    <div class="text-muted small">{{ Str::limit($expense->description, 40) }}</div>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary">{{ $expense->category_name }}</span></td>
                            <td class="fw-bold text-danger">{{ number_format($expense->amount, 0) }} <small class="text-muted">د.ع</small></td>
                            <td>{{ $expense->payment_method_name }}</td>
                            <td>{{ $expense->expense_date->format('Y/m/d') }}</td>
                            <td>{{ $expense->vendor ?? '-' }}</td>
                            <td>{{ $expense->createdBy->name ?? '-' }}</td>
                            <td>
                                @if($expense->status === 'approved')
                                    <span class="badge bg-success">موافق عليه</span>
                                @elseif($expense->status === 'pending')
                                    <span class="badge bg-warning">معلق</span>
                                @else
                                    <span class="badge bg-danger">مرفوض</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    @can('approve expenses')
                                    @if($expense->status === 'pending')
                                    <form method="POST" action="{{ route('accounting.expenses.approve', $expense) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="موافقة">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('accounting.expenses.reject', $expense) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning" title="رفض">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    @endif
                                    @endcan
                                    @can('edit expenses')
                                    <a href="{{ route('accounting.expenses.edit', $expense) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan
                                    @can('delete expenses')
                                    <form method="POST" action="{{ route('accounting.expenses.destroy', $expense) }}" class="d-inline"
                                          onsubmit="return confirm('هل تريد حذف هذا المصروف؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                لا توجد مصروفات
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($expenses->hasPages())
        <div class="card-footer">
            {{ $expenses->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
