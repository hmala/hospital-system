@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-flask me-2"></i>
                        إدارة التحاليل والفحوصات الفرعية
                    </h5>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <input type="text" 
                               id="searchTests" 
                               class="form-control" 
                               placeholder="ابحث عن تحليل...">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle" id="testsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>اسم التحليل</th>
                                    <th>الفئة الرئيسية</th>
                                    <th>الفئة الفرعية</th>
                                    <th style="width: 100px;" class="text-center">الفحوصات الفرعية</th>
                                    <th style="width: 150px;" class="text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tests as $index => $test)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $test->name }}</strong>
                                            @if($test->code)
                                                <br><small class="text-muted">كود: {{ $test->code }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $test->main_category }}</td>
                                        <td>{{ $test->subcategory }}</td>
                                        <td class="text-center">
                                            @php
                                                $subTestsCount = $test->subTests->count();
                                            @endphp
                                            @if($subTestsCount > 0)
                                                <span class="badge bg-success">{{ $subTestsCount }}</span>
                                            @else
                                                <span class="badge bg-secondary">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.lab-test-sub-tests.index', $test->id) }}" 
                                               class="btn btn-sm btn-primary"
                                               title="إدارة الفحوصات الفرعية">
                                                <i class="fas fa-list-ul me-1"></i>
                                                الفحوصات الفرعية
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            لا توجد تحاليل متاحة
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($tests->hasPages())
                        <div class="mt-3">
                            {{ $tests->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // بحث في الجدول
    document.getElementById('searchTests').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('#testsTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchValue) ? '' : 'none';
        });
    });
</script>
@endpush
@endsection
