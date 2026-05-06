@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1 fw-bold"><i class="fas fa-clipboard-list me-3"></i>طلبات المخزون</h2>
                            <p class="mb-0 text-muted">راجع طلبات النقل والإرجاع بين المخزن الرئيسي والمخازن الفرعية.</p>
                        </div>
                        <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary rounded-pill">
                            <i class="fas fa-plus me-2"></i>طلب جديد
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="border-radius: 15px;">
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>من</th>
                                    <th>إلى</th>
                                    <th>نوع الطلب</th>
                                    <th>الوضع</th>
                                    <th>مقدم الطلب</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th class="text-center">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $request)
                                    <tr>
                                        <td>#{{ $request->id }}</td>
                                        <td>{{ $request->fromLocation->name }}</td>
                                        <td>{{ $request->toLocation->name }}</td>
                                        <td>
                                            @php
                                                $typeLabel = 'نقل مخزون';
                                                if ($request->fromLocation->type === 'main' && $request->toLocation->type === 'sub') {
                                                    $typeLabel = 'طلب من الرئيسي';
                                                } elseif ($request->fromLocation->type === 'sub' && $request->toLocation->type === 'main') {
                                                    $typeLabel = 'إرجاع إلى الرئيسي';
                                                }
                                            @endphp
                                            <span class="badge bg-info text-dark">{{ $typeLabel }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $request->status === 'pending' ? 'warning' : ($request->status === 'approved' ? 'success' : 'danger') }}">
                                                {{ ucfirst($request->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $request->requestedBy->name ?? 'غير معروف' }}</td>
                                        <td>{{ $request->created_at->format('Y-m-d H:i') }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('stock-transfers.requests.show', $request) }}" class="btn btn-sm btn-outline-primary">عرض</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">لا توجد طلبات مخزون حتى الآن.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $requests->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
