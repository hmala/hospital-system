@extends('layouts.app')

@section('content')
<div class="container">
    <h2>روابط القائمة الجانبية</h2>
    <a href="{{ route('sidebar-links.create') }}" class="btn btn-success mb-3">إضافة رابط جديد</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>العنوان</th>
                <th>الرابط</th>
                <th>أدوار</th>
                <th>صلاحية</th>
                <th>ترتيب</th>
                <th>مفعل</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            @foreach($links as $link)
            <tr>
                <td>{{ $link->title }}</td>
                <td>{{ $link->route }}</td>
                <td>{{ is_array($link->roles) ? implode(',', $link->roles) : '' }}</td>
                <td>{{ $link->permission }}</td>
                <td>{{ $link->order }}</td>
                <td>{{ $link->enabled ? 'نعم' : 'لا' }}</td>
                <td>
                    <a href="{{ route('sidebar-links.edit', $link) }}" class="btn btn-sm btn-primary">تعديل</a>
                    <form action="{{ route('sidebar-links.destroy', $link) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد؟')">حذف</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
