@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list-ul me-2"></i>
                            الفحوصات الفرعية لـ: {{ $labTest->name }}
                        </h5>
                        <div>
                            <a href="{{ route('admin.lab-test-sub-tests.create', $labTest->id) }}" class="btn btn-light btn-sm">
                                <i class="fas fa-plus me-1"></i>
                                إضافة فحص فرعي
                            </a>
                            <a href="{{ url()->previous() }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-right me-1"></i>
                                رجوع
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>الفئة الرئيسية:</strong> {{ $labTest->main_category }}
                            </div>
                            <div class="col-md-3">
                                <strong>الفئة الفرعية:</strong> {{ $labTest->subcategory }}
                            </div>
                            <div class="col-md-3">
                                <strong>الكود:</strong> {{ $labTest->code }}
                            </div>
                            <div class="col-md-3">
                                <strong>عدد الفحوصات الفرعية:</strong> 
                                <span class="badge bg-info">{{ $labTest->subTests->count() }}</span>
                            </div>
                        </div>
                    </div>

                    @if($labTest->subTests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;" class="text-center">#</th>
                                        <th>اسم الفحص</th>
                                        <th style="width: 100px;">الوحدة</th>
                                        <th style="width: 150px;">المرجع</th>
                                        <th style="width: 100px;" class="text-center">النوع</th>
                                        <th style="width: 80px;" class="text-center">الترتيب</th>
                                        <th style="width: 150px;" class="text-center">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($labTest->subTests as $index => $subTest)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ $subTest->name }}</strong>
                                                @if($subTest->notes)
                                                    <br><small class="text-muted">{{ $subTest->notes }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $subTest->unit ?: '—' }}</td>
                                            <td>{{ $subTest->reference_range ?: '—' }}</td>
                                            <td class="text-center">
                                                @if($subTest->result_type === 'numeric')
                                                    <span class="badge bg-success">رقمي</span>
                                                @else
                                                    <span class="badge bg-info">نصي</span>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $subTest->sort_order }}</td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.lab-test-sub-tests.edit', [$labTest->id, $subTest->id]) }}" 
                                                       class="btn btn-sm btn-warning" 
                                                       title="تعديل">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.lab-test-sub-tests.destroy', [$labTest->id, $subTest->id]) }}" 
                                                          method="POST" 
                                                          class="d-inline"
                                                          onsubmit="return confirm('هل أنت متأكد من حذف هذا الفحص الفرعي؟')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="حذف">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            لا توجد فحوصات فرعية لهذا التحليل بعد. يمكنك إضافة فحوصات فرعية باستخدام الزر أعلاه.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
