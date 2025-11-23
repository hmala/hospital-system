@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">إضافة عيادة جديدة</h4>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('departments.store') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="name" class="col-md-4 col-form-label text-md-end">اسم العيادة</label>
                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                       name="name" value="{{ old('name') }}" required autofocus>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="type" class="col-md-4 col-form-label text-md-end">نوع العيادة</label>
                            <div class="col-md-6">
                                <select id="type" class="form-control @error('type') is-invalid @enderror" name="type" required>
                                    <option value="">اختر نوع العيادة</option>
                                    <option value="internal" {{ old('type') == 'internal' ? 'selected' : '' }}>باطنية</option>
                                    <option value="surgery" {{ old('type') == 'surgery' ? 'selected' : '' }}>جراحة</option>
                                    <option value="pediatrics" {{ old('type') == 'pediatrics' ? 'selected' : '' }}>أطفال</option>
                                    <option value="obstetrics" {{ old('type') == 'obstetrics' ? 'selected' : '' }}>نسائية</option>
                                    <option value="orthopedics" {{ old('type') == 'orthopedics' ? 'selected' : '' }}>عظام</option>
                                    <option value="cardiology" {{ old('type') == 'cardiology' ? 'selected' : '' }}>قلب</option>
                                    <option value="dentistry" {{ old('type') == 'dentistry' ? 'selected' : '' }}>أسنان</option>
                                    <option value="dermatology" {{ old('type') == 'dermatology' ? 'selected' : '' }}>جلدية</option>
                                    <option value="emergency" {{ old('type') == 'emergency' ? 'selected' : '' }}>طوارئ</option>
                                    <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>أخرى</option>
                                </select>
                                @error('type')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="room_number" class="col-md-4 col-form-label text-md-end">رقم الغرفة</label>
                            <div class="col-md-6">
                                <input id="room_number" type="text" class="form-control @error('room_number') is-invalid @enderror"
                                       name="room_number" value="{{ old('room_number') }}" required>
                                @error('room_number')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="consultation_fee" class="col-md-4 col-form-label text-md-end">رسوم الكشف (دينار)</label>
                            <div class="col-md-6">
                                <input id="consultation_fee" type="number" step="0.01" class="form-control @error('consultation_fee') is-invalid @enderror"
                                       name="consultation_fee" value="{{ old('consultation_fee') }}" required>
                                @error('consultation_fee')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="working_hours_start" class="col-md-4 col-form-label text-md-end">ساعات العمل - من</label>
                            <div class="col-md-6">
                                <input id="working_hours_start" type="time" class="form-control @error('working_hours_start') is-invalid @enderror"
                                       name="working_hours_start" value="{{ old('working_hours_start') }}" required>
                                @error('working_hours_start')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="working_hours_end" class="col-md-4 col-form-label text-md-end">ساعات العمل - إلى</label>
                            <div class="col-md-6">
                                <input id="working_hours_end" type="time" class="form-control @error('working_hours_end') is-invalid @enderror"
                                       name="working_hours_end" value="{{ old('working_hours_end') }}" required>
                                @error('working_hours_end')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="max_patients_per_day" class="col-md-4 col-form-label text-md-end">الحد الأقصى للمرضى يومياً</label>
                            <div class="col-md-6">
                                <input id="max_patients_per_day" type="number" class="form-control @error('max_patients_per_day') is-invalid @enderror"
                                       name="max_patients_per_day" value="{{ old('max_patients_per_day', 30) }}" required>
                                @error('max_patients_per_day')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        العيادة نشطة
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    إضافة العيادة
                                </button>
                                <a href="{{ route('departments.index') }}" class="btn btn-secondary">
                                    إلغاء
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection