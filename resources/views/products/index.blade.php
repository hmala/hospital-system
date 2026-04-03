@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>مواد المخزن</span>
            <a href="{{ route('products.create') }}" class="btn btn-sm btn-primary">إضافة مادة</a>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>الكود</th>
                        <th>الوحدة</th>
                        <th>قابل للتلف</th>
                        <th>تنبيه الكمية</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->code }}</td>
                        <td>{{ $product->unit }}</td>
                        <td>{{ $product->is_perishable ? 'نعم' : 'لا' }}</td>
                        <td>{{ $product->alert_quantity }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection