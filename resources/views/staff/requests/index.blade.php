@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-clipboard-list me-2"></i>
                    إدارة الطلبات الطبية
                </h2>
                <small class="text-muted">مرحباً {{ auth()->user()->name }}</small>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- فلاتر الطلبات -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>
                        فلترة الطلبات
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($allowedTypes as $allowedType)
                        <div class="col-md-4 mb-2">
                            <a href="{{ route('staff.requests.index', $allowedType) }}"
                               class="btn btn-outline-{{ $type == $allowedType ? 'primary' : 'secondary' }} w-100">
                                <i class="fas fa-{{ $allowedType == 'lab' ? 'flask' : ($allowedType == 'radiology' ? 'x-ray' : 'pills') }} me-2"></i>
                                {{ $allowedType == 'lab' ? 'المختبر' : ($allowedType == 'radiology' ? 'الأشعة' : 'الصيدلية') }}
                                @if($type == $allowedType)
                                    <span class="badge bg-primary ms-2">{{ $requests->total() }}</span>
                                @endif
                            </a>
                        </div>
                        @endforeach
                        <div class="col-md-4 mb-2">
                            <a href="{{ route('staff.requests.index') }}"
                               class="btn btn-outline-{{ !$type ? 'primary' : 'secondary' }} w-100">
                                <i class="fas fa-list me-2"></i>
                                الكل
                                @if(!$type)
                                    <span class="badge bg-primary ms-2">{{ $requests->total() }}</span>
                                @endif
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- قائمة الطلبات -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>
                        {{ $type ? ($type == 'lab' ? 'طلبات المختبر' : ($type == 'radiology' ? 'طلبات الأشعة' : 'طلبات الصيدلية')) : 'جميع الطلبات' }}
                    </h5>
                </div>
                <div class="card-body">
                    @if($requests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>رقم الطلب</th>
                                        <th>المريض</th>
                                        <th>الطبيب</th>
                                        <th>نوع الطلب</th>
                                        <th>التفاصيل</th>
                                        <th>تاريخ الطلب</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $request)
                                    <tr>
                                        <td>#{{ $request->id }}</td>
                                        <td>{{ $request->visit?->patient?->user?->name ?? 'غير محدد' }}</td>
                                        <td>د. {{ $request->visit?->doctor?->user?->name ?? 'غير محدد' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $request->type == 'lab' ? 'primary' : ($request->type == 'radiology' ? 'info' : 'success') }}">
                                                {{ $request->type_text }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                                            @endphp
                                            @if($request->type == 'lab' && isset($details['lab_test_ids']))
                                                <small class="text-muted">
                                                    @php
                                                        $testNames = [];
                                                        foreach($details['lab_test_ids'] as $testId) {
                                                            $test = \App\Models\LabTest::find($testId);
                                                            if ($test) {
                                                                $testNames[] = $test->name;
                                                            }
                                                        }
                                                    @endphp
                                                    {{ implode('، ', $testNames) }}
                                                </small>
                                            @elseif($request->type == 'radiology' && isset($details['radiology_type_ids']))
                                                <small class="text-muted">
                                                    @php
                                                        $typeNames = [];
                                                        foreach($details['radiology_type_ids'] as $typeId) {
                                                            $type = \App\Models\RadiologyType::find($typeId);
                                                            if ($type) {
                                                                $typeNames[] = $type->name;
                                                            }
                                                        }
                                                    @endphp
                                                    {{ implode('، ', $typeNames) }}
                                                </small>
                                            @else
                                                <small class="text-muted">{{ $request->description ?? '-' }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $request->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $request->status == 'completed' ? 'success' : ($request->status == 'pending' ? 'warning' : 'info') }}">
                                                {{ $request->status_text }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                @if($request->type == 'radiology')
                                                    @php
                                                        // البحث عن طلب الأشعة المرتبط بنفس الزيارة
                                                        $radiologyRequest = \App\Models\RadiologyRequest::where('visit_id', $request->visit_id)
                                                            ->latest('created_at')
                                                            ->first();
                                                        
                                                        // إذا لم نجد سجل في radiology_requests، نحاول إنشاءه من بيانات الطلب
                                                        if (!$radiologyRequest && $request->details && isset($request->details['radiology_type_id'])) {
                                                            try {
                                                                $radiologyRequest = \App\Models\RadiologyRequest::create([
                                                                    'visit_id' => $request->visit_id,
                                                                    'patient_id' => $request->visit->patient_id ?? null,
                                                                    'doctor_id' => $request->visit->doctor_id ?? null,
                                                                    'radiology_type_id' => $request->details['radiology_type_id'],
                                                                    'requested_date' => $request->created_at,
                                                                    'status' => $request->status,
                                                                    'priority' => $request->details['priority'] ?? 'normal',
                                                                    'clinical_indication' => $request->description ?? null,
                                                                ]);
                                                            } catch (\Exception $e) {
                                                                // في حالة فشل الإنشاء، نستمر بدون إنشاء
                                                            }
                                                        }
                                                    @endphp
                                                    @if($radiologyRequest)
                                                        <a href="{{ route('radiology.show', $radiologyRequest->id) }}"
                                                           class="btn btn-outline-primary"
                                                           title="عرض تفاصيل الأشعة">
                                                            <i class="fas fa-x-ray"></i>
                                                        </a>
                                                    @else
                                                        <a href="{{ route('staff.requests.show', $request) }}"
                                                           class="btn btn-outline-primary"
                                                           title="عرض الطلب">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    @endif
                                                @else
                                                    <a href="{{ route('staff.requests.show', $request) }}"
                                                       class="btn btn-outline-primary"
                                                       title="عرض">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endif
                                                @if($request->status == 'completed' && $request->type == 'lab')
                                                    <a href="{{ route('staff.requests.print', $request) }}"
                                                       class="btn btn-outline-success"
                                                       target="_blank"
                                                       title="طباعة النتائج">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                @endif
                                                @if($request->status == 'completed' && $request->type == 'radiology' && $radiologyRequest && $radiologyRequest->result)
                                                    <a href="{{ route('radiology.print', $radiologyRequest->id) }}"
                                                       class="btn btn-outline-success"
                                                       target="_blank"
                                                       title="طباعة نتائج الأشعة">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $requests->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد طلبات</h5>
                            <p class="text-muted">
                                {{ $type ? 'لا توجد طلبات في هذا القسم' : 'لا توجد طلبات متاحة لك' }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection