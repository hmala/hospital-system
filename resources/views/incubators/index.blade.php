@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-baby me-2 text-primary"></i>
                        إدارة حاضنات الخدج - ط6
                    </h2>
                    <p class="text-muted">عرض وإدارة جميع الحاضنات في قسم العناية المركزة للخدج</p>
                </div>
                <div>
                    @can('create', App\Models\Incubator::class)
                    <a href="{{ route('incubators.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        إضافة حاضنة جديدة
                    </a>
                    @endcan
                    <a href="{{ route('incubator-reservations.occupied') }}" class="btn btn-info">
                        <i class="fas fa-list me-1"></i>
                        الحاضنات المشغولة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

  
    <!-- عدادات الغرف -->
    <div class="row mb-4">
        @foreach($rooms as $room)
            <div class="col-md-3">
                <div class="card shadow-sm border-secondary room-counter" data-room-id="{{ $room->id }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">{{ $room->room_number }}</h6>
                                <div class="small">
                                    <span class="badge bg-primary">{{ $room->incubators_count }} حاضنة</span>
                                    <span class="badge bg-danger">{{ $room->occupied_incubators_count }} مشغولة</span>
                                </div>
                            </div>
                            <div class="text-secondary">
                                <i class="fas fa-door-open fa-2x"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="badge bg-{{ $room->status_color }}">{{ $room->status_name }}</span>
                            <span class="badge bg-{{ $room->room_type_color }}">{{ $room->room_type_name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- عرض الحاضنات -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-th me-2"></i>
                جميع الحاضنات
            </h5>
        </div>
        <div class="card-body">
            <!-- تصنيف حسب النوع -->
            <div class="row mb-4">
                <div class="col-12">
                    <ul class="nav nav-pills" id="incubatorTypeTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="pill" 
                                    data-bs-target="#all" type="button" role="tab">
                                <i class="fas fa-list me-1"></i>
                                الكل ({{ $stats['total'] }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="normal-tab" data-bs-toggle="pill" 
                                    data-bs-target="#normal" type="button" role="tab">
                                <i class="fas fa-baby me-1"></i>
                                عادية ({{ $stats['normal'] }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="oxygen-tab" data-bs-toggle="pill" 
                                    data-bs-target="#oxygen" type="button" role="tab">
                                <i class="fas fa-lungs me-1"></i>
                                أكسجين ({{ $stats['oxygen'] }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="phototherapy-tab" data-bs-toggle="pill" 
                                    data-bs-target="#phototherapy" type="button" role="tab">
                                <i class="fas fa-sun me-1"></i>
                                علاج ضوئي ({{ $stats['phototherapy'] }})
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- عرض شبكي للحاضنات -->
            <div class="tab-content" id="incubatorTypeTabsContent">
                <div class="tab-pane fade show active" id="all" role="tabpanel">
                    @include('incubators.partials.incubator-grid', ['incubators' => $incubators])
                </div>
                <div class="tab-pane fade" id="normal" role="tabpanel">
                    @include('incubators.partials.incubator-grid', [
                        'incubators' => $incubators->where('incubator_type', 'normal')
                    ])
                </div>
                <div class="tab-pane fade" id="oxygen" role="tabpanel">
                    @include('incubators.partials.incubator-grid', [
                        'incubators' => $incubators->where('incubator_type', 'oxygen')
                    ])
                </div>
                <div class="tab-pane fade" id="phototherapy" role="tabpanel">
                    @include('incubators.partials.incubator-grid', [
                        'incubators' => $incubators->where('incubator_type', 'phototherapy')
                    ])
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.incubator-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.incubator-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.incubator-card.available {
    border-left: 5px solid #28a745;
}

.incubator-card.occupied {
    border-left: 5px solid #dc3545;
    background: rgba(220, 53, 69, 0.12) !important;
    color: #721c24 !important;
}

.incubator-card.occupied .card-header,
.incubator-card.occupied .card-body {
    background: rgba(220, 53, 69, 0.04) !important;
}

.incubator-card.maintenance {
    border-left: 5px solid #ffc107;
}

.room-counter.selected {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 3px rgba(13,110,253,0.25) !important;
}
</style>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roomCounters = Array.from(document.querySelectorAll('.room-counter'));
        const incubatorCards = Array.from(document.querySelectorAll('.incubator-card'));

        function clearRoomSelection() {
            roomCounters.forEach(c => c.classList.remove('selected'));
            incubatorCards.forEach(card => card.style.display = '');
        }

        function filterByRoom(roomId) {
            incubatorCards.forEach(card => {
                const cardRoomId = card.dataset.roomId;
                card.style.display = (cardRoomId === roomId) ? '' : 'none';
            });
        }

        const roomContainer = document.querySelector('.row.mb-4');
        if (!roomContainer) return;

        roomContainer.addEventListener('click', function(event) {
            const counter = event.target.closest('.room-counter');
            if (!counter) return;

            const roomId = counter.dataset.roomId;
            if (!roomId) return;

            if (counter.classList.contains('selected')) {
                clearRoomSelection();
                return;
            }

            roomCounters.forEach(c => c.classList.remove('selected'));
            counter.classList.add('selected');
            filterByRoom(roomId);
        });
    });
</script>
@endsection
