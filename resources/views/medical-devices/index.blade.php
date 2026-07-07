@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-stethoscope me-2 text-primary"></i>
                        إدارة الأجهزة الطبية
                    </h2>
                    <p class="text-muted">مراقبة، إضافة وتعديل الأجهزة الطبية المستخدمة في العمليات الجراحية</p>
                </div>
                <div>
                    @can('manage medical devices')
                    <a href="{{ route('medical-devices.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        إضافة جهاز طبي جديد
                    </a>
                    @endcan
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                قائمة الأجهزة الطبية
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الرقم التسلسلي</th>
                            <th>اسم الجهاز</th>
                            <th>نوع الجهاز</th>
                            <th>المزود</th>
                            <th>سعر الجهاز</th>
                            <th>مرات الاستخدام</th>
                            <th>تاريخ الشراء</th>
                            <th>آخر صيانة</th>
                            <th class="text-center">الحالة</th>
                            @can('manage medical devices')
                            <th class="text-center" style="width: 150px;">الإجراءات</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($devices as $device)
                        <tr>
                            <td>
                                <code>{{ $device->serial_number ?? '-' }}</code>
                            </td>
                            <td>
                                <strong>{{ $device->name }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $device->type }}</span>
                            </td>
                            <td>{{ $device->supplier ?? '-' }}</td>
                            <td>
                                <strong>{{ number_format($device->price, 0) }} د.ع</strong>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark">
                                    <i class="fas fa-history me-1"></i>
                                    {{ $device->surgeries_count }} مرات
                                </span>
                            </td>
                            <td>{{ $device->purchase_date ? $device->purchase_date->format('Y-m-d') : '-' }}</td>
                            <td>{{ $device->last_maintenance_at ? $device->last_maintenance_at->format('Y-m-d') : '-' }}</td>
                            <td class="text-center">
                                @if($device->status === 'active')
                                    <span class="badge bg-success">نشط / متاح</span>
                                @elseif($device->status === 'maintenance')
                                    <span class="badge bg-warning text-dark">تحت الصيانة</span>
                                @else
                                    <span class="badge bg-danger">غير نشط</span>
                                @endif
                            </td>
                            @can('manage medical devices')
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('medical-devices.edit', $device) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="حذف" 
                                            onclick="if(confirm('هل أنت متأكد من رغبتك في حذف هذا الجهاز؟')) { document.getElementById('delete-form-{{ $device->id }}').submit(); }">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <form id="delete-form-{{ $device->id }}" action="{{ route('medical-devices.destroy', $device) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                            @endcan
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->can('manage medical devices') ? 10 : 9 }}" class="text-center py-4">
                                <i class="fas fa-info-circle text-muted fa-2x mb-2"></i>
                                <p class="text-muted mb-0">لا توجد أجهزة طبية مسجلة حالياً</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
