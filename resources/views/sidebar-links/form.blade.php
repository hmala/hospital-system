@php
    // form partial for create/edit
    $isEdit = isset($sidebarLink);
    $roles = $roles ?? [];
@endphp
<div class="mb-3">
    <label class="form-label">العنوان</label>
    <input type="text" name="title" class="form-control" value="{{ old('title', $sidebarLink->title ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">الرابط (route name)</label>
    <input type="text" name="route" class="form-control" value="{{ old('route', $sidebarLink->route ?? '') }}">
    <small class="text-muted">مثال: "dashboard" أو "patients.index"</small>
</div>
<div class="mb-3">
    <label class="form-label">أيقونة FontAwesome</label>
    <input type="text" name="icon" class="form-control" value="{{ old('icon', $sidebarLink->icon ?? '') }}">
    <small class="text-muted">مثال: "fas fa-user"</small>
</div>
<div class="mb-3">
    <label class="form-label">الأدوار المسموح لها (اختياري)</label>
    <select name="roles[]" class="form-select" multiple>
        @foreach($roles as $role)
            <option value="{{ $role }}" {{ in_array($role, old('roles', $sidebarLink->roles ?? [])) ? 'selected' : '' }}>{{ $role }}</option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label class="form-label">الصلاحية المطلوبة (اختياري)</label>
    <input type="text" name="permission" class="form-control" value="{{ old('permission', $sidebarLink->permission ?? '') }}">
</div>
<div class="mb-3">
    <label class="form-label">الترتيب</label>
    <input type="number" name="order" class="form-control" value="{{ old('order', $sidebarLink->order ?? 0) }}">
</div>
<div class="form-check mb-3">
    <input type="checkbox" name="enabled" value="1" class="form-check-input" {{ old('enabled', $sidebarLink->enabled ?? true) ? 'checked' : '' }}>
    <label class="form-check-label">مفعل</label>
</div>
<button type="submit" class="btn btn-primary">{{ $isEdit ? 'تحديث' : 'إنشاء' }}</button>