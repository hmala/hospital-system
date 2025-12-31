@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-concierge-bell me-2"></i>
                            تفاصيل الاستعلام #{{ $visit->id }}
                        </h4>
                        <div>
                            <a href="{{ route('inquiry.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>العودة للاستعلامات
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- معلومات المريض -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-injured me-2"></i>معلومات المريض
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <p><strong>الاسم:</strong> {{ $visit->patient->user->name }}</p>
                                            <p><strong>العمر:</strong> {{ $visit->patient->age ?? 'غير محدد' }} سنة</p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>الهاتف:</strong> {{ $visit->patient->user->phone ?? 'غير محدد' }}</p>
                                            <p><strong>العنوان:</strong> {{ $visit->patient->user->address ?? 'غير محدد' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">
                                        <i class="fas fa-calendar-check me-2"></i>معلومات الزيارة
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <p><strong>تاريخ الزيارة:</strong> {{ $visit->visit_date->format('Y-m-d') }}</p>
                                            <p><strong>وقت الزيارة:</strong> {{ $visit->visit_time->format('H:i') }}</p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>نوع الزيارة:</strong>
                                                <span class="badge bg-primary">{{ $visit->visit_type }}</span>
                                            </p>
                                            <p><strong>الحالة:</strong>
                                                @if($visit->status == 'completed')
                                                    <span class="badge bg-success">مكتملة</span>
                                                @elseif($visit->status == 'in_progress')
                                                    <span class="badge bg-warning">قيد التنفيذ</span>
                                                @elseif($visit->status == 'cancelled')
                                                    <span class="badge bg-danger">ملغية</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $visit->status }}</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    @if($visit->doctor)
                                    <p><strong>الطبيب المعالج:</strong> {{ $visit->doctor->user->name }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- الشكوى الرئيسية -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-notes-medical me-2"></i>الشكوى الرئيسية والملاحظات
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-3"><strong>الشكوى:</strong></p>
                                    <div class="alert alert-info">
                                        {{ $visit->chief_complaint ?? 'لا توجد شكوى مسجلة' }}
                                    </div>

                                    @if($visit->notes)
                                    <p class="mb-2"><strong>الملاحظات:</strong></p>
                                    <div class="alert alert-secondary">
                                        {{ $visit->notes }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- الطلبات المرتبطة -->
                    @if($visit->requests && $visit->requests->count() > 0)
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-tasks me-2"></i>الطلبات المرتبطة بهذا الاستعلام
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>نوع الطلب</th>
                                                    <th>الوصف</th>
                                                    <th>الحالة</th>
                                                    <th>تاريخ الإنشاء</th>
                                                    <th>الإجراءات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($visit->requests as $request)
                                                <tr>
                                                    <td>{{ $request->id }}</td>
                                                    <td>
                                                        @if($request->type == 'lab')
                                                            <span class="badge bg-primary">تحاليل طبية</span>
                                                        @elseif($request->type == 'radiology')
                                                            <span class="badge bg-info">أشعة</span>
                                                        @elseif($request->type == 'pharmacy')
                                                            <span class="badge bg-success">صيدلية</span>
                                                        @elseif($request->type == 'checkup')
                                                            <span class="badge bg-warning">كشف طبي</span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ $request->type }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ Str::limit($request->description, 50) }}</td>
                                                    <td>
                                                        @if($request->status == 'pending')
                                                            <span class="badge bg-warning">في الانتظار</span>
                                                        @elseif($request->status == 'in_progress')
                                                            <span class="badge bg-primary">قيد التنفيذ</span>
                                                        @elseif($request->status == 'completed')
                                                            <span class="badge bg-success">مكتمل</span>
                                                        @elseif($request->status == 'cancelled')
                                                            <span class="badge bg-danger">ملغي</span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ $request->status }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $request->created_at->format('Y-m-d H:i') }}</td>
                                                    <td>
                                                        @if(Auth::user()->isStaff() || Auth::user()->hasRole('receptionist'))
                                                        <a href="{{ route('staff.requests.show', $request->id) }}" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i> عرض
                                                        </a>
                                                        @endif
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
                    @endif

                    <!-- الإجراءات -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-secondary">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-cogs me-2"></i>الإجراءات المتاحة
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex gap-2 flex-wrap">
                                        @if(Auth::user()->isStaff() || Auth::user()->hasRole('receptionist'))
                                        <a href="{{ route('inquiry.edit', $visit->id) }}" class="btn btn-warning">
                                            <i class="fas fa-edit me-1"></i>تعديل الاستعلام
                                        </a>

                                        <button type="button" class="btn btn-success" onclick="completeVisit({{ $visit->id }})">
                                            <i class="fas fa-check me-1"></i>إكمال الزيارة
                                        </button>

                                        <button type="button" class="btn btn-danger" onclick="cancelVisit({{ $visit->id }})">
                                            <i class="fas fa-times me-1"></i>إلغاء الزيارة
                                        </button>
                                        @endif

                                        <a href="{{ route('inquiry.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left me-1"></i>العودة للقائمة
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function completeVisit(visitId) {
    if (confirm('هل أنت متأكد من إكمال هذه الزيارة؟')) {
        // يمكن إضافة AJAX call هنا لإكمال الزيارة
        alert('تم إكمال الزيارة بنجاح!');
        location.reload();
    }
}

function cancelVisit(visitId) {
    if (confirm('هل أنت متأكد من إلغاء هذه الزيارة؟')) {
        // يمكن إضافة AJAX call هنا لإلغاء الزيارة
        alert('تم إلغاء الزيارة!');
        location.reload();
    }
}
</script>
@endsection