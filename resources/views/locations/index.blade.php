@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px;">
                <div class="card-body p-4 text-white">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row gap-3">
                        <div>
                            <h2 class="mb-1 fw-bold">
                                <i class="fas fa-map-marker-alt me-3"></i>مخازن الأقسام
                            </h2>
                            <p class="mb-0 opacity-75">عرض جميع المخازن الرئيسية والفرعية مع حالة المخزن وعدد الدفعات.</p>
                        </div>
                        <a href="{{ route('locations.create') }}" class="btn btn-light px-4 py-2 rounded-pill fw-bold">
                            <i class="fas fa-plus me-2"></i>إضافة مخزن جديد
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card border-0 shadow-lg h-100" style="border-radius: 15px;">
                <div class="card-body p-4 text-center">
                    <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-warehouse text-white fs-4"></i>
                    </div>
                    <h3 class="mt-3 mb-1 fw-bold text-primary">{{ $locations->count() }}</h3>
                    <p class="text-muted mb-0">إجمالي المخازن</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card border-0 shadow-lg h-100" style="border-radius: 15px;">
                <div class="card-body p-4 text-center">
                    <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-building text-white fs-4"></i>
                    </div>
                    <h3 class="mt-3 mb-1 fw-bold text-success">{{ $locations->where('type', 'main')->count() }}</h3>
                    <p class="text-muted mb-0">مخازن رئيسية</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-lg h-100" style="border-radius: 15px;">
                <div class="card-body p-4 text-center">
                    <div class="bg-info rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-layer-group text-white fs-4"></i>
                    </div>
                    <h3 class="mt-3 mb-1 fw-bold text-info">{{ $locations->where('type', 'sub')->count() }}</h3>
                    <p class="text-muted mb-0">مخازن أقسام</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                <div class="card-header bg-light border-0" style="border-radius: 15px 15px 0 0;">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary rounded-circle p-2">
                                <i class="fas fa-list text-white"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">قائمة المخازن</h5>
                                <small class="text-muted">تفاصيل المخازن وعدد الدفعات لكل منها</small>
                            </div>
                        </div>
                        <div class="input-group" style="max-width: 360px;">
                            <span class="input-group-text bg-white border-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" id="locationSearch" class="form-control border-0 bg-light" placeholder="البحث في المخازن..." style="border-radius: 0 10px 10px 0;">
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="locationsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 fw-bold">اسم المخزن</th>
                                    <th class="border-0 fw-bold">النوع</th>
                                    <th class="border-0 fw-bold">عدد الدفعات</th>
                                    <th class="border-0 fw-bold">الرصيد الكلي</th>
                                    <th class="border-0 fw-bold text-center">عرض</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($locations as $location)
                                    <tr class="location-row" style="transition: all 0.2s ease;">
                                        <td class="fw-semibold">{{ $location->name }}</td>
                                        <td>
                                            <span class="badge {{ $location->type === 'main' ? 'bg-success' : 'bg-secondary' }} rounded-pill px-3 py-2">
                                                {{ $location->type === 'main' ? 'رئيسي' : 'قسم' }}
                                            </span>
                                        </td>
                                        <td>{{ $location->stockBatches->count() }}</td>
                                        <td>{{ $location->stockBatches->sum('current_qty') }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('locations.show', $location) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                <i class="fas fa-eye me-1"></i>عرض
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('locationSearch');
        const tableRows = document.querySelectorAll('.location-row');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase().trim();

                tableRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const shouldShow = text.includes(searchTerm);
                    row.style.display = shouldShow ? '' : 'none';
                });
            });
        }

        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function () {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.08)';
            });

            row.addEventListener('mouseleave', function () {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
    });
</script>
@endsection
