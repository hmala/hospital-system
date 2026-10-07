@extends('layouts.app')
@section('title', 'إعدادات الشفتات - HR')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="fas fa-calendar-day text-primary me-2"></i>إعدادات الشفتات (الورديات)</h4>
            <p class="text-muted small mb-0">إدارة أنواع الشفتات وأوقاتها لربطها بجدول الدوام</p>
        </div>
        <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addShiftModal">
            <i class="fas fa-plus me-1"></i> إضافة شفت جديد
        </button>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> يوجد خطأ في الإدخال:
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        @forelse($shifts as $shift)
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="border-top: 5px solid {{ $shift->color_code }} !important;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="fw-bold mb-0">{{ $shift->name }}</h5>
                        <span class="badge bg-{{ $shift->is_active ? 'success' : 'secondary' }} rounded-pill">
                            {{ $shift->is_active ? 'فعال' : 'معطل' }}
                        </span>
                    </div>
                    
                    <div class="mb-3 d-flex align-items-center justify-content-between bg-light rounded-3 p-2">
                        <div class="text-center w-50 border-end">
                            <div class="small text-muted">من</div>
                            <div class="fw-bold fs-5">{{ \Carbon\Carbon::parse($shift->start_time)->format('h:i A') }}</div>
                        </div>
                        <div class="text-center w-50">
                            <div class="small text-muted">إلى</div>
                            <div class="fw-bold fs-5">{{ \Carbon\Carbon::parse($shift->end_time)->format('h:i A') }}</div>
                        </div>
                    </div>

                    @if($shift->description)
                        <p class="text-muted small mb-3">{{ Str::limit($shift->description, 50) }}</p>
                    @endif

                    <div class="d-flex gap-2 mt-auto pt-2 border-top">
                        <button class="btn btn-sm btn-outline-primary flex-fill" data-bs-toggle="modal" data-bs-target="#editShiftModal{{ $shift->id }}">
                            <i class="fas fa-edit"></i> تعديل
                        </button>
                        <form action="{{ route('hr.shifts.destroy', $shift->id) }}" method="POST" class="d-inline flex-fill">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('هل أنت متأكد من الحذف؟')">
                                <i class="fas fa-trash"></i> حذف
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editShiftModal{{ $shift->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('hr.shifts.update', $shift->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold">تعديل الشفت: {{ $shift->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label">اسم الشفت <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ $shift->name }}" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">وقت البداية <span class="text-danger">*</span></label>
                                    <input type="time" name="start_time" class="form-control" value="{{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">وقت النهاية <span class="text-danger">*</span></label>
                                    <input type="time" name="end_time" class="form-control" value="{{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">لون التمييز في الجدول <span class="text-danger">*</span></label>
                                <input type="color" name="color_code" class="form-control form-control-color w-100" value="{{ $shift->color_code }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الوصف التفصيلي (اختياري)</label>
                                <textarea name="description" class="form-control" rows="2">{{ $shift->description }}</textarea>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive{{ $shift->id }}" {{ $shift->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive{{ $shift->id }}">الشفت فعال ويظهر في الجداول</label>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary px-4">حفظ التغييرات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <i class="fas fa-clock fa-4x text-muted mb-3 opacity-25"></i>
            <h5>لم يتم إضافة أي شفتات بعد</h5>
            <p class="text-muted">انقر على زر الإضافة للبدء في تعريف أوقات الدوام والخفارات.</p>
        </div>
        @endforelse
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.shifts.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">إضافة شفت جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">اسم الشفت <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="مثال: صباحي مبكر، مسائي، خفارة طوارئ..." required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">وقت البداية <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">وقت النهاية <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">لون التمييز في الجدول <span class="text-danger">*</span></label>
                        <input type="color" name="color_code" class="form-control form-control-color w-100" value="#0d6efd" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف التفصيلي (اختياري)</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="ملاحظات حول الشفت..."></textarea>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActiveNew" checked>
                        <label class="form-check-label" for="isActiveNew">الشفت فعال ويظهر في الجداول</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-plus me-1"></i> إضافة الشفت</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
