@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-bell me-2"></i>
                    الإشعارات
                </h2>
                <div>
                    <button type="button" class="btn btn-outline-primary me-2" id="markAllAsRead">
                        <i class="fas fa-check-double me-1"></i>
                        تحديد الكل كمقروء
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة للوحة التحكم
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- قائمة الإشعارات -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    @if($notifications->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($notifications as $notification)
                                @php
                                    $data = is_string($notification->data) ? json_decode($notification->data, true) : $notification->data;
                                @endphp
                                <div class="list-group-item {{ $notification->read_at ? '' : 'bg-light' }}">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                {{ $data['title'] ?? 'إشعار' }}
                                                @if(!$notification->read_at)
                                                    <span class="badge bg-primary ms-2">جديد</span>
                                                @endif
                                            </h6>
                                            <p class="mb-1">{{ $data['message'] ?? '' }}</p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $notification->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                        <div class="d-flex flex-column align-items-end">
                                            @if(isset($data['url']) && $data['url'] !== '#')
                                                <a href="{{ $data['url'] }}" class="btn btn-sm btn-outline-primary mb-2">
                                                    <i class="fas fa-eye me-1"></i>
                                                    عرض
                                                </a>
                                            @endif
                                            @if(!$notification->read_at)
                                                <button type="button" class="btn btn-sm btn-outline-success mark-as-read"
                                                        data-id="{{ $notification->id }}">
                                                    <i class="fas fa-check me-1"></i>
                                                    تحديد كمقروء
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $notifications->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد إشعارات</h5>
                            <p class="text-muted">ستظهر هنا جميع الإشعارات الخاصة بك</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // تحديد الإشعار كمقروء
    document.querySelectorAll('.mark-as-read').forEach(button => {
        button.addEventListener('click', function() {
            const notificationId = this.dataset.id;
            const listItem = this.closest('.list-group-item');

            fetch(`{{ url('/notifications') }}/${notificationId}/mark-as-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    listItem.classList.remove('bg-light');
                    this.remove();
                    // تحديث عداد الإشعارات في navigation
                    updateNotificationCount();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء تحديث الإشعار');
            });
        });
    });

    // تحديد جميع الإشعارات كمقروءة
    document.getElementById('markAllAsRead').addEventListener('click', function() {
        fetch('{{ route("notifications.mark-all-as-read") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // إزالة الخلفية الخفيفة من جميع الإشعارات
                document.querySelectorAll('.list-group-item').forEach(item => {
                    item.classList.remove('bg-light');
                });
                // إزالة أزرار "تحديد كمقروء"
                document.querySelectorAll('.mark-as-read').forEach(button => {
                    button.remove();
                });
                // تحديث عداد الإشعارات في navigation
                updateNotificationCount();
                // إظهار رسالة نجاح
                showAlert('تم تحديد جميع الإشعارات كمقروءة', 'success');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('حدث خطأ أثناء تحديث الإشعارات', 'danger');
        });
    });
});

function updateNotificationCount() {
    // تحديث عداد الإشعارات في navigation bar
    const badge = document.querySelector('.navbar .badge');
    if (badge) {
        const currentCount = parseInt(badge.textContent) || 0;
        if (currentCount > 0) {
            const newCount = currentCount - 1;
            if (newCount > 0) {
                badge.textContent = newCount;
            } else {
                badge.remove();
            }
        }
    }
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.container-fluid .row');
    container.insertBefore(alertDiv, container.firstChild);

    // إزالة التنبيه تلقائياً بعد 5 ثوانٍ
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
@endsection