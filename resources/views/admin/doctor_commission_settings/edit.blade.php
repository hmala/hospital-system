@extends('layouts.app')

@section('content')
<div class="container">
    <h3>تعديل إعداد العمولة</h3>
    @include('admin.doctor_commission_settings.form', [
        'action' => route('admin.doctor-commission-settings.update', $doctorCommissionSetting),
        'method' => 'PUT',
        'doctors' => $doctors,
        'departments' => $departments,
        'serviceTypes' => $serviceTypes,
        'setting' => $doctorCommissionSetting,
    ])
</div>
@endsection
