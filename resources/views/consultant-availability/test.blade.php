<!-- resources/views/consultant-availability/test.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>اختبار صفحة توفر الأطباء الاستشاريين</h1>
    <p>عدد الأطباء: {{ $consultantDoctors->count() }}</p>

    @if($consultantDoctors->count() > 0)
        <div class="row">
            @foreach($consultantDoctors as $doctor)
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5>{{ $doctor->user->name ?? 'غير محدد' }}</h5>
                            <p>{{ $doctor->specialization }}</p>
                            <p>التوفر: {{ $doctor->is_available_today ? 'متوفر' : 'غير متوفر' }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p>لا يوجد أطباء استشاريين</p>
    @endif
</div>
@endsection