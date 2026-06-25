@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-edit me-2 text-primary"></i>
                        تعديل مصروف
                    </h2>
                    <p class="text-muted">تعديل بيانات المصروف: {{ $expense->title }}</p>
                </div>
                <a href="{{ route('accounting.expenses.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>بيانات المصروف</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('accounting.expenses.update', $expense) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">عنوان المصروف <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                       value="{{ old('title', $expense->title) }}" placeholder="عنوان المصروف">
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">الفئة <span class="text-danger">*</span></label>
                                <select name="category" class="form-select @error('category') is-invalid @enderror">
                                    <option value="">-- اختر الفئة --</option>
                                    @foreach($categories as $key => $label)
                                        <option value="{{ $key }}" {{ old('category', $expense->category) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">المبلغ (دينار) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                       value="{{ old('amount', $expense->amount) }}" min="0" step="0.01">
                                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">طريقة الدفع <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                                    @foreach($paymentMethods as $key => $label)
                                        <option value="{{ $key }}" {{ old('payment_method', $expense->payment_method) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">تاريخ المصروف <span class="text-danger">*</span></label>
                                <input type="date" name="expense_date" class="form-control @error('expense_date') is-invalid @enderror"
                                       value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}">
                                @error('expense_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">الجهة / المورد</label>
                                <input type="text" name="vendor" class="form-control @error('vendor') is-invalid @enderror"
                                       value="{{ old('vendor', $expense->vendor) }}">
                                @error('vendor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">رقم المرجع / الفاتورة</label>
                                <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror"
                                       value="{{ old('reference_number', $expense->reference_number) }}">
                                @error('reference_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">وصف المصروف</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                          rows="3">{{ old('description', $expense->description) }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">ملاحظات</label>
                                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                                          rows="2">{{ old('notes', $expense->notes) }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('accounting.expenses.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
