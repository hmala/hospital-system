@extends('layouts.app')

@section('content')
<div class="container">
    <h2>تعديل الرابط</h2>
    <form action="{{ route('sidebar-links.update', $sidebarLink) }}" method="POST">
        @csrf
        @method('PUT')
        @include('sidebar-links.form')
    </form>
</div>
@endsection
