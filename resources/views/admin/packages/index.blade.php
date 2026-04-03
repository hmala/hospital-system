@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>باقات المختبر</h3>
        <a href="{{ route('admin.packages.create') }}" class="btn btn-primary">إنشاء باقة جديدة</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>السعر</th>
                    <th>نشط</th>
                    <th>اختبارات</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($packages as $package)
                <tr>
                    <td>{{ $package->id }}</td>
                    <td>{{ $package->name }}</td>
                    <td>{{ number_format($package->price, 0) }}</td>
                    <td>{{ $package->is_active ? 'نعم' : 'لا' }}</td>
                    <td>{{ $package->labTests()->count() }}</td>
                    <td>
                        <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                        <form action="{{ route('admin.packages.destroy', $package) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">حذف</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $packages->links() }}</div>
</div>
@endsection
