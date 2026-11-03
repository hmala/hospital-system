@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0 text-primary"><i class="fas fa-balance-scale text-warning me-2"></i> إعدادات العقوبات والمكافآت (لائحة الإجراءات)</h3>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addActionModal">
            <i class="fas fa-plus"></i> إضافة إجراء جديد
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> <strong>يوجد خطأ في الإدخال:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>النوع</th>
                            <th>اسم الإجراء (اللائحة)</th>
                            <th>نوع التأثير</th>
                            <th>مقدار التأثير</th>
                            <th>الوصف</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settings as $setting)
                        <tr>
                            <td>
                                @if($setting->category == 'penalty')
                                    <span class="badge bg-danger"><i class="fas fa-exclamation-circle"></i> عقوبة</span>
                                @elseif($setting->category == 'bonus')
                                    <span class="badge bg-success"><i class="fas fa-gift"></i> مكافأة</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="fas fa-bell"></i> إنذار</span>
                                @endif
                            </td>
                            <td class="fw-bold">{{ $setting->title }}</td>
                            <td>
                                @if($setting->effect_type == 'none')
                                    <span class="text-muted">بدون تأثير مالي</span>
                                @elseif($setting->effect_type == 'amount')
                                    مبلغ مالي ثابت
                                @elseif($setting->effect_type == 'days')
                                    خصم/إضافة أيام
                                @elseif($setting->effect_type == 'percentage')
                                    نسبة من الراتب
                                @endif
                            </td>
                            <td>
                                @if($setting->effect_type == 'none')
                                    -
                                @elseif($setting->effect_type == 'amount')
                                    <span class="text-primary fw-bold">{{ number_format($setting->effect_value, 0) }} د.ع</span>
                                @elseif($setting->effect_type == 'days')
                                    <span class="text-primary fw-bold">{{ rtrim(rtrim(number_format($setting->effect_value, 2), '0'), '.') }} أيام</span>
                                @elseif($setting->effect_type == 'percentage')
                                    <span class="text-primary fw-bold">{{ rtrim(rtrim(number_format($setting->effect_value, 2), '0'), '.') }}%</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ Str::limit($setting->description, 40) }}</small>
                            </td>
                            <td>
                                @if($setting->is_active)
                                    <span class="badge bg-success">فعال</span>
                                @else
                                    <span class="badge bg-secondary">معطل</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editActionModal{{ $setting->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('hr.action_settings.destroy', $setting->id) }}" method="POST" class="d-inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد من حذف هذا الإجراء؟')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editActionModal{{ $setting->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('hr.action_settings.update', $setting->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header bg-light">
                                            <h5 class="modal-title">تعديل إجراء: {{ $setting->title }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">النوع <span class="text-danger">*</span></label>
                                                <select name="category" class="form-select" required>
                                                    <option value="penalty" {{ $setting->category == 'penalty' ? 'selected' : '' }}>عقوبة (Penalty)</option>
                                                    <option value="bonus" {{ $setting->category == 'bonus' ? 'selected' : '' }}>مكافأة (Bonus)</option>
                                                    <option value="warning" {{ $setting->category == 'warning' ? 'selected' : '' }}>إنذار (Warning)</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">اسم الإجراء (اللائحة) <span class="text-danger">*</span></label>
                                                <input type="text" name="title" class="form-control" value="{{ $setting->title }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">نوع التأثير المالي <span class="text-danger">*</span></label>
                                                <select name="effect_type" class="form-select effect-type-select" required>
                                                    <option value="none" {{ $setting->effect_type == 'none' ? 'selected' : '' }}>بدون تأثير (توبيخ/إنذار فقط)</option>
                                                    <option value="amount" {{ $setting->effect_type == 'amount' ? 'selected' : '' }}>مبلغ مالي ثابت (د.ع)</option>
                                                    <option value="days" {{ $setting->effect_type == 'days' ? 'selected' : '' }}>أيام عمل (يخصم/يضاف راتبها)</option>
                                                    <option value="percentage" {{ $setting->effect_type == 'percentage' ? 'selected' : '' }}>نسبة مئوية (%) من الراتب الأساسي</option>
                                                </select>
                                            </div>
                                            <div class="mb-3 effect-value-container" style="{{ $setting->effect_type == 'none' ? 'display: none;' : '' }}">
                                                <label class="form-label">مقدار التأثير <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" name="effect_value" class="form-control" value="{{ rtrim(rtrim($setting->effect_value, '0'), '.') }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">الوصف التفصيلي (اختياري)</label>
                                                <textarea name="description" class="form-control" rows="2">{{ $setting->description }}</textarea>
                                            </div>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive{{ $setting->id }}" {{ $setting->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label" for="isActive{{ $setting->id }}">الإجراء فعال ومتاح للاستخدام</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-folder-open fs-1 mb-3 text-light"></i><br>
                                لم يتم إضافة أي إجراءات (عقوبات/مكافآت) في اللائحة حتى الآن.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('hr.action_settings.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title">إضافة إجراء جديد إلى اللائحة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">النوع <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="penalty">عقوبة (Penalty)</option>
                            <option value="bonus">مكافأة (Bonus)</option>
                            <option value="warning">إنذار (Warning)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اسم الإجراء (اللائحة) <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="مثال: غياب بدون عذر، تأخير 30 دقيقة..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">نوع التأثير المالي <span class="text-danger">*</span></label>
                        <select name="effect_type" class="form-select effect-type-select" required>
                            <option value="none">بدون تأثير (توبيخ/إنذار فقط)</option>
                            <option value="amount">مبلغ مالي ثابت (د.ع)</option>
                            <option value="days">أيام عمل (يخصم/يضاف راتبها)</option>
                            <option value="percentage">نسبة مئوية (%) من الراتب الأساسي</option>
                        </select>
                    </div>
                    <div class="mb-3 effect-value-container" style="display: none;">
                        <label class="form-label">مقدار التأثير <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="effect_value" class="form-control" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف التفصيلي (اختياري)</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="اكتب تفاصيل إضافية لشرح هذا البند..."></textarea>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActiveNew" checked>
                        <label class="form-check-label" for="isActiveNew">الإجراء فعال ومتاح للاستخدام</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> إضافة للإعدادات</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const effectSelects = document.querySelectorAll('.effect-type-select');
    
    effectSelects.forEach(select => {
        select.addEventListener('change', function() {
            const container = this.closest('.modal-body').querySelector('.effect-value-container');
            if (this.value === 'none') {
                container.style.display = 'none';
                container.querySelector('input').value = 0;
            } else {
                container.style.display = 'block';
            }
        });
    });
});
</script>
@endsection
