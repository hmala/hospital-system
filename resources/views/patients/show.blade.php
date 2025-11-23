<!-- في resources/views/patients/show.blade.php - تحديث قسم المواعيد -->

<!-- استبدال قسم "آخر المواعيد" بـ "سجل الزيارات" -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>سجل الزيارات</h5>
            <a href="{{ route('visits.create', ['patient_id' => $patient->id]) }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i>زيارة جديدة
            </a>
        </div>
    </div>
    <div class="card-body">
        @if($patient->visits->count() > 0)
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>نوع الزيارة</th>
                            <th>الطبيب</th>
                            <th>الشكوى</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($patient->visits->take(5) as $visit)
                        <tr>
                            <td>{{ $visit->visit_date ? $visit->visit_date->format('Y-m-d') : 'غير محدد' }}</td>
                            <td>
                                <span class="badge bg-info">{{ $visit->visit_type_text }}</span>
                            </td>
                            <td>د. {{ $visit->doctor?->user?->name ?? 'غير محدد' }}</td>
                            <td>{{ Str::limit($visit->chief_complaint, 30) }}</td>
                            <td>
                                <a href="{{ route('visits.show', $visit) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="text-center mt-3">
                <a href="{{ route('visits.index', ['patient_id' => $patient->id]) }}" class="btn btn-outline-primary btn-sm">عرض جميع الزيارات</a>
            </div>
        @else
            <p class="text-center text-muted">لا توجد زيارات مسجلة</p>
        @endif
    </div>
</div>