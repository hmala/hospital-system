@extends('layouts.app')

@section('content')
<div class="container">
    <h3>تعديل الباقة</h3>
    @include('admin.packages.form', ['action' => route('admin.packages.update', $package), 'method' => 'PUT', 'tests' => $tests, 'package' => $package, 'selected' => $selected])
</div>
@endsection
