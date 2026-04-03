<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">تفاصيل الباقة</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if($method === 'PUT')
                @method('PUT')
            @endif

            <div class="mb-3">
                <label class="form-label">اسم الباقة</label>
                <input type="text" name="name" value="{{ old('name', $package->name ?? '') }}" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">كود (اختياري)</label>
                <input type="text" name="code" value="{{ old('code', $package->code ?? '') }}" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">وصف</label>
                <textarea name="description" class="form-control">{{ old('description', $package->description ?? '') }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">سعر</label>
                <input type="number" name="price" value="{{ old('price', $package->price ?? 0) }}" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">اختبارات الباقة</label>
                <select name="tests[]" class="form-select tests-select" multiple size="10">
                    @foreach($tests as $test)
                        <option value="{{ $test->id }}" data-price="{{ $test->price ?? 0 }}" {{ (in_array($test->id, $selected ?? []) ? 'selected' : '') }}>{{ $test->name }} ({{ $test->code }}) - {{ number_format($test->price) }} د.ع</option>
                    @endforeach
                </select>
                <div class="mt-2 small text-muted">
                    <span id="selected-count">{{ count($selected ?? []) }}</span> اختبار محدد — مجموع السعر: <strong id="selected-total">{{ number_format(collect($tests)->whereIn('id', $selected ?? [])->sum('price')) }}</strong> د.ع
                </div>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ (old('is_active', $package->is_active ?? true) ? 'checked' : '') }}>
                <label class="form-check-label" for="is_active">نشط</label>
            </div>

            <button class="btn btn-primary">حفظ</button>
            <a href="{{ route('admin.packages.index') }}" class="btn btn-secondary">إلغاء</a>
        </form>
    </div>
</div>

@section('scripts')
@parent
<script>
    (function(){
        // init select2 for multi-select tests
        if (typeof $ === 'undefined') return;
        $(function(){
            const $select = $('.tests-select');
            if ($select.length && $.fn.select2) {
                $select.select2({
                    placeholder: 'اختر الاختبارات...',
                    width: '100%'
                });

                function updateSummary() {
                    let total = 0;
                    let count = 0;
                    $select.find(':selected').each(function(){
                        count++;
                        const p = parseFloat($(this).data('price')) || 0;
                        total += p;
                    });
                    $('#selected-count').text(count);
                    $('#selected-total').text(new Intl.NumberFormat('ar-EG').format(total));
                }

                $select.on('change', updateSummary);
                // initial
                updateSummary();
            }
        });
    })();
</script>
@endsection
