@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Ø§Ù„ØªØ±ÙˆÙŠØ³Ø© ÙˆØ£Ø²Ø±Ø§Ø± Ø§Ù„ØªØ­ÙƒÙ… -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-id-card me-2"></i>Ø¥Ø¶Ø¨Ø§Ø±Ø© Ø§Ù„Ù…ÙˆØ¸Ù: {{ $employee->full_name }}
            </h2>
            <p class="text-muted small mb-0">ÙƒÙˆØ¯ Ø§Ù„Ù…ÙˆØ¸Ù: <span class="badge bg-light text-dark border font-monospace">{{ $employee->employee_code }}</span> | Ø§Ù„Ù…Ø³Ù…Ù‰: {{ $employee->job_title }}</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Ø·Ø¨Ø§Ø¹Ø© Ø§Ù„Ø¥Ø¶Ø¨Ø§Ø±Ø©
            </button>
            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                <i class="fas fa-file-upload me-1"></i> Ø¥Ø¶Ø§ÙØ© Ù…Ø³ØªÙ…Ø³Ùƒ Ø±Ø³Ù…ÙŠ
            </button>
            @can('edit employees')
                <a href="{{ route('hr.employees.edit', $employee->id) }}" class="btn btn-primary btn-sm shadow-sm">
                    <i class="fas fa-user-edit me-1"></i> ØªØ¹Ø¯ÙŠÙ„ Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª
                </a>
            @endcan
            
            <a href="{{ route('hr.employees.roster', $employee->id) }}" class="btn btn-outline-info btn-sm text-dark fw-bold">
                <i class="fas fa-calendar-alt me-1"></i> Ø¬Ø¯ÙˆÙ„ Ø§Ù„Ø®ÙØ§Ø±Ø§Øª (Roster)
            </a>
            <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> Ø§Ù„Ù‚Ø§Ø¦Ù…Ø©
            </a>
        </div>
    </div>

    <!-- Ø±Ø³Ø§Ø¦Ù„ Ø§Ù„Ù†Ø¬Ø§Ø­ Ø£Ùˆ Ø§Ù„ØªÙ†Ø¨ÙŠÙ‡ -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- ØªÙ†Ø¨ÙŠÙ‡Ø§Øª Ø§Ù„ØªØ±Ø§Ø®ÙŠØµ Ø§Ù„Ø·Ø¨ÙŠØ© -->
    @if($employee->isMedicalStaff() && $employee->license_expiry_date)
        @if($employee->isLicenseExpired())
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4">
                <i class="fas fa-ban fa-2x me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">ØªÙ†Ø¨ÙŠÙ‡: ØªØ±Ø®ÙŠØµ Ù…Ø²Ø§ÙˆÙ„Ø© Ø§Ù„Ù…Ù‡Ù†Ø© Ù…Ù†ØªÙ‡ÙŠ Ø§Ù„ØµÙ„Ø§Ø­ÙŠØ©!</h6>
                    <small>Ø§Ù†ØªÙ‡Øª ØµÙ„Ø§Ø­ÙŠØ© Ø¥Ø¬Ø§Ø²Ø© Ø§Ù„Ù…Ù…Ø§Ø±Ø³Ø© ÙÙŠ ØªØ§Ø±ÙŠØ® {{ $employee->license_expiry_date->format('Y-m-d') }} (Ù…Ù†Ø° {{ $employee->license_expiry_date->diffForHumans() }}). ÙŠØ±Ø¬Ù‰ Ù…Ø±Ø§Ø¬Ø¹Ø© Ø§Ù„Ù…ÙˆØ¸Ù Ù„ØªØ¬Ø¯ÙŠØ¯ Ø§Ù„ØªØ±Ø®ÙŠØµ.</small>
                </div>
            </div>
        @elseif($employee->isLicenseExpiringSoon())
            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4">
                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                    <h6 class="fw-bold mb-0">ØªÙ†Ø¨ÙŠÙ‡: ØªØ±Ø®ÙŠØµ Ù…Ø²Ø§ÙˆÙ„Ø© Ø§Ù„Ù…Ù‡Ù†Ø© ÙŠÙ†ØªÙ‡ÙŠ Ù‚Ø±ÙŠØ¨Ø§Ù‹!</h6>
                    <small>ÙŠØªØ¨Ù‚Ù‰ Ø¹Ù„Ù‰ Ø§Ù†ØªÙ‡Ø§Ø¡ Ø§Ù„ØªØ±Ø®ÙŠØµ <strong>{{ now()->diffInDays($employee->license_expiry_date) }} ÙŠÙˆÙ…</strong> (ØªØ§Ø±ÙŠØ® Ø§Ù„Ø§Ù†ØªÙ‡Ø§Ø¡: {{ $employee->license_expiry_date->format('Y-m-d') }}). ÙŠØ±Ø¬Ù‰ Ø§ØªØ®Ø§Ø° Ø¥Ø¬Ø±Ø§Ø¡Ø§Øª Ø§Ù„ØªØ¬Ø¯ÙŠØ¯.</small>
                </div>
            </div>
        @endif
    @endif

    <div class="row g-4">
        <!-- Ø§Ù„Ø¨Ø·Ø§Ù‚Ø© Ø§Ù„Ø¬Ø§Ù†Ø¨ÙŠØ©: Ø§Ù„Ø¨Ø§Ø¬Ø© ÙˆØ§Ù„Ù…Ù„Ø®Øµ Ø§Ù„Ø³Ø±ÙŠØ¹ -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 text-center p-4 mb-4 bg-white">
                <div class="mb-3">
                    @if($employee->profile_photo)
                        <img src="{{ asset('storage/' . $employee->profile_photo) }}" alt="" class="rounded-circle shadow-sm border p-1" style="width: 140px; height: 140px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 140px; height: 140px; font-size: 50px;">
                            {{ mb_substr($employee->full_name, 0, 1) }}
                        </div>
                    @endif
                </div>

                <h5 class="fw-bold text-dark mb-1">{{ $employee->full_name }}</h5>
                <p class="text-primary fw-semibold mb-2">{{ $employee->job_title }}</p>

                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-light text-dark border font-monospace fs-6 px-3 py-2">
                        <i class="fas fa-id-badge me-1 text-secondary"></i>{{ $employee->employee_code }}
                    </span>
                </div>

                <div class="d-flex justify-content-center gap-2 mb-3">
                    @if($employee->staff_type === 'medical')
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1"><i class="fas fa-user-md me-1"></i>ÙƒØ§Ø¯Ø± Ø·Ø¨ÙŠ</span>
                    @elseif($employee->staff_type === 'nursing')
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1"><i class="fas fa-user-nurse me-1"></i>ÙƒØ§Ø¯Ø± ØªÙ…Ø±ÙŠØ¶ÙŠ</span>
                    @elseif($employee->staff_type === 'technical')
                        <span class="badge bg-purple bg-opacity-10 text-purple border border-purple border-opacity-25 px-2 py-1"><i class="fas fa-microscope me-1"></i>ÙƒØ§Ø¯Ø± ÙÙ†ÙŠ</span>
                    @elseif($employee->staff_type === 'administrative')
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1"><i class="fas fa-user-tie me-1"></i>ÙƒØ§Ø¯Ø± Ø¥Ø¯Ø§Ø±ÙŠ</span>
                    @else
                        <span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25 px-2 py-1"><i class="fas fa-tools me-1"></i>Ø®Ø¯Ù…Ø§Øª</span>
                    @endif

                    @if($employee->status === 'active')
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Ø¹Ù„Ù‰ Ø±Ø£Ø³ Ø§Ù„Ø¹Ù…Ù„</span>
                    @elseif($employee->status === 'on_leave')
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">ÙÙŠ Ø¥Ø¬Ø§Ø²Ø©</span>
                    @elseif($employee->status === 'suspended')
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">Ù…ÙˆÙ‚ÙˆÙ Ù…Ø¤Ù‚ØªØ§Ù‹</span>
                    @else
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">{{ $employee->status_name }}</span>
                    @endif
                </div>

                <hr class="my-3 opacity-25">

                <div class="text-start small">
                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                        <span class="text-muted"><i class="fas fa-hospital me-1"></i> Ø§Ù„Ù‚Ø³Ù…:</span>
                        <span class="fw-semibold text-dark">{{ $employee->department?->name ?? 'ØºÙŠØ± Ù…Ø­Ø¯Ø¯' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                        <span class="text-muted"><i class="fas fa-phone-alt me-1"></i> Ø§Ù„Ù‡Ø§ØªÙ:</span>
                        <span class="fw-semibold text-dark font-monospace">{{ $employee->phone }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                        <span class="text-muted"><i class="fas fa-envelope me-1"></i> Ø§Ù„Ø¨Ø±ÙŠØ¯:</span>
                        <span class="fw-semibold text-dark">{{ $employee->email ?? 'â€”' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                        <span class="text-muted"><i class="fas fa-tint me-1 text-danger"></i> ÙØµÙŠÙ„Ø© Ø§Ù„Ø¯Ù…:</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger">{{ $employee->blood_group ?? 'â€”' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                        <span class="text-muted"><i class="fas fa-calendar-check me-1"></i> ØªØ§Ø±ÙŠØ® Ø§Ù„Ù…Ø¨Ø§Ø´Ø±Ø©:</span>
                        <span class="fw-semibold text-dark">{{ $employee->hire_date?->format('Y-m-d') ?? 'â€”' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted"><i class="fas fa-file-pdf me-1 text-danger"></i> Ø§Ù„Ù…Ø³ØªÙ…Ø³ÙƒØ§Øª Ø§Ù„Ù…Ø¤Ø±Ø´ÙØ©:</span>
                        <span class="badge bg-primary rounded-pill">{{ $employee->documents->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- Ø¨Ø·Ø§Ù‚Ø© Ø­Ø³Ø§Ø¨ Ø§Ù„Ù†Ø¸Ø§Ù… Ø§Ù„Ù…Ø±ØªØ¨Ø· -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-user-shield text-primary me-2"></i>Ø­Ø³Ø§Ø¨ Ø§Ù„Ø¯Ø®ÙˆÙ„ Ù„Ù„Ù†Ø¸Ø§Ù…
                    </h6>
                </div>
                <div class="card-body p-3">
                    @if($employee->user)
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-success bg-opacity-10 text-success p-2 me-2">
                                <i class="fas fa-check fa-lg"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">{{ $employee->user->name }}</div>
                                <div class="text-muted small">{{ $employee->user->email }}</div>
                                <div class="mt-1">
                                    <span class="badge bg-secondary-subtle text-secondary small">Ø§Ù„Ø¯ÙˆØ±: {{ $employee->user->role ?? 'Ù…Ø³ØªØ®Ø¯Ù…' }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-2 text-muted small">
                            <i class="fas fa-user-slash fa-2x mb-1 text-secondary opacity-50"></i>
                            <p class="mb-0">Ø§Ù„Ù…ÙˆØ¸Ù ØºÙŠØ± Ù…Ø±ØªØ¨Ø· Ø¨Ø£ÙŠ Ø­Ø³Ø§Ø¨ Ø¯Ø®ÙˆÙ„ Ù„Ù„Ù†Ø¸Ø§Ù….</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Ø§Ù„Ø¹Ù…ÙˆØ¯ Ø§Ù„Ø±Ø¦ÙŠØ³ÙŠ: Ø§Ù„ØªÙØ§ØµÙŠÙ„ ÙˆØ§Ù„Ù…Ø³ØªÙ…Ø³ÙƒØ§Øª -->
        <div class="col-lg-8">
            <!-- 1. Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ø´Ø®ØµÙŠØ© ÙˆØ¬Ù‡Ø§Øª Ø§Ù„Ø§ØªØµØ§Ù„ -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-user text-primary me-2"></i>Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ø´Ø®ØµÙŠØ© ÙˆØ¬Ù‡Ø§Øª Ø§Ù„Ø§ØªØµØ§Ù„
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small">Ø§Ù„Ø§Ø³Ù… Ø§Ù„ÙƒØ§Ù…Ù„</label>
                            <div class="fw-bold text-dark">{{ $employee->full_name }}</div>
                        </div>

                        <div class="col-sm-6">
                            <label class="text-muted small">Ø±Ù‚Ù… Ø§Ù„Ø¨Ø·Ø§Ù‚Ø© Ø§Ù„Ù…ÙˆØ­Ø¯Ø© / Ø§Ù„Ù‡ÙˆÙŠØ© Ø§Ù„ÙˆØ·Ù†ÙŠØ©</label>
                            <div class="fw-bold text-dark font-monospace">{{ $employee->national_id ?? 'â€”' }}</div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">Ø§Ù„Ø¬Ù†Ø³</label>
                            <div class="fw-bold text-dark">{{ $employee->gender === 'male' ? 'Ø°ÙƒØ±' : 'Ø£Ù†Ø«Ù‰' }}</div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">ØªØ§Ø±ÙŠØ® Ø§Ù„Ù…ÙŠÙ„Ø§Ø¯</label>
                            <div class="fw-bold text-dark">
                                {{ $employee->date_of_birth ? $employee->date_of_birth->format('Y-m-d') . ' (' . $employee->date_of_birth->age . ' Ø³Ù†Ø©)' : 'â€”' }}
                            </div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">Ù‡Ø§ØªÙ Ø§Ù„Ø·ÙˆØ§Ø±Ø¦</label>
                            <div class="fw-bold text-dark font-monospace">{{ $employee->emergency_phone ?? 'â€”' }}</div>
                        </div>

                        <div class="col-12">
                            <label class="text-muted small">Ø¹Ù†ÙˆØ§Ù† Ø§Ù„Ø³ÙƒÙ† Ø§Ù„ÙƒØ§Ù…Ù„</label>
                            <div class="fw-bold text-dark">{{ $employee->address ?? 'ØºÙŠØ± Ù…Ø­Ø¯Ø¯' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„ÙˆØ¸ÙŠÙÙŠØ© ÙˆØ§Ù„ØªØ¹Ø§Ù‚Ø¯ ÙˆØ§Ù„Ø±Ø§ØªØ¨ -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-briefcase text-primary me-2"></i>ØªÙØ§ØµÙŠÙ„ Ø§Ù„ØªØ¹Ø§Ù‚Ø¯ ÙˆØ§Ù„ÙˆØ¸ÙŠÙØ©
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="text-muted small">Ù†ÙˆØ¹ Ø§Ù„ÙƒØ§Ø¯Ø±</label>
                            <div class="fw-bold text-dark">{{ $employee->staff_type_name }}</div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">Ø§Ù„Ù…Ø³Ù…Ù‰ Ø§Ù„ÙˆØ¸ÙŠÙÙŠ</label>
                            <div class="fw-bold text-dark">{{ $employee->job_title }}</div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">Ø§Ù„Ù‚Ø³Ù…</label>
                            <div class="fw-bold text-dark">{{ $employee->department?->name ?? 'ØºÙŠØ± Ù…Ø­Ø¯Ø¯' }}</div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">Ù†ÙˆØ¹ Ø§Ù„ØªØ¹Ø§Ù‚Ø¯</label>
                            <div class="fw-bold text-dark">{{ $employee->employment_type_name }}</div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">ØªØ§Ø±ÙŠØ® Ø§Ù„Ù…Ø¨Ø§Ø´Ø±Ø©</label>
                            <div class="fw-bold text-dark">{{ $employee->hire_date?->format('Y-m-d') }}</div>
                        </div>

                        <div class="col-sm-4">
                            <label class="text-muted small">ØªØ§Ø±ÙŠØ® Ø§Ù†ØªÙ‡Ø§Ø¡ Ø§Ù„Ø¹Ù‚Ø¯</label>
                            <div class="fw-bold text-dark">{{ $employee->contract_end_date?->format('Y-m-d') ?? 'Ø¹Ù‚Ø¯ Ø¯Ø§Ø¦Ù… / ØºÙŠØ± Ù…Ø­Ø¯Ø¯' }}</div>
                        </div>

                        <div class="col-sm-6">
                            <label class="text-muted small">Ø§Ù„Ø±Ø§ØªØ¨ Ø§Ù„Ø£Ø³Ø§Ø³ÙŠ Ø§Ù„Ø´Ù‡Ø±ÙŠ</label>
                            <div class="h5 fw-bold text-success mb-0">
                                {{ number_format($employee->basic_salary) }} <small class="fs-6 text-muted">Ø¯.Ø¹</small>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <label class="text-muted small">Ù…Ø¯Ø© Ø§Ù„Ø®Ø¯Ù…Ø© Ø¨Ø§Ù„Ù…Ø³ØªØ´ÙÙ‰</label>
                            <div class="fw-bold text-dark">
                                {{ $employee->hire_date ? $employee->hire_date->diffForHumans(['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) : 'â€”' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Ø§Ù„ØªØ±Ø§Ø®ÙŠØµ ÙˆØ§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ø·Ø¨ÙŠØ© (Ø¥Ø°Ø§ ÙƒØ§Ù† ÙƒØ§Ø¯Ø± Ø·Ø¨ÙŠ) -->
            @if($employee->isMedicalStaff())
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-info bg-opacity-10 py-3 border-bottom border-info border-opacity-25">
                        <h6 class="card-title fw-bold text-info mb-0">
                            <i class="fas fa-stethoscope me-2"></i>Ø§Ù„ØªØ±Ø§Ø®ÙŠØµ Ø§Ù„Ù…Ù‡Ù†ÙŠØ© ÙˆØ§Ù„Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ø·Ø¨ÙŠØ©
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="text-muted small">Ø±Ù‚Ù… Ø¥Ø¬Ø§Ø²Ø© / ØªØ±Ø®ÙŠØµ Ù…Ù…Ø§Ø±Ø³Ø© Ø§Ù„Ù…Ù‡Ù†Ø©</label>
                                <div class="fw-bold text-dark font-monospace">{{ $employee->medical_license_number ?? 'ØºÙŠØ± Ù…Ø³Ø¬Ù„' }}</div>
                            </div>

                            <div class="col-sm-6">
                                <label class="text-muted small">ØªØ§Ø±ÙŠØ® Ø§Ù†ØªÙ‡Ø§Ø¡ ØªØ±Ø®ÙŠØµ Ø§Ù„Ù…Ù…Ø§Ø±Ø³Ø©</label>
                                <div class="fw-bold">
                                    @if($employee->license_expiry_date)
                                        <span class="font-monospace {{ $employee->isLicenseExpired() ? 'text-danger' : ($employee->isLicenseExpiringSoon() ? 'text-warning' : 'text-success') }}">
                                            {{ $employee->license_expiry_date->format('Y-m-d') }}
                                        </span>
                                    @else
                                        <span class="text-muted">ØºÙŠØ± Ù…Ø­Ø¯Ø¯</span>
                                    @endif
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <label class="text-muted small">Ø±Ù‚Ù… Ù‡ÙˆÙŠØ© Ø§Ù„Ù†Ù‚Ø§Ø¨Ø©</label>
                                <div class="fw-bold text-dark">{{ $employee->syndicate_card_number ?? 'â€”' }}</div>
                            </div>

                            <div class="col-sm-4">
                                <label class="text-muted small">Ø§Ù„Ù…Ø¤Ù‡Ù„ Ø§Ù„Ø¹Ù„Ù…ÙŠ / Ø§Ù„Ø´Ù‡Ø§Ø¯Ø©</label>
                                <div class="fw-bold text-dark">{{ $employee->qualification ?? 'â€”' }}</div>
                            </div>

                            <div class="col-sm-4">
                                <label class="text-muted small">Ø§Ù„ØªØ®ØµØµ Ø§Ù„Ø¯Ù‚ÙŠÙ‚</label>
                                <div class="fw-bold text-dark">{{ $employee->sub_specialty ?? 'â€”' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            
            <!-- 3.5. Ø³Ø¬Ù„ Ø§Ù„Ø¹Ù‚ÙˆØ¨Ø§Øª ÙˆØ§Ù„Ù…ÙƒØ§ÙØ¢Øª ÙˆØ§Ù„Ø¥Ù†Ø°Ø§Ø±Ø§Øª -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-balance-scale text-warning me-2"></i>Ø³Ø¬Ù„ Ø§Ù„Ø¹Ù‚ÙˆØ¨Ø§Øª ÙˆØ§Ù„Ù…ÙƒØ§ÙØ¢Øª
                    </h6>
                    <button type="button" class="btn btn-outline-warning btn-sm text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#addActionModal">
                        <i class="fas fa-gavel me-1"></i> ØªØ³Ø¬ÙŠÙ„ Ø¥Ø¬Ø±Ø§Ø¡ Ø¬Ø¯ÙŠØ¯
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light small">
                                <tr>
                                    <th>Ø§Ù„ØªØ§Ø±ÙŠØ®</th>
                                    <th>Ù†ÙˆØ¹ Ø§Ù„Ø¥Ø¬Ø±Ø§Ø¡</th>
                                    <th>Ø§Ø³Ù… Ø§Ù„Ù„Ø§Ø¦Ø­Ø© / Ø§Ù„Ø³Ø¨Ø¨</th>
                                    <th>Ø§Ù„ØªØ£Ø«ÙŠØ± Ø§Ù„Ù…Ø§Ù„ÙŠ (Ø¯.Ø¹)</th>
                                    <th>Ø­Ø§Ù„Ø© Ø§Ù„ØªØ±Ø­ÙŠÙ„ Ù„Ù„Ø±Ø§ØªØ¨</th>
                                    <th>Ù…Ø³Ø¬Ù„ Ø¨ÙˆØ§Ø³Ø·Ø©</th>
                                    <th>Ø­Ø°Ù</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @forelse($employee->actions()->latest('action_date')->get() as $action)
                                    <tr>
                                        <td>{{ $action->action_date->format('Y-m-d') }}</td>
                                        <td>
                                            @if($action->actionSetting->category == 'penalty')
                                                <span class="badge bg-danger"><i class="fas fa-minus-circle"></i> Ø¹Ù‚ÙˆØ¨Ø©</span>
                                            @elseif($action->actionSetting->category == 'bonus')
                                                <span class="badge bg-success"><i class="fas fa-plus-circle"></i> Ù…ÙƒØ§ÙØ£Ø©</span>
                                            @else
                                                <span class="badge bg-secondary"><i class="fas fa-exclamation-triangle"></i> Ø¥Ù†Ø°Ø§Ø±</span>
                                            @endif
                                        </td>
                                        <td class="text-start">
                                            <strong>{{ $action->actionSetting->title }}</strong><br>
                                            <span class="text-muted">{{ Str::limit($action->reason, 40) }}</span>
                                        </td>
                                        <td>
                                            @if($action->financial_amount == 0)
                                                <span class="text-muted">Ø¨Ø¯ÙˆÙ† ØªØ£Ø«ÙŠØ± Ù…Ø§Ù„ÙŠ</span>
                                            @elseif($action->financial_amount > 0)
                                                <span class="text-success fw-bold">+{{ number_format($action->financial_amount, 0) }}</span>
                                            @else
                                                <span class="text-danger fw-bold">{{ number_format($action->financial_amount, 0) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($action->status == 'pending')
                                                <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half"></i> Ø¨Ø§Ù†ØªØ¸Ø§Ø± Ø§Ù„Ø±Ø§ØªØ¨</span>
                                            @else
                                                <span class="badge bg-success"><i class="fas fa-check-double"></i> Ù…ÙØ±Ø­Ù‘Ù„ ({{ $action->payrollCycle->cycle_month ?? '' }})</span>
                                            @endif
                                        </td>
                                        <td>{{ $action->creator->name ?? 'Ø§Ù„Ù†Ø¸Ø§Ù…' }}</td>
                                        <td>
                                            @if($action->status == 'pending')
                                            <form action="{{ route('hr.employees.actions.destroy', $action->id) }}" method="POST" class="d-inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Ù‡Ù„ Ø£Ù†Øª Ù…ØªØ£ÙƒØ¯ Ù…Ù† Ø§Ù„Ø­Ø°ÙØŸ')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @else
                                                <i class="fas fa-lock text-muted" title="Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø­Ø°ÙÙ‡ Ù„Ø£Ù†Ù‡ Ù…Ø±Ø­Ù‘Ù„ Ù„Ù„Ø±ÙˆØ§ØªØ¨"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-check-circle fa-2x mb-2 text-success opacity-50"></i>
                                            <p class="mb-0">Ø³Ø¬Ù„ Ø§Ù„Ù…ÙˆØ¸Ù Ù†Ø¸ÙŠÙØŒ Ù„Ø§ ØªÙˆØ¬Ø¯ Ø¹Ù‚ÙˆØ¨Ø§Øª Ø£Ùˆ Ù…ÙƒØ§ÙØ¢Øª Ù…Ø³Ø¬Ù„Ø©.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 4. Ø¬Ø¯ÙˆÙ„ Ø§Ù„Ù…Ø³ØªÙ…Ø³ÙƒØ§Øª ÙˆØ§Ù„ÙˆØ«Ø§Ø¦Ù‚ Ø§Ù„Ø±Ø³Ù…ÙŠØ© (Documents Table) -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-folder-open text-primary me-2"></i>Ø§Ù„Ø£Ø±Ø´ÙŠÙ ÙˆØ§Ù„Ù…Ø³ØªÙ…Ø³ÙƒØ§Øª Ø§Ù„Ø±Ø³Ù…ÙŠØ© ({{ $employee->documents->count() }})
                    </h6>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                        <i class="fas fa-plus me-1"></i> Ø±ÙØ¹ Ù…Ø³ØªÙ…Ø³Ùƒ Ø¬Ø¯ÙŠØ¯
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="ps-3">Ø§Ù„Ø±Ù…Ø² Ø§Ù„ÙˆØ¸ÙŠÙÙŠ</th>
                                    <th>Ù†ÙˆØ¹ Ø§Ù„Ù…Ø³ØªÙ…Ø³Ùƒ</th>
                                    <th>Ø§Ø³Ù… Ø§Ù„Ù…Ù„Ù Ø§Ù„Ù…ÙˆØ±Ø«</th>
                                    <th>Ø§Ù„Ø­Ø¬Ù…</th>
                                    <th>ØªØ§Ø±ÙŠØ® Ø§Ù„Ø±ÙØ¹</th>
                                    <th class="text-center pe-3" style="width: 120px;">Ø§Ù„Ø¥Ø¬Ø±Ø§Ø¡Ø§Øª</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employee->documents as $doc)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge bg-light text-dark border font-monospace">{{ $doc->employee_code }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                                <i class="fas fa-file-alt me-1"></i>{{ $doc->document_type }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-dark small font-monospace fw-semibold">
                                                @if($doc->isPdf())
                                                    <i class="fas fa-file-pdf text-danger me-1"></i>
                                                @else
                                                    <i class="fas fa-file-image text-info me-1"></i>
                                                @endif
                                                {{ $doc->file_name }}
                                            </div>
                                            @if($doc->notes)
                                                <small class="text-muted">{{ $doc->notes }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="small text-muted">{{ $doc->formatted_file_size }}</span>
                                        </td>
                                        <td>
                                            <span class="small text-muted">{{ $doc->created_at->format('Y-m-d H:i') }}</span>
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('hr.employees.documents.download', $doc->id) }}" class="btn btn-outline-primary" title="ØªØ­Ù…ÙŠÙ„ / Ù…Ø¹Ø§ÙŠÙ†Ø©" target="_blank">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                @can('delete employees')
                                                    <form action="{{ route('hr.employees.documents.destroy', $doc->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Ù‡Ù„ Ø£Ù†Øª Ù…ØªØ£ÙƒØ¯ Ù…Ù† Ø­Ø°Ù Ù‡Ø°Ø§ Ø§Ù„Ù…Ø³ØªÙ…Ø³ÙƒØŸ')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger" title="Ø­Ø°Ù Ø§Ù„Ù…Ø³ØªÙ…Ø³Ùƒ">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fas fa-folder-empty fa-2x mb-2 opacity-50"></i>
                                            <p class="mb-0">Ù„Ø§ ØªÙˆØ¬Ø¯ Ù…Ø³ØªÙ…Ø³ÙƒØ§Øª Ø£Ùˆ ÙˆØ«Ø§Ø¦Ù‚ Ù…Ø±ÙÙ‚Ø© Ù„Ù‡Ø°Ø§ Ø§Ù„Ù…ÙˆØ¸Ù Ø­Ø§Ù„ÙŠØ§Ù‹.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            
            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <!-- ðŸ“Š Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª Ù‡ÙŠÙƒÙ„Ø© Ø§Ù„Ø±Ø§ØªØ¨ -->
            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-info">
                <div class="card-header bg-info bg-opacity-10 py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold text-info mb-0">
                        <i class="fas fa-cog me-2"></i>Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª Ù‡ÙŠÙƒÙ„Ø© Ø§Ù„Ø±Ø§ØªØ¨ ÙˆØ§Ù„Ø¯ÙØ¹
                    </h6>
                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#editPayrollSettingsModal">
                        <i class="fas fa-edit"></i> ØªØ¹Ø¯ÙŠÙ„ Ø§Ù„Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="row g-0 text-center">
                        <div class="col-md-3 border-end p-3">
                            <div class="text-muted small">Ø§Ù„Ø±Ø§ØªØ¨ Ø§Ù„Ø£Ø³Ø§Ø³ÙŠ</div>
                            <div class="fw-bold fs-5 text-primary">{{ number_format($employee->basic_salary, 0) }} <small class="text-muted">Ø¯.Ø¹</small></div>
                        </div>
                        <div class="col-md-3 border-end p-3">
                            <div class="text-muted small">Ø¥Ø¬Ù…Ø§Ù„ÙŠ Ø§Ù„Ù…Ø®ØµØµØ§Øª Ø§Ù„Ø«Ø§Ø¨ØªØ©</div>
                            <div class="fw-bold fs-5 text-success">{{ number_format($employee->activeAllowances->sum('amount'), 0) }} <small class="text-muted">Ø¯.Ø¹</small></div>
                        </div>
                        <div class="col-md-3 border-end p-3">
                            <div class="text-muted small">Ø·Ø±ÙŠÙ‚Ø© ØµØ±Ù Ø§Ù„Ø±Ø§ØªØ¨</div>
                            <div class="fw-bold">
                                @if($employee->payment_method == 'bank')
                                    <span class="badge bg-primary"><i class="fas fa-university"></i> Ø¨Ù†Ùƒ</span>
                                    <div class="small text-muted mt-1">{{ $employee->bank_name }} - {{ $employee->bank_account_number }}</div>
                                @else
                                    <span class="badge bg-success"><i class="fas fa-money-bill-wave"></i> ÙƒØ§Ø´</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3 p-3">
                            <div class="text-muted small">Ø§Ù„Ø§Ø³ØªÙ‚Ø·Ø§Ø¹Ø§Øª</div>
                            <div class="small mt-1">
                                @if($employee->subject_to_social_security)
                                    <span class="badge bg-warning text-dark">Ø¶Ù…Ø§Ù† {{ $employee->social_security_percentage }}%</span>
                                @endif
                                @if($employee->subject_to_tax)
                                    <span class="badge bg-danger">Ø¶Ø±ÙŠØ¨Ø© {{ $employee->tax_percentage }}%</span>
                                @endif
                                @if(!$employee->subject_to_social_security && !$employee->subject_to_tax)
                                    <span class="text-muted">Ø¨Ø¯ÙˆÙ† Ø§Ø³ØªÙ‚Ø·Ø§Ø¹Ø§Øª</span>
                                @endif
                            </div>
                            @if($employee->overtime_hourly_rate > 0)
                                <div class="small text-muted mt-1">Ø³Ø¹Ø± Ø§Ù„Ø¥Ø¶Ø§ÙÙŠ: {{ number_format($employee->overtime_hourly_rate, 0) }} Ø¯.Ø¹/Ø³Ø§Ø¹Ø©</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <!-- ðŸ’° Ø§Ù„Ù…Ø®ØµØµØ§Øª Ø§Ù„Ø«Ø§Ø¨ØªØ© -->
            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-layer-group text-success me-2"></i>Ø§Ù„Ù…Ø®ØµØµØ§Øª ÙˆØ§Ù„Ø¹Ù„Ø§ÙˆØ§Øª Ø§Ù„Ø«Ø§Ø¨ØªØ©
                        <span class="badge bg-success ms-1">{{ $employee->activeAllowances->count() }}</span>
                    </h6>
                    <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#addAllowanceModal">
                        <i class="fas fa-plus"></i> Ø¥Ø¶Ø§ÙØ© Ù…Ø®ØµØµ
                    </button>
                </div>
                <div class="card-body p-0">
                    @forelse($employee->allowances as $allowance)
                    <div class="d-flex justify-content-between align-items-center px-4 py-2 border-bottom {{ $allowance->is_active ? '' : 'bg-light opacity-50' }}">
                        <div>
                            <span class="fw-bold">{{ $allowance->title }}</span>
                            @if(!$allowance->is_active) <span class="badge bg-secondary ms-2">Ù…Ø¹Ø·Ù‘Ù„</span> @endif
                            @if($allowance->notes) <div class="text-muted small">{{ $allowance->notes }}</div> @endif
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="fw-bold text-success fs-6">{{ number_format($allowance->amount, 0) }} Ø¯.Ø¹</span>
                            <form action="{{ route('hr.employees.allowances.destroy', $allowance->id) }}" method="POST" class="d-inline-block">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Ø­Ø°Ù Ù‡Ø°Ø§ Ø§Ù„Ù…Ø®ØµØµØŸ')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2 opacity-25"></i>
                        <p class="mb-0 small">Ù„Ù… ÙŠØªÙ… Ø¥Ø¶Ø§ÙØ© Ø£ÙŠ Ù…Ø®ØµØµØ§Øª Ø«Ø§Ø¨ØªØ© Ù„Ù‡Ø°Ø§ Ø§Ù„Ù…ÙˆØ¸Ù Ø¨Ø¹Ø¯.</p>
                    </div>
                    @endforelse
                    @if($employee->activeAllowances->count() > 0)
                    <div class="bg-success-subtle d-flex justify-content-between align-items-center px-4 py-2 fw-bold">
                        <span>Ø¥Ø¬Ù…Ø§Ù„ÙŠ Ø§Ù„Ù…Ø®ØµØµØ§Øª Ø§Ù„Ø´Ù‡Ø±ÙŠØ©</span>
                        <span class="text-success fs-6">{{ number_format($employee->activeAllowances->sum('amount'), 0) }} Ø¯.Ø¹</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <!-- ðŸ¦ Ø§Ù„Ø³Ù„Ù ÙˆØ§Ù„Ø£Ù‚Ø³Ø§Ø· -->
            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-hand-holding-usd text-warning me-2"></i>Ø§Ù„Ø³Ù„Ù ÙˆØ§Ù„Ø£Ù‚Ø³Ø§Ø·
                    </h6>
                    <button class="btn btn-outline-warning btn-sm text-dark" data-bs-toggle="modal" data-bs-target="#addLoanModal">
                        <i class="fas fa-plus"></i> ØªØ³Ø¬ÙŠÙ„ Ø³Ù„ÙØ© Ø¬Ø¯ÙŠØ¯Ø©
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0 small text-center">
                        <thead class="table-light">
                            <tr>
                                <th>ØªØ§Ø±ÙŠØ® Ø§Ù„Ø¨Ø¯Ø¡</th>
                                <th>Ø¥Ø¬Ù…Ø§Ù„ÙŠ Ø§Ù„Ø³Ù„ÙØ©</th>
                                <th>Ø§Ù„Ù‚Ø³Ø· Ø§Ù„Ø´Ù‡Ø±ÙŠ</th>
                                <th>Ø§Ù„Ù…Ø³Ø¯Ø¯</th>
                                <th>Ø§Ù„Ù…ØªØ¨Ù‚ÙŠ</th>
                                <th>Ø§Ù„Ø­Ø§Ù„Ø©</th>
                                <th>Ø§Ù„Ø³Ø¨Ø¨</th>
                                <th>Ø¥Ø¬Ø±Ø§Ø¡</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employee->loans()->latest()->get() as $loan)
                            <tr>
                                <td>{{ $loan->start_date->format('Y-m-d') }}</td>
                                <td class="fw-bold">{{ number_format($loan->total_amount, 0) }}</td>
                                <td class="text-warning fw-bold">{{ number_format($loan->monthly_installment, 0) }}</td>
                                <td class="text-success">{{ number_format($loan->paid_amount, 0) }}</td>
                                <td class="text-danger fw-bold">{{ number_format($loan->remaining_amount, 0) }}</td>
                                <td>
                                    @if($loan->status == 'active')
                                        <span class="badge bg-primary">Ù†Ø´Ø·Ø©</span>
                                    @elseif($loan->status == 'completed')
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Ù…ÙƒØªÙ…Ù„Ø©</span>
                                    @else
                                        <span class="badge bg-secondary">Ù…Ù„ØºØ§Ø©</span>
                                    @endif
                                </td>
                                <td>{{ $loan->reason ?? 'â€”' }}</td>
                                <td>
                                    @if($loan->status == 'active')
                                    <form action="{{ route('hr.employees.loans.cancel', $loan->id) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-secondary border-0 small" onclick="return confirm('Ø¥Ù„ØºØ§Ø¡ Ù‡Ø°Ù‡ Ø§Ù„Ø³Ù„ÙØ©ØŸ')">
                                            Ø¥Ù„ØºØ§Ø¡
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">Ù„Ø§ ØªÙˆØ¬Ø¯ Ø³Ù„Ù Ù…Ø³Ø¬Ù„Ø©.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <!-- ðŸ“… Ø§Ù„ØºÙŠØ§Ø¨Ø§Øª ÙˆØ§Ù„ØªØ£Ø®ÙŠØ±Ø§Øª -->
            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-calendar-times text-danger me-2"></i>Ø³Ø¬Ù„ Ø§Ù„ØºÙŠØ§Ø¨Ø§Øª ÙˆØ§Ù„ØªØ£Ø®ÙŠØ±Ø§Øª
                    </h6>
                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addAbsenceModal">
                        <i class="fas fa-plus"></i> ØªØ³Ø¬ÙŠÙ„ ØºÙŠØ§Ø¨ / ØªØ£Ø®ÙŠØ±
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0 small text-center">
                        <thead class="table-light">
                            <tr>
                                <th>Ø§Ù„ØªØ§Ø±ÙŠØ®</th>
                                <th>Ø§Ù„Ù†ÙˆØ¹</th>
                                <th>Ø§Ù„Ø£ÙŠØ§Ù…</th>
                                <th>Ø¨Ø¹Ø°Ø±ØŸ</th>
                                <th>Ù…Ø¨Ù„Øº Ø§Ù„Ø®ØµÙ…</th>
                                <th>Ø§Ù„Ø­Ø§Ù„Ø©</th>
                                <th>Ø§Ù„Ø³Ø¨Ø¨</th>
                                <th>Ø­Ø°Ù</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employee->absences()->latest('absence_date')->limit(20)->get() as $abs)
                            <tr>
                                <td>{{ $abs->absence_date->format('Y-m-d') }}</td>
                                <td>
                                    @if($abs->type == 'absence') <span class="badge bg-danger">ØºÙŠØ§Ø¨</span>
                                    @elseif($abs->type == 'late') <span class="badge bg-warning text-dark">ØªØ£Ø®ÙŠØ±</span>
                                    @else <span class="badge bg-secondary">Ø§Ù†ØµØ±Ø§Ù Ù…Ø¨ÙƒØ±</span>
                                    @endif
                                </td>
                                <td>{{ $abs->days_count }}</td>
                                <td>
                                    @if($abs->is_excused) <span class="badge bg-success">Ø¨Ø¹Ø°Ø±</span>
                                    @else <span class="badge bg-danger">Ø¨Ø¯ÙˆÙ† Ø¹Ø°Ø±</span>
                                    @endif
                                </td>
                                <td class="{{ $abs->deduction_amount > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                    {{ $abs->deduction_amount > 0 ? number_format($abs->deduction_amount, 0) . ' Ø¯.Ø¹' : 'Ø¨Ø¯ÙˆÙ† Ø®ØµÙ…' }}
                                </td>
                                <td>
                                    @if($abs->status == 'pending')
                                        <span class="badge bg-warning text-dark">Ø¨Ø§Ù†ØªØ¸Ø§Ø± Ø§Ù„Ø±Ø§ØªØ¨</span>
                                    @else
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Ù…ÙØ±Ø­Ù‘Ù„</span>
                                    @endif
                                </td>
                                <td>{{ Str::limit($abs->reason ?? 'â€”', 30) }}</td>
                                <td>
                                    @if($abs->status == 'pending')
                                    <form action="{{ route('hr.employees.absences.destroy', $abs->id) }}" method="POST" class="d-inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Ø­Ø°ÙØŸ')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @else <i class="fas fa-lock text-muted"></i> @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">Ù„Ø§ ØªÙˆØ¬Ø¯ ØºÙŠØ§Ø¨Ø§Øª Ù…Ø³Ø¬Ù„Ø©.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <!-- â° Ø§Ù„Ø¹Ù…Ù„ Ø§Ù„Ø¥Ø¶Ø§ÙÙŠ -->
            <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title fw-bold text-dark mb-0">
                        <i class="fas fa-clock text-primary me-2"></i>Ø³Ø¬Ù„ Ø§Ù„Ø¹Ù…Ù„ Ø§Ù„Ø¥Ø¶Ø§ÙÙŠ
                    </h6>
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addOvertimeModal">
                        <i class="fas fa-plus"></i> ØªØ³Ø¬ÙŠÙ„ Ø¥Ø¶Ø§ÙÙŠ
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0 small text-center">
                        <thead class="table-light">
                            <tr>
                                <th>Ø§Ù„ØªØ§Ø±ÙŠØ®</th>
                                <th>Ø·Ø±ÙŠÙ‚Ø© Ø§Ù„Ø­Ø³Ø§Ø¨</th>
                                <th>Ø§Ù„Ø³Ø§Ø¹Ø§Øª</th>
                                <th>Ø³Ø¹Ø± Ø§Ù„Ø³Ø§Ø¹Ø©</th>
                                <th>Ø§Ù„Ù…Ø¨Ù„Øº Ø§Ù„Ø¥Ø¬Ù…Ø§Ù„ÙŠ</th>
                                <th>Ø§Ù„Ø­Ø§Ù„Ø©</th>
                                <th>Ø§Ù„ÙˆØµÙ</th>
                                <th>Ø­Ø°Ù</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employee->overtimes()->latest('overtime_date')->limit(20)->get() as $ot)
                            <tr>
                                <td>{{ $ot->overtime_date->format('Y-m-d') }}</td>
                                <td>
                                    @if($ot->input_type == 'hours')
                                        <span class="badge bg-info text-dark">Ø¨Ø§Ù„Ø³Ø§Ø¹Ø§Øª</span>
                                    @else
                                        <span class="badge bg-secondary">Ù…Ø¨Ù„Øº Ù…Ù‚Ø·ÙˆØ¹</span>
                                    @endif
                                </td>
                                <td>{{ $ot->hours_count ?? 'â€”' }}</td>
                                <td>{{ $ot->hourly_rate_used ? number_format($ot->hourly_rate_used, 0) . ' Ø¯.Ø¹' : 'â€”' }}</td>
                                <td class="text-success fw-bold">{{ number_format($ot->total_amount, 0) }} Ø¯.Ø¹</td>
                                <td>
                                    @if($ot->status == 'pending')
                                        <span class="badge bg-warning text-dark">Ø¨Ø§Ù†ØªØ¸Ø§Ø± Ø§Ù„Ø±Ø§ØªØ¨</span>
                                    @else
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Ù…ÙØ±Ø­Ù‘Ù„</span>
                                    @endif
                                </td>
                                <td>{{ Str::limit($ot->description ?? 'â€”', 30) }}</td>
                                <td>
                                    @if($ot->status == 'pending')
                                    <form action="{{ route('hr.employees.overtimes.destroy', $ot->id) }}" method="POST" class="d-inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Ø­Ø°ÙØŸ')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @else <i class="fas fa-lock text-muted"></i> @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">Ù„Ø§ ÙŠÙˆØ¬Ø¯ Ø¹Ù…Ù„ Ø¥Ø¶Ø§ÙÙŠ Ù…Ø³Ø¬Ù„.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 5. Ø§Ù„Ù…Ù„Ø§Ø­Ø¸Ø§Øª -->
            @if($employee->notes)
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="card-title fw-bold text-dark mb-0">
                            <i class="fas fa-sticky-note text-secondary me-2"></i>Ù…Ù„Ø§Ø­Ø¸Ø§Øª Ø¥Ø¯Ø§Ø±ÙŠØ©
                        </h6>
                    </div>
                    <div class="card-body p-4 text-secondary">
                        {!! nl2br(e($employee->notes)) !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('modals')
<!-- Modal Ø±ÙØ¹ Ù…Ø³ØªÙ…Ø³Ùƒ Ø¬Ø¯ÙŠØ¯ Ù…Ø¨Ø§Ø´Ø±Ø© Ù…Ù† Ø§Ù„Ø¥Ø¶Ø¨Ø§Ø±Ø© -->
<div class="modal fade" id="uploadDocModal" tabindex="-1" aria-labelledby="uploadDocModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold" id="uploadDocModalLabel">
                    <i class="fas fa-upload me-1"></i> Ø±ÙØ¹ Ù…Ø³ØªÙ…Ø³Ùƒ Ø±Ø³Ù…ÙŠ Ù„Ù„Ù…ÙˆØ¸Ù: {{ $employee->full_name }}
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('hr.employees.documents.upload', $employee->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-light border small text-muted mb-3">
                        Ø§Ù„Ø±Ù…Ø² Ø§Ù„ÙˆØ¸ÙŠÙÙŠ: <strong>{{ $employee->employee_code }}</strong> â€” Ø³ÙŠØªÙ… ØªØ±Ù…ÙŠØ² Ø§Ù„Ù…Ù„Ù ÙˆØªØ³Ù…ÙŠØªÙ‡ Ø¢Ù„ÙŠØ§Ù‹ ÙˆÙÙ‚ Ø§Ù„Ø±Ù…Ø² ÙˆØ§Ù„Ù†ÙˆØ¹.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Ù†ÙˆØ¹ Ø§Ù„Ù…Ø³ØªÙ…Ø³Ùƒ <span class="text-danger">*</span></label>
                        <select name="document_type" class="form-select" required>
                            <option value="">Ø§Ø®ØªØ± Ù†ÙˆØ¹ Ø§Ù„Ù…Ø³ØªÙ…Ø³Ùƒ...</option>
                            @foreach($documentTypes as $dt)
                                <option value="{{ $dt->name }}">{{ $dt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Ø§Ù„Ù…Ù„Ù (PDF Ø£Ùˆ ØµÙˆØ±Ø©) <span class="text-danger">*</span></label>
                        <input type="file" name="document_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                        <div class="form-text text-muted small">Ø§Ù„Ø­Ø¯ Ø§Ù„Ø£Ù‚ØµÙ‰ Ù„Ù„Ù…Ù„Ù: 10 Ù…ÙŠØºØ§Ø¨Ø§ÙŠØª.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Ù…Ù„Ø§Ø­Ø¸Ø§Øª Ø¥Ø¶Ø§ÙÙŠØ© (Ø§Ø®ØªÙŠØ§Ø±ÙŠ)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Ø£ÙŠ ØªÙØ§ØµÙŠÙ„ Ø­ÙˆÙ„ Ù‡Ø°Ø§ Ø§Ù„Ù…Ø³ØªÙ†Ø¯..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Ø¥Ù„ØºØ§Ø¡</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">
                        <i class="fas fa-save me-1"></i> ØªØ£ÙƒÙŠØ¯ Ø§Ù„Ø±ÙØ¹ ÙˆØ§Ù„Ø£Ø±Ø´ÙØ©
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add Action (Penalty/Bonus) -->
<div class="modal fade" id="addActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.employees.actions.store', $employee->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning bg-opacity-10">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-gavel text-warning me-2"></i> ØªØ³Ø¬ÙŠÙ„ Ø¥Ø¬Ø±Ø§Ø¡ Ø¬Ø¯ÙŠØ¯ Ù„Ù„Ù…ÙˆØ¸Ù</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ø§Ø®ØªØ± Ù†ÙˆØ¹ Ø§Ù„Ø¥Ø¬Ø±Ø§Ø¡ Ù…Ù† Ø§Ù„Ù„Ø§Ø¦Ø­Ø© <span class="text-danger">*</span></label>
                        <select name="hr_action_setting_id" class="form-select" required>
                            <option value="">-- Ø§Ø®ØªØ± Ø§Ù„Ø¥Ø¬Ø±Ø§Ø¡ --</option>
                            @php
                                $penalties = $actionSettings->where('category', 'penalty');
                                $bonuses = $actionSettings->where('category', 'bonus');
                                $warnings = $actionSettings->where('category', 'warning');
                            @endphp
                            
                            @if($penalties->count() > 0)
                                <optgroup label="ðŸ”´ Ø§Ù„Ø¹Ù‚ÙˆØ¨Ø§Øª ÙˆØ§Ù„Ø®ØµÙˆÙ…Ø§Øª">
                                    @foreach($penalties as $s)
                                        <option value="{{ $s->id }}">{{ $s->title }} ({{ $s->effect_type == 'none' ? 'Ø¨Ø¯ÙˆÙ† ØªØ£Ø«ÙŠØ± Ù…Ø§Ù„ÙŠ' : 'ØªØ£Ø«ÙŠØ± Ù…Ø§Ù„ÙŠ' }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            
                            @if($bonuses->count() > 0)
                                <optgroup label="ðŸŸ¢ Ø§Ù„Ù…ÙƒØ§ÙØ¢Øª ÙˆØ§Ù„Ø­ÙˆØ§ÙØ²">
                                    @foreach($bonuses as $s)
                                        <option value="{{ $s->id }}">{{ $s->title }} ({{ $s->effect_type == 'none' ? 'Ø¨Ø¯ÙˆÙ† ØªØ£Ø«ÙŠØ± Ù…Ø§Ù„ÙŠ' : 'ØªØ£Ø«ÙŠØ± Ù…Ø§Ù„ÙŠ' }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            
                            @if($warnings->count() > 0)
                                <optgroup label="âšª Ø¥Ù†Ø°Ø§Ø±Ø§Øª ÙˆØªÙˆØ¨ÙŠØ®">
                                    @foreach($warnings as $s)
                                        <option value="{{ $s->id }}">{{ $s->title }} ({{ $s->effect_type == 'none' ? 'Ø¨Ø¯ÙˆÙ† ØªØ£Ø«ÙŠØ± Ù…Ø§Ù„ÙŠ' : 'ØªØ£Ø«ÙŠØ± Ù…Ø§Ù„ÙŠ' }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <small class="text-muted d-block mt-1">Ø³ÙŠÙ‚ÙˆÙ… Ø§Ù„Ù†Ø¸Ø§Ù… Ø¨Ø§Ø­ØªØ³Ø§Ø¨ Ù‚ÙŠÙ…Ø© Ø§Ù„Ø®ØµÙ…/Ø§Ù„Ù…ÙƒØ§ÙØ£Ø© Ø¢Ù„ÙŠØ§Ù‹ Ø¨Ù†Ø§Ø¡Ù‹ Ø¹Ù„Ù‰ Ø§Ù„Ø±Ø§ØªØ¨ Ø§Ù„Ø£Ø³Ø§Ø³ÙŠ ÙˆØ¥Ø¹Ø¯Ø§Ø¯Ø§Øª Ø§Ù„Ù„Ø§Ø¦Ø­Ø© ÙˆØ¥Ø¯Ø±Ø§Ø¬Ù‡Ø§ ÙÙŠ Ø±Ø§ØªØ¨ Ø§Ù„Ø´Ù‡Ø± Ø§Ù„Ø­Ø§Ù„ÙŠ.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ØªØ§Ø±ÙŠØ® Ø§Ù„Ø¥Ø¬Ø±Ø§Ø¡/Ø§Ù„Ù…Ø®Ø§Ù„ÙØ© <span class="text-danger">*</span></label>
                        <input type="date" name="action_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ø§Ù„Ø³Ø¨Ø¨ Ø§Ù„ØªÙØµÙŠÙ„ÙŠ ÙˆØ§Ù„Ù…Ù„Ø§Ø­Ø¸Ø§Øª <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Ø§ÙƒØªØ¨ ØªÙØ§ØµÙŠÙ„ Ø§Ù„Ù…Ø®Ø§Ù„ÙØ© Ø£Ùˆ Ø§Ù„Ù…ÙƒØ§ÙØ£Ø© Ù„Ø­ÙØ¸Ù‡Ø§ ÙÙŠ Ø§Ù„Ù…Ù„Ù..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ø¥Ù„ØºØ§Ø¡</button>
                    <button type="submit" class="btn btn-warning fw-bold text-dark"><i class="fas fa-save me-1"></i> Ø­ÙØ¸ Ø§Ù„Ø¥Ø¬Ø±Ø§Ø¡ ÙˆØªØ·Ø¨ÙŠÙ‚ Ø§Ù„ØªØ£Ø«ÙŠØ±</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<!-- MODALS: Payroll Settings, Allowances, Loans, Absences, Overtime -->
<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->

<!-- Modal: Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª Ù‡ÙŠÙƒÙ„Ø© Ø§Ù„Ø±Ø§ØªØ¨ -->
<div class="modal fade" id="editPayrollSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.employees.update', $employee->id) }}" method="POST">
                @csrf @method('PUT')
                <input type="hidden" name="_section" value="payroll_settings">
                <div class="modal-header bg-info bg-opacity-10">
                    <h5 class="modal-title fw-bold"><i class="fas fa-cog text-info me-2"></i> ØªØ¹Ø¯ÙŠÙ„ Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª Ø§Ù„Ø±Ø§ØªØ¨</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ø·Ø±ÙŠÙ‚Ø© ØµØ±Ù Ø§Ù„Ø±Ø§ØªØ¨</label>
                            <select name="payment_method" class="form-select" id="paymentMethodSelect">
                                <option value="cash" {{ $employee->payment_method == 'cash' ? 'selected' : '' }}>ðŸ’µ ÙƒØ§Ø´ (Ù†Ù‚Ø¯Ø§Ù‹)</option>
                                <option value="bank" {{ $employee->payment_method == 'bank' ? 'selected' : '' }}>ðŸ¦ Ø¨Ù†Ùƒ (ØªÙˆØ·ÙŠÙ†)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ø³Ø¹Ø± Ø³Ø§Ø¹Ø© Ø§Ù„Ø¥Ø¶Ø§ÙÙŠ (Ø¯.Ø¹)</label>
                            <input type="number" name="overtime_hourly_rate" class="form-control" value="{{ $employee->overtime_hourly_rate }}" step="500" min="0">
                        </div>
                        <div id="bankFields" class="{{ $employee->payment_method == 'bank' ? '' : 'd-none' }} col-12 row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Ø§Ø³Ù… Ø§Ù„Ø¨Ù†Ùƒ</label>
                                <input type="text" name="bank_name" class="form-control" value="{{ $employee->bank_name }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ø±Ù‚Ù… Ø§Ù„Ø­Ø³Ø§Ø¨</label>
                                <input type="text" name="bank_account_number" class="form-control" value="{{ $employee->bank_account_number }}">
                            </div>
                        </div>
                        <div class="col-12"><hr class="my-2"><p class="fw-bold mb-2">Ø§Ù„Ø§Ø³ØªÙ‚Ø·Ø§Ø¹Ø§Øª Ø§Ù„Ø¥Ù„Ø²Ø§Ù…ÙŠØ©</p></div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="subject_to_social_security" id="ssCheck" value="1" {{ $employee->subject_to_social_security ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="ssCheck">Ù…Ø´Ù…ÙˆÙ„ Ø¨Ø§Ù„Ø¶Ù…Ø§Ù† Ø§Ù„Ø§Ø¬ØªÙ…Ø§Ø¹ÙŠ</label>
                            </div>
                            <div class="input-group input-group-sm">
                                <input type="number" name="social_security_percentage" class="form-control" value="{{ $employee->social_security_percentage }}" step="0.5" min="0" max="25">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="subject_to_tax" id="taxCheck" value="1" {{ $employee->subject_to_tax ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="taxCheck">Ù…Ø´Ù…ÙˆÙ„ Ø¨Ø§Ù„Ø¶Ø±ÙŠØ¨Ø©</label>
                            </div>
                            <div class="input-group input-group-sm">
                                <input type="number" name="tax_percentage" class="form-control" value="{{ $employee->tax_percentage }}" step="0.5" min="0" max="50">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ø¥Ù„ØºØ§Ø¡</button>
                    <button type="submit" class="btn btn-info fw-bold text-white"><i class="fas fa-save me-1"></i> Ø­ÙØ¸ Ø§Ù„Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Ø¥Ø¶Ø§ÙØ© Ù…Ø®ØµØµ -->
<div class="modal fade" id="addAllowanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.employees.allowances.store', $employee->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-success bg-opacity-10">
                    <h5 class="modal-title fw-bold"><i class="fas fa-layer-group text-success me-2"></i> Ø¥Ø¶Ø§ÙØ© Ù…Ø®ØµØµ Ø«Ø§Ø¨Øª</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ù†ÙˆØ¹ Ø§Ù„Ù…Ø®ØµØµ <span class="text-danger">*</span></label>
                        <select name="title" class="form-select" required>
                            <option value="">-- Ø§Ø®ØªØ± Ù†ÙˆØ¹ Ø§Ù„Ù…Ø®ØµØµ --</option>
                            <optgroup label="Ù…Ø®ØµØµØ§Øª Ø¹Ø§Ø¦Ù„ÙŠØ©">
                                <option>Ù…Ø®ØµØµ Ø²ÙˆØ¬ÙŠØ©</option>
                                <option>Ù…Ø®ØµØµ Ø£Ø·ÙØ§Ù„</option>
                            </optgroup>
                            <optgroup label="Ù…Ø®ØµØµØ§Øª ÙˆØ¸ÙŠÙÙŠØ©">
                                <option>Ù…Ø®ØµØµ Ø®Ø·ÙˆØ±Ø©</option>
                                <option>Ù…Ø®ØµØµ Ù†Ù‚Ù„</option>
                                <option>Ù…Ø®ØµØµ Ø´Ù‡Ø§Ø¯Ø©</option>
                                <option>Ù…Ø®ØµØµ Ø§Ù…ØªÙŠØ§Ø²</option>
                                <option>Ù…Ø®ØµØµ ØªÙØ±Øº</option>
                                <option>Ù…Ø®ØµØµ Ù…Ù†Ø§ÙˆØ¨Ø©</option>
                                <option>Ø¹Ù„Ø§ÙˆØ© Ø³Ù†ÙˆÙŠØ©</option>
                            </optgroup>
                            <option value="_custom">Ø£Ø®Ø±Ù‰ (ÙƒØªØ§Ø¨Ø© ÙŠØ¯ÙˆÙŠØ©)</option>
                        </select>
                    </div>
                    <div class="mb-3" id="customTitleDiv" style="display:none;">
                        <label class="form-label">Ø§Ø³Ù… Ø§Ù„Ù…Ø®ØµØµ (Ù…Ø®ØµØµ)</label>
                        <input type="text" name="title_custom" class="form-control" placeholder="Ø£Ø¯Ø®Ù„ Ø§Ø³Ù… Ø§Ù„Ù…Ø®ØµØµ...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ø§Ù„Ù…Ø¨Ù„Øº Ø§Ù„Ø´Ù‡Ø±ÙŠ (Ø¯.Ø¹) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="1000" min="0" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Ù…Ù„Ø§Ø­Ø¸Ø§Øª</label>
                        <input type="text" name="notes" class="form-control" placeholder="Ø§Ø®ØªÙŠØ§Ø±ÙŠ...">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ø¥Ù„ØºØ§Ø¡</button>
                    <button type="submit" class="btn btn-success fw-bold"><i class="fas fa-plus me-1"></i> Ø¥Ø¶Ø§ÙØ© Ø§Ù„Ù…Ø®ØµØµ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: ØªØ³Ø¬ÙŠÙ„ Ø³Ù„ÙØ© -->
<div class="modal fade" id="addLoanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.employees.loans.store', $employee->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning bg-opacity-10">
                    <h5 class="modal-title fw-bold"><i class="fas fa-hand-holding-usd text-warning me-2"></i> ØªØ³Ø¬ÙŠÙ„ Ø³Ù„ÙØ© Ø¬Ø¯ÙŠØ¯Ø©</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        Ø§Ù„Ø±Ø§ØªØ¨ Ø§Ù„Ø£Ø³Ø§Ø³ÙŠ Ù„Ù„Ù…ÙˆØ¸Ù: <strong>{{ number_format($employee->basic_salary, 0) }} Ø¯.Ø¹</strong>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ø¥Ø¬Ù…Ø§Ù„ÙŠ Ù…Ø¨Ù„Øº Ø§Ù„Ø³Ù„ÙØ© <span class="text-danger">*</span></label>
                            <input type="number" name="total_amount" class="form-control" step="50000" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ù‚ÙŠÙ…Ø© Ø§Ù„Ù‚Ø³Ø· Ø§Ù„Ø´Ù‡Ø±ÙŠ <span class="text-danger">*</span></label>
                            <input type="number" name="monthly_installment" class="form-control" step="50000" min="0" required>
                            <small class="text-muted">0 = Ø¯ÙØ¹Ø© ÙˆØ§Ø­Ø¯Ø©</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ØªØ§Ø±ÙŠØ® Ø¨Ø¯Ø¡ Ø§Ù„Ø§Ø³ØªÙ‚Ø·Ø§Ø¹ <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-01') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ø³Ø¨Ø¨ Ø§Ù„Ø³Ù„ÙØ©</label>
                            <input type="text" name="reason" class="form-control" placeholder="Ø§Ø®ØªÙŠØ§Ø±ÙŠ...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ø¥Ù„ØºØ§Ø¡</button>
                    <button type="submit" class="btn btn-warning fw-bold text-dark"><i class="fas fa-save me-1"></i> ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø³Ù„ÙØ©</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: ØªØ³Ø¬ÙŠÙ„ ØºÙŠØ§Ø¨ -->
<div class="modal fade" id="addAbsenceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.employees.absences.store', $employee->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger bg-opacity-10">
                    <h5 class="modal-title fw-bold"><i class="fas fa-calendar-times text-danger me-2"></i> ØªØ³Ø¬ÙŠÙ„ ØºÙŠØ§Ø¨ / ØªØ£Ø®ÙŠØ±</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning py-2 small">
                        <i class="fas fa-calculator me-1"></i>
                        Ù‚ÙŠÙ…Ø© Ø§Ù„ÙŠÙˆÙ… Ø§Ù„ÙˆØ§Ø­Ø¯: <strong>{{ number_format($employee->basic_salary / 30, 0) }} Ø¯.Ø¹</strong>
                        (Ø§Ù„Ø±Ø§ØªØ¨ Ø§Ù„Ø£Ø³Ø§Ø³ÙŠ {{ number_format($employee->basic_salary, 0) }} Ã· 30 ÙŠÙˆÙ…)
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ØªØ§Ø±ÙŠØ® Ø§Ù„ØºÙŠØ§Ø¨ <span class="text-danger">*</span></label>
                            <input type="date" name="absence_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ù†ÙˆØ¹ Ø§Ù„ØºÙŠØ§Ø¨ <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="absence">ØºÙŠØ§Ø¨ ÙƒØ§Ù…Ù„</option>
                                <option value="late">ØªØ£Ø®ÙŠØ±</option>
                                <option value="early_leave">Ø§Ù†ØµØ±Ø§Ù Ù…Ø¨ÙƒØ±</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ø¹Ø¯Ø¯ Ø§Ù„Ø£ÙŠØ§Ù… <span class="text-danger">*</span></label>
                            <input type="number" name="days_count" class="form-control" step="0.25" min="0.25" max="30" value="1" required>
                            <small class="text-muted">ÙŠÙ…ÙƒÙ† ÙƒØ³Ø± (0.5 = Ù†ØµÙ ÙŠÙˆÙ…)</small>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="is_excused" id="isExcusedCheck" value="1">
                                <label class="form-check-label fw-bold" for="isExcusedCheck">ØºÙŠØ§Ø¨ Ø¨Ø¹Ø°Ø± (Ø¨Ø¯ÙˆÙ† Ø®ØµÙ… Ù…Ø§Ù„ÙŠ)</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ø§Ù„Ø³Ø¨Ø¨</label>
                            <input type="text" name="reason" class="form-control" placeholder="Ø§ÙƒØªØ¨ Ø³Ø¨Ø¨ Ø§Ù„ØºÙŠØ§Ø¨...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ø¥Ù„ØºØ§Ø¡</button>
                    <button type="submit" class="btn btn-danger fw-bold"><i class="fas fa-save me-1"></i> ØªØ³Ø¬ÙŠÙ„ Ø§Ù„ØºÙŠØ§Ø¨</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: ØªØ³Ø¬ÙŠÙ„ Ø¹Ù…Ù„ Ø¥Ø¶Ø§ÙÙŠ -->
<div class="modal fade" id="addOvertimeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.employees.overtimes.store', $employee->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary bg-opacity-10">
                    <h5 class="modal-title fw-bold"><i class="fas fa-clock text-primary me-2"></i> ØªØ³Ø¬ÙŠÙ„ Ø¹Ù…Ù„ Ø¥Ø¶Ø§ÙÙŠ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    @if($employee->overtime_hourly_rate > 0)
                    <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        Ø³Ø¹Ø± Ø§Ù„Ø³Ø§Ø¹Ø© Ø§Ù„Ù…Ø³Ø¬Ù„: <strong>{{ number_format($employee->overtime_hourly_rate, 0) }} Ø¯.Ø¹/Ø³Ø§Ø¹Ø©</strong>
                    </div>
                    @else
                    <div class="alert alert-warning py-2 small">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Ù„Ù… ÙŠØªÙ… ØªØ­Ø¯ÙŠØ¯ Ø³Ø¹Ø± Ø³Ø§Ø¹Ø© Ø§Ù„Ø¥Ø¶Ø§ÙÙŠ. Ø³ÙŠØªÙ… Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø§Ù„Ù…Ø¨Ù„Øº Ø§Ù„Ù…Ù‚Ø·ÙˆØ¹ ÙÙ‚Ø·.
                        <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#editPayrollSettingsModal" class="ms-1">ØªØ­Ø¯ÙŠØ« Ø§Ù„Ø³Ø¹Ø±</a>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ø·Ø±ÙŠÙ‚Ø© Ø§Ù„Ø­Ø³Ø§Ø¨ <span class="text-danger">*</span></label>
                        <select name="input_type" class="form-select" id="overtimeInputType" required>
                            <option value="hours">â±ï¸ Ø¨Ø§Ù„Ø³Ø§Ø¹Ø§Øª (Ø³Ø§Ø¹Ø§Øª Ã— Ø³Ø¹Ø± Ø§Ù„Ø³Ø§Ø¹Ø©)</option>
                            <option value="manual_amount">ðŸ’° Ù…Ø¨Ù„Øº Ù…Ù‚Ø·ÙˆØ¹ ÙŠØ¯ÙˆÙŠ</option>
                        </select>
                    </div>
                    <div id="hoursDiv" class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label">Ø¹Ø¯Ø¯ Ø§Ù„Ø³Ø§Ø¹Ø§Øª</label>
                            <input type="number" name="hours_count" class="form-control" step="0.5" min="0.5">
                        </div>
                        <div class="col">
                            <label class="form-label">Ø§Ù„ØªØ§Ø±ÙŠØ®</label>
                            <input type="date" name="overtime_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div id="manualDiv" class="mb-3 d-none">
                        <label class="form-label fw-bold">Ø§Ù„Ù…Ø¨Ù„Øº (Ø¯.Ø¹)</label>
                        <input type="number" name="manual_amount" class="form-control" step="5000" min="0">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">ÙˆØµÙ / Ù…Ù„Ø§Ø­Ø¸Ø©</label>
                        <input type="text" name="description" class="form-control" placeholder="Ù…Ø«Ø§Ù„: Ø®ÙØ§Ø±Ø© Ù„ÙŠÙ„ÙŠØ©ØŒ Ø¹Ù…Ù„ Ø¹Ø·Ù„Ø© Ø±Ø³Ù…ÙŠØ©...">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ø¥Ù„ØºØ§Ø¡</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-save me-1"></i> ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø¥Ø¶Ø§ÙÙŠ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Bank fields toggle
document.getElementById("paymentMethodSelect")?.addEventListener("change", function() {
    document.getElementById("bankFields").classList.toggle("d-none", this.value !== "bank");
});

// Overtime type toggle
document.getElementById("overtimeInputType")?.addEventListener("change", function() {
    const isHours = this.value === "hours";
    document.getElementById("hoursDiv").classList.toggle("d-none", !isHours);
    document.getElementById("manualDiv").classList.toggle("d-none", isHours);
});

// Allowance custom title
document.querySelector("select[name='title']")?.addEventListener("change", function() {
    const custom = document.getElementById("customTitleDiv");
    if (this.value === "_custom") {
        custom.style.display = "block";
        this.name = "title_dropdown";
        custom.querySelector("input").name = "title";
    } else {
        custom.style.display = "none";
        this.name = "title";
        custom.querySelector("input").name = "title_custom";
    }
});
</script>

@endpush
