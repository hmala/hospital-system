@extends('layouts.app')

@section('content')
<div class="container">
    <h3>إنشاء باقة جديدة</h3>
    @include('admin.packages.form', ['action' => route('admin.packages.store'), 'method' => 'POST', 'tests' => $tests])
</div>
@endsection
