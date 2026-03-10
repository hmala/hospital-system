@extends('layouts.app')

@section('content')
<div class="container">
    <h2>إنشاء رابط جديد</h2>
    <form action="{{ route('sidebar-links.store') }}" method="POST">
        @csrf
        @include('sidebar-links.form')
    </form>
</div>
@endsection
