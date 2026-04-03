<div class="row">
    @forelse($incubators as $incubator)
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="card incubator-card {{ $incubator->status }} shadow-sm h-100" 
                 data-room-id="{{ $incubator->room_id }}"
                 onclick="window.location.href='{{ route('incubators.show', $incubator) }}'">
                <div class="card-header text-white bg-{{ $incubator->type_color }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas {{ $incubator->type_icon }} me-2"></i>
                            رقم {{ $incubator->incubator_number }}
                        </h5>
                        <span class="badge bg-{{ $incubator->status_color }}">
                            {{ $incubator->status_name }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>النوع:</strong> {{ $incubator->type_name }}
                    </p>
                    
                    @if($incubator->room)
                        <p class="mb-2">
                            <i class="fas fa-door-open me-1 text-muted"></i>
                            <strong>الغرفة:</strong> {{ $incubator->room->room_number }}
                        </p>
                    @endif
                    
                    <p class="mb-2">
                        <i class="fas fa-money-bill-wave me-1 text-success"></i>
                        <strong>الأجرة:</strong> 
                        <span class="text-success">{{ number_format($incubator->daily_fee) }} د.ع/يوم</span>
                    </p>

                    @if($incubator->activeReservation)
                        @php $reservation = $incubator->activeReservation; @endphp
                        <hr>
                        <div class="alert alert-info mb-0 py-2">
                            <small>
                                <i class="fas fa-baby me-1"></i>
                                <strong>{{ $reservation->baby_name }}</strong><br>
                                <i class="fas fa-clock me-1"></i>
                                {{ $reservation->admission_date->format('Y-m-d') }}
                            </small>
                        </div>
                    @else
                        @if($incubator->status === 'available')
                            <hr>
                            <a href="{{ route('incubator-reservations.create', ['incubator_id' => $incubator->id]) }}" 
                               class="btn btn-sm btn-success w-100" onclick="event.stopPropagation();">
                                <i class="fas fa-plus me-1"></i>
                                حجز الآن
                            </a>
                        @endif
                    @endif
                </div>
                
                @if($incubator->description)
                    <div class="card-footer text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        {{ Str::limit($incubator->description, 50) }}
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>
                لا توجد حاضنات متاحة في هذا التصنيف
            </div>
        </div>
    @endforelse
</div>
