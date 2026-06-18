@extends('layouts.app')

@section('content')
<div class="container">
    <h3>إضافة إعداد عمولة جديد</h3>
    @include('admin.doctor_commission_settings.form', [
        'action' => route('admin.doctor-commission-settings.store'),
        'method' => 'POST',
        'doctors' => $doctors,
        'departments' => $departments,
        'serviceTypes' => $serviceTypes,
        'setting' => null,
    ])
</div>
@endsection
