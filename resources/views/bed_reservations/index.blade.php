@extends('layouts.app')

@section('styles')
<style>
.bed-reservations-page {
    background-color: #f0f8ff;
}
.bed-reservations-page .card {
    border: 2px solid #17a2b8;
}
.bed-reservations-page table {
    background: #ffffff;
}
.bed-reservations-page h2 {
    color: #17a2b8;
}
</style>
@endsection

@section('content')
<div class="container-fluid bed-reservations-page">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-bed me-2"></i>حدد الرقود المبدئي</h2>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            @if($reservations->count())
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>المريض</th>
                        <th>الطبيب المرسل</th>
                        <th>القسم</th>
                        <th>الغرفة</th>
                        <th>التاريخ</th>
                        <th>الوقت</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reservations as $r)
                    <tr>
                        <td>{{ $r->patient->user->name }}</td>
                        <td>{{ $r->doctor->user->name ?? '-' }}</td>
                        <td>{{ $r->department->name }}</td>
                        <td>@if($r->room){{ $r->room->room_number }}@else - @endif</td>
                        <td>{{ $r->scheduled_date->format('Y-m-d') }}</td>
                        <td>{{ $r->scheduled_time->format('H:i') }}</td>
                        <td>
                            @if($r->status == 'pending')
                                <span class="badge bg-warning">قيد الانتظار</span>
                            @elseif($r->status == 'confirmed')
                                <span class="badge bg-success">دخول الغرفة</span>
                            @elseif($r->status == 'completed')
                                <span class="badge bg-secondary">مكتملة</span>
                            @elseif($r->status == 'cancelled')
                                <span class="badge bg-danger">ملغاة</span>
                            @else
                                {{ $r->status }}
                            @endif
                        </td>
                        <td>
                            @if($r->status == 'pending')
                                <form action="{{ route('bed-reservations.confirm', $r) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary" title="تأكيد دخول المريض">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $reservations->links() }}
            @else
            <div class="alert alert-info">لا توجد حجوزات رقود حتى الآن.</div>
            @endif
        </div>
    </div>
</div>
@endsection