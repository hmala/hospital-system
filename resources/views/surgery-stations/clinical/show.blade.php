@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" id="clinicalStationApp">
    {{-- CSS Styles --}}
    <style>
        :root {
            --clin-navy: #476abdff;
            --clin-teal: #1c6dd8ff;
            --clin-blue: #2563eb;
            --clin-card-bg: #6866ceff;
        }

        .vital-grid-input {
            border-radius: 8px;
            font-size: 0.88rem;
            padding: 5px 4px;
            min-width: 60px;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease-in-out;
        }
        .vital-grid-input:focus {
            background-color: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .fluid-grid-input {
            border-radius: 8px;
            font-size: 0.85rem;
            padding: 5px 3px;
            min-width: 58px;
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease-in-out;
        }
        .fluid-grid-input:not([readonly]):focus {
            background-color: #ffffff;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }
        #vitalsGridTable th, #vitalsGridTable td {
            vertical-align: middle;
        }

        /* Ultra-Modern Clinical Executive Header */
        .clin-header-modern {
            background: #ffffff !important;
            border-radius: 16px;
            padding: 12px 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05), 0 1px 3px rgba(15, 23, 42, 0.03);
            position: relative;
            overflow: hidden;
        }
        .clin-header-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #2563eb 0%, #06b6d4 50%, #3b82f6 100%);
        }

        .avatar-modern-box {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1px solid #bfdbfe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.12);
        }

        .clin-patient-name {
            color: #0f172a !important;
            font-size: 1.22rem;
            font-weight: 800;
            letter-spacing: -0.2px;
        }

        .badge-modern-code {
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid #bfdbfe;
            font-family: monospace, system-ui;
        }

        .badge-modern-sub {
            background: #f8fafc;
            color: #475569;
            font-size: 0.73rem;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .clin-v-divider {
            width: 1px;
            height: 40px;
            background: #e2e8f0;
            margin: 0 4px;
        }

        .clin-meta-label {
            font-size: 0.68rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }

        .badge-doctor-chip {
            background: #f0fdf4;
            color: #166534;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid #bbf7d0;
        }

        .badge-room-chip {
            background: #fffbeb;
            color: #92400e;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid #fde68a;
        }

        .badge-time-chip {
            background: #f8fafc;
            color: #475569;
            font-size: 0.74rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        /* Modern Status Badge with Colored Pulse Indicator */
        .badge-status-modern {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .status-pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
        }
        .badge-status-completed {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .badge-status-progress {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .badge-status-waiting {
            background: #f8fafc;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        /* Responsive Tablet-Ready Horizontal Tabs Bar (Zero Scroll / Auto-Fit) */
        .clin-tabs-wrapper {
            background: #ffffff;
            border-radius: 12px;
            padding: 5px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }

        .clin-tabs-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            padding: 0;
            margin: 0;
            list-style: none;
            width: 100%;
        }

        .clin-tab-item {
            flex: 1 1 0px;
            min-width: 72px;
        }

        @media (max-width: 991px) {
            .clin-tab-item {
                flex: 1 1 calc(20% - 4px);
                min-width: 80px;
            }
            .clin-header-card {
                padding: 10px 14px;
            }
        }

        @media (max-width: 576px) {
            .clin-tab-item {
                flex: 1 1 calc(33.33% - 4px);
            }
        }

        .clin-nav-tab {
            font-size: 0.81rem;
            font-weight: 700;
            padding: 8px 4px;
            min-height: 42px;
            border-radius: 8px;
            color: #475569;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            border: 1px solid #f1f5f9;
            background: #f8fafc;
            text-decoration: none;
            text-align: center;
            width: 100%;
            white-space: nowrap;
        }
        .clin-nav-tab:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #cbd5e1;
        }
        .clin-nav-tab.active {
            background: #2563eb !important;
            color: #ffffff !important;
            border-color: #1d4ed8 !important;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
        }
        .clin-nav-tab.active * {
            color: #ffffff !important;
        }
        .clin-nav-tab.active .badge-perm-dot {
            background: rgba(255, 255, 255, 0.3) !important;
            color: #ffffff !important;
        }

        .badge-perm-dot {
            font-size: 0.6rem;
            padding: 1px 4px;
            border-radius: 4px;
            line-height: 1;
        }
        .badge-perm-read {
            background: #eff6ff;
            color: #1d4ed8;
        }
        .badge-perm-write {
            background: #f0fdf4;
            color: #15803d;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            padding-left: 0.4rem !important;
            padding-right: 0.4rem !important;
        }

        .pane-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }
        .pane-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 22px;
        }

        .vital-box {
            border-radius: 14px;
            padding: 14px;
            text-align: center;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .vital-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .vital-box .vital-val {
            font-size: 1.4rem;
            font-weight: 800;
            margin: 4px 0;
        }

        .vital-box-red { border-color: #fecaca; background: #fff5f5; }
        .vital-box-orange { border-color: #fed7aa; background: #fffaf0; }
        .vital-box-blue { border-color: #bfdbfe; background: #f0f9ff; }
        .vital-box-green { border-color: #bbf7d0; background: #f0fdf4; }
        .vital-box-purple { border-color: #ddd6fe; background: #faf5ff; }

        .timeline-card {
            border-inline-start: 3px solid #2563eb;
            background: #f8fafc;
            border-radius: 0 12px 12px 0;
            padding: 14px 18px;
            margin-bottom: 12px;
        }

        /* Compact Organized Clinical History Tab */
        .history-section-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 12px;
        }
        .history-section-card.history-past {
            background: #ffffff;
        }
        .history-field-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 10px;
            height: 100%;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .history-field-card:focus-within {
            border-color: #93c5fd;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
        }
        .history-allergy-card {
            background: #fff5f5;
            border-color: #fecaca;
        }
        .history-allergy-card:focus-within {
            border-color: #f87171;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.1);
        }
        .history-compact-textarea {
            font-size: 0.86rem;
            line-height: 1.45;
            min-height: 54px;
            padding: 6px 9px;
            resize: vertical;
        }
        .history-quick-chip {
            font-size: 0.68rem;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #475569;
            line-height: 1.35;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .history-quick-chip:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .history-quick-chip.chip-danger {
            border-color: #fca5a5;
            background: #fef2f2;
            color: #b91c1c;
        }
        .history-quick-chip.chip-danger:hover {
            background: #fee2e2;
        }
        .plan-phase-active {
            border-color: #93c5fd;
            background: #f8fbff;
        }
        .plan-phase-active-post {
            border-color: #86efac;
            background: #f6fef9;
        }

        /* MAR Medication Schedule & Dose Checkboxes */
        .mar-dose-grid {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .mar-day-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 5px;
        }
        .mar-day-label {
            font-size: 0.69rem;
            font-weight: 800;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 2px 6px;
            min-width: 70px;
            text-align: center;
        }
        .mar-dose-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            font-size: 0.74rem;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
            min-width: 102px;
        }
        .mar-dose-chip:hover:not(.mar-dose-locked) {
            border-color: #2563eb;
            background: #eff6ff;
        }
        .mar-dose-chip.mar-dose-checked {
            background: #f0fdf4;
            border-color: #86efac;
            color: #166534;
        }
        .mar-dose-chip.mar-dose-overdue {
            background: #fff7ed;
            border-color: #fdba74;
            border-style: dashed;
        }
        .mar-dose-chip.mar-dose-due {
            background: #eff6ff;
            border-color: #93c5fd;
        }
        .mar-dose-chip.mar-dose-locked {
            cursor: default;
            opacity: 0.92;
        }
        .mar-dose-chip .form-check-input {
            margin: 0;
            width: 15px;
            height: 15px;
            cursor: pointer;
        }
        .mar-dose-chip .form-check-input:checked {
            background-color: #16a34a;
            border-color: #15803d;
        }
        .mar-dose-meta {
            display: flex;
            flex-direction: column;
            line-height: 1.15;
            text-align: start;
        }
        .mar-dose-time {
            font-weight: 700;
            font-size: 0.73rem;
        }
        .mar-dose-stamp {
            font-size: 0.65rem;
            color: #15803d;
            font-weight: 600;
        }
        .mar-autocomplete-wrap {
            position: relative;
        }
        .mar-suggestions-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            left: 0;
            z-index: 1055;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.14);
            max-height: 250px;
            overflow-y: auto;
            display: none;
            margin-top: 3px;
        }
        .mar-suggestion-item {
            padding: 8px 11px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            text-align: start;
            transition: background 0.12s ease;
        }
        .mar-suggestion-item:last-child {
            border-bottom: none;
        }
        .mar-suggestion-item:hover {
            background: #eff6ff;
        }
    </style>

    @php
        $resolvedBackUrl = $backUrl ?? (url()->previous() ?: route('surgeries.index'));
        $resolvedBackLabel = $backLabel ?? __('العودة لجدول العمليات');
    @endphp

    {{-- Breadcrumb & Quick Actions --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('surgeries.index') }}" class="text-decoration-none">{{ __('إدارة العمليات') }}</a></li>
                @if($resolvedBackUrl !== route('surgeries.index'))
                    <li class="breadcrumb-item"><a href="{{ $resolvedBackUrl }}" class="text-decoration-none">{{ __($resolvedBackLabel) }}</a></li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">{{ __('المحطة السريرية الموحدة') }} (#{{ $surgery->id }})</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            @can('manage surgery tab permissions')
                <a href="{{ route('surgery-stations.tab-settings.index') }}" class="btn btn-outline-dark btn-sm rounded-pill px-3" title="{{ __('تخصيص صلاحيات التبويبات') }}">
                    <i class="fas fa-sliders-h me-1"></i> {{ __('ضبط صلاحيات التبويبات') }}
                </a>
            @endcan
            <a href="{{ $resolvedBackUrl }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-xs">
                <i class="fas {{ app()->getLocale() === 'en' ? 'fa-arrow-left' : 'fa-arrow-right' }} me-1"></i> {{ __($resolvedBackLabel) }}
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center mb-3" role="alert">
            <i class="fas fa-check-circle fa-lg me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center mb-3" role="alert">
            <i class="fas fa-exclamation-triangle fa-lg me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Ultra-Modern Clinical Executive Header --}}
    <div class="clin-header-modern mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            
            {{-- Left Side: 3 Visual Pillars --}}
            <div class="d-flex flex-wrap align-items-center gap-3 gap-xl-4">
                
                {{-- Pillar 1: Patient Identity --}}
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-modern-box flex-shrink-0">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h3 class="clin-patient-name mb-0">{{ $surgery->patient?->name ?? __('غير محدد') }}</h3>
                            <span class="badge-modern-code">
                                #{{ $surgery->patient?->patient_id ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted small mt-1">
                            <span class="badge-modern-sub">
                                <i class="fas fa-birthday-cake me-1 text-primary"></i> {{ $surgery->patient?->age ? $surgery->patient->age . ' ' . __('سنة') : __('العمر غير مسجل') }}
                            </span>
                            <span class="badge-modern-sub">
                                <i class="fas fa-venus-mars me-1 text-info"></i> {{ $surgery->patient?->gender === 'male' ? __('ذكر') : __('أنثى') }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Vertical Divider 1 --}}
                <div class="clin-v-divider d-none d-md-block"></div>

                {{-- Pillar 2: Procedure & Doctor --}}
                <div>
                    <div class="clin-meta-label">
                        <i class="fas fa-procedures text-primary me-1"></i> {{ __('العملية الجراحية') }}
                    </div>
                    <div class="fw-bold text-dark fs-6 text-truncate" style="max-width: 260px;" title="{{ $surgery->surgery_name }}">
                        {{ $surgery->surgery_name }}
                    </div>
                    <div class="text-secondary small mt-0.5 d-flex align-items-center gap-1.5">
                        <span class="badge-doctor-chip">
                            <i class="fas fa-user-md text-success me-1"></i> {{ __('د.') }} {{ $surgery->doctor?->name ?? __('غير محدد') }}
                        </span>
                    </div>
                </div>

                {{-- Vertical Divider 2 --}}
                <div class="clin-v-divider d-none d-lg-block"></div>

                {{-- Pillar 3: Location & Time --}}
                <div>
                    <div class="clin-meta-label">
                        <i class="fas fa-map-marker-alt text-warning me-1"></i> {{ __('الموقع والجدولة') }}
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-0.5">
                        <span class="badge-room-chip">
                            <i class="fas fa-door-open me-1 text-amber"></i> {{ $surgery->operationTheater?->name ?? $surgery->room?->room_number ?? __('غير مخصصة') }}
                        </span>
                        <span class="badge-time-chip">
                            <i class="far fa-clock me-1 text-secondary"></i> {{ $surgery->scheduled_time ? $surgery->scheduled_time->format('h:i A') : '--:--' }}
                        </span>
                    </div>
                </div>

            </div>

            {{-- Right Side: Smart Active Clinical Phase & Status --}}
            <div class="d-flex flex-wrap align-items-center gap-2 ms-auto ms-lg-0">
                {{-- Smart Phase Indicator & Subtle Switcher --}}
                <div class="d-flex align-items-center gap-2">
                    @php
                        $isOpTheaterDone = ($surgery->operationTheaterStation && $surgery->operationTheaterStation->status === 'completed')
                            || ($surgery->anesthesiaStation && $surgery->anesthesiaStation->status === 'completed')
                            || $surgery->status === 'completed';

                        $isInOpTheater = ($surgery->status === 'in_progress')
                            || ($surgery->operationTheaterStation && $surgery->operationTheaterStation->status === 'in_progress');
                    @endphp

                    @if($isOpTheaterDone || $currentPhase === 'post_op')
                        <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-1.5 fw-bold d-inline-flex align-items-center gap-1.5 shadow-xs" title="{{ __('المرحلة السريرية: عناية ما بعد العملية (الرقود)') }}">
                            <i class="fas fa-procedures text-success"></i>
                            <span>{{ __('بعد الصالة (عناية الرقود)') }}</span>
                        </span>
                        @if($preOpStation)
                            <a href="{{ route('clinical-station.show', array_filter(['surgery' => $surgery->id, 'phase' => 'pre_op', 'tab' => $activeTab, 'from' => $from ?? null])) }}" 
                               class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 py-1 text-xs d-inline-flex align-items-center gap-1"
                               title="{{ __('مراجعة سجل ما قبل العملية') }}">
                                <i class="fas fa-history fa-xs"></i>
                                <span>{{ __('أرشيف قبل الصالة') }}</span>
                            </a>
                        @endif
                    @elseif($isInOpTheater)
                        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 py-1.5 fw-bold d-inline-flex align-items-center gap-1.5 shadow-xs" title="{{ __('المريض داخل صالة العمليات حالياً') }}">
                            <i class="fas fa-spinner fa-spin text-primary"></i>
                            <span>{{ __('داخل صالة العمليات') }}</span>
                        </span>
                    @else
                        <span class="badge rounded-pill bg-warning bg-opacity-15 text-warning-emphasis border border-warning-subtle px-3 py-1.5 fw-bold d-inline-flex align-items-center gap-1.5 shadow-xs" title="{{ __('المرحلة السريرية: تحضير وتهيئة ما قبل العملية') }}">
                            <i class="fas fa-hourglass-start text-warning"></i>
                            <span>{{ __('قبل الصالة (تحضير وتهيئة)') }}</span>
                        </span>
                        @if($postOpStation)
                            <a href="{{ route('clinical-station.show', array_filter(['surgery' => $surgery->id, 'phase' => 'post_op', 'tab' => $activeTab, 'from' => $from ?? null])) }}" 
                               class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 py-1 text-xs d-inline-flex align-items-center gap-1"
                               title="{{ __('الانتقال إلى مرحلة ما بعد العملية') }}">
                                <span>{{ __('بعد الصالة') }}</span>
                                <i class="fas {{ app()->getLocale() === 'en' ? 'fa-arrow-right' : 'fa-arrow-left' }} fa-xs"></i>
                            </a>
                        @endif
                    @endif
                </div>

                {{-- Phase Status Modern Pill with Glowing Pulse Indicator --}}
                <div class="d-flex align-items-center gap-1.5">
                    @if($station && $station->status === 'completed')
                        <span class="badge-status-modern badge-status-completed">
                            <span class="status-pulse-dot bg-success"></span>
                            {{ __('مكتملة') }}
                        </span>
                    @elseif($station && $station->status === 'in_progress')
                        <span class="badge-status-modern badge-status-progress">
                            <span class="status-pulse-dot bg-warning"></span>
                            {{ __('قيد المتابعة') }}
                        </span>
                    @else
                        <span class="badge-status-modern badge-status-waiting">
                            <span class="status-pulse-dot bg-secondary"></span>
                            {{ __('بالانتظار') }}
                        </span>
                    @endif


                </div>
            </div>

        </div>
    </div>

    {{-- Tabs Navigation Bar (Responsive Tablet Grid - Zero Scroll Auto-Fit) --}}
    @php
        $shortTabLabels = [
            'history' => __('السيرة'),
            'vitals' => __('العلامات والسوائل'),
            'plan' => __('الخطة'),
            'follow_up' => __('المتابعة'),
            'treatments' => __('الأدوية'),
            'supplies' => __('المستلزمات'),
            'nursing_care' => __('التمريض'),
            'inpatient_survey' => __('الاستبيان'),
            'tests' => __('الفحوصات'),
            'consultations' => __('الاستشارات'),
        ];
    @endphp

    <div class="clin-tabs-wrapper mb-3">
        <ul class="clin-tabs-grid" id="clinicalStationTabs" role="tablist">
            @foreach($visibleTabs as $tKey => $tMeta)
                <li class="clin-tab-item" role="presentation">
                    <a class="clin-nav-tab {{ $activeTab === $tKey ? 'active' : '' }}" 
                       href="{{ route('clinical-station.show', array_filter(['surgery' => $surgery->id, 'phase' => $currentPhase, 'tab' => $tKey, 'from' => $from ?? null])) }}"
                       title="{{ __($tMeta['label']) }}">
                        <i class="{{ $tMeta['icon'] }} small"></i>
                        <span>{{ $shortTabLabels[$tKey] ?? __($tMeta['label']) }}</span>
                        @if($tMeta['can_write'])
                            <span class="badge-perm-dot badge-perm-write" title="{{ __('صلاحية إدخال وتعديل') }}"><i class="fas fa-pen"></i></span>
                        @else
                            <span class="badge-perm-dot badge-perm-read" title="{{ __('صلاحية قراءة فقط') }}"><i class="fas fa-eye"></i></span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Active Tab Content Card --}}
    @php
        $currentTabMeta = $visibleTabs[$activeTab] ?? null;
        $canWriteCurrentTab = $currentTabMeta['can_write'] ?? false;
    @endphp

    @if(!$currentTabMeta)
        <div class="alert alert-warning text-center p-5 rounded-4 shadow-sm">
            <i class="fas fa-lock fa-3x text-warning mb-3"></i>
            <h5 class="fw-bold">{{ __('لا تملك صلاحية الوصول لأي تبويب في هذه المحطة') }}</h5>
            <p class="text-muted mb-0">{{ __('يرجى مراجعة إدارة المستشفى لمنحك الصلاحيات المناسبة لدورك الوظيفي.') }}</p>
        </div>
    @else
        <div class="pane-card mb-4">
            {{-- Tab Pane Header --}}
            @php
                $isCompactFormTab = in_array($activeTab, ['history', 'plan']);
            @endphp
            <div class="pane-header d-flex flex-wrap align-items-center justify-content-between gap-2 {{ $isCompactFormTab ? 'py-2.5 px-3' : '' }}">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary {{ $isCompactFormTab ? 'p-2' : 'p-2.5' }}">
                        <i class="{{ $currentTabMeta['icon'] }} {{ $isCompactFormTab ? '' : 'fa-lg' }}"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold text-dark {{ $isCompactFormTab ? 'fs-6' : '' }}">{{ __($currentTabMeta['label']) }}</h5>
                        @if(!$isCompactFormTab)
                            <p class="text-muted small mb-0">{{ __($currentTabMeta['description']) }}</p>
                        @endif
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($canWriteCurrentTab)
                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-1.5 rounded-pill fw-bold small">
                            <i class="fas fa-edit me-1"></i> {{ __('إدخال وتعديل متاح بحسابك') }}
                        </span>
                    @else
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 py-1.5 rounded-pill fw-bold small">
                            <i class="fas fa-eye me-1"></i> {{ __('وضع القراءة والاطلاع فقط') }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Form for tabs that submit directly to update --}}
            @if(in_array($activeTab, ['history', 'vitals', 'plan', 'nursing_care', 'discharge']))
            <form action="{{ route('clinical-station.update', $surgery) }}" method="POST">
                @csrf
                @method('PATCH')
                <input type="hidden" name="phase" value="{{ $currentPhase }}">
                <input type="hidden" name="active_tab" value="{{ $activeTab }}">

                <div class="{{ $isCompactFormTab ? 'p-3' : 'p-4' }}">
                    {{-- =============================================================== --}}
                    {{-- TAB 1: HISTORY (السيرة المرضية) --}}
                    {{-- =============================================================== --}}
                    @if($activeTab === 'history')
                        <div id="clinicalHistoryGrid" class="d-flex flex-column gap-2.5">
                            {{-- Section 1: Present Illness (2 balanced columns) --}}
                            <div class="history-section-card">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small fw-bold text-primary">
                                        <i class="fas fa-stethoscope me-1"></i> {{ __('القصة والشكوى الحالية') }} (Present Illness)
                                    </span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-6 col-lg-5">
                                        <div class="history-field-card">
                                            <label class="form-label fw-bold text-dark small mb-1 d-flex align-items-center gap-1">
                                                <i class="fas fa-comment-medical text-primary"></i>
                                                <span>{{ __('الشكوى الرئيسية') }}</span>
                                                <span class="text-muted fw-normal" style="font-size: 0.72rem;">(Chief Complaint)</span>
                                            </label>
                                            <textarea name="chief_complaint" class="form-control history-compact-textarea rounded-2" rows="2"
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="{{ __('الشكوى والأعراض التي يشكو منها المريض...') }}">{{ $station?->chief_complaint }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-7">
                                        <div class="history-field-card">
                                            <label class="form-label fw-bold text-dark small mb-1 d-flex align-items-center gap-1">
                                                <i class="fas fa-notes-medical text-info"></i>
                                                <span>{{ __('تاريخ المرض الحالي') }}</span>
                                                <span class="text-muted fw-normal" style="font-size: 0.72rem;">(HPI)</span>
                                            </label>
                                            <textarea name="history_present_illness" class="form-control history-compact-textarea rounded-2" rows="2"
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="{{ __('التدرج الزمني وتفاصيل الحالة المرضية الحالية...') }}">{{ $station?->history_present_illness }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 2: Past History, Medications & Allergies (4 columns on xl, 2x2 on tablet) --}}
                            <div class="history-section-card history-past">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small fw-bold text-secondary">
                                        <i class="fas fa-history me-1"></i> {{ __('السوابق الطبية والأدوية والتحسس') }} (Past Hx &amp; Allergies)
                                    </span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-6 col-xl-3">
                                        <div class="history-field-card">
                                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                                <label class="form-label fw-bold text-dark small mb-0">
                                                    <i class="fas fa-heartbeat text-danger me-1"></i> {{ __('التاريخ المرضي السابق') }}
                                                </label>
                                                @if($canWriteCurrentTab)
                                                    <button type="button" class="history-quick-chip" onclick="fillHistoryQuickText('past_medical_hx', '{{ __('لا يوجد') }}')">{{ __('لا يوجد') }}</button>
                                                @endif
                                            </div>
                                            <textarea name="past_medical_hx" id="hist_past_medical_hx" class="form-control history-compact-textarea rounded-2" rows="2"
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="{{ __('ضغط، سكري، ربو، أمراض مزمنة...') }}">{{ $station?->past_medical_hx }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-xl-3">
                                        <div class="history-field-card">
                                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                                <label class="form-label fw-bold text-dark small mb-0">
                                                    <i class="fas fa-cut text-warning me-1"></i> {{ __('التاريخ الجراحي السابق') }}
                                                </label>
                                                @if($canWriteCurrentTab)
                                                    <button type="button" class="history-quick-chip" onclick="fillHistoryQuickText('past_surgical_hx', '{{ __('لا يوجد') }}')">{{ __('لا يوجد') }}</button>
                                                @endif
                                            </div>
                                            <textarea name="past_surgical_hx" id="hist_past_surgical_hx" class="form-control history-compact-textarea rounded-2" rows="2"
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="{{ __('عمليات سابقة، مضاعفات تخدير...') }}">{{ $station?->past_surgical_hx }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-xl-3">
                                        <div class="history-field-card">
                                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                                <label class="form-label fw-bold text-dark small mb-0">
                                                    <i class="fas fa-pills text-success me-1"></i> {{ __('التاريخ الدوائي') }}
                                                </label>
                                                @if($canWriteCurrentTab)
                                                    <button type="button" class="history-quick-chip" onclick="fillHistoryQuickText('drug_hx', '{{ __('لا يوجد') }}')">{{ __('لا يوجد') }}</button>
                                                @endif
                                            </div>
                                            <textarea name="drug_hx" id="hist_drug_hx" class="form-control history-compact-textarea rounded-2" rows="2"
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="{{ __('الأدوية التي يتناولها بانتظام...') }}">{{ $station?->drug_hx }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-xl-3">
                                        <div class="history-field-card history-allergy-card">
                                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                                <label class="form-label fw-bold text-danger small mb-0">
                                                    <i class="fas fa-exclamation-triangle me-1"></i> {{ __('التحسس الدوائي والغذائي') }}
                                                </label>
                                                @if($canWriteCurrentTab)
                                                    <button type="button" class="history-quick-chip chip-danger" onclick="fillHistoryQuickText('drug_allergy', '{{ __('لا توجد حساسية معروفة (NKDA)') }}')">NKDA</button>
                                                @endif
                                            </div>
                                            <textarea name="drug_allergy" id="hist_drug_allergy" class="form-control history-compact-textarea rounded-2 border-danger-subtle bg-white" rows="2"
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="{{ __('حساسية البنسلين، أدوية أو أغذية...') }}">{{ $station?->drug_allergy }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    {{-- =============================================================== --}}
                    {{-- TAB 2: VITALS, EXAM & FLUIDS (جدول موحد للعلامات الحيوية والسوائل) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'vitals')
                        @php
                            $canActuallyEditVitals = $canWriteCurrentTab && ($canEditVitals ?? true);
                            $canEditFluidsFlag = ! empty($canEditFluids);
                            $hasRecordedVitals = !empty($station?->vitals_recorded_at);
                            $recordedTime = $station?->vitals_recorded_at;
                            if ($recordedTime) {
                                $lockExpiresAt = $recordedTime->copy()->addMinutes(30);
                                $secondsRemaining = now()->diffInSeconds($lockExpiresAt, false);
                                $minutesLeft = (int) max(0, min(30, ceil($secondsRemaining / 60)));
                            } else {
                                $minutesLeft = 30;
                            }
                            $recordedByName = $station?->vitalsRecordedBy?->name ?? __('طاقم الرعاية');
                        @endphp

                        {{-- Default Mode: Row 1 starts empty in New Reading mode (is_new_reading = 1), switches to 0 only when editing a saved row below --}}
                        <input type="hidden" name="is_new_reading" id="isNewReadingInput" value="1">
                        <input type="hidden" name="editing_reading_id" id="editingReadingId" value="">

                        {{-- Compact Flowsheet Guidance & Governance Banner --}}
                        @if($readings->isEmpty())
                            <div class="alert alert-info py-2 px-3 rounded-4 d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 border-0 shadow-sm" style="background: #e0f2fe; color: #0369a1;">
                                <div class="small fw-semibold">
                                    <i class="fas fa-info-circle me-1 text-primary"></i> {{ __("لم يتم تسجيل أي قراءات لهذه العملية بعد. أدخل القراءة الأولى في الصف العلوي للجدول الموحد ثم اضغط حفظ لتُدرج مباشرة في السجل.") }}
                                </div>
                            </div>
                        @else
                            <div class="alert py-2 px-3 rounded-4 d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 border-0 shadow-sm" style="background: #f8fafc; border: 1px solid #e2e8f0 !important; color: #334155;">
                                <div class="small">
                                    <i class="fas fa-clipboard-list me-1 text-primary"></i>
                                    <strong>{{ __("آلية الجدول الموحد:") }}</strong> {{ __("الصف الأول مخصص لإدخال قراءة دورية جديدة مباشرة، وتظهر النتائج المسجلة أدناه مرتبة من الأحدث للأقدم (تعديل أو حذف أي قراءة متاح لمسجّلها خلال 30 دقيقة أو لمدير النظام).") }}
                                    @if($hasRecordedVitals)
                                        <span class="text-muted ms-2">| {{ __("آخر تسجيل علامات:") }} <strong>{{ $recordedByName }}</strong> ({{ $recordedTime->format('h:i A') }})</span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Unified Vital Signs & Fluid Balance Flowsheet + History Table --}}
                        <div class="card rounded-4 border shadow-sm mb-4 overflow-hidden" id="vitalsFlowsheetCard">
                            <div class="card-header bg-light py-2.5 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h6 class="mb-0 fw-bold text-dark">
                                        <i class="fas fa-heartbeat text-danger me-1"></i> {{ __("جدول العلامات الحيوية السريرية وميزان السوائل (Intake & Output)") }}
                                    </h6>
                                    <span class="badge bg-white text-secondary border small">{{ $readings->count() }} {{ __("قراءة مسجلة") }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if($canEditFluidsFlag)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-1.5 rounded-pill fw-semibold small">
                                            <i class="fas fa-user-nurse me-1"></i> {{ __("السوائل: متاح للإدخال والتعديل (التمريض / الإدارة)") }}
                                        </span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-15 text-dark border border-warning-subtle px-3 py-1.5 rounded-pill fw-semibold small">
                                            <i class="fas fa-eye me-1 text-warning"></i> {{ __("السوائل: للقراءة فقط — التعديل متاح لكادر التمريض والأدمن فقط") }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Dynamic Banner when Editing a Historical Reading in Row 1 --}}
                            <div id="editingReadingAlert" class="p-2.5 px-3 bg-primary bg-opacity-10 border-bottom border-primary-subtle align-items-center justify-content-between flex-wrap gap-2" style="display: none;">
                                <div class="small text-primary-emphasis fw-bold">
                                    <i class="fas fa-pen-square me-1 fa-lg text-primary"></i>
                                    <span>{{ __("أنت الآن تقوم بتعديل القراءة المسجلة في:") }} </span>
                                    <span id="editingReadingTimestamp" class="badge bg-white text-dark border border-primary-subtle px-2 py-1 mx-1"></span>
                                    <span>{{ __("بواسطة:") }} <span id="editingReadingUser" class="text-dark fw-bold"></span></span>
                                    <span class="text-muted fw-normal d-block mt-0.5" style="font-size: 0.78rem;">{{ __("عدّل القيم في الصف الأول أدناه ثم اضغط \"حفظ\" لتحديث القراءة، أو اضغط \"إلغاء التعديل\" للعودة لإدخال قراءة جديدة.") }}</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger bg-white rounded-pill px-3 fw-bold" onclick="cancelEditingReading()">
                                    <i class="fas fa-times me-1"></i> {{ __("إلغاء التعديل") }}
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle text-center mb-0 small" id="vitalsGridTable" style="min-width: 1380px;">
                                    <thead class="align-middle small">
                                        {{-- Group Header Row --}}
                                        <tr>
                                            <th rowspan="2" class="bg-light text-dark fw-bold" style="min-width: 122px;">
                                                <i class="far fa-clock me-1 text-secondary"></i> {{ __("الوقت / المسجل") }}
                                            </th>
                                            <th colspan="6" class="bg-danger bg-opacity-10 text-danger fw-bold border-bottom">
                                                <i class="fas fa-heartbeat me-1"></i> {{ __("العلامات الحيوية (Vital Signs)") }}
                                            </th>
                                            <th colspan="3" class="bg-primary bg-opacity-10 text-primary fw-bold border-bottom">
                                                <i class="fas fa-arrow-alt-circle-down me-1"></i> {{ __("السوائل الداخلة - Intake (ml)") }}
                                            </th>
                                            <th colspan="5" class="bg-warning bg-opacity-15 text-dark fw-bold border-bottom">
                                                <i class="fas fa-arrow-alt-circle-up me-1 text-danger"></i> {{ __("السوائل الخارجة - Output (ml)") }}
                                            </th>
                                            <th rowspan="2" class="bg-info bg-opacity-10 text-dark fw-bold" style="min-width: 105px;">
                                                <i class="fas fa-balance-scale me-1 text-info"></i> {{ __("صافي السوائل") }}
                                                <div class="text-muted fw-normal" style="font-size: 0.68rem;">Balance (ml)</div>
                                            </th>
                                            <th rowspan="2" class="bg-light text-dark fw-bold" style="min-width: 112px;">
                                                {{ __('الإجراءات') }}
                                            </th>
                                        </tr>
                                        {{-- Column Sub-Headers --}}
                                        <tr class="table-light text-secondary">
                                            <th style="min-width: 82px;">
                                                <div class="fw-bold text-danger">{{ __("ضغط الدم") }}</div>
                                                <div class="text-muted" style="font-size: 0.68rem;">BP (120/80)</div>
                                            </th>
                                            <th style="min-width: 68px;">
                                                <div class="fw-bold text-warning-emphasis">{{ __("الحرارة") }}</div>
                                                <div class="text-muted" style="font-size: 0.68rem;">Temp (°C)</div>
                                            </th>
                                            <th style="min-width: 66px;">
                                                <div class="fw-bold text-danger">{{ __("النبض") }}</div>
                                                <div class="text-muted" style="font-size: 0.68rem;">PR (bpm)</div>
                                            </th>
                                            <th style="min-width: 64px;">
                                                <div class="fw-bold text-primary">{{ __("التنفس") }}</div>
                                                <div class="text-muted" style="font-size: 0.68rem;">RR (/min)</div>
                                            </th>
                                            <th style="min-width: 66px;">
                                                <div class="fw-bold text-success">{{ __("الأكسجين") }}</div>
                                                <div class="text-muted" style="font-size: 0.68rem;">SpO2 (%)</div>
                                            </th>
                                            <th style="min-width: 68px;">
                                                <div class="fw-bold text-secondary">{{ __("سكر الدم") }}</div>
                                                <div class="text-muted" style="font-size: 0.68rem;">RBS</div>
                                            </th>
                                            <th class="bg-primary bg-opacity-5" style="min-width: 68px;">
                                                <div class="fw-bold text-primary">{{ __("وريدية") }}</div>
                                                <div class="text-muted" style="font-size: 0.68rem;">IV Fluids</div>
                                            </th>
                                            <th class="bg-primary bg-opacity-5" style="min-width: 62px;">
                                                <div class="fw-bold text-primary">{{ __("فموي") }}</div>
                                                <div class="text-muted" style="font-size: 0.68rem;">Oral</div>
                                            </th>
                                            <th class="bg-primary bg-opacity-5" style="min-width: 62px;">
                                                <div class="fw-bold text-primary">{{ __("دم") }}</div>
                                                <div class="text-muted" style="font-size: 0.68rem;">Blood</div>
                                            </th>
                                            <th class="bg-warning bg-opacity-5" style="min-width: 62px;">
                                                <div class="fw-bold text-danger">{{ __("بول") }}</div>
                                                <div class="text-muted" style="font-size: 0.68rem;">Urine</div>
                                            </th>
                                            <th class="bg-warning bg-opacity-5" style="min-width: 62px;">
                                                <div class="fw-bold text-danger">{{ __("درنقة") }}</div>
                                                <div class="text-muted" style="font-size: 0.68rem;">Drain</div>
                                            </th>
                                            <th class="bg-warning bg-opacity-5" style="min-width: 68px;">
                                                <div class="fw-bold text-danger">{{ __("أنبوب معدة") }}</div>
                                                <div class="text-muted" style="font-size: 0.68rem;">NG Tube</div>
                                            </th>
                                            <th class="bg-warning bg-opacity-5" style="min-width: 62px;">
                                                <div class="fw-bold text-danger">{{ __("قيء") }}</div>
                                                <div class="text-muted" style="font-size: 0.68rem;">Vomiting</div>
                                            </th>
                                            <th class="bg-warning bg-opacity-5" style="min-width: 62px;">
                                                <div class="fw-bold text-danger">{{ __("براز") }}</div>
                                                <div class="text-muted" style="font-size: 0.68rem;">Stool</div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- ROW 1: Single Horizontal Entry / Edit Row --}}
                                        @if($canWriteCurrentTab)
                                            <tr id="vitalsInputRow" class="table-success bg-opacity-10">
                                                <td class="p-1.5 text-center" id="row1ModeCell">
                                                    <span class="badge bg-success text-white rounded-pill px-2 py-1 d-inline-block">
                                                        <i class="fas fa-plus-circle me-1"></i> {{ __("قراءة جديدة") }}
                                                    </span>
                                                    <div class="text-muted mt-1" style="font-size: 0.66rem;">{{ __("أدخل القيم أفقياً") }}</div>
                                                </td>
                                                {{-- 6 Vital Inputs --}}
                                                <td class="p-1">
                                                    <input type="text" name="bp" id="grid_bp" class="form-control form-control-sm text-center fw-bold vital-grid-input text-danger" placeholder="120/80" value="{{ old('bp') }}" data-original="" data-original-readonly="0">
                                                </td>
                                                <td class="p-1">
                                                    <input type="text" name="temp" id="grid_temp" class="form-control form-control-sm text-center fw-bold vital-grid-input text-warning-emphasis" placeholder="37.0" value="{{ old('temp') }}" data-original="" data-original-readonly="0">
                                                </td>
                                                <td class="p-1">
                                                    <input type="text" name="pr" id="grid_pr" class="form-control form-control-sm text-center fw-bold vital-grid-input text-danger" placeholder="75" value="{{ old('pr') }}" data-original="" data-original-readonly="0">
                                                </td>
                                                <td class="p-1">
                                                    <input type="text" name="rr" id="grid_rr" class="form-control form-control-sm text-center fw-bold vital-grid-input text-primary" placeholder="16" value="{{ old('rr') }}" data-original="" data-original-readonly="0">
                                                </td>
                                                <td class="p-1">
                                                    <input type="text" name="spo2" id="grid_spo2" class="form-control form-control-sm text-center fw-bold vital-grid-input text-success" placeholder="98" value="{{ old('spo2') }}" data-original="" data-original-readonly="0">
                                                </td>
                                                <td class="p-1">
                                                    <input type="text" name="rbs" id="grid_rbs" class="form-control form-control-sm text-center fw-bold vital-grid-input" placeholder="110" value="{{ old('rbs') }}" data-original="" data-original-readonly="0">
                                                </td>
                                                {{-- 3 Intake Fluid Inputs (Nurse & Admin Only) --}}
                                                <td class="p-1 bg-primary bg-opacity-5">
                                                    <input type="number" step="0.01" min="0" name="intake_iv_fluids" id="grid_intake_iv_fluids"
                                                           class="form-control form-control-sm text-center fw-bold fluid-grid-input {{ !$canEditFluidsFlag ? 'bg-light text-muted' : '' }}"
                                                           value="{{ $canEditFluidsFlag ? old('intake_iv_fluids') : '' }}"
                                                           data-original=""
                                                           data-original-readonly="{{ !$canEditFluidsFlag ? '1' : '0' }}"
                                                           data-fluid-editable="{{ $canEditFluidsFlag ? '1' : '0' }}"
                                                           {{ !$canEditFluidsFlag ? 'readonly' : '' }}
                                                           title="{{ !$canEditFluidsFlag ? __('للقراءة فقط — التعديل لكادر التمريض والأدمن فقط') : __('السوائل الوريدية (ml)') }}"
                                                           placeholder="{{ $canEditFluidsFlag ? '0' : '--' }}">
                                                </td>
                                                <td class="p-1 bg-primary bg-opacity-5">
                                                    <input type="number" step="0.01" min="0" name="intake_oral" id="grid_intake_oral"
                                                           class="form-control form-control-sm text-center fw-bold fluid-grid-input {{ !$canEditFluidsFlag ? 'bg-light text-muted' : '' }}"
                                                           value="{{ $canEditFluidsFlag ? old('intake_oral') : '' }}"
                                                           data-original=""
                                                           data-original-readonly="{{ !$canEditFluidsFlag ? '1' : '0' }}"
                                                           data-fluid-editable="{{ $canEditFluidsFlag ? '1' : '0' }}"
                                                           {{ !$canEditFluidsFlag ? 'readonly' : '' }}
                                                           title="{{ !$canEditFluidsFlag ? __('للقراءة فقط — التعديل لكادر التمريض والأدمن فقط') : __('السوائل الفموية (ml)') }}"
                                                           placeholder="{{ $canEditFluidsFlag ? '0' : '--' }}">
                                                </td>
                                                <td class="p-1 bg-primary bg-opacity-5">
                                                    <input type="number" step="0.01" min="0" name="intake_blood" id="grid_intake_blood"
                                                           class="form-control form-control-sm text-center fw-bold fluid-grid-input {{ !$canEditFluidsFlag ? 'bg-light text-muted' : '' }}"
                                                           value="{{ $canEditFluidsFlag ? old('intake_blood') : '' }}"
                                                           data-original=""
                                                           data-original-readonly="{{ !$canEditFluidsFlag ? '1' : '0' }}"
                                                           data-fluid-editable="{{ $canEditFluidsFlag ? '1' : '0' }}"
                                                           {{ !$canEditFluidsFlag ? 'readonly' : '' }}
                                                           title="{{ !$canEditFluidsFlag ? __('للقراءة فقط — التعديل لكادر التمريض والأدمن فقط') : __('الدم ومشتقاته (ml)') }}"
                                                           placeholder="{{ $canEditFluidsFlag ? '0' : '--' }}">
                                                </td>
                                                {{-- 5 Output Fluid Inputs (Nurse & Admin Only) --}}
                                                <td class="p-1 bg-warning bg-opacity-5">
                                                    <input type="number" step="0.01" min="0" name="output_urine" id="grid_output_urine"
                                                           class="form-control form-control-sm text-center fw-bold fluid-grid-input {{ !$canEditFluidsFlag ? 'bg-light text-muted' : '' }}"
                                                           value="{{ $canEditFluidsFlag ? old('output_urine') : '' }}"
                                                           data-original=""
                                                           data-original-readonly="{{ !$canEditFluidsFlag ? '1' : '0' }}"
                                                           data-fluid-editable="{{ $canEditFluidsFlag ? '1' : '0' }}"
                                                           {{ !$canEditFluidsFlag ? 'readonly' : '' }}
                                                           title="{{ !$canEditFluidsFlag ? __('للقراءة فقط — التعديل لكادر التمريض والأدمن فقط') : __('إدرار البول (ml)') }}"
                                                           placeholder="{{ $canEditFluidsFlag ? '0' : '--' }}">
                                                </td>
                                                <td class="p-1 bg-warning bg-opacity-5 position-relative text-center align-middle" style="min-width: 85px;">
                                                    <input type="hidden" name="output_drain" id="grid_output_drain"
                                                           class="fluid-grid-input"
                                                           value="{{ $canEditFluidsFlag ? old('output_drain') : '' }}"
                                                           data-original=""
                                                           data-original-readonly="{{ !$canEditFluidsFlag ? '1' : '0' }}"
                                                           data-fluid-editable="{{ $canEditFluidsFlag ? '1' : '0' }}">
                                                    <input type="hidden" name="drains_json" id="grid_drains_json" value="">

                                                    @if($canEditFluidsFlag)
                                                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-1.5 rounded-pill text-nowrap fw-bold shadow-xs w-100"
                                                                id="btnOpenDrainModal"
                                                                onclick="openDrainModal()"
                                                                title="{{ __('إدخال وتفصيل قراءات درنقات المريض') }}"
                                                                style="font-size: 0.72rem; min-height: 28px;">
                                                            <span id="drainButtonLabel">
                                                                <i class="fas fa-plus-circle me-1"></i> {{ __("الدرنقات") }}
                                                            </span>
                                                        </button>
                                                    @else
                                                        <span class="badge bg-light text-muted border py-1.5 px-2 rounded-pill small" title="{{ __('للقراءة فقط — التعديل للتمريض والأدمن') }}">
                                                            <span id="drainButtonLabel">--</span>
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="p-1 bg-warning bg-opacity-5">
                                                    <input type="number" step="0.01" min="0" name="output_gtube_ng" id="grid_output_gtube_ng"
                                                           class="form-control form-control-sm text-center fw-bold fluid-grid-input {{ !$canEditFluidsFlag ? 'bg-light text-muted' : '' }}"
                                                           value="{{ $canEditFluidsFlag ? old('output_gtube_ng') : '' }}"
                                                           data-original=""
                                                           data-original-readonly="{{ !$canEditFluidsFlag ? '1' : '0' }}"
                                                           data-fluid-editable="{{ $canEditFluidsFlag ? '1' : '0' }}"
                                                           {{ !$canEditFluidsFlag ? 'readonly' : '' }}
                                                           title="{{ !$canEditFluidsFlag ? __('للقراءة فقط — التعديل لكادر التمريض والأدمن فقط') : __('أنبوب المعدة NG (ml)') }}"
                                                           placeholder="{{ $canEditFluidsFlag ? '0' : '--' }}">
                                                </td>
                                                <td class="p-1 bg-warning bg-opacity-5">
                                                    <input type="number" step="0.01" min="0" name="output_vomiting" id="grid_output_vomiting"
                                                           class="form-control form-control-sm text-center fw-bold fluid-grid-input {{ !$canEditFluidsFlag ? 'bg-light text-muted' : '' }}"
                                                           value="{{ $canEditFluidsFlag ? old('output_vomiting') : '' }}"
                                                           data-original=""
                                                           data-original-readonly="{{ !$canEditFluidsFlag ? '1' : '0' }}"
                                                           data-fluid-editable="{{ $canEditFluidsFlag ? '1' : '0' }}"
                                                           {{ !$canEditFluidsFlag ? 'readonly' : '' }}
                                                           title="{{ !$canEditFluidsFlag ? __('للقراءة فقط — التعديل لكادر التمريض والأدمن فقط') : __('القيء (ml)') }}"
                                                           placeholder="{{ $canEditFluidsFlag ? '0' : '--' }}">
                                                </td>
                                                <td class="p-1 bg-warning bg-opacity-5">
                                                    <input type="number" step="0.01" min="0" name="output_stool" id="grid_output_stool"
                                                           class="form-control form-control-sm text-center fw-bold fluid-grid-input {{ !$canEditFluidsFlag ? 'bg-light text-muted' : '' }}"
                                                           value="{{ $canEditFluidsFlag ? old('output_stool') : '' }}"
                                                           data-original=""
                                                           data-original-readonly="{{ !$canEditFluidsFlag ? '1' : '0' }}"
                                                           data-fluid-editable="{{ $canEditFluidsFlag ? '1' : '0' }}"
                                                           {{ !$canEditFluidsFlag ? 'readonly' : '' }}
                                                           title="{{ !$canEditFluidsFlag ? __('للقراءة فقط — التعديل لكادر التمريض والأدمن فقط') : __('البراز (ml)') }}"
                                                           placeholder="{{ $canEditFluidsFlag ? '0' : '--' }}">
                                                </td>
                                                {{-- Live Balance Preview Cell --}}
                                                <td class="p-1.5">
                                                    <div id="fluidBalanceSummaryBox" class="rounded-2 border px-1.5 py-1 bg-light border-secondary-subtle">
                                                        <div id="fluidNetBalanceDisplay" class="fw-bold small text-secondary">--</div>
                                                        <div class="text-muted text-nowrap" style="font-size: 0.65rem;">
                                                            {{ __("د:") }}<span id="fluidTotalIntakeDisplay">0</span> | {{ __("خ:") }}<span id="fluidTotalOutputDisplay">0</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                {{-- Row 1 Inline Save / Cancel Buttons --}}
                                                <td class="p-1.5">
                                                    <div class="d-flex flex-column gap-1 align-items-center">
                                                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-2.5 py-1 fw-bold shadow-xs text-nowrap w-100" id="row1SaveBtn">
                                                            <i class="fas fa-save me-1"></i> {{ __("حفظ") }}
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0.5 fw-bold text-nowrap w-100 d-none" id="row1CancelEditBtn" onclick="cancelEditingReading()">
                                                            <i class="fas fa-times me-1"></i> {{ __("إلغاء") }}
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif

                                        {{-- ROWS 2..N: Recorded Historical Readings Directly Underneath (Newest First) --}}
                                        @forelse($readings as $r)
                                            @php
                                                $canManageThisReading = $canWriteCurrentTab && (
                                                    !empty($isAdmin) || (
                                                        auth()->check() && (
                                                            (int)$r->user_id === (int)auth()->id() || (empty($r->user_id) && $r->resident_id === auth()->user()->doctor?->id)
                                                        ) && now()->lte($r->created_at->copy()->addMinutes(30))
                                                    )
                                                );
                                                $rIntake = (float) ($r->intake_iv_fluids ?? 0) + (float) ($r->intake_oral ?? 0) + (float) ($r->intake_blood ?? 0);
                                                $rOutput = (float) ($r->output_urine ?? 0) + (float) ($r->output_drain ?? 0) + (float) ($r->output_gtube_ng ?? 0) + (float) ($r->output_vomiting ?? 0) + (float) ($r->output_stool ?? 0);
                                            @endphp
                                            <tr class="bg-white">
                                                <td class="p-1.5 text-center">
                                                    <div class="fw-bold text-dark text-nowrap" style="font-size: 0.78rem;">{{ $r->created_at->format('h:i A') }}</div>
                                                    <div class="text-muted text-nowrap" style="font-size: 0.68rem;">{{ $r->created_at->format('Y-m-d') }}</div>
                                                    <div class="text-primary fw-semibold text-truncate mx-auto" style="font-size: 0.72rem; max-width: 112px;" title="{{ $r->user?->name ?? $r->resident?->user?->name ?? __('طاقم الرعاية') }}">
                                                        {{ $r->user?->name ?? $r->resident?->user?->name ?? __('طاقم الرعاية') }}
                                                    </div>
                                                </td>
                                                {{-- 6 Recorded Vitals --}}
                                                <td>
                                                    @if($r->bp)
                                                        <span class="badge bg-danger bg-opacity-10 text-danger border">{{ $r->bp }}</span>
                                                    @else
                                                        <span class="text-muted">--</span>
                                                    @endif
                                                </td>
                                                <td>{{ $r->temp ? $r->temp . '°' : '--' }}</td>
                                                <td>{{ $r->pr ?: '--' }}</td>
                                                <td>{{ $r->rr ?: '--' }}</td>
                                                <td>
                                                    @if($r->spo2)
                                                        <span class="badge bg-success bg-opacity-10 text-success border">{{ $r->spo2 }}%</span>
                                                    @else
                                                        <span class="text-muted">--</span>
                                                    @endif
                                                </td>
                                                <td>{{ $r->rbs ?: '--' }}</td>
                                                {{-- 3 Recorded Intake Fluids --}}
                                                <td class="bg-primary bg-opacity-5 fw-semibold text-primary">
                                                    {{ !is_null($r->intake_iv_fluids) ? (float) $r->intake_iv_fluids : '--' }}
                                                </td>
                                                <td class="bg-primary bg-opacity-5 fw-semibold text-primary">
                                                    {{ !is_null($r->intake_oral) ? (float) $r->intake_oral : '--' }}
                                                </td>
                                                <td class="bg-primary bg-opacity-5 fw-semibold text-primary">
                                                    {{ !is_null($r->intake_blood) ? (float) $r->intake_blood : '--' }}
                                                </td>
                                                {{-- 5 Recorded Output Fluids --}}
                                                <td class="bg-warning bg-opacity-5 fw-semibold text-danger">
                                                    {{ !is_null($r->output_urine) ? (float) $r->output_urine : '--' }}
                                                </td>
                                                <td class="bg-warning bg-opacity-5 fw-semibold text-danger text-center">
                                                    @if(!is_null($r->output_drain))
                                                        <div>{{ (float) $r->output_drain }}</div>
                                                        @if($r->drains && $r->drains->count() > 0)
                                                            @php
                                                                $drainTooltip = $r->drains->map(fn($d) => ($d->drain_label ?: (__('درنقة') . ' ' . $d->drain_number)) . ': ' . (float)$d->amount . ' ml')->implode(' | ');
                                                            @endphp
                                                            <div class="mt-0.5">
                                                                <span class="badge bg-danger bg-opacity-15 text-danger border border-danger-subtle px-1 py-0.5 rounded-pill"
                                                                      style="font-size: 0.62rem; cursor: pointer;"
                                                                      title="{{ $drainTooltip }}"
                                                                      data-bs-toggle="tooltip"
                                                                      onclick='viewReadingDrainsModal(@json($r->drains), {{ (float)$r->output_drain }})'>
                                                                    <i class="fas fa-eye me-0.5"></i> {{ $r->drains->count() }} {{ __("درنقة") }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                    @else
                                                        --
                                                    @endif
                                                </td>
                                                <td class="bg-warning bg-opacity-5 fw-semibold text-danger">
                                                    {{ !is_null($r->output_gtube_ng) ? (float) $r->output_gtube_ng : '--' }}
                                                </td>
                                                <td class="bg-warning bg-opacity-5 fw-semibold text-danger">
                                                    {{ !is_null($r->output_vomiting) ? (float) $r->output_vomiting : '--' }}
                                                </td>
                                                <td class="bg-warning bg-opacity-5 fw-semibold text-danger">
                                                    {{ !is_null($r->output_stool) ? (float) $r->output_stool : '--' }}
                                                </td>
                                                {{-- Recorded Net Balance --}}
                                                <td class="p-1.5">
                                                    @if(!is_null($r->fluid_balance))
                                                        <span class="badge {{ $r->fluid_balance >= 0 ? 'bg-success bg-opacity-15 text-success border border-success-subtle' : 'bg-danger bg-opacity-10 text-danger border border-danger-subtle' }}">
                                                            {{ $r->fluid_balance > 0 ? '+' . (float) $r->fluid_balance : (float) $r->fluid_balance }} ml
                                                        </span>
                                                        <div class="text-muted text-nowrap mt-0.5" style="font-size: 0.65rem;">
                                                            {{ __("د:") }}{{ (float) $rIntake }} | {{ __("خ:") }}{{ (float) $rOutput }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted">--</span>
                                                    @endif
                                                </td>
                                                {{-- Recorded Row Actions --}}
                                                <td>
                                                    @if($canManageThisReading)
                                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill edit-reading-btn fw-bold text-nowrap"
                                                                    title="{{ __('تعديل هذه القراءة') }}"
                                                                    onclick='loadReadingIntoTopForm(@json($r))'>
                                                                <i class="fas fa-pen small me-1"></i> {{ __("تعديل") }}
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 rounded-pill delete-reading-btn"
                                                                    title="{{ __('حذف القراءة') }}"
                                                                    onclick='confirmDeleteReading("{{ route('clinical-station.readings.delete', $r->id) }}")'>
                                                                <i class="fas fa-trash small"></i>
                                                            </button>
                                                        </div>
                                                    @else
                                                        <span class="badge bg-light text-muted border" title="{{ __('مقفل (مضى أكثر من 30 دقيقة أو مسجل بحساب آخر)') }}">
                                                            <i class="fas fa-lock" style="font-size: 0.7rem;"></i> {{ __("مقفل") }}
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="17" class="text-muted py-3">{{ __("لم يتم تسجيل قراءات سابقة حتى الآن.") }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Clinical Examination (Doctors, Residents, Surgeons & Admin only — Hidden from Nurses) --}}
                        @if(!empty($canManageClinicalExam))
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark">
                                    <i class="fas fa-stethoscope text-primary me-1"></i> {{ __("ملاحظات الفحص السريري (Clinical Examination):") }}
                                    <small class="text-muted fw-normal ms-1">{{ __("(تُحفظ عبر زر \"حفظ\" في الصف الأول للجدول أعلاه)") }}</small>
                                </label>
                                <textarea name="clinical_examination" class="form-control rounded-3" rows="3"
                                          data-original="{{ $station?->clinical_examination }}"
                                          data-original-readonly="{{ !$canWriteCurrentTab ? '1' : '0' }}"
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="{{ __('ملاحظات الفحص العام وفحص الأجهزة الحيوية...') }}">{{ $station?->clinical_examination }}</textarea>
                            </div>
                        @endif

                        {{-- Trend Charts Section (Visible only to Surgeon, Resident, and Admin - Hidden from Nurses) --}}
                        @if(!empty($canViewVitalsCharts))
                            <div class="card rounded-4 border p-3 mb-2">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-line text-primary me-2"></i> {{ __("المنحنيات البيانية لتطور العلامات الحيوية") }}</h6>
                                    <span class="badge bg-light text-secondary border small">{{ $readingsAsc->count() }} {{ __("قراءة مسجلة") }}</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="border rounded-3 p-2 bg-light bg-opacity-25">
                                            <div class="text-center small fw-bold text-danger mb-2">{{ __("منحنى ضغط الدم (BP Trend)") }}</div>
                                            <div style="height: 180px;">
                                                <canvas id="bpChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded-3 p-2 bg-light bg-opacity-25">
                                            <div class="text-center small fw-bold text-danger mb-2">{{ __("منحنى النبض (Pulse Rate Trend)") }}</div>
                                            <div style="height: 180px;">
                                                <canvas id="prChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    {{-- =============================================================== --}}
                    {{-- TAB 3: MEDICAL PLAN (الخطة الطبية وتوصيات الجراحة) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'plan')
                        @php
                            $surgeonSt = $surgery->surgeonStation;
                            $protocolLabels = [
                                'standard' => __('قياسي (علامات حيوية)'),
                                'fluid_monitoring' => __('مراقبة السوائل والعلامات'),
                                'intensive' => __('مكثف (عناية ومراقبة دقيقة)'),
                            ];
                            $hasSurgeonSummary = ($surgeonSt && (
                                !empty($surgeonSt->treatment_plan) ||
                                !empty($surgeonSt->notes) ||
                                (!empty($surgeonSt->monitoring_protocol) && $surgeonSt->monitoring_protocol !== 'standard')
                            )) || !empty($surgery->post_op_notes) || !empty($surgery->treatment_plan);
                        @endphp

                        <div id="clinicalPlanGrid" class="d-flex flex-column gap-2.5">
                            @if($hasSurgeonSummary)
                                <div class="alert alert-light border border-primary-subtle rounded-3 py-2 px-3 mb-0 d-flex flex-wrap align-items-center gap-2 small shadow-xs" style="background: #f0f7ff;">
                                    <span class="fw-bold text-primary">
                                        <i class="fas fa-user-md me-1"></i> {{ __("خلاصة توصية الجراح:") }}
                                    </span>
                                    @if($surgeonSt && !empty($surgeonSt->monitoring_protocol))
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill">
                                            {{ __("البروتوكول:") }} {{ $protocolLabels[$surgeonSt->monitoring_protocol] ?? $surgeonSt->monitoring_protocol }}
                                        </span>
                                    @endif
                                    @if(!empty($surgery->post_op_notes) || ($surgeonSt && !empty($surgeonSt->notes)))
                                        <span class="badge bg-white text-success border border-success-subtle rounded-pill fw-normal text-truncate" style="max-width: 360px;" title="{{ $surgery->post_op_notes ?? $surgeonSt->notes }}">
                                            <strong><i class="fas fa-clipboard-check text-success me-1"></i>{{ __("ملاحظات ما بعد العملية:") }}</strong> {{ $surgery->post_op_notes ?? $surgeonSt->notes }}
                                        </span>
                                    @endif
                                    @if(!empty($surgery->treatment_plan) || ($surgeonSt && !empty($surgeonSt->treatment_plan)))
                                        <span class="badge bg-white text-dark border rounded-pill fw-normal text-truncate" style="max-width: 340px;" title="{{ $surgery->treatment_plan ?? $surgeonSt->treatment_plan }}">
                                            <strong>{{ __("الخطة:") }}</strong> {{ $surgery->treatment_plan ?? $surgeonSt->treatment_plan }}
                                        </span>
                                    @endif
                                </div>
                            @endif

                            {{-- Section 1: Pre-Op & Post-Op Surgical Instructions (2 balanced columns) --}}
                            <div class="history-section-card">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small fw-bold text-primary">
                                        <i class="fas fa-procedures me-1"></i> {{ __("التوصيات الجراحية حسب المرحلة (Pre-Op & Post-Op)") }}
                                    </span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="history-field-card {{ $currentPhase === 'pre_op' ? 'plan-phase-active' : '' }}">
                                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                                <label class="form-label fw-bold text-dark small mb-0">
                                                    <i class="fas fa-clipboard-check text-primary me-1"></i> {{ __("توصيات ما قبل الجراحة") }}
                                                    <span class="text-muted fw-normal" style="font-size: 0.72rem;">(Pre-Op Plan)</span>
                                                </label>
                                                @if($currentPhase === 'pre_op')
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill" style="font-size: 0.66rem;">{{ __("المرحلة الحالية") }}</span>
                                                @endif
                                            </div>
                                            <textarea name="notes" id="plan_notes" class="form-control history-compact-textarea rounded-2" rows="2"
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="{{ __('تعليمات الصيام، تحضير الدم، تجهيز الصالة...') }}">{{ $station?->notes }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="history-field-card {{ $currentPhase === 'post_op' ? 'plan-phase-active-post' : '' }}">
                                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                                <label class="form-label fw-bold text-dark small mb-0">
                                                    <i class="fas fa-notes-medical text-success me-1"></i> {{ __("توصيات ما بعد الجراحة") }}
                                                    <span class="text-muted fw-normal" style="font-size: 0.72rem;">(Post-Op)</span>
                                                </label>
                                                @if(!empty($surgery->post_op_notes))
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill" style="font-size: 0.66rem;" title="{{ __('ملاحظات الطبيب الجراح المدونة في تفاصيل العملية') }}">
                                                        <i class="fas fa-user-md me-1"></i> {{ __("من ملاحظات الجراح") }}
                                                    </span>
                                                @elseif($currentPhase === 'post_op')
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill" style="font-size: 0.66rem;">{{ __("المرحلة الحالية") }}</span>
                                                @endif
                                            </div>
                                            <textarea name="post_op_notes" id="plan_post_op_notes" class="form-control history-compact-textarea rounded-2" rows="2"
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="{{ __('العناية بالجرح، المسكنات، وقت بدء السوائل...') }}">{{ old('post_op_notes', $station?->post_op_notes ?: ($surgery->post_op_notes ?? '')) }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 2: General Treatment Plan & Review of Systems (2 balanced columns) --}}
                            <div class="history-section-card history-past">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small fw-bold text-secondary">
                                        <i class="fas fa-stethoscope me-1"></i> {{ __("الخطة العلاجية ومراجعة الأجهزة الحيوية (Plan & ROS)") }}
                                    </span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="history-field-card">
                                            <label class="form-label fw-bold text-dark small mb-1 d-flex align-items-center gap-1">
                                                <i class="fas fa-file-medical text-warning"></i>
                                                <span>{{ __("الخطة العلاجية العامة") }}</span>
                                                <span class="text-muted fw-normal" style="font-size: 0.72rem;">(Treatment Plan)</span>
                                            </label>
                                            <textarea name="treatment_plan" id="plan_treatment_plan" class="form-control history-compact-textarea rounded-2" rows="2"
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="{{ __('خطة العلاج والمتابعة، الاستشارات المطلوبة، التحضير للخروج...') }}">{{ $station?->treatment_plan }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="history-field-card">
                                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                                <label class="form-label fw-bold text-dark small mb-0">
                                                    <i class="fas fa-heartbeat text-info me-1"></i> {{ __("مراجعة الأجهزة الحيوية") }}
                                                    <span class="text-muted fw-normal" style="font-size: 0.72rem;">(ROS)</span>
                                                </label>
                                                @if($canWriteCurrentTab)
                                                    <button type="button" class="history-quick-chip" onclick="fillHistoryQuickText('review_of_other_systems', '{{ __("سليمة ولا توجد ملاحظات (NAD)") }}')">{{ __("سليمة NAD") }}</button>
                                                @endif
                                            </div>
                                            <textarea name="review_of_other_systems" id="plan_review_of_other_systems" class="form-control history-compact-textarea rounded-2" rows="2"
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="{{ __('الصدر، البطن، الجهاز العصبي، الجهاز البولي...') }}">{{ $station?->review_of_other_systems }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    {{-- =============================================================== --}}
                    {{-- TAB 5: TREATMENTS (الأدوية وجدول الإعطاء) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'treatments')
                        {{-- Handled below outside standard form to support administer actions --}}

                    {{-- =============================================================== --}}
                    {{-- TAB 5B: MEDICAL SUPPLIES (المستلزمات الطبية) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'supplies')
                        {{-- Handled below outside standard form to support direct floor stock dispense --}}
                    
                    {{-- =============================================================== --}}
                    {{-- TAB 6: FOLLOW UP SHEET (سجل المتابعة اليومي) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'follow_up')
                        {{-- Handled below outside standard form to support multi-entries --}}

                    {{-- =============================================================== --}}
                    {{-- TAB 7: NURSING CARE & NOTES (الرعاية التمريضية والتخريج) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'nursing_care')
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-dark"><i class="fas fa-notes-medical text-primary me-1"></i> {{ __("ملاحظات الرعاية التمريضية (Nursing Care Notes):") }}</label>
                                <textarea name="nursing_notes" class="form-control rounded-3" rows="4" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="{{ __('ملاحظات الممرض حول حالة المريض، تغيير الضماد، الاستجابة للعلاج...') }}">{{ $nursingStation?->nursing_notes }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-dark"><i class="fas fa-walking text-success me-1"></i> {{ __("تعليمات وملاحظات التخريج (Discharge Notes):") }}</label>
                                <textarea name="discharge_notes" class="form-control rounded-3" rows="3" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="{{ __('تعليمات الخروج للمنزل، مواعيد المراجعة، النصائح الغذائية...') }}">{{ $nursingStation?->discharge_notes }}</textarea>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Action Footer for direct update forms (excluded from vitals since Row 1 has the unified Save button) --}}
                @if(!in_array($activeTab, ['vitals', 'treatments', 'supplies', 'follow_up', 'inpatient_survey', 'tests', 'consultations']) && $canWriteCurrentTab)
                    <div class="{{ $isCompactFormTab ? 'py-2 px-3' : 'p-3' }} bg-light border-top d-flex align-items-center justify-content-between">
                        <span class="text-muted small">
                            <i class="fas fa-info-circle text-info me-1"></i> {{ __("المحطة السريرية تحفظ البيانات فورياً وتزامنها مع ملف المريض وقسم التمريض.") }}
                        </span>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                            <i class="fas fa-save me-1"></i> {{ __("حفظ التحديثات السريرية") }}
                        </button>
                    </div>
                @endif
            </form>
            @endif

            {{-- =============================================================== --}}
            {{-- SPECIAL SUB-VIEW FOR LAB & RADIOLOGY TESTS TAB --}}
            {{-- =============================================================== --}}
            @if($activeTab === 'tests')
                <div class="p-4">
                    @php
                        $insType = $surgery->insurance_type ?? $surgery->patient?->insurance_type ?? 'none';
                        $isPatientInsurance = in_array($insType, ['hi', 'moi']);
                        $insLabel = match($insType) {
                            'hi' => __('ضمان صحي (HI)'),
                            'moi' => __('قوى الأمن الداخلي (MOI)'),
                            default => __('دفع نقدي (كاش)')
                        };
                        $labCopayVal = $isPatientInsurance ? ($surgery->patient?->getCopayPercentageFor('lab') ?? 15) : 0;
                        $radCopayVal = $isPatientInsurance ? ($surgery->patient?->getCopayPercentageFor('radiology') ?? 15) : 0;
                    @endphp

                    {{-- Financial & Workflow Guidance Banner --}}
                    <div class="alert alert-info border-0 rounded-4 shadow-sm mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3 p-3" style="background: #f0f9ff; border-inline-start: 4px solid #0284c7 !important;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="fas fa-file-invoice-dollar fa-lg"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark small mb-0.5">
                                    {{ __("الهوية المالية للعملية:") }} <span class="badge {{ $isPatientInsurance ? 'bg-success' : 'bg-primary' }} rounded-pill px-2.5 py-1">{{ $insLabel }}</span>
                                    @if($isPatientInsurance)
                                        <span class="text-success small ms-2"><i class="fas fa-shield-alt"></i> {{ __("نسبة تحمل المريض: ") }}{{ $labCopayVal }}%{{ __(" للتحاليل و ") }}{{ $radCopayVal }}%{{ __(" للأشعة") }}</span>
                                    @endif
                                </div>
                                <div class="text-muted small">{{ __("تظهر جميع الطلبات فوراً في شاشات المختبر والأشعة دون حجب مع تمييز حالة السداد، وتُدرج في كاشير العمليات للتحصيل (نقداً لمريض الكاش، وبنسبة التحمل المقررة لمريض الضمان).") }}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('cashier.surgeries.payment.form', $surgery) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" target="_blank">
                                <i class="fas fa-cash-register me-1"></i> {{ __("كاشير العملية") }}
                            </a>
                        </div>
                    </div>

                    <div class="row g-4">
                        {{-- Lab Tests --}}
                        <div class="col-lg-6">
                            <div class="card rounded-4 border shadow-sm h-100 bg-white overflow-hidden">
                                <div class="card-header bg-white py-3 px-3.5 d-flex align-items-center justify-content-between border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="rounded-3 bg-danger bg-opacity-10 text-danger p-2 d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                            <i class="fas fa-flask"></i>
                                        </span>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark">{{ __("تحاليل المختبر (Lab Tests)") }}</h6>
                                            <span class="text-muted small" style="font-size: 0.72rem;">{{ __("إجمالي الفحوصات: ") }}{{ $surgery->labTests?->count() ?? 0 }}</span>
                                        </div>
                                    </div>
                                    @if(!empty($canEditTests))
                                        <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#orderLabTestsModal">
                                            <i class="fas fa-plus me-1"></i> {{ __("طلب تحاليل") }}
                                        </button>
                                    @endif
                                </div>
                                <div class="p-3">
                                    @if($surgery->labTests && $surgery->labTests->count() > 0)
                                        <div class="list-group list-group-flush gap-2">
                                            @foreach($surgery->labTests as $lt)
                                                @php
                                                    $isPaid = ($lt->payment_status === 'paid');
                                                    $labModel = $lt->labTest;
                                                    $bPrice = (float)($labModel?->price ?? 0);
                                                    $hiPrice = (float)(($labModel && $labModel->hi_price > 0) ? $labModel->hi_price : $bPrice);
                                                    $moiPrice = (float)(($labModel && $labModel->moi_price > 0) ? $labModel->moi_price : $bPrice);
                                                    $approvedPrice = match($insType) {
                                                        'hi' => $hiPrice,
                                                        'moi' => $moiPrice,
                                                        default => $bPrice
                                                    };
                                                    $patientShare = $isPatientInsurance ? round($approvedPrice * ($labCopayVal / 100)) : $approvedPrice;
                                                    $insShare = max(0, $approvedPrice - $patientShare);
                                                @endphp
                                                <div class="list-group-item p-3 border rounded-3 d-flex flex-wrap align-items-center justify-content-between gap-2" style="background: #fafafa;">
                                                    <div class="d-flex align-items-start gap-2.5">
                                                        <div class="rounded-circle bg-light border p-2 text-center" style="width: 36px; height: 36px;">
                                                            <i class="fas fa-vial text-danger"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-dark">{{ $lt->labTest?->name ?? $lt->test?->name ?? __('فحص مخبري') }}</div>
                                                            <div class="text-muted small">
                                                                {{ __('النتيجة:') }} <strong>{{ $lt->result ?: __('قيد الإجراء بالمختبر') }}</strong>
                                                                @if(!empty($lt->notes))
                                                                    <span class="text-secondary ms-1">({{ $lt->notes }})</span>
                                                                @endif
                                                            </div>
                                                            <div class="text-muted" style="font-size: 0.72rem;">
                                                                @if($isPatientInsurance)
                                                                    <span>{{ __("سعر الضمان:") }} <strong>{{ number_format($approvedPrice, 0) }}</strong> {{ __("د.ع") }}</span>
                                                                    <span class="text-success ms-1">• {{ __("استقطاع المريض") }} ({{ $labCopayVal }}%): <strong>{{ number_format($patientShare, 0) }}</strong> {{ __("د.ع") }}</span>
                                                                    <span class="text-primary ms-1">• {{ __("مطالبة الضمان:") }} {{ number_format($insShare, 0) }} {{ __("د.ع") }}</span>
                                                                @else
                                                                    <span>{{ __("سعر الكاش:") }} <strong>{{ number_format($approvedPrice, 0) }}</strong> {{ __("د.ع") }}</span>
                                                                @endif
                                                                @if($lt->created_at)
                                                                    • {{ __("الطلب:") }} {{ $lt->created_at->format('Y-m-d H:i') }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        {{-- Technical status --}}
                                                        @if($lt->status === 'completed')
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill small">
                                                                <i class="fas fa-check-circle me-1"></i> {{ __("جاهز") }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1 rounded-pill small">
                                                                <i class="fas fa-hourglass-half me-1"></i> {{ __("قيد الفحص") }}
                                                            </span>
                                                        @endif

                                                        {{-- Payment status badge --}}
                                                        @if($isPaid)
                                                            <span class="badge bg-success text-white px-2.5 py-1 rounded-pill small">
                                                                <i class="fas fa-check-double me-1"></i> {{ __("مدفوع") }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill small" title="{{ __("غير مسدد بالكاشير") }}">
                                                                <i class="fas fa-clock me-1"></i> {{ __("غير مدفوع") }}
                                                            </span>
                                                        @endif

                                                        {{-- Delete button if unpaid and user has write access --}}
                                                        @if(!empty($canEditTests) && !$isPaid)
                                                            <form action="{{ route('clinical-station.lab-tests.delete', ['surgery' => $surgery->id, 'labTest' => $lt->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('هل أنت متأكد من حذف هذا التحليل غير المدفوع؟') }}');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width: 28px; height: 28px;" title="{{ __("حذف التحليل") }}">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center text-muted py-4 small">
                                            <i class="fas fa-flask fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                                            {{ __("لا توجد طلبات تحاليل مخبرية مسجلة لهذه العملية.") }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Radiology Tests --}}
                        <div class="col-lg-6">
                            <div class="card rounded-4 border shadow-sm h-100 bg-white overflow-hidden">
                                <div class="card-header bg-white py-3 px-3.5 d-flex align-items-center justify-content-between border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="rounded-3 bg-primary bg-opacity-10 text-primary p-2 d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                            <i class="fas fa-x-ray"></i>
                                        </span>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark">{{ __("الفحوصات الإشعاعية (Radiology)") }}</h6>
                                            <span class="text-muted small" style="font-size: 0.72rem;">{{ __("إجمالي الفحوصات: ") }}{{ $surgery->radiologyTests?->count() ?? 0 }}</span>
                                        </div>
                                    </div>
                                    @if(!empty($canEditTests))
                                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#orderRadiologyModal">
                                            <i class="fas fa-plus me-1"></i> {{ __("طلب فحص أشعة") }}
                                        </button>
                                    @endif
                                </div>
                                <div class="p-3">
                                    @if($surgery->radiologyTests && $surgery->radiologyTests->count() > 0)
                                        <div class="list-group list-group-flush gap-2">
                                            @foreach($surgery->radiologyTests as $rt)
                                                @php
                                                    $isPaid = ($rt->payment_status === 'paid');
                                                    $radModel = $rt->radiologyType;
                                                    $rPrice = (float)($radModel?->base_price ?? 0);
                                                    $rHi = (float)(($radModel && $radModel->hi_price > 0) ? $radModel->hi_price : $rPrice);
                                                    $rMoi = (float)(($radModel && $radModel->moi_price > 0) ? $radModel->moi_price : $rPrice);
                                                    $approvedRadPrice = match($insType) {
                                                        'hi' => $rHi,
                                                        'moi' => $rMoi,
                                                        default => $rPrice
                                                    };
                                                    $patientRadShare = $isPatientInsurance ? round($approvedRadPrice * ($radCopayVal / 100)) : $approvedRadPrice;
                                                    $insRadShare = max(0, $approvedRadPrice - $patientRadShare);
                                                @endphp
                                                <div class="list-group-item p-3 border rounded-3 d-flex flex-wrap align-items-center justify-content-between gap-2" style="background: #fafafa;">
                                                    <div class="d-flex align-items-start gap-2.5">
                                                        <div class="rounded-circle bg-light border p-2 text-center" style="width: 36px; height: 36px;">
                                                            <i class="fas fa-x-ray text-primary"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-dark">{{ $rt->radiologyType?->name ?? $rt->radiologyService?->name ?? __('فحص إشعاعي') }}</div>
                                                            <div class="text-muted small">
                                                                {{ __('النتيجة:') }} <strong>{{ $rt->result ?: ($rt->findings ?? __('قيد التصوير والتقرير')) }}</strong>
                                                                @if(!empty($rt->notes))
                                                                    <span class="text-secondary ms-1">({{ $rt->notes }})</span>
                                                                @endif
                                                            </div>
                                                            <div class="text-muted" style="font-size: 0.72rem;">
                                                                @if($isPatientInsurance)
                                                                    <span>{{ __("سعر الضمان:") }} <strong>{{ number_format($approvedRadPrice, 0) }}</strong> {{ __("د.ع") }}</span>
                                                                    <span class="text-success ms-1">• {{ __("استقطاع المريض") }} ({{ $radCopayVal }}%): <strong>{{ number_format($patientRadShare, 0) }}</strong> {{ __("د.ع") }}</span>
                                                                    <span class="text-primary ms-1">• {{ __("مطالبة الضمان:") }} {{ number_format($insRadShare, 0) }} {{ __("د.ع") }}</span>
                                                                @else
                                                                    <span>{{ __("سعر الكاش:") }} <strong>{{ number_format($approvedRadPrice, 0) }}</strong> {{ __("د.ع") }}</span>
                                                                @endif
                                                                @if($rt->created_at)
                                                                    • {{ __("الطلب:") }} {{ $rt->created_at->format('Y-m-d H:i') }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        {{-- Technical status --}}
                                                        @if($rt->status === 'completed')
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill small">
                                                                <i class="fas fa-check-circle me-1"></i> {{ __("مكتمل") }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-info-subtle text-info border border-info-subtle px-2.5 py-1 rounded-pill small">
                                                                <i class="fas fa-camera me-1"></i> {{ __("قيد الإجراء") }}
                                                            </span>
                                                        @endif

                                                        {{-- Payment status badge --}}
                                                        @if($isPaid)
                                                            <span class="badge bg-success text-white px-2.5 py-1 rounded-pill small">
                                                                <i class="fas fa-check-double me-1"></i> {{ __("مدفوع") }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill small" title="{{ __("غير مسدد بالكاشير") }}">
                                                                <i class="fas fa-clock me-1"></i> {{ __("غير مدفوع") }}
                                                            </span>
                                                        @endif

                                                        {{-- Delete button if unpaid and user has write access --}}
                                                        @if(!empty($canEditTests) && !$isPaid)
                                                            <form action="{{ route('clinical-station.radiology-tests.delete', ['surgery' => $surgery->id, 'radiologyTest' => $rt->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('هل أنت متأكد من حذف فحص الأشعة غير المدفوع؟') }}');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width: 28px; height: 28px;" title="{{ __("حذف فحص الأشعة") }}">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center text-muted py-4 small">
                                            <i class="fas fa-x-ray fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                                            {{ __("لا توجد صور أو فحوصات إشعاعية مسجلة لهذه العملية.") }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- =============================================================== --}}
            {{-- SPECIAL SUB-VIEW FOR INPATIENT SURVEY TAB --}}
            {{-- =============================================================== --}}
            @if($activeTab === 'inpatient_survey')
                @php
                    $survey = $surgery->inpatientSurvey ?? \App\Models\InpatientRoomSurvey::firstOrCreate(
                        ['surgery_id' => $surgery->id],
                        [
                            'patient_id' => $surgery->patient_id,
                            'doctor_id' => $surgery->doctor_id,
                            'room_id' => $surgery->room_id,
                            'location_id' => $surgery->location_id ?? $surgery->room?->location_id,
                            'room_type' => $surgery->room?->type ?? 'regular',
                            'status' => 'pending',
                        ]
                    );
                    $surveyUrl = route('inpatient-surveys.public.show', ['uuid' => $survey->uuid]);
                    $canResetSurvey = auth()->check() && (
                        auth()->user()->hasAnyRole(['admin', 'super_admin', 'super-admin', 'hospital_admin', 'مدير النظام']) ||
                        auth()->user()->role === 'admin' ||
                        auth()->user()->can('manage settings') ||
                        auth()->user()->can('manage surgery waiting list')
                    );
                @endphp

                <div class="p-4">
                    {{-- STATE A: PENDING (NOT YET COMPLETED) -> GRAND CENTERED QR CODE ACROSS THE PAGE --}}
                    @if($survey->status !== 'completed')
                        {{-- Top Action & Info Bar --}}
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 p-3 bg-white rounded-4 border shadow-xs">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                    <i class="fas fa-qrcode fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">{{ __("استبيان تقييم غرفة الرقود وجودة الرعاية") }}</h6>
                                    <small class="text-muted">{{ __("امسح الرمز أو اطبعه لتمكين المريض أو المرافق من تعبئة التقييم السريري مباشرة من الهاتف") }}</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3 py-2 fw-semibold">
                                    <i class="fas fa-hourglass-half me-1"></i> {{ __("بانتظار مسح وإرسال التقييم") }}
                                </span>
                                <a href="{{ route('inpatient-surveys.print-qr', $surgery) }}" target="_blank" class="btn btn-outline-dark rounded-pill px-3 py-1.5 fw-semibold small shadow-xs">
                                    <i class="fas fa-receipt me-1"></i> {{ __("طباعة كرت حراري (80mm)") }}
                                </a>
                                <a href="{{ route('inpatient-surveys.print-a4', $surgery) }}" target="_blank" class="btn btn-outline-primary rounded-pill px-3 py-1.5 fw-semibold small shadow-xs">
                                    <i class="fas fa-file-pdf me-1"></i> {{ __("طباعة تقرير A4") }}
                                </a>
                                <a href="{{ route('inpatient-surveys.print-blank') }}" target="_blank" class="btn btn-light border rounded-pill px-3 py-1.5 fw-semibold small">
                                    <i class="fas fa-print me-1"></i> {{ __("استبيان فارغ A4") }}
                                </a>
                                <button type="button" class="btn btn-light border rounded-pill px-3 py-1.5 fw-semibold small" onclick="window.location.reload();" title="{{ __("تحديث للتحقق من وصول التقييم") }}">
                                    <i class="fas fa-sync-alt me-1"></i> {{ __("تحديث") }}
                                </button>
                            </div>
                        </div>

                        {{-- Grand Centered QR Presentation Card --}}
                        <div class="card border-0 shadow-sm rounded-4 bg-white text-center p-4 p-md-5 my-2">
                            <div class="mx-auto" style="max-width: 680px;">
                                <div class="mb-3">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 fw-bold fs-6">
                                        <i class="fas fa-door-open me-1"></i> {{ __('غرفة') }} {{ $surgery->room?->room_number ?? '-' }} — {{ $surgery->room?->type === 'vip' ? __('جناح خاص VIP') : __('غرفة مشتركة') }}
                                    </span>
                                </div>

                                <h3 class="fw-bold text-dark mb-2">{{ __("امسح رمز التقييم عبر كاميرا الهاتف") }}</h3>
                                <p class="text-muted mb-4 fs-6">{{ __("وجّه كاميرا الهاتف المحمول أو تطبيق الباركود نحو الرمز التالي ليقوم المريض ") }}<strong class="text-dark">({{ $surgery->patient?->name }})</strong>{{ __(" أو مرافقه بالإجابة بخصوصية تامة.") }}</p>

                                {{-- The Big QR Code Display Box --}}
                                <div class="p-3 p-sm-4 bg-light rounded-4 border d-inline-block shadow-sm my-2 position-relative">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=340x340&margin=0&data={{ urlencode($surveyUrl) }}"
                                         alt="Inpatient Room Survey QR Code"
                                         class="rounded-3 bg-white p-2 shadow-xs"
                                         style="width: 270px; height: 270px; max-width: 100%; display: block; aspect-ratio: 1/1;">
                                </div>

                                <div class="mt-3 mb-4">
                                    <span class="badge bg-light text-muted border rounded-pill px-3 py-2 small fw-normal">
                                        <i class="fas fa-shield-alt text-success me-1"></i> {{ __("رابط آمن ومباشر لا يتطلب تسجيل الدخول") }}
                                    </span>
                                </div>

                                {{-- Fast Actions / Link Tools --}}
                                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mb-4">
                                    <a href="{{ $surveyUrl }}" target="_blank" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                                        <i class="fas fa-external-link-alt me-1"></i> {{ __("فتح صفحة الاستبيان") }}
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold" id="btnCopySurveyLink" onclick="navigator.clipboard.writeText('{{ $surveyUrl }}'); this.innerHTML='<i class=\'fas fa-check text-success me-1\'></i> {{ __('تم النسخ بنجاح!') }}'; setTimeout(() => { this.innerHTML='<i class=\'fas fa-copy me-1\'></i> {{ __('نسخ الرابط') }}'; }, 2500);">
                                        <i class="fas fa-copy me-1"></i> {{ __("نسخ الرابط") }}
                                    </button>
                                    <a href="{{ route('inpatient-surveys.print-qr', $surgery) }}" target="_blank" class="btn btn-dark rounded-pill px-4 py-2 fw-semibold">
                                        <i class="fas fa-receipt me-1"></i> {{ __("كرت مريض حراري") }}
                                    </a>
                                    <button type="button" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#qrFullScreenModal">
                                        <i class="fas fa-expand me-1"></i> {{ __("تكبير بملء الشاشة") }}
                                    </button>
                                </div>

                                {{-- Auto-Transition Note --}}
                                <div class="p-3 rounded-3 bg-info bg-opacity-10 border border-info border-opacity-25 text-start d-flex align-items-start gap-2">
                                    <i class="fas fa-info-circle text-info mt-1"></i>
                                    <div class="small text-dark">
                                        <strong>{{ __("ملاحظة للمحطة السريرية:") }}</strong> {{ __("بمجرد قيام المريض أو مرافقه بإرسال التقييم عبر هاتفه، سيختفي رمز الباركود من هذه الصفحة وستظهر مباشرة نتائج جميع مفردات الاستبيان (النظافة، التكييف، الأثاث، راحة السرير، وزيارة الطبيب).") }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Full-screen QR Modal (Only available in pending state) --}}
                        <div class="modal fade" id="qrFullScreenModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4 border-0 shadow-lg text-center p-4">
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold text-dark w-100">
                                            {{ __("مسح استبيان غرفة الرقود (غرفة ") }}{{ $surgery->room?->room_number ?? '-' }})
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body py-4">
                                        <div class="p-3 bg-white border rounded-4 d-inline-block shadow-sm mb-3">
                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=340x340&margin=0&data={{ urlencode($surveyUrl) }}"
                                                 alt="QR Code" style="width: 280px; height: 280px; display: block;">
                                        </div>
                                        <p class="text-muted fw-semibold mb-0">
                                            {{ __("وجّه كاميرا الهاتف نحو الرمز لفتح الاستبيان مباشرة بكل أريحية.") }}
                                        </p>
                                    </div>
                                    <div class="modal-footer border-0 pt-0 justify-content-center">
                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">{{ __("إغلاق") }}</button>
                                        <a href="{{ route('inpatient-surveys.print-qr', $surgery) }}" target="_blank" class="btn btn-primary rounded-pill px-4 fw-bold">
                                            <i class="fas fa-print me-1"></i> {{ __("طباعة كرت") }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    {{-- STATE B: COMPLETED -> QR CODE DISAPPEARS AND FULL-WIDTH SCORECARD REPLACES IT --}}
                    @else
                        {{-- Executive Result Hero Banner --}}
                        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                            <div class="card-body p-4 text-white">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                    {{-- Left: Score & Verdict --}}
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-4 d-flex flex-column align-items-center justify-content-center text-center shadow-sm px-3 py-2"
                                             style="min-width: 95px; height: 95px; background: {{ $survey->overall_score >= 85 ? 'linear-gradient(135deg, #10b981, #059669)' : ($survey->overall_score >= 70 ? 'linear-gradient(135deg, #3b82f6, #1d4ed8)' : ($survey->overall_score >= 50 ? 'linear-gradient(135deg, #f59e0b, #d97706)' : 'linear-gradient(135deg, #ef4444, #b91c1c)')) }};">
                                            <span class="fs-2 fw-black text-white lh-1">{{ round($survey->overall_score) }}%</span>
                                            <span class="small text-white-50 mt-1" style="font-size: 0.72rem;">{{ __("التقييم الإجمالي") }}</span>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h5 class="mb-0 fw-bold text-white">{{ __("نتائج استبيان تقييم غرفة الرقود") }}</h5>
                                                <span class="badge bg-success text-white rounded-pill px-2.5 py-1 small">
                                                    <i class="fas fa-check-circle me-1"></i> {{ __("تقييم مكتمل") }}
                                                </span>
                                            </div>
                                            <p class="mb-1 text-light opacity-75 small">
                                                {{ __("تم إرسال هذا التقييم بواسطة: ") }}
                                                <strong class="text-white">{{ $survey->submitted_via === 'patient_qr' ? __('📱 هاتف المريض عبر رمز الباركود (QR)') : __('👨‍⚕️ إدخال الكادر السريري') }}</strong>
                                                @if($survey->completed_at)
                                                    {{ __("بتاريخ ") }}{{ \Carbon\Carbon::parse($survey->completed_at)->format('Y-m-d h:i A') }}
                                                @endif
                                            </p>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                @if($survey->recommend_score_nps)
                                                    <span class="badge bg-white bg-opacity-15 text-white border border-white border-opacity-25 rounded-pill px-3 py-1 small">
                                                        <i class="fas fa-thumbs-up text-warning me-1"></i> {{ __("مؤشر التوصية (NPS): ") }}<strong class="text-warning">{{ $survey->recommend_score_nps }}</strong>{{ __(" من 10") }}
                                                    </span>
                                                @endif
                                                @if($survey->room_score)
                                                    <span class="badge bg-white bg-opacity-15 text-white border border-white border-opacity-25 rounded-pill px-2.5 py-1 small">
                                                        {{ __("🛏️ الغرفة: ") }}{{ round($survey->room_score) }}%
                                                    </span>
                                                @endif
                                                @if($survey->nursing_score)
                                                    <span class="badge bg-white bg-opacity-15 text-white border border-white border-opacity-25 rounded-pill px-2.5 py-1 small">
                                                        {{ __("🩺 التمريض: ") }}{{ round($survey->nursing_score) }}%
                                                    </span>
                                                @endif
                                                @if($survey->lab_score)
                                                    <span class="badge bg-white bg-opacity-15 text-white border border-white border-opacity-25 rounded-pill px-2.5 py-1 small">
                                                        {{ __("🧪 المختبر: ") }}{{ round($survey->lab_score) }}%
                                                    </span>
                                                @endif
                                                @if($survey->pharmacy_score)
                                                    <span class="badge bg-white bg-opacity-15 text-white border border-white border-opacity-25 rounded-pill px-2.5 py-1 small">
                                                        {{ __("💊 الصيدلية: ") }}{{ round($survey->pharmacy_score) }}%
                                                    </span>
                                                @endif
                                                <span class="badge bg-white bg-opacity-15 text-white border border-white border-opacity-25 rounded-pill px-3 py-1 small">
                                                    <i class="fas fa-door-closed me-1"></i> {{ __('غرفة') }} {{ $surgery->room?->room_number ?? '-' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Right: Actions & Admin Reset --}}
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        @if($canResetSurvey)
                                            <form method="POST" action="{{ route('inpatient-surveys.reset', $surgery) }}" class="d-inline" onsubmit="return confirm('{{ __('تنبيه هام: هل أنت متأكد من تصفير وإعادة فتح الاستبيان؟

سيتم حذف الإجابات الحالية وإعادة إظهار باركود التقييم للمريض مجدداً.') }}');">
                                                @csrf
                                                <button type="submit" class="btn btn-warning text-dark rounded-pill px-3 py-2 fw-bold shadow-sm" title="{{ __("إعادة فتح وتصفير الاستبيان وإعادة إظهار الباركود") }}">
                                                    <i class="fas fa-undo-alt me-1"></i> {{ __("إعادة فتح الاستبيان (أدمن)") }}
                                                </button>
                                            </form>
                                        @endif

                                        <a href="{{ route('inpatient-surveys.print-a4', $surgery) }}" target="_blank" class="btn btn-primary rounded-pill px-3 py-2 fw-bold shadow-sm">
                                            <i class="fas fa-file-pdf me-1"></i> {{ __("طباعة تقرير A4") }}
                                        </a>
                                        <a href="{{ route('inpatient-surveys.print-qr', $surgery) }}" target="_blank" class="btn btn-light rounded-pill px-3 py-2 fw-bold shadow-sm">
                                            <i class="fas fa-receipt me-1"></i> {{ __("إيصال حراري") }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Compact Results Dashboard: 2-Column Balanced View (27 Clinical & Hospitality Metrics) --}}
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-3 px-1 flex-wrap gap-2">
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">
                                        <i class="fas fa-poll-h text-primary me-2"></i> {{ __("تفاصيل تقييم جميع خدمات ومفردات الاستبيان (27 معياراً سريرياً وفندقياً)") }}
                                    </h6>
                                    <small class="text-muted">{{ __("درجات التقييم من 1 (ضعيف) إلى 5 (ممتاز) مدخلة ومقسمة حسب الأقسام السريرية والخدمية") }}</small>
                                </div>
                                <span class="badge bg-white text-dark border shadow-xs rounded-pill px-3 py-1.5 fw-semibold small">
                                    {{ __("إجمالي البنود المقيمة: 27 / 27") }}
                                </span>
                            </div>

                            @php
                                $getRatingInfo = function($val) {
                                    return match((int)$val) {
                                        5 => ['text' => __('ممتاز (5/5)'), 'badge' => 'bg-success text-white', 'style' => ''],
                                        4 => ['text' => __('جيد جداً (4/5)'), 'badge' => 'bg-info text-white', 'style' => ''],
                                        3 => ['text' => __('جيد (3/5)'), 'badge' => 'bg-warning text-dark', 'style' => ''],
                                        2 => ['text' => __('مقبول (2/5)'), 'badge' => 'text-white', 'style' => 'background:#f97316;'],
                                        1 => ['text' => __('ضعيف (1/5)'), 'badge' => 'bg-danger text-white', 'style' => ''],
                                        default => ['text' => __('غير مقيم'), 'badge' => 'bg-secondary-subtle text-secondary border', 'style' => ''],
                                    };
                                };

                                $roomMetrics = [
                                    'cleanliness_room' => ['label' => __('النظافة العامة لغرفة الرقود'), 'icon' => 'fas fa-broom', 'color' => '#10b981'],
                                    'cleanliness_bathroom' => ['label' => __('نظافة الحمام والمرافق الصحية'), 'icon' => 'fas fa-sink', 'color' => '#06b6d4'],
                                    'temperature_ac' => ['label' => __('التكييف والحرارة والتهوية'), 'icon' => 'fas fa-wind', 'color' => '#3b82f6'],
                                    'lighting' => ['label' => __('إضاءة الغرفة ومستوى الراحة البصرية'), 'icon' => 'fas fa-lightbulb', 'color' => '#f59e0b'],
                                    'quietness_privacy' => ['label' => __('الهدوء والسكينة والخصوصية بالقسم'), 'icon' => 'fas fa-volume-mute', 'color' => '#8b5cf6'],
                                    'bed_comfort' => ['label' => __('راحة السرير والمفارش ونظافتها'), 'icon' => 'fas fa-bed', 'color' => '#ec4899'],
                                    'control_panel' => ['label' => __('لوحة استدعاء التمريض وسرعة الاستجابة'), 'icon' => 'fas fa-bell', 'color' => '#ef4444'],
                                    'storage_space' => ['label' => __('مساحات التخزين والدواليب'), 'icon' => 'fas fa-door-closed', 'color' => '#64748b'],
                                    'furniture_safety' => ['label' => __('جودة وأمان الأثاث ومرافق الغرفة'), 'icon' => 'fas fa-chair', 'color' => '#14b8a6'],
                                    'entertainment' => ['label' => __('الترفيه (شاشة التلفاز والواي فاي)'), 'icon' => 'fas fa-tv', 'color' => '#6366f1'],
                                ];

                                $doctorMetrics = [
                                    'doctor_communication' => ['label' => __('تواصل وزيارة الطبيب المعالج واهتمامه'), 'icon' => 'fas fa-user-md', 'color' => '#059669'],
                                ];

                                $nursingMetrics = [
                                    'nursing_greeting' => ['label' => __('الترحيب والمهنية في التعامل'), 'icon' => 'fas fa-smile', 'color' => '#8b5cf6'],
                                    'nursing_skill' => ['label' => __('المهارة في تقديم الرعاية (حقن وتضميد)'), 'icon' => 'fas fa-syringe', 'color' => '#7c3aed'],
                                    'nursing_care_attention' => ['label' => __('الاهتمام والرعاية المستمرة'), 'icon' => 'fas fa-heartbeat', 'color' => '#ec4899'],
                                    'nursing_instructions' => ['label' => __('وضوح التعليمات والتواصل معك'), 'icon' => 'fas fa-comment-medical', 'color' => '#6366f1'],
                                    'nursing_response_speed' => ['label' => __('سرعة الاستجابة عند طلب المساعدة'), 'icon' => 'fas fa-stopwatch', 'color' => '#f59e0b'],
                                    'nursing_privacy_respect' => ['label' => __('المحافظة على الخصوصية والكرامة'), 'icon' => 'fas fa-user-shield', 'color' => '#10b981'],
                                ];

                                $labMetrics = [
                                    'lab_prep_instructions' => ['label' => __('وضوح تعليمات التحضير للتحليل'), 'icon' => 'fas fa-clipboard-list', 'color' => '#06b6d4'],
                                    'lab_sampling_skill' => ['label' => __('الاحتراف والمهارة في سحب العينة'), 'icon' => 'fas fa-hand-holding-medical', 'color' => '#0284c7'],
                                    'lab_hygiene_sterilization' => ['label' => __('النظافة والتعقيم في سحب العينات'), 'icon' => 'fas fa-pump-soap', 'color' => '#14b8a6'],
                                    'lab_results_speed' => ['label' => __('سرعة استخراج النتائج وتسليمها'), 'icon' => 'fas fa-clock', 'color' => '#3b82f6'],
                                    'lab_staff_behavior' => ['label' => __('التعامل اللطيف والمطمئن أثناء العملية'), 'icon' => 'fas fa-user-check', 'color' => '#10b981'],
                                ];

                                $pharmacyMetrics = [
                                    'pharmacy_dispense_speed' => ['label' => __('سرعة وصحة صرف الأدوية المطلوبة'), 'icon' => 'fas fa-shipping-fast', 'color' => '#f97316'],
                                    'pharmacy_instruction_clarity' => ['label' => __('وضوح شرح طريقة استخدام الدواء'), 'icon' => 'fas fa-info-circle', 'color' => '#eab308'],
                                    'pharmacy_comprehension_check' => ['label' => __('التأكد من فهمك الكامل لتعليمات الدواء'), 'icon' => 'fas fa-tasks', 'color' => '#84cc16'],
                                    'pharmacy_respect_politeness' => ['label' => __('اللباقة والاحترام في التعامل من الصيدلي'), 'icon' => 'fas fa-smile-beam', 'color' => '#10b981'],
                                    'pharmacy_med_availability' => ['label' => __('التوفر الكامل للأدوية الموصوفة'), 'icon' => 'fas fa-boxes', 'color' => '#3b82f6'],
                                ];
                            @endphp

                            <div class="row g-3">
                                {{-- Column 1: Room & Doctor (11 Metrics) --}}
                                <div class="col-lg-6">
                                    {{-- Room Facilities Card --}}
                                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-3 overflow-hidden">
                                        <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                    <i class="fas fa-bed" style="font-size: 0.8rem;"></i>
                                                </div>
                                                <strong class="text-dark small">{{ __("١. مرافق غرفة الرقود (الورقة الأولى)") }}</strong>
                                            </div>
                                            @if($survey->room_score)
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 small fw-bold">
                                                    {{ __("نسبة الرضا: ") }}{{ round($survey->room_score) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="card-body p-0">
                                            @foreach($roomMetrics as $field => $info)
                                                @php
                                                    $val = $survey->{$field};
                                                    $rInfo = $getRatingInfo($val);
                                                @endphp
                                                <div class="d-flex align-items-center justify-content-between py-2 px-3 border-bottom" style="font-size: 0.82rem;">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                                                        <i class="{{ $info['icon'] }}" style="color: {{ $info['color'] }}; width: 16px; text-align: center; font-size: 0.82rem;"></i>
                                                        <span class="text-dark fw-semibold text-truncate">{{ $info['label'] }}</span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                        <div class="d-inline-flex align-items-center" style="gap: 2px;">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                <i class="{{ $i <= $val ? 'fas fa-star text-warning' : 'far fa-star text-muted opacity-25' }}" style="font-size: 0.72rem;"></i>
                                                            @endfor
                                                        </div>
                                                        <span class="badge {{ $rInfo['badge'] }} rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem; min-width: 62px; text-align: center; {{ $rInfo['style'] ?? '' }}">
                                                            {{ $rInfo['text'] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Doctor Card --}}
                                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-3 overflow-hidden">
                                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                    <i class="fas fa-user-md" style="font-size: 0.8rem;"></i>
                                                </div>
                                                <strong class="text-dark small">{{ __("٢. الفريق الطبي المعالج") }}</strong>
                                            </div>
                                        </div>
                                        <div class="card-body p-0">
                                            @foreach($doctorMetrics as $field => $info)
                                                @php
                                                    $val = $survey->{$field};
                                                    $rInfo = $getRatingInfo($val);
                                                @endphp
                                                <div class="d-flex align-items-center justify-content-between py-2 px-3" style="font-size: 0.82rem;">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                                                        <i class="{{ $info['icon'] }}" style="color: {{ $info['color'] }}; width: 16px; text-align: center; font-size: 0.82rem;"></i>
                                                        <span class="text-dark fw-semibold text-truncate">{{ $info['label'] }}</span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                        <div class="d-inline-flex align-items-center" style="gap: 2px;">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                <i class="{{ $i <= $val ? 'fas fa-star text-warning' : 'far fa-star text-muted opacity-25' }}" style="font-size: 0.72rem;"></i>
                                                            @endfor
                                                        </div>
                                                        <span class="badge {{ $rInfo['badge'] }} rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem; min-width: 62px; text-align: center; {{ $rInfo['style'] ?? '' }}">
                                                            {{ $rInfo['text'] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Column 2: Nursing, Lab & Pharmacy (16 Metrics) --}}
                                <div class="col-lg-6">
                                    {{-- Nursing Card --}}
                                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-3 overflow-hidden">
                                        <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: rgba(147, 51, 234, 0.1); color: #7c3aed;">
                                                    <i class="fas fa-user-nurse" style="font-size: 0.8rem;"></i>
                                                </div>
                                                <strong class="text-dark small">{{ __("٣. طاقم التمريض (الورقة الثانية)") }}</strong>
                                            </div>
                                            @if($survey->nursing_score)
                                                <span class="badge rounded-pill px-2.5 py-1 small fw-bold" style="background: rgba(147, 51, 234, 0.1); color: #7c3aed; border: 1px solid rgba(147, 51, 234, 0.2);">
                                                    {{ __("نسبة الرضا: ") }}{{ round($survey->nursing_score) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="card-body p-0">
                                            @foreach($nursingMetrics as $field => $info)
                                                @php
                                                    $val = $survey->{$field};
                                                    $rInfo = $getRatingInfo($val);
                                                @endphp
                                                <div class="d-flex align-items-center justify-content-between py-2 px-3 border-bottom" style="font-size: 0.82rem;">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                                                        <i class="{{ $info['icon'] }}" style="color: {{ $info['color'] }}; width: 16px; text-align: center; font-size: 0.82rem;"></i>
                                                        <span class="text-dark fw-semibold text-truncate">{{ $info['label'] }}</span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                        <div class="d-inline-flex align-items-center" style="gap: 2px;">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                <i class="{{ $i <= $val ? 'fas fa-star text-warning' : 'far fa-star text-muted opacity-25' }}" style="font-size: 0.72rem;"></i>
                                                            @endfor
                                                        </div>
                                                        <span class="badge {{ $rInfo['badge'] }} rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem; min-width: 62px; text-align: center; {{ $rInfo['style'] ?? '' }}">
                                                            {{ $rInfo['text'] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Lab Card --}}
                                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-3 overflow-hidden">
                                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                    <i class="fas fa-vial" style="font-size: 0.8rem;"></i>
                                                </div>
                                                <strong class="text-dark small">{{ __("٤. قسم المختبر والتحاليل (الورقة الثانية)") }}</strong>
                                            </div>
                                            @if($survey->lab_score)
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2.5 py-1 small fw-bold">
                                                    {{ __("نسبة الرضا: ") }}{{ round($survey->lab_score) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="card-body p-0">
                                            @foreach($labMetrics as $field => $info)
                                                @php
                                                    $val = $survey->{$field};
                                                    $rInfo = $getRatingInfo($val);
                                                @endphp
                                                <div class="d-flex align-items-center justify-content-between py-2 px-3 border-bottom" style="font-size: 0.82rem;">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                                                        <i class="{{ $info['icon'] }}" style="color: {{ $info['color'] }}; width: 16px; text-align: center; font-size: 0.82rem;"></i>
                                                        <span class="text-dark fw-semibold text-truncate">{{ $info['label'] }}</span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                        <div class="d-inline-flex align-items-center" style="gap: 2px;">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                <i class="{{ $i <= $val ? 'fas fa-star text-warning' : 'far fa-star text-muted opacity-25' }}" style="font-size: 0.72rem;"></i>
                                                            @endfor
                                                        </div>
                                                        <span class="badge {{ $rInfo['badge'] }} rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem; min-width: 62px; text-align: center; {{ $rInfo['style'] ?? '' }}">
                                                            {{ $rInfo['text'] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Pharmacy Card --}}
                                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-3 overflow-hidden">
                                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                    <i class="fas fa-pills" style="font-size: 0.8rem;"></i>
                                                </div>
                                                <strong class="text-dark small">{{ __("٥. قسم الصيدلية والتعامل مع الأدوية (الورقة الثانية)") }}</strong>
                                            </div>
                                            @if($survey->pharmacy_score)
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 small fw-bold">
                                                    {{ __("نسبة الرضا: ") }}{{ round($survey->pharmacy_score) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="card-body p-0">
                                            @foreach($pharmacyMetrics as $field => $info)
                                                @php
                                                    $val = $survey->{$field};
                                                    $rInfo = $getRatingInfo($val);
                                                @endphp
                                                <div class="d-flex align-items-center justify-content-between py-2 px-3 border-bottom {{ $loop->last ? 'border-bottom-0' : '' }}" style="font-size: 0.82rem;">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                                                        <i class="{{ $info['icon'] }}" style="color: {{ $info['color'] }}; width: 16px; text-align: center; font-size: 0.82rem;"></i>
                                                        <span class="text-dark fw-semibold text-truncate">{{ $info['label'] }}</span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                        <div class="d-inline-flex align-items-center" style="gap: 2px;">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                <i class="{{ $i <= $val ? 'fas fa-star text-warning' : 'far fa-star text-muted opacity-25' }}" style="font-size: 0.72rem;"></i>
                                                            @endfor
                                                        </div>
                                                        <span class="badge {{ $rInfo['badge'] }} rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem; min-width: 62px; text-align: center; {{ $rInfo['style'] ?? '' }}">
                                                            {{ $rInfo['text'] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Patient Feedback, Priorities & Departmental Remarks --}}
                        @php
                            $hasPriorities = !empty($survey->top_three_improvements) && is_array($survey->top_three_improvements) && count($survey->top_three_improvements) > 0;
                            $hasComments = !empty($survey->room_notes) || !empty($survey->patient_suggestions) || !empty($survey->doctor_notes) || !empty($survey->nursing_notes) || !empty($survey->lab_notes) || !empty($survey->pharmacy_notes) || !empty($survey->respondent_name);
                        @endphp

                        @if($hasPriorities || $hasComments)
                            <div class="row g-4">
                                {{-- Top 3 Priorities --}}
                                @if($hasPriorities)
                                    <div class="{{ $hasComments ? 'col-lg-5' : 'col-12' }}">
                                        <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                                            <div class="card-header bg-white py-3 px-4 border-bottom">
                                                <h6 class="mb-0 fw-bold text-dark">
                                                    <i class="fas fa-bullseye text-warning me-2"></i> {{ __("أهم أولويات التحسين المحددة من المريض") }}
                                                </h6>
                                            </div>
                                            <div class="card-body p-4">
                                                <p class="small text-muted mb-3">{{ __("حدد المريض المجالات التالية كأولويات تتطلب التطوير مستقبلاً:") }}</p>
                                                <div class="d-flex flex-column gap-2">
                                                    @foreach($survey->top_three_improvements as $idx => $priority)
                                                        <div class="d-flex align-items-center gap-2 p-2.5 rounded-3 bg-light border">
                                                            <span class="badge bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.8rem;">
                                                                {{ $idx + 1 }}
                                                            </span>
                                                            <span class="small fw-semibold text-dark">{{ $priority }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Qualitative Notes & Departmental Remarks --}}
                                @if($hasComments)
                                    <div class="{{ $hasPriorities ? 'col-lg-7' : 'col-12' }}">
                                        <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                                            <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                                                <h6 class="mb-0 fw-bold text-dark">
                                                    <i class="fas fa-comment-dots text-primary me-2"></i> {{ __("ملاحظات ومقترحات المريض التفصيلية") }}
                                                </h6>
                                                @if($survey->respondent_name || $survey->respondent_phone || $survey->respondent_email)
                                                    <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 small">
                                                        <i class="fas fa-user-edit me-1"></i> {{ $survey->respondent_name ?? __('المريض/المرافق') }}
                                                        {{ $survey->respondent_phone ? ' | '.$survey->respondent_phone : '' }}
                                                        {{ $survey->respondent_email ? ' | '.$survey->respondent_email : '' }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="card-body p-4 d-flex flex-column gap-3">
                                                @if(!empty($survey->patient_suggestions))
                                                    <div class="p-3 rounded-3 bg-primary bg-opacity-10 border border-primary border-opacity-25">
                                                        <strong class="d-block small text-primary mb-1"><i class="fas fa-lightbulb me-1"></i> {{ __("مقترحات المريض الإضافية:") }}</strong>
                                                        <p class="small text-dark mb-0">{{ $survey->patient_suggestions }}</p>
                                                    </div>
                                                @endif

                                                @if(!empty($survey->nursing_notes))
                                                    <div class="p-3 rounded-3 bg-purple bg-opacity-10 border border-purple border-opacity-25" style="background: rgba(147, 51, 234, 0.05); border-color: rgba(147, 51, 234, 0.2);">
                                                        <strong class="d-block small text-purple mb-1" style="color: #7c3aed;"><i class="fas fa-user-nurse me-1"></i> {{ __("ملاحظات كادر التمريض:") }}</strong>
                                                        <p class="small text-dark mb-0">{{ $survey->nursing_notes }}</p>
                                                    </div>
                                                @endif

                                                @if(!empty($survey->lab_notes))
                                                    <div class="p-3 rounded-3 bg-info bg-opacity-10 border border-info border-opacity-25">
                                                        <strong class="d-block small text-info mb-1"><i class="fas fa-vial me-1"></i> {{ __("ملاحظات قسم المختبر:") }}</strong>
                                                        <p class="small text-dark mb-0">{{ $survey->lab_notes }}</p>
                                                    </div>
                                                @endif

                                                @if(!empty($survey->pharmacy_notes))
                                                    <div class="p-3 rounded-3 bg-warning bg-opacity-10 border border-warning border-opacity-25">
                                                        <strong class="d-block small text-warning text-dark mb-1"><i class="fas fa-pills me-1"></i> {{ __("ملاحظات الصيدلية وصرف الأدوية:") }}</strong>
                                                        <p class="small text-dark mb-0">{{ $survey->pharmacy_notes }}</p>
                                                    </div>
                                                @endif

                                                @if(!empty($survey->room_notes))
                                                    <div class="p-3 rounded-3 bg-light border">
                                                        <strong class="d-block small text-dark mb-1"><i class="fas fa-bed me-1"></i> {{ __("ملاحظات مرافق الغرفة:") }}</strong>
                                                        <p class="small text-muted mb-0">{{ $survey->room_notes }}</p>
                                                    </div>
                                                @endif

                                                @if(!empty($survey->doctor_notes))
                                                    <div class="p-3 rounded-3 bg-light border">
                                                        <strong class="d-block small text-dark mb-1"><i class="fas fa-user-md me-1"></i> {{ __("ملاحظات زيارة الطبيب:") }}</strong>
                                                        <p class="small text-muted mb-0">{{ $survey->doctor_notes }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            {{-- =============================================================== --}}
            {{-- SPECIAL SUB-VIEW FOR TREATMENTS TAB (الأدوية وجدول الإعطاء MAR) --}}
            {{-- =============================================================== --}}
            @if($activeTab === 'treatments')
                <div class="p-4">
                    {{-- Live MAR Feedback Banner --}}
                    <div id="marToastAlert" class="alert alert-dismissible rounded-3 border-0 shadow-sm d-none align-items-center mb-3 py-2 px-3 small" role="alert">
                        <i id="marToastIcon" class="fas fa-check-circle me-2"></i>
                        <div id="marToastMessage" class="fw-semibold"></div>
                        <button type="button" class="btn-close btn-sm ms-auto" onclick="document.getElementById('marToastAlert').classList.add('d-none')"></button>
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger rounded-3 border-0 shadow-xs py-2 px-3 small mb-3">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            {{ $errors->first() }}
                        </div>
                    @endif

                    {{-- Form to prescribe new medication from Pharmacy Catalog or free text (Resident / Doctor / Surgeon / Admin only) --}}
                    @if($canPrescribeTreatments)
                        <div class="card rounded-3 border mb-4 bg-light bg-opacity-50 shadow-xs">
                            <div class="card-header bg-white py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <h6 class="mb-0 fw-bold text-dark">
                                        <i class="fas fa-prescription text-primary me-2"></i> {{ __("الأوامر الدوائية وباقات البروتوكولات السريرية") }}
                                    </h6>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if(isset($medicationTreatments) && $medicationTreatments->isNotEmpty())
                                        <button type="button" class="btn btn-warning btn-sm text-dark fw-bold rounded-pill px-3 shadow-xs" data-bs-toggle="modal" data-bs-target="#saveCurrentMedsAsPackageModal" title="{{ __('حفظ الأدوية الحالية كباقة مشتركة لجميع المقيمين') }}">
                                            <i class="fas fa-save me-1"></i> {{ __("حفظ كباقة مشتركة") }}
                                        </button>
                                    @endif
                                    <a href="{{ route('medicine-groups.index') }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3" title="{{ __('إدارة باقات الأدوية') }}">
                                        <i class="fas fa-cog me-1"></i> {{ __("إدارة الباقات") }}
                                    </a>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                                        <i class="fas fa-user-md me-1"></i> {{ __("صلاحية الطبيب / المقيم") }}
                                    </span>
                                </div>
                            </div>

                            {{-- شريط باقات الأدوية المشتركة للمقيمين والأطباء (ظاهرة للجميع مع الترتيب والتثبيت) --}}
                            @if(isset($medicineGroups) && $medicineGroups->isNotEmpty())
                                <div class="p-2.5 bg-white border-bottom">
                                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-1">
                                        <span class="small fw-bold text-dark d-flex align-items-center gap-1.5">
                                            <i class="fas fa-bolt text-warning"></i>
                                            <span>{{ __("باقات الأدوية المشتركة للمقيمين:") }}</span>
                                            <span class="text-muted fw-normal" style="font-size: 0.75rem;">{{ __("(نقرة واحدة لمعاينة وتطبيق بروتوكول الأدوية فوراً في جدول الإعطاء | ⭐ لتثبيت بالصدارة)") }}</span>
                                        </span>
                                        <span class="badge bg-light text-secondary border rounded-pill small" style="font-size: 0.7rem;">
                                            {{ $medicineGroups->count() }} {{ __("باقة متاحة") }}
                                        </span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 align-items-center" id="clinicalQuickMedPkgsContainer">
                                        @foreach($medicineGroups as $pkg)
                                            @php
                                                $medsList = $pkg->medicines->map(function($m) {
                                                    return [
                                                        'id' => $m->id,
                                                        'name' => $m->name . ($m->strength ? " {$m->strength}" : ''),
                                                        'form' => $m->pivot->dosage_form ?: $m->dosage_form,
                                                        'dosage' => $m->pivot->dosage ?: $m->strength,
                                                        'frequency' => $m->pivot->frequency ?: 'q8h',
                                                        'duration' => $m->pivot->duration ?: 3,
                                                        'instructions' => $m->pivot->instructions,
                                                    ];
                                                })->values();

                                                $customList = $pkg->customItems->map(function($c) {
                                                    return [
                                                        'id' => 'custom_' . $c->id,
                                                        'name' => $c->medicine_name ?: ($c->name ?: ''),
                                                        'form' => $c->dosage_form,
                                                        'dosage' => $c->dosage,
                                                        'frequency' => $c->frequency ?: 'q8h',
                                                        'duration' => $c->duration ?: 3,
                                                        'instructions' => $c->instructions,
                                                    ];
                                                })->values();

                                                $allMedsCombined = $medsList->concat($customList);
                                                $totalCount = $allMedsCombined->count();
                                            @endphp
                                            <div class="btn-group btn-group-sm shadow-xs rounded-pill border clinical-pkg-btn-group {{ $pkg->is_starred ? 'border-warning bg-warning bg-opacity-10' : 'border-primary-subtle bg-white' }}"
                                                 role="group">
                                                {{-- زر النجمة الذهبية للتثبيت بالصدارة --}}
                                                <button type="button"
                                                        class="btn btn-sm btn-link text-decoration-none px-2 py-1 {{ $pkg->is_starred ? 'text-warning' : 'text-muted opacity-50' }}"
                                                        onclick="toggleClinicalPkgStar(this, {{ $pkg->id }})"
                                                        title="{{ $pkg->is_starred ? __('إلغاء التثبيت من الصدارة') : __('تثبيت في الصدارة ⭐') }}">
                                                    <i class="{{ $pkg->is_starred ? 'fas fa-star text-warning' : 'far fa-star' }}"></i>
                                                </button>

                                                {{-- زر فتح معاينة وتطبيق الباقة --}}
                                                <button type="button"
                                                        class="btn btn-sm px-2.5 py-1 text-dark fw-bold d-flex align-items-center gap-1.5"
                                                        onclick='openApplyClinicalMedPkgModal({{ $pkg->id }}, @json($pkg->name), @json($allMedsCombined))'
                                                        title="{{ __('معاينة وتطبيق باقة ') }}{{ $pkg->name }}">
                                                    @if($pkg->is_public)
                                                        <i class="fas fa-hospital text-primary small" title="{{ __('باقة عامة مشتركة لجميع المقيمين') }}"></i>
                                                    @else
                                                        <i class="fas fa-layer-group text-success small"></i>
                                                    @endif
                                                    <span>{{ $pkg->name }}</span>
                                                    <span class="badge {{ $pkg->is_starred ? 'bg-warning text-dark' : 'bg-primary text-white' }} rounded-pill" style="font-size: 0.65rem;">
                                                        {{ $totalCount }}
                                                    </span>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="card-body p-3">
                                <form action="{{ route('clinical-station.treatments.store', $surgery) }}" method="POST" id="prescribeMedicationForm">
                                    @csrf
                                    <input type="hidden" name="phase" value="{{ $currentPhase }}">
                                    <input type="hidden" name="medicine_id" id="medPrescriptionMedicineId" value="{{ old('medicine_id') }}">

                                    <div class="row g-2 align-items-end mb-2">
                                        <div class="col-lg-5 col-md-12">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <label class="form-label small fw-bold text-dark mb-0">{{ __("اسم الدواء (من دليل الصيدلية أو كتابة مباشرة):") }} <span class="text-danger">*</span></label>
                                                <span id="medCatalogLinkedBadge" class="badge bg-success bg-opacity-10 text-success border border-success-subtle d-none" style="font-size: 0.68rem;">
                                                    <i class="fas fa-pills me-1"></i> {{ __("مرتبط بالصيدلية") }}
                                                    <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.5rem;" onclick="clearSelectedPharmacyMedicine()" title="{{ __("إلغاء الربط") }}"></button>
                                                </span>
                                            </div>
                                            <div class="mar-autocomplete-wrap">
                                                <input type="text"
                                                       name="description"
                                                       id="medPrescriptionSearchInput"
                                                       class="form-control form-control-sm rounded-2"
                                                       value="{{ old('description') }}"
                                                       autocomplete="off"
                                                       required
                                                       data-search-url="{{ route('clinical-station.medicines.search') }}"
                                                       placeholder="{{ __('ابحث باسم الدواء في الصيدلية أو اكتب اسماً مباشرة (مثال: Ceftriaxone 1g)...') }}">
                                                <div id="medPrescriptionSuggestions" class="mar-suggestions-dropdown"></div>
                                            </div>
                                        </div>

                                        <div class="col-lg-2 col-md-4 col-6">
                                            <label class="form-label small fw-bold text-dark mb-1">{{ __("الجرعة:") }}</label>
                                            <input type="text" name="dosage" id="medPrescriptionDosageInput" class="form-control form-control-sm rounded-2" value="{{ old('dosage') }}" placeholder="{{ __('مثال: 1g / 1 vial') }}">
                                        </div>

                                        <div class="col-lg-2 col-md-4 col-6">
                                            <label class="form-label small fw-bold text-dark mb-1">{{ __("طريقة الإعطاء:") }}</label>
                                            <select name="route" id="medPrescriptionRouteSelect" class="form-select form-select-sm rounded-2">
                                                <option value="">{{ __("-- اختر --") }}</option>
                                                <option value="IV" @selected(old('route') === 'IV')>{{ __("وريدي (IV)") }}</option>
                                                <option value="Infusion" @selected(old('route') === 'Infusion')>{{ __("تسريب وريدي (Infusion)") }}</option>
                                                <option value="IM" @selected(old('route') === 'IM')>{{ __("عضلي (IM)") }}</option>
                                                <option value="Oral" @selected(old('route') === 'Oral')>{{ __("فموي (Oral)") }}</option>
                                                <option value="SC" @selected(old('route') === 'SC')>{{ __("تحت الجلد (SC)") }}</option>
                                                <option value="Nebulizer" @selected(old('route') === 'Nebulizer')>{{ __("تبخير (Nebulizer)") }}</option>
                                                <option value="Topical" @selected(old('route') === 'Topical')>{{ __("موضعي (Topical)") }}</option>
                                                <option value="Drops" @selected(old('route') === 'Drops')>{{ __("قطرة (Drops)") }}</option>
                                            </select>
                                        </div>

                                        <div class="col-lg-3 col-md-4">
                                            <label class="form-label small fw-semibold text-dark mb-1">{{ __("وقت البدء:") }}</label>
                                            <input type="datetime-local" name="start_at" id="medStartAtInput" class="form-control form-control-sm rounded-2" value="{{ old('start_at', now()->format('Y-m-d\TH:i')) }}">
                                        </div>
                                    </div>

                                    <div class="row g-2 align-items-end">
                                        <div class="col-lg-3 col-md-4">
                                            <label class="form-label small fw-bold text-dark mb-1">{{ __("التكرار:") }}</label>
                                            <select name="frequency_preset" id="medFrequencyPreset" class="form-select form-select-sm rounded-2" onchange="updateMarSchedulePreview()">
                                                @foreach(\App\Models\SurgeryTreatment::FREQUENCY_PRESETS as $presetKey => $presetMeta)
                                                    <option value="{{ $presetKey }}"
                                                            data-mode="{{ $presetMeta['mode'] }}"
                                                            data-interval="{{ $presetMeta['interval_hours'] ?? '' }}"
                                                            @selected(old('frequency_preset', 'q8h') === $presetKey)>
                                                        {{ $presetMeta['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-lg-3 col-md-4" id="medCustomTimingWrapper">
                                            <label class="form-label small fw-semibold text-dark mb-1">{{ __("وصف التوقيت (اختياري):") }}</label>
                                            <input type="text" name="timing" id="medCustomTimingInput" class="form-control form-control-sm rounded-2" value="{{ old('timing') }}" placeholder="{{ __('مثل: كل 8 ساعات بعد الأكل') }}">
                                        </div>

                                        <div class="col-lg-3 col-md-4" id="medDurationWrapper">
                                            <label class="form-label small fw-bold text-dark mb-1">{{ __("المدة:") }}</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="duration_value" id="medDurationValue" class="form-control rounded-start-2" min="1" max="365" value="{{ old('duration_value', 1) }}" oninput="updateMarSchedulePreview()">
                                                <select name="duration_unit" id="medDurationUnit" class="form-select rounded-end-2" onchange="updateMarSchedulePreview()" style="max-width: 95px;">
                                                    <option value="days" @selected(old('duration_unit', 'days') === 'days')>{{ __("أيام") }}</option>
                                                    <option value="hours" @selected(old('duration_unit') === 'hours')>{{ __("ساعات") }}</option>
                                                    <option value="weeks" @selected(old('duration_unit') === 'weeks')>{{ __("أسابيع") }}</option>
                                                    <option value="doses" @selected(old('duration_unit') === 'doses')>{{ __("جرعات") }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-3 col-md-12">
                                            <div class="d-flex flex-column gap-1">
                                                <div id="medSchedulePreviewBadge" class="badge bg-info bg-opacity-10 text-info border border-info-subtle py-1 px-2 text-wrap" style="font-size: 0.71rem;">
                                                    {{ __("سيتم توليد 3 مربعات تنفيذ") }}
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm rounded-pill w-100 fw-bold shadow-xs">
                                                    <i class="fas fa-plus-circle me-1"></i> {{ __("إضافة الدواء وتوليد الجدول") }}
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-12 mt-1">
                                            <input type="text" name="admin_notes" class="form-control form-control-sm rounded-2" value="{{ old('admin_notes') }}" placeholder="{{ __('ملاحظات الطبيب أو إرشادات الإعطاء للتمريض (اختياري)...') }}">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    {{-- Prescribed Medications & MAR Checkbox Grid --}}
                    <div class="card rounded-3 border">
                        <div class="card-header bg-light py-2.5 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-pills text-warning me-2"></i> {{ __("جدول الأدوية وجدول الإعطاء (MAR)") }}</h6>
                                <span class="badge bg-primary rounded-pill">{{ $medicationTreatments->count() }} {{ __("دواء") }}</span>
                                @if($canConfirmTreatmentDoses && !$canPrescribeTreatments)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">
                                        <i class="fas fa-check-square me-1"></i> {{ __("دور التمريض: تأشير التنفيذ وتأكيد الصرف لكل جرعة") }}
                                    </span>
                                @elseif($canPrescribeTreatments && !$canConfirmTreatmentDoses)
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle">
                                        <i class="fas fa-eye me-1"></i> {{ __("متابعة تأشيرات التمريض على جدول الجرعات (للاطلاع فقط)") }}
                                    </span>
                                @elseif($canConfirmTreatmentDoses && $canPrescribeTreatments)
                                    <span class="badge bg-dark bg-opacity-10 text-dark border">
                                        <i class="fas fa-shield-alt me-1"></i> {{ __("تحكم كامل بالوصف وتأشير الجرعات") }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-warehouse text-secondary me-1"></i> {{ __("مصدر الأدوية: الصيدلية المركزية للمستشفى") }}
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle text-center small" id="marTreatmentsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th class="text-start" style="min-width: 210px;">{{ __("اسم الدواء والجرعة") }}</th>
                                        <th style="min-width: 155px;">{{ __("التكرار والمدة والإنجاز") }}</th>
                                        <th class="text-start" style="min-width: 340px;">{{ __("جدول أوقات التكرار وتأكيد الصرف") }}</th>
                                        @if($canPrescribeTreatments)
                                            <th style="width: 60px;">{{ __("حذف") }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($medicationTreatments as $idx => $t)
                                        @php
                                            $freq = $t->resolveEffectiveFrequency();
                                            $slots = $t->getDoseSlots(auth()->user(), !empty($isAdmin));
                                            $expectedCount = $t->expected_doses_count;
                                            $adminCount = $t->administered_count;
                                            $progressPercent = ($expectedCount && $expectedCount > 0)
                                                ? min(100, (int) round(($adminCount / $expectedCount) * 100))
                                                : null;
                                            $isCompleted = ($expectedCount !== null && $adminCount >= $expectedCount);
                                            $slotsByDay = collect($slots)->groupBy('day_number');
                                        @endphp
                                        <tr data-treatment-row="{{ $t->id }}">
                                            <td class="fw-bold text-muted">{{ $idx + 1 }}</td>
                                            <td class="text-start">
                                                <div class="fw-bold text-dark d-flex align-items-center gap-1 flex-wrap">
                                                    <span>{{ $t->description }}</span>
                                                    @if($t->medicine_id)
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle" style="font-size: 0.65rem;">
                                                            <i class="fas fa-capsules me-1"></i> {{ __("صيدلية") }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border" style="font-size: 0.65rem;">
                                                            {{ __("وصف سريري") }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                                    @if($t->dosage)
                                                        <span class="badge bg-light text-dark border">{{ $t->dosage }}</span>
                                                    @endif
                                                    @if($t->route)
                                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">{{ $t->route }}</span>
                                                    @endif
                                                    @if($t->prescribedBy)
                                                        <span class="text-muted" style="font-size: 0.7rem;">
                                                            <i class="fas fa-user-md me-1"></i>{{ $t->prescribedBy->name }}
                                                        </span>
                                                    @endif
                                                </div>

                                                @if($t->admin_notes)
                                                    <div class="text-muted mt-1" style="font-size: 0.72rem;">
                                                        <i class="fas fa-info-circle text-info me-1"></i>{{ $t->admin_notes }}
                                                    </div>
                                                @endif

                                                @if($t->batch)
                                                    <div class="mt-1">
                                                        <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">
                                                            {{ __("سجل قديم (") }}{{ $t->batch->location?->name ?? __("عهدة") }})
                                                        </span>
                                                    </div>
                                                @endif
                                            </td>

                                            <td>
                                                <div class="fw-semibold text-dark">{{ $t->timing ?: __('حسب الإرشاد') }}</div>
                                                <div class="text-muted" style="font-size: 0.72rem;">
                                                    {{ __("المدة:") }} <span class="fw-bold text-dark">{{ $t->duration_label }}</span>
                                                </div>
                                                <div class="mt-1">
                                                    @if($t->status === 'cancelled')
                                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle">{{ __("ملغى") }}</span>
                                                    @elseif($t->is_prn)
                                                        <span id="marProgressBadge_{{ $t->id }}" class="badge bg-info bg-opacity-10 text-info border border-info-subtle">
                                                            {{ __("عند اللزوم — ") }}{{ $adminCount }}{{ __(" جرعة") }}
                                                        </span>
                                                    @else
                                                        <span id="marProgressBadge_{{ $t->id }}" class="badge {{ $isCompleted ? 'bg-success text-white' : ($adminCount > 0 ? 'bg-primary bg-opacity-10 text-primary border border-primary-subtle' : 'bg-warning bg-opacity-20 text-warning-emphasis border border-warning-subtle') }}">
                                                            {{ $adminCount }} / {{ $expectedCount }} {{ $isCompleted ? __('مكتمل ✓') : ($adminCount > 0 ? __('قيد الإعطاء') : __('قيد الانتظار')) }}
                                                        </span>
                                                        <div class="progress mt-1 mx-auto" style="height: 5px; max-width: 110px;">
                                                            <div id="marProgressBar_{{ $t->id }}"
                                                                 class="progress-bar {{ $isCompleted ? 'bg-success' : 'bg-primary' }}"
                                                                 role="progressbar"
                                                                 style="width: {{ $progressPercent ?? 0 }}%;"
                                                                 aria-valuenow="{{ $progressPercent ?? 0 }}"
                                                                 aria-valuemin="0"
                                                                 aria-valuemax="100"></div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>

                                            <td class="text-start">
                                                @if($t->status === 'cancelled')
                                                    <span class="text-muted small"><i class="fas fa-ban me-1"></i> {{ __("تم إيقاف هذا الأمر الدوائي") }}</span>
                                                @elseif($t->is_prn)
                                                    <div class="d-flex flex-wrap align-items-center gap-2" id="marPrnList_{{ $t->id }}">
                                                        @foreach($slots as $slot)
                                                            <label class="mar-dose-chip mar-dose-checked {{ (!$canConfirmTreatmentDoses || !$slot['can_undo']) ? 'mar-dose-locked' : '' }}"
                                                                   title="{{ $slot['administered_by_name'] }} — {{ $slot['administered_at_label'] }}">
                                                                <input type="checkbox"
                                                                       class="form-check-input mar-dose-checkbox"
                                                                       data-treatment-id="{{ $t->id }}"
                                                                       data-slot-index="{{ $slot['slot_index'] }}"
                                                                       data-url="{{ route('clinical-station.treatments.toggle-dose', $t) }}"
                                                                       data-dose-number="{{ $slot['dose_number'] }}"
                                                                       data-scheduled-label="{{ $slot['administered_at_label'] }}"
                                                                       data-is-prn="1"
                                                                       checked
                                                                       onchange="window.handleMarCheckboxToggle(this)"
                                                                       @disabled(!$canConfirmTreatmentDoses || !$slot['can_undo'])>
                                                                <span class="mar-dose-meta">
                                                                    <span class="mar-dose-time">{{ __('ج') }}{{ $slot['dose_number'] }} • {{ $slot['administered_at_label'] }}</span>
                                                                    <span class="mar-dose-stamp">✓ {{ $slot['administered_by_name'] }}</span>
                                                                </span>
                                                            </label>
                                                        @endforeach

                                                        @if($canConfirmTreatmentDoses)
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 fw-bold"
                                                                    data-treatment-id="{{ $t->id }}"
                                                                    data-url="{{ route('clinical-station.treatments.toggle-dose', $t) }}"
                                                                    onclick="window.addPrnDoseConfirmation(this)">
                                                                <i class="fas fa-plus me-1"></i> {{ __("تأشير صرف جرعة عند اللزوم") }}
                                                            </button>
                                                        @elseif(empty($slots))
                                                            <span class="text-muted small">{{ __("بانتظار تأشير التمريض عند اللزوم") }}</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="mar-dose-grid">
                                                        @foreach($slotsByDay as $dayNum => $daySlots)
                                                            <div class="mar-day-row">
                                                                @if($slotsByDay->count() > 1)
                                                                    <span class="mar-day-label">{{ __("اليوم ") }}{{ $dayNum }} ({{ $daySlots->first()['scheduled_date_label'] }})</span>
                                                                @endif
                                                                @foreach($daySlots as $slot)
                                                                    @php
                                                                        $locked = !$canConfirmTreatmentDoses || ($slot['is_checked'] && !$slot['can_undo']);
                                                                        $chipClass = $slot['is_checked']
                                                                            ? 'mar-dose-checked'
                                                                            : ($slot['is_overdue'] ? 'mar-dose-overdue' : ($slot['is_due'] ? 'mar-dose-due' : ''));
                                                                        if ($locked) {
                                                                            $chipClass .= ' mar-dose-locked';
                                                                        }
                                                                    @endphp
                                                                    <label class="mar-dose-chip {{ $chipClass }}"
                                                                           id="marChip_{{ $t->id }}_{{ $slot['slot_index'] }}"
                                                                           title="{{ $slot['is_checked'] ? (__('صُرف الساعة ').$slot['administered_at_label'].__(' بواسطة ').($slot['administered_by_name'] ?? __('التمريض'))) : (__('موعد الجرعة ').$slot['dose_number'].': '.$slot['scheduled_time_label']) }}">
                                                                        <input type="checkbox"
                                                                               class="form-check-input mar-dose-checkbox"
                                                                               data-treatment-id="{{ $t->id }}"
                                                                               data-slot-index="{{ $slot['slot_index'] }}"
                                                                               data-url="{{ route('clinical-station.treatments.toggle-dose', $t) }}"
                                                                               data-dose-number="{{ $slot['dose_number'] }}"
                                                                               data-scheduled-label="{{ $slot['scheduled_time_label'] }}"
                                                                               data-is-due="{{ $slot['is_due'] ? '1' : '0' }}"
                                                                               data-is-overdue="{{ $slot['is_overdue'] ? '1' : '0' }}"
                                                                               onchange="window.handleMarCheckboxToggle(this)"
                                                                               @checked($slot['is_checked'])
                                                                               @disabled($locked)>
                                                                        <span class="mar-dose-meta">
                                                                            <span class="mar-dose-time">{{ __('ج') }}{{ $slot['dose_number'] }} • {{ $slot['scheduled_time_label'] }}</span>
                                                                            <span class="mar-dose-stamp" id="marStamp_{{ $t->id }}_{{ $slot['slot_index'] }}">
                                                                                @if($slot['is_checked'])
                                                                                    ✓ {{ $slot['administered_at_label'] }} • {{ $slot['administered_by_name'] ?? __('تمريض') }}
                                                                                @elseif($slot['is_overdue'])
                                                                                    <span class="text-warning-emphasis">{{ __("مستحقة الآن") }}</span>
                                                                                @endif
                                                                            </span>
                                                                        </span>
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>

                                            @if($canPrescribeTreatments)
                                                <td>
                                                    <form action="{{ route('clinical-station.treatments.delete', $t) }}" method="POST" onsubmit="return confirm('{{ __('هل تريد حذف هذا الأمر الدوائي من جدول المريض؟') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="phase" value="{{ $currentPhase }}">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-1" style="width: 28px; height: 28px;" title="{{ __("حذف الدواء") }}">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $canPrescribeTreatments ? '5' : '4' }}" class="text-muted py-4">
                                                <i class="fas fa-prescription-bottle fa-2x mb-2 d-block text-secondary opacity-50"></i>
                                                {{ __("لا توجد أوامر دوائية مسجلة لهذا المريض حتى الآن.") }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <script>
                    (function () {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

                        function escapeHtml(str) {
                            return String(str ?? '')
                                .replace(/&/g, '&amp;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;')
                                .replace(/"/g, '&quot;')
                                .replace(/'/g, '&#039;');
                        }

                        function showMarToast(message, isSuccess = true) {
                            const box = document.getElementById('marToastAlert');
                            const msgEl = document.getElementById('marToastMessage');
                            const iconEl = document.getElementById('marToastIcon');
                            if (!box || !msgEl || !iconEl) return;

                            box.classList.remove('d-none', 'alert-success', 'alert-danger');
                            box.classList.add('d-flex', isSuccess ? 'alert-success' : 'alert-danger');
                            iconEl.className = isSuccess ? 'fas fa-check-circle me-2' : 'fas fa-exclamation-triangle me-2';
                            msgEl.textContent = message;
                        }

                        function updateProgressRow(treatmentId, data) {
                            const badge = document.getElementById('marProgressBadge_' + treatmentId);
                            const bar = document.getElementById('marProgressBar_' + treatmentId);

                            if (badge) {
                                if (data.is_prn) {
                                    badge.className = 'badge bg-info bg-opacity-10 text-info border border-info-subtle';
                                    badge.textContent = '{{ __('عند اللزوم') }} — ' + (data.administered_count ?? 0) + ' {{ __('جرعة') }}';
                                } else {
                                    const expected = data.expected_doses_count ?? 1;
                                    const count = data.administered_count ?? 0;
                                    const completed = count >= expected;
                                    if (completed) {
                                        badge.className = 'badge bg-success text-white';
                                        badge.textContent = count + ' / ' + expected + ' {{ __('مكتمل') }} ✓';
                                    } else if (count > 0) {
                                        badge.className = 'badge bg-primary bg-opacity-10 text-primary border border-primary-subtle';
                                        badge.textContent = count + ' / ' + expected + ' {{ __('قيد الإعطاء') }}';
                                    } else {
                                        badge.className = 'badge bg-warning bg-opacity-20 text-warning-emphasis border border-warning-subtle';
                                        badge.textContent = count + ' / ' + expected + ' {{ __('قيد الانتظار') }}';
                                    }
                                }
                            }

                            if (bar && data.progress_percent !== null && data.progress_percent !== undefined) {
                                bar.style.width = data.progress_percent + '%';
                                bar.setAttribute('aria-valuenow', data.progress_percent);
                                if (data.progress_percent >= 100) {
                                    bar.classList.remove('bg-primary');
                                    bar.classList.add('bg-success');
                                } else {
                                    bar.classList.remove('bg-success');
                                    bar.classList.add('bg-primary');
                                }
                            }
                        }

                        window.handleMarCheckboxToggle = async function (checkbox) {
                            const treatmentId = checkbox.getAttribute('data-treatment-id');
                            const slotIndex = parseInt(checkbox.getAttribute('data-slot-index'), 10);
                            const url = checkbox.getAttribute('data-url');
                            const isPrn = checkbox.getAttribute('data-is-prn') === '1';
                            const desiredChecked = checkbox.checked;

                            checkbox.disabled = true;

                            try {
                                const res = await fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken
                                    },
                                    body: JSON.stringify({
                                        slot_index: isNaN(slotIndex) ? null : slotIndex,
                                        checked: desiredChecked ? 1 : 0
                                    })
                                });

                                const data = await res.json();
                                if (!res.ok || !data.ok) {
                                    checkbox.checked = !desiredChecked;
                                    checkbox.disabled = false;
                                    showMarToast(data.message || '{{ __('تعذر تحديث حالة الجرعة.') }}', false);
                                    return;
                                }

                                checkbox.checked = !!data.checked;
                                if (data.checked && !data.can_undo) {
                                    checkbox.disabled = true;
                                    checkbox.title = '{{ __('تم توثيق إعطاء الجرعة ولا يمكن إلغاؤها من التمريض لسلامة المريض') }}';
                                } else {
                                    checkbox.disabled = false;
                                }

                                if (isPrn && !data.checked) {
                                    const label = checkbox.closest('label.mar-dose-chip');
                                    if (label) label.remove();
                                } else {
                                    const chip = document.getElementById('marChip_' + treatmentId + '_' + slotIndex);
                                    const stamp = document.getElementById('marStamp_' + treatmentId + '_' + slotIndex);
                                    if (chip) {
                                        chip.classList.remove('mar-dose-checked', 'mar-dose-overdue', 'mar-dose-due', 'mar-dose-locked');
                                        if (data.checked) {
                                            chip.classList.add('mar-dose-checked');
                                            if (!data.can_undo) {
                                                chip.classList.add('mar-dose-locked');
                                            }
                                        } else if (checkbox.getAttribute('data-is-overdue') === '1') {
                                            chip.classList.add('mar-dose-overdue');
                                        } else if (checkbox.getAttribute('data-is-due') === '1') {
                                            chip.classList.add('mar-dose-due');
                                        }
                                    }
                                    if (stamp) {
                                        if (data.checked) {
                                            stamp.textContent = '✓ ' + (data.administered_at_label || '') + ' • ' + (data.administered_by_name || '{{ __('تمريض') }}');
                                        } else if (checkbox.getAttribute('data-is-overdue') === '1') {
                                            stamp.innerHTML = '<span class="text-warning-emphasis">{{ __("مستحقة الآن") }}</span>';
                                        } else {
                                            stamp.textContent = '';
                                        }
                                    }
                                }

                                updateProgressRow(treatmentId, data);
                                showMarToast(data.message || '{{ __('تم تحديث حالة الجرعة بنجاح.') }}', true);
                            } catch (e) {
                                checkbox.checked = !desiredChecked;
                                checkbox.disabled = false;
                                showMarToast('{{ __('حدث خطأ في الاتصال بالخادم أثناء تحديث الجرعة.') }}', false);
                            }
                        };

                        window.addPrnDoseConfirmation = async function (btn) {
                            const treatmentId = btn.getAttribute('data-treatment-id');
                            const url = btn.getAttribute('data-url');
                            btn.disabled = true;

                            try {
                                const res = await fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken
                                    },
                                    body: JSON.stringify({
                                        slot_index: null,
                                        checked: 1
                                    })
                                });
                                const data = await res.json();
                                btn.disabled = false;

                                if (!res.ok || !data.ok) {
                                    showMarToast(data.message || '{{ __('تعذر توثيق الجرعة.') }}', false);
                                    return;
                                }

                                const container = document.getElementById('marPrnList_' + treatmentId);
                                if (container) {
                                    const label = document.createElement('label');
                                    const chipLockedClass = (!data.can_undo) ? ' mar-dose-locked' : '';
                                    label.className = 'mar-dose-chip mar-dose-checked' + chipLockedClass;
                                    const doseNum = data.administered_count || 1;
                                    const timeLabel = escapeHtml(data.administered_at_label || '');
                                    const nurseName = escapeHtml(data.administered_by_name || '{{ __('تمريض') }}');
                                    const safeUrl = escapeHtml(url);
                                    const slotIdx = parseInt(data.slot_index ?? 0, 10);
                                    const disabledAttr = (!data.can_undo) ? ' disabled title="{{ __('تم توثيق إعطاء الجرعة ولا يمكن إلغاؤها من التمريض لسلامة المريض') }}"' : '';

                                    label.innerHTML =
                                        '<input type="checkbox" class="form-check-input mar-dose-checkbox" ' +
                                        'data-treatment-id="' + escapeHtml(treatmentId) + '" ' +
                                        'data-slot-index="' + slotIdx + '" ' +
                                        'data-url="' + safeUrl + '" ' +
                                        'data-is-prn="1" checked' + disabledAttr + ' onchange="window.handleMarCheckboxToggle(this)">' +
                                        '<span class="mar-dose-meta">' +
                                        '<span class="mar-dose-time">{{ __('ج') }}' + doseNum + ' • ' + timeLabel + '</span>' +
                                        '<span class="mar-dose-stamp">✓ ' + nurseName + '</span>' +
                                        '</span>';
                                    container.insertBefore(label, btn);
                                }

                                updateProgressRow(treatmentId, data);
                                showMarToast(data.message || '{{ __('تم تسجيل الجرعة الإضافية بنجاح.') }}', true);
                            } catch (e) {
                                btn.disabled = false;
                                showMarToast('{{ __('حدث خطأ في الاتصال بالخادم أثناء توثيق الجرعة.') }}', false);
                            }
                        };

                        // Live schedule dose count calculator in Prescribe form
                        window.updateMarSchedulePreview = function () {
                            const presetEl = document.getElementById('medFrequencyPreset');
                            const durValEl = document.getElementById('medDurationValue');
                            const durUnitEl = document.getElementById('medDurationUnit');
                            const durWrapEl = document.getElementById('medDurationWrapper');
                            const previewEl = document.getElementById('medSchedulePreviewBadge');
                            if (!presetEl || !previewEl) return;

                            const selectedOpt = presetEl.options[presetEl.selectedIndex];
                            const mode = selectedOpt ? selectedOpt.getAttribute('data-mode') : 'scheduled';
                            const interval = parseInt(selectedOpt?.getAttribute('data-interval') || '24', 10) || 24;

                            if (mode === 'prn') {
                                if (durWrapEl) durWrapEl.classList.add('opacity-50');
                                previewEl.textContent = '{{ __('يتم التأشير يدوياً عند اللزوم (PRN)') }}';
                                return;
                            }
                            if (durWrapEl) durWrapEl.classList.remove('opacity-50');

                            if (mode === 'stat') {
                                previewEl.textContent = '{{ __('سيتم توليد مربع تأشير لجرعة واحدة فورية') }}';
                                return;
                            }

                            const durVal = Math.max(1, parseInt(durValEl?.value || '1', 10) || 1);
                            const durUnit = durUnitEl?.value || 'days';
                            let doses = 1;
                            if (durUnit === 'doses') {
                                doses = Math.min(60, durVal);
                            } else if (durUnit === 'hours') {
                                doses = Math.min(60, Math.max(1, Math.ceil(durVal / interval)));
                            } else if (durUnit === 'weeks') {
                                doses = Math.min(60, Math.max(1, Math.floor((durVal * 7 * 24) / interval)));
                            } else {
                                doses = Math.min(60, Math.max(1, Math.floor((durVal * 24) / interval)));
                            }

                            previewEl.textContent = '{{ __('سيتم توليد') }} ' + doses + ' {{ __('مربعات تأشير (جرعات)') }}';
                        };

                        window.clearSelectedPharmacyMedicine = function () {
                            const hiddenId = document.getElementById('medPrescriptionMedicineId');
                            const badge = document.getElementById('medCatalogLinkedBadge');
                            if (hiddenId) hiddenId.value = '';
                            if (badge) badge.classList.add('d-none');
                        };

                        // Pharmacy catalog live autocomplete
                        const searchInp = document.getElementById('medPrescriptionSearchInput');
                        const dropdown = document.getElementById('medPrescriptionSuggestions');
                        const hiddenMedId = document.getElementById('medPrescriptionMedicineId');
                        const linkedBadge = document.getElementById('medCatalogLinkedBadge');
                        const routeSelect = document.getElementById('medPrescriptionRouteSelect');
                        let searchTimer = null;

                        async function fetchMedicines(query) {
                            if (!searchInp || !dropdown) return;
                            const baseUrl = searchInp.getAttribute('data-search-url');
                            try {
                                const res = await fetch(baseUrl + '?q=' + encodeURIComponent(query), {
                                    headers: { 'Accept': 'application/json' }
                                });
                                const data = await res.json();
                                const items = data.medicines || [];
                                if (!items.length) {
                                    dropdown.innerHTML = '<div class="p-2 text-muted small text-center">{{ __('لا توجد نتائج مطابقة في الصيدلية — يمكنك اعتماد النص المكتوب مباشرة كوصف سريري.') }}</div>';
                                    dropdown.style.display = 'block';
                                    return;
                                }

                                dropdown.innerHTML = items.map(function (m) {
                                    const fullLabel = [m.name, m.strength, m.dosage_form ? '(' + m.dosage_form + ')' : ''].filter(Boolean).join(' ');
                                    const stockBadge = m.total_stock > 0
                                        ? '<span class="badge bg-success bg-opacity-10 text-success border border-success-subtle" style="font-size:0.68rem;">{{ __('متوفر') }}: ' + m.total_stock + ' ' + escapeHtml(m.main_unit || '{{ __('علبة') }}') + '</span>'
                                        : '<span class="badge bg-secondary bg-opacity-10 text-secondary border" style="font-size:0.68rem;">{{ __('الرصيد') }}: 0</span>';
                                    return '<div class="mar-suggestion-item" data-id="' + m.id + '" data-label="' + escapeHtml(fullLabel) + '" data-form="' + escapeHtml(m.dosage_form || '') + '">' +
                                        '<div class="d-flex justify-content-between align-items-center gap-2">' +
                                        '<div class="fw-bold text-dark small">' + escapeHtml(fullLabel) + '</div>' +
                                        stockBadge +
                                        '</div>' +
                                        (m.generic_name ? '<div class="text-muted" style="font-size:0.71rem;">{{ __('الاسم العلمي') }}: ' + escapeHtml(m.generic_name) + '</div>' : '') +
                                        '</div>';
                                }).join('');

                                dropdown.querySelectorAll('.mar-suggestion-item').forEach(function (el) {
                                    el.addEventListener('click', function () {
                                        searchInp.value = el.getAttribute('data-label') || '';
                                        if (hiddenMedId) hiddenMedId.value = el.getAttribute('data-id') || '';
                                        if (linkedBadge) linkedBadge.classList.remove('d-none');

                                        const form = (el.getAttribute('data-form') || '').toLowerCase();
                                        if (routeSelect && !routeSelect.value) {
                                            if (form.includes('vial') || form.includes('iv') || form.includes('infusion')) {
                                                routeSelect.value = 'IV';
                                            } else if (form.includes('amp')) {
                                                routeSelect.value = 'IV';
                                            } else if (form.includes('tab') || form.includes('cap') || form.includes('syrup')) {
                                                routeSelect.value = 'Oral';
                                            }
                                        }
                                        dropdown.style.display = 'none';
                                    });
                                });

                                dropdown.style.display = 'block';
                            } catch (e) {
                                dropdown.style.display = 'none';
                            }
                        }

                        if (searchInp && dropdown) {
                            searchInp.addEventListener('input', function () {
                                // Clear pharmacy linkage when user manually edits text
                                if (hiddenMedId && hiddenMedId.value) {
                                    window.clearSelectedPharmacyMedicine();
                                }
                                clearTimeout(searchTimer);
                                const val = searchInp.value.trim();
                                searchTimer = setTimeout(function () {
                                    fetchMedicines(val);
                                }, 180);
                            });

                            searchInp.addEventListener('focus', function () {
                                if (dropdown.innerHTML.trim() === '') {
                                    fetchMedicines(searchInp.value.trim());
                                } else {
                                    dropdown.style.display = 'block';
                                }
                            });

                            document.addEventListener('click', function (ev) {
                                if (!searchInp.contains(ev.target) && !dropdown.contains(ev.target)) {
                                    dropdown.style.display = 'none';
                                }
                            });
                        }

                        window.updateMarSchedulePreview();
                    })();
                </script>
            @endif

            {{-- =============================================================== --}}
            {{-- SPECIAL SUB-VIEW FOR SUPPLIES TAB (المستلزمات الطبية فقط) --}}
            {{-- =============================================================== --}}
            @if($activeTab === 'supplies')
                <div class="p-4">
                    {{-- Direct Dispense Supply Card (if write permission) --}}
                    @if($canWriteCurrentTab)
                        <div class="card rounded-3 border mb-4 bg-light bg-opacity-50">
                            <div class="card-header bg-white py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <h6 class="mb-0 fw-bold text-dark">
                                    <i class="fas fa-box-open text-info me-2"></i> {{ __("صرف مستلزم طبي من عهدة الطابق") }}
                                </h6>
                                @if(isset($floorLocation) && $floorLocation)
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle">
                                        <i class="fas fa-warehouse me-1"></i> {{ __("عهدة: ") }}{{ $floorLocation->name }} ({{ $surgery->room?->room_number ? __("غرفة ") . $surgery->room->room_number : __("طابق ") . ($surgery->room?->floor ?? '--') }})
                                    </span>
                                @endif
                            </div>
                            <div class="card-body p-3">
                                @if(!isset($floorLocation) || !$floorLocation)
                                    <div class="alert alert-warning mb-0 py-2 px-3 small">
                                        <i class="fas fa-exclamation-triangle me-1"></i> {{ __("لم يتم العثور على مخزن فرعي مرتبط بطابق غرفة هذا المريض، لذا يتعذر صرف المستلزمات مباشرة من عهدة الطابق.") }}
                                    </div>
                                @elseif(isset($floorSupplyBatches) && $floorSupplyBatches->isNotEmpty())
                                    {{-- شريط الإدخال والبحث السريع للمستلزم --}}
                                    <div class="p-3 bg-light rounded-3 border mb-3">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-lg-4 col-md-6 position-relative" id="supSearchWrapper">
                                                <label class="form-label small fw-bold text-dark mb-1">{{ __("اختر المستلزم الطبي: ") }}<span class="text-danger">*</span></label>
                                                
                                                {{-- حقول الحالة المؤقتة للعنصر المختار --}}
                                                <input type="hidden" id="sup_selected_batch_id" value="">
                                                <input type="hidden" id="sup_selected_max" value="1">
                                                <input type="hidden" id="sup_selected_unit" value="{{ __('قطعة') }}">
                                                <input type="hidden" id="sup_selected_name" value="">
                                                <input type="hidden" id="sup_selected_cat" value="">
                                                <input type="hidden" id="sup_selected_lot" value="">

                                                {{-- حقل البحث اللحظي التفاعلي --}}
                                                <div class="position-relative">
                                                    <input type="text" id="sup_search_input" class="form-control form-control-sm rounded-2 ps-5" 
                                                           placeholder="{{ __('ابحث أو اضغط لاختيار مستلزم من القائمة...') }}" 
                                                           autocomplete="off">
                                                    <button type="button" id="sup_toggle_dropdown_btn" 
                                                            class="btn btn-link position-absolute top-50 translate-middle-y text-secondary p-0 border-0 text-decoration-none" 
                                                            style="left: 8px; z-index: 5;" title="{{ __('عرض كامل قائمة المستلزمات') }}">
                                                        <i class="fas fa-chevron-down small" id="sup_dropdown_chevron"></i>
                                                    </button>
                                                    <button type="button" id="sup_clear_search_btn" 
                                                            class="btn btn-link position-absolute top-50 translate-middle-y text-danger p-0 border-0 text-decoration-none" 
                                                            style="left: 28px; display: none; z-index: 5;" title="{{ __('مسح التحديد') }}">
                                                        <i class="fas fa-times-circle"></i>
                                                    </button>
                                                </div>

                                                {{-- قائمة الاقتراحات والنتائج اللحظية --}}
                                                <div id="sup_suggestions_dropdown" class="position-absolute start-0 end-0 bg-white border rounded-3 shadow-lg mt-1 p-1 overflow-auto" 
                                                     style="max-height: 280px; z-index: 1055; display: none;">
                                                    @foreach($floorSupplyBatches as $b)
                                                        @php
                                                            $cat = $b->product?->category ?: __('مستلزم');
                                                            $name = $b->product?->name ?? __('مستلزم غير معروف');
                                                            $qty = (float) $b->current_qty;
                                                            $unit = $b->product?->unit ?? __('قطعة');
                                                            $lot = $b->manufacturer_lot_number ?: ($b->internal_barcode ?: '#'.$b->id);
                                                            $searchKeywords = mb_strtolower($name . ' ' . $cat . ' ' . $lot . ' ' . ($b->product?->barcode ?? ''));
                                                            $displayLabel = "[{$cat}] {$name} — " . __('المتوفر: ') . "{$qty} {$unit} ({$lot})";
                                                        @endphp
                                                        <div class="sup-suggestion-item p-2 rounded-2 border-bottom border-light" 
                                                             data-id="{{ $b->id }}"
                                                             data-name="{{ $name }}"
                                                             data-cat="{{ $cat }}"
                                                             data-lot="{{ $lot }}"
                                                             data-label="{{ $displayLabel }}"
                                                             data-keywords="{{ $searchKeywords }}"
                                                             data-max="{{ $qty }}"
                                                             data-unit="{{ $unit }}"
                                                             style="cursor: pointer; transition: background-color 0.15s ease;">
                                                            <div class="d-flex justify-content-between align-items-center gap-2">
                                                                <div class="fw-bold text-dark small text-truncate">
                                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border me-1" style="font-size: 0.68rem;">{{ $cat }}</span>
                                                                    {{ $name }}
                                                                </div>
                                                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle flex-shrink-0" style="font-size: 0.72rem;">
                                                                    <i class="fas fa-boxes me-1"></i> {{ __('المتوفر: ') }}{{ $qty }} {{ $unit }}
                                                                </span>
                                                            </div>
                                                            <div class="d-flex align-items-center gap-2 text-muted mt-0.5" style="font-size: 0.7rem;">
                                                                <span><i class="fas fa-barcode me-1"></i> {{ $lot }}</span>
                                                                @if($b->expiry_date)
                                                                    <span><i class="far fa-calendar-alt me-1"></i> {{ __('الصلاحية: ') }}{{ $b->expiry_date->format('Y-m-d') }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    <div id="sup_no_match" class="p-3 text-muted small text-center" style="display: none;">
                                                        <i class="fas fa-search me-1"></i> {{ __('لا توجد مستلزمات مطابقة لبحثك في عهدة الطابق') }}
                                                    </div>
                                                </div>

                                                {{-- إشعار العنصر المحدد وتفاصيله --}}
                                                <div id="sup_selected_info" class="mt-1 small d-none">
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1">
                                                        <i class="fas fa-check-circle"></i>
                                                        <span id="sup_selected_text"></span>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-md-3 col-6">
                                                <label class="form-label small fw-bold text-dark mb-1">{{ __("الكمية: ") }}<span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" id="sup_add_qty" class="form-control rounded-start-2" min="1" value="1">
                                                    <span class="input-group-text rounded-end-2 small" id="sup_add_unit_label">{{ __("قطعة") }}</span>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-md-3 col-6">
                                                <label class="form-label small fw-semibold text-dark mb-1">{{ __("سبب / مكان الاستخدام:") }}</label>
                                                <input type="text" id="sup_add_timing" class="form-control form-control-sm rounded-2" placeholder="{{ __('مثال: تضميد / كانيولا / قسطرة') }}">
                                            </div>
                                            <div class="col-lg-2 col-md-6">
                                                <label class="form-label small fw-semibold text-dark mb-1">{{ __('ملاحظات:') }}</label>
                                                <input type="text" id="sup_add_notes" class="form-control form-control-sm rounded-2" placeholder="{{ __('ملاحظة إضافية...') }}">
                                            </div>
                                            <div class="col-lg-2 col-md-6">
                                                <button type="button" id="sup_add_to_cart_btn" class="btn btn-primary text-white btn-sm rounded-pill w-100 fw-bold shadow-xs">
                                                    <i class="fas fa-plus-circle me-1"></i> {{ __("إضافة للقائمة") }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- جدول سلة المستلزمات قيد الصرف مع الحفظ الجماعي --}}
                                    <form id="sup_dispense_form" action="{{ route('clinical-station.supplies.dispense', $surgery) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="expected_type" value="supply">
                                        <input type="hidden" name="phase" value="{{ $currentPhase }}">
                                        <input type="hidden" name="from" value="{{ request('from', $from ?? '') }}">

                                        {{-- حاوية الحقول المخفية المولدة تلقائياً --}}
                                        <div id="sup_cart_hidden_inputs"></div>

                                        {{-- تنبيه القائمة الفارغة --}}
                                        <div id="sup_cart_empty_state" class="text-center py-3 px-3 bg-light bg-opacity-50 border border-dashed rounded-3 text-muted small mb-3">
                                            <i class="fas fa-cart-plus fa-2x mb-1 text-primary opacity-50 d-block"></i>
                                            <span>{{ __('ابحث عن المستلزم واختره ثم اضغط «إضافة للقائمة». يمكنك إضافة عدة مستلزمات وتعديل كمياتها وحذفها ثم حفظها جميعاً بنقرة واحدة.') }}</span>
                                        </div>

                                        {{-- جدول المستلزمات المضافة --}}
                                        <div id="sup_cart_table_wrapper" class="card border rounded-3 shadow-xs mb-3" style="display: none;">
                                            <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                                                <span class="small fw-bold text-dark"><i class="fas fa-clipboard-list text-primary me-1"></i> {{ __('المستلزمات المحددة قيد الصرف (بانتظار الحفظ)') }}</span>
                                                <span class="badge bg-primary text-white rounded-pill" id="sup_cart_badge_count">0</span>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover align-middle mb-0 text-center small">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="width: 40px;">#</th>
                                                            <th class="text-start">{{ __('اسم المستلزم وتفاصيل الوجبة') }}</th>
                                                            <th style="width: 120px;">{{ __('الرصيد المتاح') }}</th>
                                                            <th style="width: 130px;">{{ __('الكمية المصروفة') }}</th>
                                                            <th class="text-start">{{ __('سبب الاستخدام والملاحظات') }}</th>
                                                            <th style="width: 60px;">{{ __('حذف') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="sup_cart_tbody">
                                                        {{-- تتولد الصفوف ديناميكياً بواسطة JavaScript --}}
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="card-footer bg-light py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-white text-dark border px-2.5 py-1.5 shadow-xs">
                                                        <i class="fas fa-boxes text-info me-1"></i> {{ __('إجمالي المواد:') }} <strong id="sup_cart_summary_items">0</strong>
                                                    </span>
                                                    <span class="badge bg-white text-dark border px-2.5 py-1.5 shadow-xs">
                                                        <i class="fas fa-cubes text-primary me-1"></i> {{ __('إجمالي القطع:') }} <strong id="sup_cart_summary_pieces">0</strong>
                                                    </span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button type="button" id="sup_cart_clear_all_btn" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-xs">
                                                        <i class="fas fa-trash-alt me-1"></i> {{ __('تفريغ القائمة') }}
                                                    </button>
                                                    <button type="submit" id="sup_cart_save_btn" class="btn btn-success btn-sm rounded-pill px-4 fw-bold shadow-sm">
                                                        <i class="fas fa-check-double me-1"></i> {{ __('حفظ وصرف كافة المستلزمات المحددة') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                @else
                                    <div class="alert alert-secondary mb-0 py-2 px-3 small">
                                        <i class="fas fa-info-circle text-secondary me-1"></i> {{ __("لا توجد مستلزمات طبية برصيد موجب حالياً في مخزن هذا الطابق (") }}{{ $floorLocation->name }}).
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Dispensed Supplies Table --}}
                    <div class="card rounded-3 border">
                        <div class="card-header bg-light py-2.5 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-boxes text-info me-2"></i> {{ __("سجل المستلزمات الطبية المصروفة للمريض") }}</h6>
                                <span class="badge bg-info text-white rounded-pill">{{ $supplyTreatments->count() }} {{ __("مستلزم") }}</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle text-center small">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th class="text-start">{{ __("اسم المستلزم وتفاصيل الوجبة") }}</th>
                                        <th>{{ __("التصنيف") }}</th>
                                        <th>{{ __("الكمية المصروفة") }}</th>
                                        <th>{{ __("بيان الاستخدام والملاحظات") }}</th>
                                        <th>{{ __("وقت الصرف والكادر") }}</th>
                                        @if($canWriteCurrentTab)
                                            <th>{{ __("إجراء") }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($supplyTreatments as $idx => $sup)
                                        <tr>
                                            <td>{{ $idx + 1 }}</td>
                                            <td class="text-start fw-bold text-dark">
                                                {{ $sup->description }}
                                                @if($sup->batch)
                                                    <div class="mt-1">
                                                        <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle" style="font-size: 0.72rem;">
                                                            <i class="fas fa-warehouse me-1"></i> {{ __("عهدة: ") }}{{ $sup->batch->location?->name ?? __("مخزن الطابق") }}
                                                            @if($sup->batch->manufacturer_lot_number) | Lot: {{ $sup->batch->manufacturer_lot_number }} @endif
                                                            @if($sup->batch->expiry_date) | {{ __('نفاد:') }} {{ $sup->batch->expiry_date->format('Y-m') }} @endif
                                                        </span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-secondary border">{{ $sup->batch?->product?->category ?: __("مستلزمات") }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info bg-opacity-10 text-dark border border-info-subtle fw-bold">{{ $sup->dosage ?: (($sup->quantity ?? 1) . ' ' . __('قطعة')) }}</span>
                                            </td>
                                            <td class="text-start">
                                                <div class="text-dark">{{ $sup->timing ?: __('صرف مستلزم') }}</div>
                                                @if($sup->admin_notes)
                                                    <small class="text-muted d-block">{{ $sup->admin_notes }}</small>
                                                @endif
                                            </td>
                                            <td class="text-muted" style="font-size: 0.75rem;">
                                                @if($sup->administered_at)
                                                    {{ $sup->administered_at->format('Y-m-d h:i A') }}
                                                    <div class="text-dark fw-semibold">{{ $sup->administeredBy?->name ?? __('كادر التمريض') }}</div>
                                                @else
                                                    {{ $sup->created_at?->format('Y-m-d h:i A') ?? '--' }}
                                                @endif
                                            </td>
                                            @if($canWriteCurrentTab)
                                                <td>
                                                    <form action="{{ route('clinical-station.treatments.delete', $sup) }}" method="POST" onsubmit="return confirm('{{ __('هل تريد حذف سجل هذا المستلزم؟') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="phase" value="{{ $currentPhase }}">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-1" style="width: 28px; height: 28px;" title="{{ __("حذف السجل") }}">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $canWriteCurrentTab ? '7' : '6' }}" class="text-muted py-4">
                                                {{ __("لا توجد مستلزمات طبية مصروفة لهذه العملية حتى الآن.") }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if($activeTab === 'supplies')
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        // عناصر البحث والاختيار السريع
                        const searchInp = document.getElementById('sup_search_input');
                        const dropdown = document.getElementById('sup_suggestions_dropdown');
                        const toggleBtn = document.getElementById('sup_toggle_dropdown_btn');
                        const clearBtn = document.getElementById('sup_clear_search_btn');
                        const selectedInfo = document.getElementById('sup_selected_info');
                        const selectedText = document.getElementById('sup_selected_text');
                        const noMatch = document.getElementById('sup_no_match');
                        const items = dropdown ? dropdown.querySelectorAll('.sup-suggestion-item') : [];

                        // حقول التخزين المؤقت للاختيار الحالي
                        const selectedBatchId = document.getElementById('sup_selected_batch_id');
                        const selectedMax = document.getElementById('sup_selected_max');
                        const selectedUnit = document.getElementById('sup_selected_unit');
                        const selectedName = document.getElementById('sup_selected_name');
                        const selectedCat = document.getElementById('sup_selected_cat');
                        const selectedLot = document.getElementById('sup_selected_lot');

                        // حقول الإدخال السريع
                        const addQtyInput = document.getElementById('sup_add_qty');
                        const addUnitLabel = document.getElementById('sup_add_unit_label');
                        const addTimingInput = document.getElementById('sup_add_timing');
                        const addNotesInput = document.getElementById('sup_add_notes');
                        const addToCartBtn = document.getElementById('sup_add_to_cart_btn');

                        // عناصر السلة والجدول والحفظ
                        const dispenseForm = document.getElementById('sup_dispense_form');
                        const cartHiddenInputs = document.getElementById('sup_cart_hidden_inputs');
                        const cartEmptyState = document.getElementById('sup_cart_empty_state');
                        const cartTableWrapper = document.getElementById('sup_cart_table_wrapper');
                        const cartTbody = document.getElementById('sup_cart_tbody');
                        const cartBadgeCount = document.getElementById('sup_cart_badge_count');
                        const cartSummaryItems = document.getElementById('sup_cart_summary_items');
                        const cartSummaryPieces = document.getElementById('sup_cart_summary_pieces');
                        const cartClearAllBtn = document.getElementById('sup_cart_clear_all_btn');
                        const cartSaveBtn = document.getElementById('sup_cart_save_btn');

                        // مصفوفة عناصر السلة المؤقتة
                        let cartItems = [];

                        function escapeHtml(str) {
                            if (!str) return '';
                            return String(str)
                                .replace(/&/g, '&amp;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;')
                                .replace(/"/g, '&quot;')
                                .replace(/'/g, '&#039;');
                        }

                        // تصفية نتائج البحث فور الكتابة
                        function filterItems(query) {
                            if (!dropdown) return;
                            query = (query || '').trim().toLowerCase();
                            let matches = 0;

                            items.forEach(function (el) {
                                const keywords = el.getAttribute('data-keywords') || '';
                                const isMatch = !query || keywords.includes(query);
                                el.style.display = isMatch ? 'block' : 'none';
                                if (isMatch) matches++;
                            });

                            if (noMatch) {
                                noMatch.style.display = (matches === 0) ? 'block' : 'none';
                            }

                            dropdown.style.display = 'block';
                        }

                        if (searchInp && dropdown) {
                            searchInp.addEventListener('focus', function () {
                                filterItems(this.value);
                            });

                            searchInp.addEventListener('click', function (e) {
                                e.stopPropagation();
                                filterItems(this.value);
                            });

                            if (toggleBtn) {
                                toggleBtn.addEventListener('click', function (e) {
                                    e.stopPropagation();
                                    if (dropdown.style.display === 'block') {
                                        dropdown.style.display = 'none';
                                    } else {
                                        filterItems(searchInp.value);
                                        searchInp.focus();
                                    }
                                });
                            }

                            searchInp.addEventListener('input', function () {
                                if (selectedBatchId && selectedBatchId.value) {
                                    resetCurrentSelection(false);
                                }
                                filterItems(this.value);
                            });

                            items.forEach(function (el) {
                                el.addEventListener('mouseenter', function() {
                                    this.style.backgroundColor = '#f1f5f9';
                                });
                                el.addEventListener('mouseleave', function() {
                                    this.style.backgroundColor = '';
                                });
                                el.addEventListener('click', function () {
                                    const id = this.getAttribute('data-id');
                                    const name = this.getAttribute('data-name');
                                    const cat = this.getAttribute('data-cat') || '';
                                    const lot = this.getAttribute('data-lot') || '';
                                    const max = parseInt(this.getAttribute('data-max')) || 1;
                                    const unit = this.getAttribute('data-unit') || '{{ __('قطعة') }}';

                                    if (selectedBatchId) selectedBatchId.value = id;
                                    if (selectedName) selectedName.value = name;
                                    if (selectedCat) selectedCat.value = cat;
                                    if (selectedLot) selectedLot.value = lot;
                                    if (selectedMax) selectedMax.value = max;
                                    if (selectedUnit) selectedUnit.value = unit;

                                    searchInp.value = name;
                                    searchInp.setCustomValidity('');

                                    if (addQtyInput) {
                                        addQtyInput.max = max;
                                        if (parseInt(addQtyInput.value) > max) {
                                            addQtyInput.value = max;
                                        }
                                    }
                                    if (addUnitLabel) addUnitLabel.textContent = unit;

                                    if (selectedText) {
                                        selectedText.textContent = name + ' (' + '{{ __('المتوفر') }}: ' + max + ' ' + unit + ')';
                                    }
                                    if (selectedInfo) selectedInfo.classList.remove('d-none');
                                    if (clearBtn) clearBtn.style.display = 'inline-block';

                                    dropdown.style.display = 'none';
                                    if (addQtyInput) addQtyInput.focus();
                                });
                            });

                            if (clearBtn) {
                                clearBtn.addEventListener('click', function () {
                                    resetCurrentSelection(true);
                                    filterItems('');
                                    searchInp.focus();
                                });
                            }

                            document.addEventListener('click', function (e) {
                                if (!e.target.closest('#supSearchWrapper') && !e.target.closest('#sup_add_to_cart_btn')) {
                                    dropdown.style.display = 'none';
                                }
                            });
                        }

                        function resetCurrentSelection(clearSearchText) {
                            if (selectedBatchId) selectedBatchId.value = '';
                            if (selectedName) selectedName.value = '';
                            if (selectedCat) selectedCat.value = '';
                            if (selectedLot) selectedLot.value = '';
                            if (selectedMax) selectedMax.value = '1';
                            if (selectedUnit) selectedUnit.value = '{{ __('قطعة') }}';

                            if (clearSearchText && searchInp) searchInp.value = '';
                            if (clearBtn) clearBtn.style.display = 'none';
                            if (selectedInfo) selectedInfo.classList.add('d-none');
                            if (addQtyInput) {
                                addQtyInput.removeAttribute('max');
                                addQtyInput.value = 1;
                            }
                            if (addUnitLabel) addUnitLabel.textContent = '{{ __('قطعة') }}';
                        }

                        // إضافة مستلزم إلى السلة
                        if (addToCartBtn) {
                            addToCartBtn.addEventListener('click', function () {
                                if (!selectedBatchId || !selectedBatchId.value) {
                                    if (searchInp) {
                                        searchInp.setCustomValidity('{{ __('يرجى اختيار مستلزم من القائمة المقترحة أولاً') }}');
                                        searchInp.reportValidity();
                                        filterItems(searchInp.value);
                                        searchInp.focus();
                                    }
                                    return;
                                }

                                const batchId = selectedBatchId.value;
                                const maxStock = parseInt(selectedMax.value) || 1;
                                let qty = parseInt(addQtyInput ? addQtyInput.value : 1) || 1;
                                if (qty < 1) qty = 1;

                                const timing = addTimingInput ? addTimingInput.value.trim() : '';
                                const notes = addNotesInput ? addNotesInput.value.trim() : '';

                                // التحقق مما إذا كان المستلزم موجوداً مسبقاً في السلة
                                const existingIdx = cartItems.findIndex(it => it.batchId == batchId);
                                if (existingIdx > -1) {
                                    const combinedQty = cartItems[existingIdx].qty + qty;
                                    if (combinedQty > maxStock) {
                                        if (cartItems[existingIdx].qty >= maxStock) {
                                            alert('{{ __('الكمية المضافة مسبقاً بلغت الحد الأقصى المتوفر في المخزن (') }}' + maxStock + ')');
                                            return;
                                        }
                                        cartItems[existingIdx].qty = maxStock;
                                        alert('{{ __('تمت زيادة الكمية إلى الحد الأقصى المتوفر في المخزن (') }}' + maxStock + ')');
                                    } else {
                                        cartItems[existingIdx].qty = combinedQty;
                                    }
                                    if (timing) cartItems[existingIdx].timing = timing;
                                    if (notes) cartItems[existingIdx].notes = notes;
                                } else {
                                    if (qty > maxStock) {
                                        qty = maxStock;
                                        alert('{{ __('تم تعديل الكمية إلى الحد الأقصى المتوفر في المخزن (') }}' + maxStock + ')');
                                    }
                                    cartItems.push({
                                        batchId: batchId,
                                        name: selectedName ? selectedName.value : '',
                                        cat: selectedCat ? selectedCat.value : '',
                                        lot: selectedLot ? selectedLot.value : '',
                                        unit: selectedUnit ? selectedUnit.value : '{{ __('قطعة') }}',
                                        max: maxStock,
                                        qty: qty,
                                        timing: timing,
                                        notes: notes
                                    });
                                }

                                // تفريغ حقول الإدخال والعودة للبحث
                                resetCurrentSelection(true);
                                if (addTimingInput) addTimingInput.value = '';
                                if (addNotesInput) addNotesInput.value = '';

                                renderCart();

                                // إعادة فتح القائمة فوراً بكافة المستلزمات لاختيار مادة أخرى
                                filterItems('');
                                dropdown.style.display = 'block';
                                if (searchInp) searchInp.focus();
                            });
                        }

                        // السماح بالضغط على Enter في حقول الإدخال السريع لإضافة المستلزم
                        [searchInp, addQtyInput, addTimingInput, addNotesInput].forEach(function (inp) {
                            if (!inp) return;
                            inp.addEventListener('keydown', function (e) {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    if (addToCartBtn) addToCartBtn.click();
                                }
                            });
                        });

                        // رسم وتحديث السلة
                        function renderCart() {
                            if (!cartTbody || !cartHiddenInputs) return;

                            if (cartItems.length === 0) {
                                if (cartEmptyState) cartEmptyState.style.display = 'block';
                                if (cartTableWrapper) cartTableWrapper.style.display = 'none';
                                if (cartBadgeCount) cartBadgeCount.textContent = '0';
                                if (cartSummaryItems) cartSummaryItems.textContent = '0';
                                if (cartSummaryPieces) cartSummaryPieces.textContent = '0';
                                cartTbody.innerHTML = '';
                                cartHiddenInputs.innerHTML = '';
                                return;
                            }

                            if (cartEmptyState) cartEmptyState.style.display = 'none';
                            if (cartTableWrapper) cartTableWrapper.style.display = 'block';
                            if (cartBadgeCount) cartBadgeCount.textContent = cartItems.length;
                            if (cartSummaryItems) cartSummaryItems.textContent = cartItems.length;

                            const totalPieces = cartItems.reduce((sum, it) => sum + (parseInt(it.qty) || 0), 0);
                            if (cartSummaryPieces) cartSummaryPieces.textContent = totalPieces;

                            let rowsHtml = '';
                            let hiddenHtml = '';

                            cartItems.forEach(function (item, idx) {
                                rowsHtml += `
                                    <tr>
                                        <td class="text-muted fw-bold">${idx + 1}</td>
                                        <td class="text-start">
                                            <div class="fw-bold text-dark">${escapeHtml(item.name)}</div>
                                            <div class="text-muted mt-0.5" style="font-size: 0.72rem;">
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border me-1" style="font-size: 0.68rem;">${escapeHtml(item.cat)}</span>
                                                <span><i class="fas fa-barcode me-1"></i>${escapeHtml(item.lot)}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border px-2 py-1">
                                                ${item.max} ${escapeHtml(item.unit)}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm justify-content-center mx-auto" style="max-width: 130px;">
                                                <input type="number" class="form-control form-control-sm text-center sup-cart-qty-input rounded-start-2 fw-bold" 
                                                       data-idx="${idx}" min="1" max="${item.max}" value="${item.qty}">
                                                <span class="input-group-text rounded-end-2 small px-1 text-muted" style="font-size: 0.75rem;">${escapeHtml(item.unit)}</span>
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <input type="text" class="form-control form-control-sm sup-cart-timing-input rounded-2 mb-1" 
                                                   data-idx="${idx}" placeholder="{{ __('سبب / مكان الاستخدام...') }}" value="${escapeHtml(item.timing || '')}">
                                            <input type="text" class="form-control form-control-sm sup-cart-notes-input rounded-2" 
                                                   data-idx="${idx}" placeholder="{{ __('ملاحظات...') }}" value="${escapeHtml(item.notes || '')}">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle p-1 sup-cart-remove-btn" 
                                                   data-idx="${idx}" style="width: 30px; height: 30px;" title="{{ __('حذف من القائمة') }}">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `;

                                hiddenHtml += `
                                    <input type="hidden" name="items[${idx}][batch_id]" value="${item.batchId}">
                                    <input type="hidden" name="items[${idx}][quantity]" id="sup_hidden_qty_${idx}" value="${item.qty}">
                                    <input type="hidden" name="items[${idx}][timing]" id="sup_hidden_timing_${idx}" value="${escapeHtml(item.timing || '')}">
                                    <input type="hidden" name="items[${idx}][admin_notes]" id="sup_hidden_notes_${idx}" value="${escapeHtml(item.notes || '')}">
                                    <input type="hidden" name="items[${idx}][expected_type]" value="supply">
                                `;
                            });

                            cartTbody.innerHTML = rowsHtml;
                            cartHiddenInputs.innerHTML = hiddenHtml;

                            // ربط أحداث التعديل المباشر داخل الجدول
                            cartTbody.querySelectorAll('.sup-cart-qty-input').forEach(function (inp) {
                                inp.addEventListener('input', function () {
                                    const idx = parseInt(this.getAttribute('data-idx'));
                                    let val = parseInt(this.value) || 1;
                                    const max = cartItems[idx].max;
                                    if (val < 1) {
                                        val = 1;
                                        this.value = 1;
                                    }
                                    if (val > max) {
                                        val = max;
                                        this.value = max;
                                        alert('{{ __('الحد الأقصى المتوفر في المخزن هو ') }}' + max);
                                    }
                                    cartItems[idx].qty = val;
                                    const hiddenQty = document.getElementById('sup_hidden_qty_' + idx);
                                    if (hiddenQty) hiddenQty.value = val;

                                    const newTotalPieces = cartItems.reduce((sum, it) => sum + (parseInt(it.qty) || 0), 0);
                                    if (cartSummaryPieces) cartSummaryPieces.textContent = newTotalPieces;
                                });
                            });

                            cartTbody.querySelectorAll('.sup-cart-timing-input').forEach(function (inp) {
                                inp.addEventListener('input', function () {
                                    const idx = parseInt(this.getAttribute('data-idx'));
                                    cartItems[idx].timing = this.value;
                                    const hiddenTiming = document.getElementById('sup_hidden_timing_' + idx);
                                    if (hiddenTiming) hiddenTiming.value = this.value;
                                });
                            });

                            cartTbody.querySelectorAll('.sup-cart-notes-input').forEach(function (inp) {
                                inp.addEventListener('input', function () {
                                    const idx = parseInt(this.getAttribute('data-idx'));
                                    cartItems[idx].notes = this.value;
                                    const hiddenNotes = document.getElementById('sup_hidden_notes_' + idx);
                                    if (hiddenNotes) hiddenNotes.value = this.value;
                                });
                            });

                            cartTbody.querySelectorAll('.sup-cart-remove-btn').forEach(function (btn) {
                                btn.addEventListener('click', function () {
                                    const idx = parseInt(this.getAttribute('data-idx'));
                                    cartItems.splice(idx, 1);
                                    renderCart();
                                });
                            });
                        }

                        // زر تفريغ كامل القائمة
                        if (cartClearAllBtn) {
                            cartClearAllBtn.addEventListener('click', function () {
                                if (cartItems.length === 0) return;
                                if (confirm('{{ __('هل تريد تفريغ قائمة المستلزمات بالكامل؟') }}')) {
                                    cartItems = [];
                                    renderCart();
                                }
                            });
                        }

                        // التحقق عند إرسال نموذج الحفظ والصرف
                        if (dispenseForm) {
                            dispenseForm.addEventListener('submit', function (e) {
                                if (cartItems.length === 0) {
                                    e.preventDefault();
                                    alert('{{ __('يرجى إضافة مستلزم واحد على الأقل إلى القائمة قبل الحفظ') }}');
                                    if (searchInp) searchInp.focus();
                                    return;
                                }

                                if (cartSaveBtn) {
                                    cartSaveBtn.disabled = true;
                                    cartSaveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> {{ __('جاري الحفظ والصرف...') }}';
                                }
                            });
                        }
                    });
                </script>
            @endif

            {{-- =============================================================== --}}
            {{-- SPECIAL SUB-VIEW FOR FOLLOW-UP TAB --}}
            {{-- =============================================================== --}}
            @if($activeTab === 'follow_up')
                <div class="p-4">
                    {{-- Form to add follow up entry --}}
                    @if($canWriteCurrentTab)
                        <div class="card rounded-4 border shadow-sm p-3 mb-4 bg-light bg-opacity-25">
                            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                                <i class="fas fa-notes-medical text-primary"></i>
                                <span>{{ __("إضافة قيد متابعة سريري جديد (Daily Follow-Up Sheet)") }}</span>
                            </h6>
                            <form action="{{ route('clinical-station.follow-ups.store', $surgery) }}" method="POST">
                                @csrf
                                <input type="hidden" name="phase" value="{{ $currentPhase }}">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-dark">{{ __("تاريخ المرور والمتابعة:") }}</label>
                                        <input type="date" name="follow_up_date" class="form-control form-control-sm rounded-3" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-dark">{{ __("الجولة / الدورية:") }}</label>
                                        <select name="session" class="form-select form-select-sm rounded-3">
                                            <option value="morning">{{ __("جولة صباحية (Morning Session)") }}</option>
                                            <option value="evening">{{ __("جولة مسائية (Evening Session)") }}</option>
                                        </select>
                                    </div>

                                    {{-- Clinical Checkboxes --}}
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-dark d-flex align-items-center gap-1.5 mb-2">
                                            <i class="fas fa-stethoscope text-primary"></i>
                                            <span>{{ __("العلامات والتقييم السريري (Clinical Assessment Checkboxes):") }}</span>
                                        </label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <!-- 1. Oriented -->
                                            <label class="clinical-check-pill d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border border-secondary-subtle bg-white cursor-pointer shadow-xs user-select-none transition-all">
                                                <input type="checkbox" name="clinical_signs[]" value="oriented" class="form-check-input clinical-sign-checkbox m-0">
                                                <span class="fw-bold text-dark">Oriented</span>
                                                <span class="sign-status-badge badge bg-danger text-white px-2.5 py-1 fw-bold shadow-xs">
                                                    <i class="fas fa-minus-circle me-1"></i> Negative
                                                </span>
                                            </label>

                                            <!-- 2. Clear chest -->
                                            <label class="clinical-check-pill d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border border-secondary-subtle bg-white cursor-pointer shadow-xs user-select-none transition-all">
                                                <input type="checkbox" name="clinical_signs[]" value="clear_chest" class="form-check-input clinical-sign-checkbox m-0">
                                                <span class="fw-bold text-dark">Clear chest</span>
                                                <span class="sign-status-badge badge bg-danger text-white px-2.5 py-1 fw-bold shadow-xs">
                                                    <i class="fas fa-minus-circle me-1"></i> Negative
                                                </span>
                                            </label>

                                            <!-- 3. Soft abdomen -->
                                            <label class="clinical-check-pill d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border border-secondary-subtle bg-white cursor-pointer shadow-xs user-select-none transition-all">
                                                <input type="checkbox" name="clinical_signs[]" value="soft_abdomen" class="form-check-input clinical-sign-checkbox m-0">
                                                <span class="fw-bold text-dark">Soft abdomen</span>
                                                <span class="sign-status-badge badge bg-danger text-white px-2.5 py-1 fw-bold shadow-xs">
                                                    <i class="fas fa-minus-circle me-1"></i> Negative
                                                </span>
                                            </label>

                                            <!-- 4. Urine output -->
                                            <label class="clinical-check-pill d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border border-secondary-subtle bg-white cursor-pointer shadow-xs user-select-none transition-all">
                                                <input type="checkbox" name="clinical_signs[]" value="urine_output" class="form-check-input clinical-sign-checkbox m-0">
                                                <span class="fw-bold text-dark">Urine output</span>
                                                <span class="sign-status-badge badge bg-danger text-white px-2.5 py-1 fw-bold shadow-xs">
                                                    <i class="fas fa-minus-circle me-1"></i> Negative
                                                </span>
                                            </label>

                                            <!-- 5. Yellowish discolouration -->
                                            <label class="clinical-check-pill d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border border-secondary-subtle bg-white cursor-pointer shadow-xs user-select-none transition-all">
                                                <input type="checkbox" name="clinical_signs[]" value="yellowish_discolouration" class="form-check-input clinical-sign-checkbox m-0">
                                                <span class="fw-bold text-dark">Yellowish discolouration</span>
                                                <span class="sign-status-badge badge bg-danger text-white px-2.5 py-1 fw-bold shadow-xs">
                                                    <i class="fas fa-minus-circle me-1"></i> Negative
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-dark">
                                            <i class="fas fa-comment-medical text-secondary me-1"></i> {{ __("Others (أخرى / ملاحظات إضافية وتوصيات المقيم):") }}
                                        </label>
                                        <textarea name="other_notes" class="form-control rounded-3" rows="2" placeholder="{{ __('سجل أي علامات سريرية أخرى، تقييم الجرح، فحص السوائل، توجيهات اليوم...') }}"></textarea>
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-xs">
                                            <i class="fas fa-save me-1"></i> {{ __("حفظ قيد المتابعة") }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif

                    {{-- Follow-Up Timeline --}}
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="fas fa-stream text-primary"></i>
                        <span>{{ __("سجل المتابعات الدورية السابقة") }}</span>
                    </h6>
                    @forelse($followUps as $f)
                        <div class="timeline-card p-3 mb-3 bg-white rounded-3 border shadow-xs">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="text-dark">{{ $f->session === 'morning' ? __('جولة صباحية (Morning Session)') : ($f->session === 'evening' ? __('جولة مسائية (Evening Session)') : $f->session) }}</strong>
                                    <span class="text-muted small">({{ $f->follow_up_date?->format('Y-m-d') }})</span>
                                </div>
                                <span class="badge bg-primary bg-opacity-10 text-primary border small">
                                    <i class="fas fa-user-md me-1"></i> {{ $f->resident_name ?: ($f->resident?->user?->name ?? __('كادر المتابعة')) }}
                                </span>
                            </div>

                            {{-- Structured Clinical Signs Badges (Positive & Negative) --}}
                            @php
                                $positiveSigns = $f->positive_signs;
                                $negativeSigns = $f->negative_signs;
                                $definitions = \App\Models\ResidentStationFollowUp::availableClinicalSigns();
                            @endphp
                            @if(!empty($positiveSigns) || !empty($f->clinical_signs))
                                <div class="mb-2">
                                    @if(!empty($positiveSigns))
                                        <div class="d-flex flex-wrap align-items-center gap-1.5 mb-1.5">
                                            <span class="badge bg-success text-white small fw-bold px-2 py-0.5"><i class="fas fa-plus-circle me-1"></i> Positive:</span>
                                            @foreach($positiveSigns as $sign)
                                                @php $def = $definitions[$sign] ?? null; @endphp
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2.5 py-1 fw-bold">
                                                    <i class="fas {{ $def['icon'] ?? 'fa-check-circle' }} me-1"></i> {{ $def['en'] ?? $sign }} (Positive)
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(!empty($negativeSigns))
                                        <div class="d-flex flex-wrap align-items-center gap-1.5 mb-1.5">
                                            <span class="badge bg-danger text-white small fw-bold px-2 py-0.5"><i class="fas fa-minus-circle me-1"></i> Negative:</span>
                                            @foreach($negativeSigns as $sign)
                                                @php $def = $definitions[$sign] ?? null; @endphp
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2.5 py-1 fw-bold">
                                                    <i class="fas fa-times-circle text-danger me-1"></i> {{ $def['en'] ?? $sign }} (Negative)
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Notes / Others --}}
                            @if($f->other_notes)
                                <div class="text-dark small mb-1" style="font-size: 0.92rem; line-height: 1.6;">
                                    <strong>{{ __("أخرى (Others):") }}</strong> {!! nl2br(e($f->other_notes)) !!}
                                </div>
                            @elseif(!empty($f->notes) && empty($f->clinical_signs))
                                <div class="text-dark mb-1" style="font-size: 0.92rem; line-height: 1.6;">
                                    {!! nl2br(e($f->notes)) !!}
                                </div>
                            @endif

                            <div class="text-muted mt-2 pt-1 border-top" style="font-size: 0.72rem;">
                                {{ __("وُثق بتاريخ: ") }}{{ $f->created_at->format('Y-m-d h:i A') }}
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-light text-center border p-4 rounded-3 text-muted">
                            <i class="fas fa-clipboard-list fa-2x mb-2 d-block text-secondary"></i>
                            {{ __("لا توجد قيود متابعة مسجلة للمريض حتى الآن.") }}
                        </div>
                    @endforelse

                    <script>
                        (function() {
                            function initClinicalPills() {
                                var checkboxes = document.querySelectorAll('.clinical-sign-checkbox');
                                checkboxes.forEach(function(chk) {
                                    function updatePill(checkbox) {
                                        var pill = checkbox.closest('.clinical-check-pill');
                                        if (!pill) return;
                                        var badge = pill.querySelector('.sign-status-badge');
                                        if (checkbox.checked) {
                                            pill.classList.remove('bg-white', 'border-secondary-subtle');
                                            pill.classList.add('bg-success', 'bg-opacity-10', 'border-success');
                                            if (badge) {
                                                badge.className = 'sign-status-badge badge bg-success text-white px-2.5 py-1 fw-bold shadow-xs';
                                                badge.innerHTML = '<i class="fas fa-plus-circle me-1"></i> Positive';
                                            }
                                        } else {
                                            pill.classList.remove('bg-success', 'bg-opacity-10', 'border-success');
                                            pill.classList.add('bg-white', 'border-secondary-subtle');
                                            if (badge) {
                                                badge.className = 'sign-status-badge badge bg-danger text-white px-2.5 py-1 fw-bold shadow-xs';
                                                badge.innerHTML = '<i class="fas fa-minus-circle me-1"></i> Negative';
                                            }
                                        }
                                    }
                                    chk.addEventListener('change', function() {
                                        updatePill(this);
                                    });
                                    updatePill(chk);
                                });
                            }
                            if (document.readyState === 'loading') {
                                document.addEventListener('DOMContentLoaded', initClinicalPills);
                            } else {
                                initClinicalPills();
                            }
                        })();
                    </script>
                </div>
            @endif

            {{-- =============================================================== --}}
            {{-- SPECIAL SUB-VIEW FOR MEDICAL CONSULTATIONS & PHYSIOTHERAPY TAB --}}
            {{-- =============================================================== --}}
            @if($activeTab === 'consultations')
                @php
                    $allConsultations = $surgery->consultations;
                    $activeConsultations = $allConsultations->where('status', '!=', 'cancelled')->sortByDesc('created_at');
                    $cancelledConsultations = $allConsultations->where('status', 'cancelled')->sortByDesc('created_at');
                    $pendingCount = $activeConsultations->where('status', 'pending')->count();
                    $completedCount = $activeConsultations->where('status', 'completed')->count();
                    $cancelledCount = $cancelledConsultations->count();

                    $physioOrders = $surgery->extraServices->filter(function($srv) {
                        return str_contains($srv->name, 'فيزيائ') 
                            || str_contains($srv->name, 'طبيعي') 
                            || str_contains(strtolower($srv->name), 'physio')
                            || ($srv->service && ($srv->service->code === 'PHYS-01' || str_contains($srv->service->name, 'فيزيائ')));
                    });
                @endphp

                <div class="p-4">
                    {{-- 1. Sub-navigation Pills (فصل الاستشارات الطبية عن العلاج الفيزيائي والأرشيف) --}}
                    <ul class="nav nav-pills gap-2 mb-4 p-2 bg-light rounded-4 border align-items-center" id="consultSubTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill fw-bold px-3 py-2 d-flex align-items-center gap-2" 
                                    id="pill-medical-consult-tab" data-bs-toggle="pill" data-bs-target="#pill-medical-consult" 
                                    type="button" role="tab" aria-controls="pill-medical-consult" aria-selected="true">
                                <i class="fas fa-user-md text-primary"></i>
                                <span>{{ __("استشارات الأطباء الاختصاص") }}</span>
                                <span class="badge {{ $pendingCount > 0 ? 'bg-warning text-dark' : 'bg-primary' }} rounded-pill px-2">
                                    {{ $activeConsultations->count() }}
                                </span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold px-3 py-2 d-flex align-items-center gap-2" 
                                    id="pill-physiotherapy-tab" data-bs-toggle="pill" data-bs-target="#pill-physiotherapy" 
                                    type="button" role="tab" aria-controls="pill-physiotherapy" aria-selected="false">
                                <i class="fas fa-hand-holding-medical text-teal" style="color: #0d9488;"></i>
                                <span>{{ __("العلاج الفيزيائي والتأهيل") }}</span>
                                <span class="badge text-white rounded-pill px-2" style="background-color: #0d9488;">
                                    {{ $physioOrders->count() }}
                                </span>
                            </button>
                        </li>
                        @if($cancelledCount > 0)
                            <li class="nav-item ms-auto" role="presentation">
                                <button class="nav-link rounded-pill text-muted px-3 py-2 d-flex align-items-center gap-1.5" 
                                        id="pill-cancelled-consult-tab" data-bs-toggle="pill" data-bs-target="#pill-cancelled-consult" 
                                        type="button" role="tab" aria-controls="pill-cancelled-consult" aria-selected="false"
                                        title="{{ __('عرض الاستشارات التي تم إلغاؤها سابقاً') }}">
                                    <i class="fas fa-archive"></i>
                                    <span class="small">{{ __("أرشيف الملغاة") }}</span>
                                    <span class="badge bg-secondary rounded-pill">{{ $cancelledCount }}</span>
                                </button>
                            </li>
                        @endif
                    </ul>

                    {{-- 2. Tab Content Panes --}}
                    <div class="tab-content" id="consultSubTabsContent">

                        {{-- ============================================================== --}}
                        {{-- PANE 1: MEDICAL CONSULTATIONS (الاستشارات الطبية التخصصية) --}}
                        {{-- ============================================================== --}}
                        <div class="tab-pane fade show active" id="pill-medical-consult" role="tabpanel" aria-labelledby="pill-medical-consult-tab">
                            
                            {{-- Action Bar & Stats --}}
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 p-3 bg-white rounded-4 border shadow-sm">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-md rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                        <i class="fas fa-stethoscope fa-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">{{ __("استشارات الأطباء السريرية للعملية") }}</h6>
                                        <small class="text-muted">{{ __("طلب استشارة أخصائي، الردود الطبية، والتوصيات السريرية") }}</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-warning text-dark px-2.5 py-1.5 rounded-pill small">
                                        <i class="fas fa-clock me-1"></i>{{ __("معلقة:") }} {{ $pendingCount }}
                                    </span>
                                    <span class="badge bg-success px-2.5 py-1.5 rounded-pill small">
                                        <i class="fas fa-check-circle me-1"></i>{{ __("موثقة:") }} {{ $completedCount }}
                                    </span>
                                    @if($canWriteCurrentTab)
                                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-xs ms-2"
                                                data-bs-toggle="collapse" data-bs-target="#newConsultationCollapse" aria-expanded="{{ $activeConsultations->isEmpty() ? 'true' : 'false' }}">
                                            <i class="fas fa-plus me-1"></i> {{ __("طلب استشارة جديدة") }}
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Request Form (Collapsible, open by default only if no active consultations exist) --}}
                            @if($canWriteCurrentTab)
                                <div class="collapse {{ $activeConsultations->isEmpty() ? 'show' : '' }} mb-4" id="newConsultationCollapse">
                                    <div class="card rounded-4 border shadow-sm p-4 bg-white" style="border-top: 3px solid #0284c7 !important;">
                                        <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                                            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                                <i class="fas fa-paper-plane text-primary"></i>
                                                <span>{{ __("إرسال طلب استشارة طبية جديدة") }}</span>
                                            </h6>
                                            <button type="button" class="btn-close btn-sm" data-bs-toggle="collapse" data-bs-target="#newConsultationCollapse" aria-label="Close"></button>
                                        </div>

                                        <form action="{{ route('clinical-station.consultations.store', $surgery) }}" method="POST">
                                            @csrf
                                            <div class="row g-3">
                                                {{-- Doctor Selection --}}
                                                <div class="col-md-5">
                                                    <label for="consultation_doctor_name" class="form-label small fw-bold text-dark">
                                                        <i class="fas fa-user-doctor text-primary me-1"></i> {{ __("الطبيب الاستشاري المطلوب:") }} <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="hidden" id="consultation_doctor_id" name="doctor_id" value="{{ old('doctor_id') }}" required>
                                                    <div class="position-relative" id="consultantAutocompleteWrapper">
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-light text-primary"><i class="fas fa-user-md"></i></span>
                                                            <input type="text" id="consultation_doctor_name"
                                                                   class="form-control rounded-0"
                                                                   autocomplete="off"
                                                                   placeholder="{{ __('اختيار أو بحث طبيب استشاري متاح اليوم...') }}"
                                                                   value="">
                                                            <button type="button" class="btn btn-outline-secondary d-none" id="clearConsultDocBtn" title="{{ __("مسح") }}">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-primary" id="toggleConsultDocMenuBtn" title="{{ __("عرض القائمة") }}">
                                                                <i class="fas fa-chevron-down"></i>
                                                            </button>
                                                        </div>
                                                        <div id="consultantDoctorsMenu" class="list-group position-absolute w-100 shadow-lg mt-1" style="z-index: 1065; max-height: 240px; overflow-y: auto; display: none; font-size: 0.85rem;"></div>
                                                    </div>
                                                </div>

                                                {{-- Specialty / Clinic --}}
                                                <div class="col-md-3">
                                                    <label class="form-label small fw-bold text-dark">
                                                        <i class="fas fa-hospital-user text-secondary me-1"></i> {{ __("التخصص / العيادة:") }}
                                                    </label>
                                                    <input type="text" name="specialty" id="consultationSpecialtyInput" class="form-control rounded-3" 
                                                           value="{{ old('specialty') }}" placeholder="{{ __('يتم تحديده تلقائياً') }}">
                                                </div>

                                                {{-- Priority --}}
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-bold text-dark">
                                                        <i class="fas fa-flag text-warning me-1"></i> {{ __("الأولوية:") }} <span class="text-danger">*</span>
                                                    </label>
                                                    <select name="priority" class="form-select rounded-3" required>
                                                        <option value="routine" {{ old('priority', 'routine') === 'routine' ? 'selected' : '' }}>{{ __("اعتيادية (Routine)") }}</option>
                                                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>{{ __("عاجلة (Urgent)") }}</option>
                                                        <option value="stat" {{ old('priority') === 'stat' ? 'selected' : '' }}>{{ __("طارئة جداً (STAT)") }}</option>
                                                    </select>
                                                </div>

                                                {{-- Fee --}}
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-bold text-dark">
                                                        <i class="fas fa-receipt text-success me-1"></i> {{ __("الأجر (د.ع):") }}
                                                    </label>
                                                    <input type="number" step="1000" min="0" name="fee" id="consultationFeeInput" class="form-control rounded-3 fw-bold text-success" 
                                                           value="{{ old('fee', 50000) }}">
                                                    <div class="form-text text-muted" style="font-size: 10px;">
                                                        {{ __('مبدئياً 50,000 د.ع (كامل على المريض كالطوارئ)') }}
                                                    </div>
                                                </div>

                                                {{-- Reason --}}
                                                <div class="col-12">
                                                    <label class="form-label small fw-bold text-dark">
                                                        <i class="fas fa-clipboard-question text-info me-1"></i> {{ __("سبب طلب الاستشارة والتشخيص المبدئي:") }} <span class="text-danger">*</span>
                                                    </label>
                                                    <textarea name="reason" class="form-control rounded-3" rows="2" 
                                                              placeholder="{{ __('اكتب سبب طلب الاستشارة، الأعراض، والمطلوب من الطبيب الاستشاري بدقة...') }}" required>{{ old('reason') }}</textarea>
                                                </div>

                                                {{-- Actions --}}
                                                <div class="col-12 text-end">
                                                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3 me-2" data-bs-toggle="collapse" data-bs-target="#newConsultationCollapse">
                                                        {{ __("إلغاء") }}
                                                    </button>
                                                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-xs">
                                                        <i class="fas fa-paper-plane me-1"></i> {{ __("إرسال الطلب للاستشاري والكاشير") }}
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif

                            {{-- Active Consultations List --}}
                            @if($activeConsultations->isEmpty())
                                <div class="card rounded-4 border shadow-sm p-5 text-center bg-white">
                                    <div class="avatar-lg rounded-circle bg-light text-muted d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                                        <i class="fas fa-user-md fa-2x opacity-50"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">{{ __("لا توجد استشارات طبية نشطة حالياً") }}</h6>
                                    <p class="text-muted small mb-3">
                                        {{ __("لم يتم تسجيل أي طلب استشارة نشط لهذه العملية، أو تم إلغاء جميع الطلبات السابقة.") }}
                                    </p>
                                    @if($canWriteCurrentTab)
                                        <div>
                                            <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-xs" 
                                                    data-bs-toggle="collapse" data-bs-target="#newConsultationCollapse">
                                                <i class="fas fa-plus me-1"></i> {{ __("إرسال طلب استشارة جديد الآن") }}
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="d-flex flex-column gap-3">
                                    @foreach($activeConsultations as $consultation)
                                        <div class="card rounded-4 border shadow-sm overflow-hidden bg-white">
                                            {{-- Header --}}
                                            <div class="card-header bg-light border-bottom py-2.5 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="badge {{ $consultation->status_badge_class }} px-2.5 py-1 rounded-pill">
                                                        {{ $consultation->status_label }}
                                                    </span>
                                                    <span class="badge {{ $consultation->priority_badge_class }} px-2.5 py-1 rounded-pill">
                                                        {{ $consultation->priority_label }}
                                                    </span>
                                                    <strong class="text-dark fs-6">
                                                        <i class="fas fa-user-doctor text-primary me-1"></i> {{ __('د.') }} {{ $consultation->doctor?->user?->name ?? __('طبيب غير محدد') }}
                                                    </strong>
                                                    <span class="badge bg-light text-primary border">{{ $consultation->specialty }}</span>
                                                </div>

                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    @if($consultation->isPaid())
                                                        <span class="badge bg-success-subtle text-success border border-success rounded-pill px-2.5 py-1">
                                                            <i class="fas fa-check-circle me-1"></i>{{ number_format($consultation->fee, 0) }} د.ع ({{ __('مسدد') }})
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning-subtle text-dark border border-warning rounded-pill px-2.5 py-1" title="{{ __('خارج الضمان - يسدد نقداً لدى الكاشير') }}">
                                                            <i class="fas fa-coins me-1 text-warning"></i>{{ number_format($consultation->fee > 0 ? $consultation->fee : 50000, 0) }} د.ع ({{ __('معلق بالكاشير') }})
                                                        </span>
                                                    @endif
                                                    <small class="text-muted">
                                                        <i class="far fa-clock me-1"></i>{{ $consultation->created_at?->diffForHumans() }}
                                                    </small>
                                                </div>
                                            </div>

                                            {{-- Body --}}
                                            <div class="card-body p-3">
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <div class="p-2.5 bg-light rounded-3 border">
                                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                                <small class="text-muted fw-bold">
                                                                    <i class="fas fa-comment-medical text-primary me-1"></i>{{ __("سبب الاستشارة وسؤال المقيم:") }}
                                                                </small>
                                                                <small class="text-muted">
                                                                    {{ __("الطالب:") }} <strong>{{ $consultation->requestedBy?->name ?? __('المقيم') }}</strong>
                                                                </small>
                                                            </div>
                                                            <div class="text-dark small" style="white-space: pre-wrap;">{{ $consultation->reason }}</div>
                                                        </div>
                                                    </div>

                                                    {{-- If Completed: Show Findings & Recommendations --}}
                                                    @if($consultation->status === 'completed')
                                                        <div class="col-12">
                                                            <div class="p-3 rounded-3 border bg-success bg-opacity-10 border-success-subtle">
                                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                                    <span class="fw-bold text-success d-flex align-items-center gap-1.5">
                                                                        <i class="fas fa-check-circle"></i>
                                                                        <span>{{ __("تقرير ورأي الطبيب الاستشاري (Consultant Findings):") }}</span>
                                                                    </span>
                                                                    @if($consultation->responded_at)
                                                                        <small class="text-muted">
                                                                            <i class="far fa-clock me-1"></i>{{ $consultation->responded_at->format('Y-m-d h:i A') }}
                                                                            ({{ __("بواسطة:") }} {{ $consultation->respondedBy?->name ?? __('الاستشاري') }})
                                                                        </small>
                                                                    @endif
                                                                </div>
                                                                <p class="mb-2 text-dark small" style="white-space: pre-wrap;">{{ $consultation->consultant_response }}</p>

                                                                @if($consultation->consultant_recommendations)
                                                                    <div class="border-top border-success-subtle pt-2 mt-2">
                                                                        <strong class="text-success small d-block mb-1">
                                                                            <i class="fas fa-clipboard-list me-1"></i>{{ __("التوصيات والخطة العلاجية:") }}
                                                                        </strong>
                                                                        <p class="mb-0 text-dark small" style="white-space: pre-wrap;">{{ $consultation->consultant_recommendations }}</p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- Actions for Pending Consultation --}}
                                                @if($consultation->status === 'pending')
                                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 pt-2 border-top">
                                                        <div class="text-muted small">
                                                            <i class="fas fa-hourglass-half text-warning me-1"></i> {{ __("الطلب معلق بانتظار مرور الطبيب الاستشاري وتوثيق الرد السريري.") }}
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold"
                                                                    data-bs-toggle="collapse" data-bs-target="#respondConsultationCollapse{{ $consultation->id }}">
                                                                <i class="fas fa-stethoscope me-1"></i> {{ __("توثيق رد الاستشاري") }}
                                                            </button>

                                                            <form action="{{ route('clinical-station.consultations.cancel', [$surgery, $consultation]) }}" method="POST"
                                                                  onsubmit="return confirm('{{ __('هل أنت متأكد من إلغاء طلب الاستشارة هذا؟') }}');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                                    <i class="fas fa-times me-1"></i> {{ __("إلغاء الطلب") }}
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>

                                                    {{-- Collapse Response Form --}}
                                                    <div class="collapse mt-3" id="respondConsultationCollapse{{ $consultation->id }}">
                                                        <div class="card card-body bg-light border-success-subtle rounded-3 p-3">
                                                            <h6 class="fw-bold text-success mb-2">
                                                                <i class="fas fa-notes-medical me-1"></i> {{ __("توثيق رد وتوصيات الاستشاري: د. ") }}{{ $consultation->doctor?->user?->name }}
                                                            </h6>
                                                            <form action="{{ route('clinical-station.consultations.respond', [$surgery, $consultation]) }}" method="POST">
                                                                @csrf
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-bold text-dark">
                                                                        {{ __("رد ومعاينة الاستشاري (Clinical Findings & Opinion): ") }}<span class="text-danger">*</span>
                                                                    </label>
                                                                    <textarea name="consultant_response" class="form-control rounded-3" rows="3" 
                                                                              placeholder="{{ __('اكتب التقرير السريري والرأي الطبي للاستشاري...') }}" required></textarea>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-bold text-dark">
                                                                        {{ __("الخطة والتوصيات العلاجية (Recommendations & Treatment Plan):") }}
                                                                    </label>
                                                                    <textarea name="consultant_recommendations" class="form-control rounded-3" rows="2" 
                                                                              placeholder="{{ __('توصيات الأدوية، الإجراءات الإضافية، المتابعة...') }}"></textarea>
                                                                </div>
                                                                <div class="text-end">
                                                                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">
                                                                        <i class="fas fa-check-circle me-1"></i> {{ __("اعتماد وحفظ رد الاستشارة") }}
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- ============================================================== --}}
                        {{-- PANE 2: PHYSIOTHERAPY (العلاج الفيزيائي والتأهيل) --}}
                        {{-- ============================================================== --}}
                        <div class="tab-pane fade" id="pill-physiotherapy" role="tabpanel" aria-labelledby="pill-physiotherapy-tab">
                            <div class="card rounded-4 border shadow-sm mb-4 bg-white overflow-hidden" style="border-color: #cbd5e1 !important;">
                                <div class="card-header bg-light border-bottom py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="rounded-circle p-2 d-inline-flex align-items-center justify-content-center text-white" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); width: 36px; height: 36px;">
                                            <i class="fas fa-hand-holding-medical"></i>
                                        </span>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">{{ __("طلب خدمة علاج فيزيائي وتأهيل سريري (Physiotherapy Request)") }}</h6>
                                            <small class="text-muted">{{ __("طلب مباشر من الطبيب المقيم يُرحل فوراً إلى كشف حساب الكاشير بسعر مبدئي 60,000 د.ع قابل للتعديل") }}</small>
                                        </div>
                                    </div>
                                    <span class="badge rounded-pill px-3 py-1.5" style="background: #ccfbf1; color: #0f766e; font-weight: 700;">
                                        <i class="fas fa-tag me-1"></i> {{ __("تسعير معتمد: 60,000 د.ع للجلسة (تُسدد وتعدل لدى الكاشير)") }}
                                    </span>
                                </div>
                                <div class="card-body p-4">
                                    @if($canWriteCurrentTab)
                                        <form action="{{ route('clinical-station.physiotherapy.store', $surgery) }}" method="POST" class="mb-4 p-3 rounded-3" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                                            @csrf
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-3">
                                                    <label class="form-label small fw-bold text-dark">
                                                        <i class="fas fa-list-ol text-primary me-1"></i> {{ __("عدد الجلسات:") }} <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="number" name="sessions" class="form-control rounded-3 fw-bold text-center" min="1" max="30" value="1" required id="physioSessionsInput">
                                                </div>
                                                <div class="col-md-7">
                                                    <label class="form-label small fw-bold text-dark">
                                                        <i class="fas fa-clipboard-check text-info me-1"></i> {{ __("التوصية السريرية والتشخيص (ملاحظات الجلسة):") }}
                                                    </label>
                                                    <input type="text" name="notes" class="form-control rounded-3" placeholder="{{ __('مثال: تأهيل حركي وتمارين تنفسية بعد العملية، تخفيف وذمة...') }}" value="{{ __('جلسة علاج فيزيائي وتأهيل بتوصية الطبيب المقيم') }}">
                                                </div>
                                                <div class="col-md-2 text-end">
                                                    <button type="submit" class="btn text-white w-100 rounded-pill py-2 fw-bold shadow-sm" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);">
                                                        <i class="fas fa-paper-plane me-1"></i> {{ __("إرسال للكاشير") }}
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    @endif

                                    {{-- سجل طلبات العلاج الفيزيائي الحالية للعملية --}}
                                    @if($physioOrders->isEmpty())
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-spa fa-2x opacity-25 mb-2 d-block"></i>
                                            <span class="small">{{ __("لم يتم تسجيل أي طلب علاج فيزيائي لهذه العملية حتى الآن.") }}</span>
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>{{ __("الخدمة والبيان") }}</th>
                                                        <th class="text-center">{{ __("الجلسات") }}</th>
                                                        <th class="text-end">{{ __("سعر الجلسة") }}</th>
                                                        <th class="text-end">{{ __("المبلغ المطلوب") }}</th>
                                                        <th class="text-center">{{ __("حالة الكاشير") }}</th>
                                                        <th>{{ __("التأريخ والطالب") }}</th>
                                                        @if($canWriteCurrentTab)
                                                            <th class="text-center" style="width: 80px;">{{ __("إجراء") }}</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($physioOrders as $pOrder)
                                                        <tr>
                                                            <td>
                                                                <div class="fw-bold text-dark d-flex align-items-center gap-1.5">
                                                                    <i class="fas fa-spa text-teal" style="color: #0d9488;"></i>
                                                                    <span>{{ $pOrder->name }}</span>
                                                                </div>
                                                                @if($pOrder->notes)
                                                                    <small class="text-muted d-block mt-0.5"><i class="fas fa-info-circle me-1"></i>{{ $pOrder->notes }}</small>
                                                                @endif
                                                            </td>
                                                            <td class="text-center fw-bold">{{ $pOrder->quantity }}</td>
                                                            <td class="text-end">{{ number_format($pOrder->price, 0) }} {{ __("د.ع") }}</td>
                                                            <td class="text-end fw-bold text-success">{{ number_format($pOrder->total_amount, 0) }} {{ __("د.ع") }}</td>
                                                            <td class="text-center">
                                                                @if($pOrder->payment_status === 'paid')
                                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill">
                                                                        <i class="fas fa-check-circle me-1"></i> {{ __("مسدد في الكاشير") }}
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50 px-2.5 py-1 rounded-pill" title="{{ __('يمكن للكاشير تعديل السعر عند السداد') }}">
                                                                        <i class="fas fa-clock me-1 text-warning"></i> {{ __("معلق بالكاشير (قابل للتعديل)") }}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="small">
                                                                <div>{{ $pOrder->created_at ? $pOrder->created_at->format('Y-m-d H:i') : '-' }}</div>
                                                                <small class="text-muted">{{ $pOrder->createdBy?->name ?? __('النظام') }}</small>
                                                            </td>
                                                            @if($canWriteCurrentTab)
                                                                <td class="text-center">
                                                                    @if($pOrder->payment_status !== 'paid')
                                                                        <form action="{{ route('clinical-station.physiotherapy.cancel', [$surgery, $pOrder]) }}" method="POST" onsubmit="return confirm('{{ __('هل أنت متأكد من إلغاء طلب العلاج الفيزيائي هذا؟') }}');">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-1 shadow-xs" title="{{ __('إلغاء الطلب') }}">
                                                                                <i class="fas fa-trash-alt me-1"></i> {{ __("إلغاء") }}
                                                                            </button>
                                                                        </form>
                                                                    @else
                                                                        <span class="text-muted small">-</span>
                                                                    @endif
                                                                </td>
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ============================================================== --}}
                        {{-- PANE 3: CANCELLED CONSULTATIONS ARCHIVE (أرشيف الطلبات الملغاة) --}}
                        {{-- ============================================================== --}}
                        @if($cancelledCount > 0)
                            <div class="tab-pane fade" id="pill-cancelled-consult" role="tabpanel" aria-labelledby="pill-cancelled-consult-tab">
                                <div class="card rounded-4 border shadow-sm p-4 bg-white">
                                    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-archive text-secondary"></i>
                                            <h6 class="fw-bold text-dark mb-0">{{ __("سجل الاستشارات الطبية الملغاة (Cancelled Requests)") }}</h6>
                                        </div>
                                        <span class="badge bg-secondary-subtle text-secondary border rounded-pill">{{ $cancelledCount }} {{ __("طلب ملغي") }}</span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>{{ __("الطبيب المطلوب") }}</th>
                                                    <th>{{ __("العيادة / التخصص") }}</th>
                                                    <th>{{ __("الأولوية والأجر") }}</th>
                                                    <th>{{ __("سبب الاستشارة الملغاة") }}</th>
                                                    <th>{{ __("تأريخ الطلب والإلغاء") }}</th>
                                                    <th class="text-center">{{ __("الحالة") }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($cancelledConsultations as $cConsult)
                                                    <tr class="opacity-75">
                                                        <td>
                                                            <strong class="text-dark">د. {{ $cConsult->doctor?->user?->name ?? __('طبيب استشاري') }}</strong>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-secondary border">{{ $cConsult->specialty }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="badge {{ $cConsult->priority_badge_class }} me-1">{{ $cConsult->priority_label }}</span>
                                                            <small class="text-muted">{{ number_format($cConsult->fee, 0) }} د.ع</small>
                                                        </td>
                                                        <td class="text-truncate" style="max-width: 250px;" title="{{ $cConsult->reason }}">
                                                            <span class="text-muted small">{{ Str::limit($cConsult->reason, 60) }}</span>
                                                        </td>
                                                        <td class="small text-muted">
                                                            <div>{{ $cConsult->created_at?->format('Y-m-d H:i') }}</div>
                                                            <div>{{ __("بواسطة:") }} {{ $cConsult->requestedBy?->name ?? __('النظام') }}</div>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1">
                                                                <i class="fas fa-ban me-1"></i> {{ __("ملغية") }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            @endif
        </div>
    @endif
</div>

{{-- Hidden Form for Delete Reading (Placed outside all parent forms) --}}
<form id="deleteReadingForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

{{-- Hidden Form for Delete Clinical Tests (Placed outside all parent forms to avoid form nesting) --}}
<form id="deleteClinicalTestForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

{{-- Scripts for Chart.js and Vitals Actions --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
window.confirmDeleteClinicalTest = function(actionUrl, confirmMsg) {
    if (!actionUrl) return;
    if (confirm(confirmMsg || '{{ __('هل أنت متأكد من الحذف؟') }}')) {
        const form = document.getElementById('deleteClinicalTestForm');
        if (form) {
            form.action = actionUrl;
            form.submit();
        }
    }
};
window.loadReadingIntoTopForm = function(reading) {
    if (!reading) return;

    // 1. تحديد معرف القراءة المراد تعديلها وتبديل وضع الصف الأول للتعديل
    const idInput = document.getElementById('editingReadingId');
    if (idInput) idInput.value = reading.id;

    const isNewInput = document.getElementById('isNewReadingInput');
    if (isNewInput) isNewInput.value = '0';

    const inputRow = document.getElementById('vitalsInputRow');
    if (inputRow) {
        inputRow.classList.remove('table-success');
        inputRow.classList.add('table-primary');
    }
    const modeCell = document.getElementById('row1ModeCell');
    if (modeCell) {
        modeCell.innerHTML = '<span class="badge bg-primary text-white rounded-pill px-2 py-1 d-inline-block"><i class="fas fa-pen me-1"></i> {{ __("قيد التعديل") }}</span><div class="text-primary mt-1" style="font-size: 0.66rem;">{{ __("تعديل قراءة سابقة") }}</div>';
    }
    const cancelRowBtn = document.getElementById('row1CancelEditBtn');
    if (cancelRowBtn) cancelRowBtn.classList.remove('d-none');

    // 2. تعبئة خانات العلامات الحيوية الـ 6 بالقيم
    const setGridVal = function(id, val) {
        const inp = document.getElementById(id);
        if (inp) {
            inp.value = (val !== null && val !== undefined) ? val : '';
            inp.removeAttribute('readonly');
            inp.removeAttribute('disabled');
            inp.classList.remove('bg-light');
            inp.style.border = '2px solid #2563eb';
            inp.style.backgroundColor = '#eff6ff';
        }
    };

    setGridVal('grid_bp', reading.bp);
    setGridVal('grid_temp', reading.temp);
    setGridVal('grid_pr', reading.pr);
    setGridVal('grid_rr', reading.rr);
    setGridVal('grid_spo2', reading.spo2);
    setGridVal('grid_rbs', reading.rbs);

    // تعبئة حقول السوائل الـ 8 (القراءة فقط لغير التمريض/الأدمن)
    const setFluidGridVal = function(id, val) {
        const inp = document.getElementById(id);
        if (!inp) return;
        inp.value = (val !== null && val !== undefined) ? val : '';
        if (inp.getAttribute('data-fluid-editable') === '1') {
            inp.removeAttribute('readonly');
            inp.removeAttribute('disabled');
            inp.classList.remove('bg-light');
            inp.style.border = '2px solid #2563eb';
            inp.style.backgroundColor = '#eff6ff';
        }
    };

    setFluidGridVal('grid_intake_iv_fluids', reading.intake_iv_fluids);
    setFluidGridVal('grid_intake_oral', reading.intake_oral);
    setFluidGridVal('grid_intake_blood', reading.intake_blood);
    setFluidGridVal('grid_output_urine', reading.output_urine);
    setFluidGridVal('grid_output_drain', reading.output_drain);
    if (reading.drains && Array.isArray(reading.drains) && reading.drains.length > 0) {
        window.currentReadingDrains = reading.drains.map(d => ({
            drain_number: d.drain_number,
            drain_label: d.drain_label || ('{{ __('درنقة') }} ' + d.drain_number),
            amount: parseFloat(d.amount) || 0
        }));
    } else {
        window.currentReadingDrains = [];
    }
    if (typeof window.updateDrainBadge === 'function') {
        window.updateDrainBadge();
    }
    setFluidGridVal('grid_output_gtube_ng', reading.output_gtube_ng);
    setFluidGridVal('grid_output_vomiting', reading.output_vomiting);
    setFluidGridVal('grid_output_stool', reading.output_stool);
    if (typeof window.updateLiveFluidBalance === 'function') {
        window.updateLiveFluidBalance();
    }

    @if(!empty($canManageClinicalExam))
        const examTextarea = document.querySelector('textarea[name="clinical_examination"]');
        if (examTextarea) {
            examTextarea.value = reading.clinical_examination || '';
            examTextarea.removeAttribute('readonly');
            examTextarea.style.border = '2px solid #2563eb';
            examTextarea.style.backgroundColor = '#eff6ff';
        }
    @endif

    // 3. إظهار شارة التعديل العلوية
    const alertBox = document.getElementById('editingReadingAlert');
    if (alertBox) {
        alertBox.style.display = 'flex';
        const tsEl = document.getElementById('editingReadingTimestamp');
        if (tsEl) {
            let dateStr = reading.created_at ? new Date(reading.created_at).toLocaleString('ar-EG', { hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'short' }) : '{{ __('قراءة أرشيفية') }}';
            tsEl.textContent = dateStr;
        }
        const userEl = document.getElementById('editingReadingUser');
        if (userEl) {
            userEl.textContent = (reading.user && reading.user.name) ? reading.user.name : (reading.resident && reading.resident.user ? reading.resident.user.name : '{{ __('طاقم الرعاية') }}');
        }
    }

    // 4. تمرير الصفحة بسلاسة نحو جدول المؤشرات في الأعلى
    const tableCard = document.getElementById('vitalsFlowsheetCard') || document.getElementById('vitalsGridTable');
    if (tableCard) {
        tableCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    const firstInp = document.getElementById('grid_bp');
    if (firstInp) firstInp.focus();
};

window.cancelEditingReading = function() {
    // 1. تصفير معرف القراءة والعودة لوضع القراءة الجديدة في الصف الأول
    const idInput = document.getElementById('editingReadingId');
    if (idInput) idInput.value = '';

    const isNewInput = document.getElementById('isNewReadingInput');
    if (isNewInput) isNewInput.value = '1';

    const inputRow = document.getElementById('vitalsInputRow');
    if (inputRow) {
        inputRow.classList.remove('table-primary');
        inputRow.classList.add('table-success');
    }
    const modeCell = document.getElementById('row1ModeCell');
    if (modeCell) {
        modeCell.innerHTML = '<span class="badge bg-success text-white rounded-pill px-2 py-1 d-inline-block"><i class="fas fa-plus-circle me-1"></i> {{ __("قراءة جديدة") }}</span><div class="text-muted mt-1" style="font-size: 0.66rem;">{{ __("أدخل القيم أفقياً") }}</div>';
    }
    const cancelRowBtn = document.getElementById('row1CancelEditBtn');
    if (cancelRowBtn) cancelRowBtn.classList.add('d-none');

    // 2. تفريغ خانات الإدخال وإعادة حالة القراءة فقط الأصلية
    const resetGridVal = function(id) {
        const inp = document.getElementById(id);
        if (inp) {
            inp.value = inp.getAttribute('data-original') || '';
            inp.style.border = '';
            inp.style.backgroundColor = '';
            if (inp.getAttribute('data-original-readonly') === '1') {
                inp.setAttribute('readonly', 'readonly');
                inp.classList.add('bg-light');
            } else {
                inp.removeAttribute('readonly');
                inp.classList.remove('bg-light');
            }
        }
    };

    resetGridVal('grid_bp');
    resetGridVal('grid_temp');
    resetGridVal('grid_pr');
    resetGridVal('grid_rr');
    resetGridVal('grid_spo2');
    resetGridVal('grid_rbs');

    resetGridVal('grid_intake_iv_fluids');
    resetGridVal('grid_intake_oral');
    resetGridVal('grid_intake_blood');
    resetGridVal('grid_output_urine');
    resetGridVal('grid_output_drain');
    window.currentReadingDrains = [];
    if (typeof window.updateDrainBadge === 'function') {
        window.updateDrainBadge();
    }
    resetGridVal('grid_output_gtube_ng');
    resetGridVal('grid_output_vomiting');
    resetGridVal('grid_output_stool');
    if (typeof window.updateLiveFluidBalance === 'function') {
        window.updateLiveFluidBalance();
    }

    @if(!empty($canManageClinicalExam))
        const examTextarea = document.querySelector('textarea[name="clinical_examination"]');
        if (examTextarea) {
            examTextarea.value = examTextarea.getAttribute('data-original') || '';
            examTextarea.style.border = '';
            examTextarea.style.backgroundColor = '';
            if (examTextarea.getAttribute('data-original-readonly') === '1') {
                examTextarea.setAttribute('readonly', 'readonly');
            }
        }
    @endif

    // 3. إخفاء شارة التعديل
    const alertBox = document.getElementById('editingReadingAlert');
    if (alertBox) alertBox.style.display = 'none';
};

window.enableNewReadingMode = function() {
    window.cancelEditingReading();
    const tableCard = document.getElementById('vitalsFlowsheetCard') || document.getElementById('vitalsGridTable');
    if (tableCard) {
        tableCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    const firstInp = document.getElementById('grid_bp');
    if (firstInp) firstInp.focus();
};

window.confirmDeleteReading = function(deleteUrl) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '{{ __('هل أنت متأكد من حذف هذه القراءة؟') }}',
            text: '{{ __('سيتم حذف القراءة من السجل ومزامنة قراءات المريض الحالية تلقائياً.') }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __('نعم، احذف') }}',
            cancelButtonText: '{{ __('إلغاء') }}'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('deleteReadingForm');
                form.action = deleteUrl;
                form.submit();
            }
        });
    } else {
        if (confirm('{{ __('هل أنت متأكد من حذف هذه القراءة؟') }}')) {
            const form = document.getElementById('deleteReadingForm');
            form.action = deleteUrl;
            form.submit();
        }
    }
};

window.fillHistoryQuickText = function(fieldName, value) {
    const el = document.getElementById('hist_' + fieldName)
        || document.getElementById('plan_' + fieldName)
        || document.querySelector('#clinicalHistoryGrid textarea[name="' + fieldName + '"], #clinicalPlanGrid textarea[name="' + fieldName + '"]');
    if (!el || el.readOnly || el.disabled) {
        return;
    }
    el.value = value;
    el.focus();
};

window.updateLiveFluidBalance = function() {
    const val = (id) => {
        const el = document.getElementById(id);
        if (!el || el.value === '' || isNaN(parseFloat(el.value))) return null;
        return parseFloat(el.value);
    };

    const intakeIds = ['grid_intake_iv_fluids', 'grid_intake_oral', 'grid_intake_blood'];
    const outputIds = ['grid_output_urine', 'grid_output_drain', 'grid_output_gtube_ng', 'grid_output_vomiting', 'grid_output_stool'];

    let totalIntake = 0;
    let totalOutput = 0;
    let hasAny = false;

    intakeIds.forEach((id) => {
        const v = val(id);
        if (v !== null) {
            totalIntake += v;
            hasAny = true;
        }
    });

    outputIds.forEach((id) => {
        const v = val(id);
        if (v !== null) {
            totalOutput += v;
            hasAny = true;
        }
    });

    const intakeEl = document.getElementById('fluidTotalIntakeDisplay');
    const outputEl = document.getElementById('fluidTotalOutputDisplay');
    const netEl = document.getElementById('fluidNetBalanceDisplay');
    const boxEl = document.getElementById('fluidBalanceSummaryBox');

    if (intakeEl) intakeEl.textContent = Math.round(totalIntake * 100) / 100;
    if (outputEl) outputEl.textContent = Math.round(totalOutput * 100) / 100;

    if (netEl && boxEl) {
        if (!hasAny) {
            netEl.textContent = '--';
            netEl.className = 'fw-bold small text-secondary';
            boxEl.className = 'rounded-2 border px-1.5 py-1 bg-light border-secondary-subtle';
        } else {
            const net = Math.round((totalIntake - totalOutput) * 100) / 100;
            netEl.textContent = (net > 0 ? '+' : '') + net + ' ml';
            if (net >= 0) {
                netEl.className = 'fw-bold small text-success';
                boxEl.className = 'rounded-2 border px-1.5 py-1 bg-success bg-opacity-10 border-success-subtle';
            } else {
                netEl.className = 'fw-bold small text-danger';
                boxEl.className = 'rounded-2 border px-1.5 py-1 bg-danger bg-opacity-10 border-danger-subtle';
            }
        }
    }
};

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.fluid-grid-input').forEach(function (inp) {
        inp.addEventListener('input', window.updateLiveFluidBalance);
    });
    if (document.getElementById('vitalsFlowsheetCard')) {
        window.updateLiveFluidBalance();
    }
    @if($activeTab === 'vitals' && !empty($canViewVitalsCharts) && $readingsAsc->count() > 0)
        const labels = @json($readingsAsc->map(fn($r) => $r->created_at->format('h:i A')));
        
        // BP Data
        const systolicData = @json($readingsAsc->map(function($r) {
            $parts = explode('/', (string)$r->bp);
            return isset($parts[0]) && is_numeric($parts[0]) ? (int)$parts[0] : null;
        }));
        const diastolicData = @json($readingsAsc->map(function($r) {
            $parts = explode('/', (string)$r->bp);
            return isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;
        }));

        const bpCtx = document.getElementById('bpChart');
        if (bpCtx) {
            new Chart(bpCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { label: '{{ __('انقباضي (SYS)') }}', data: systolicData, borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', tension: 0.3, fill: true },
                        { label: '{{ __('انبساطي (DIA)') }}', data: diastolicData, borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', tension: 0.3, fill: true }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', rtl: true } }
                }
            });
        }

        // Pulse Data
        const pulseData = @json($readingsAsc->map(fn($r) => is_numeric($r->pr) ? (int)$r->pr : null));
        const prCtx = document.getElementById('prChart');
        if (prCtx) {
            new Chart(prCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '{{ __('النبض (bpm)') }}',
                        data: pulseData,
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.15)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', rtl: true } }
                }
            });
        }
    @endif
});
</script>

{{-- =============================================================== --}}
{{-- MODALS FOR ORDERING LAB AND RADIOLOGY TESTS --}}
{{-- =============================================================== --}}
@push('modals')
@php
    $modalInsType = $surgery->insurance_type ?? $surgery->patient?->insurance_type ?? 'none';
    $modalIsInsurance = in_array($modalInsType, ['hi', 'moi']);
    $modalInsLabel = match($modalInsType) {
        'hi' => __('ضمان صحي (HI)'),
        'moi' => __('قوى الأمن الداخلي (MOI)'),
        default => __('دفع نقدي (كاش)')
    };
    $modalLabCopay = $modalIsInsurance ? ($surgery->patient?->getCopayPercentageFor('lab') ?? 15) : 0;
    $modalRadCopay = $modalIsInsurance ? ($surgery->patient?->getCopayPercentageFor('radiology') ?? 15) : 0;
@endphp

{{-- 1. Order Lab Tests Modal --}}
<div class="modal fade" id="orderLabTestsModal" tabindex="-1" aria-labelledby="orderLabTestsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 650px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-white border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2" id="orderLabTestsModalLabel" style="color: #0f172a;">
                    <span class="rounded-3 bg-danger bg-opacity-10 text-danger d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="fas fa-flask"></i>
                    </span>
                    <span>{{ __("طلب تحاليل مخبرية للعملية #") }}{{ $surgery->id }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('clinical-station.lab-tests.store', $surgery) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    {{-- Financial Banner --}}
                    <div class="p-2.5 rounded-3 mb-3 border d-flex align-items-center justify-content-between gap-2" style="background: #f8fafc;">
                        <div class="small">
                            <span class="text-secondary fw-semibold">{{ __("المريض:") }}</span> <strong>{{ $surgery->patient?->user?->name ?? __('غير محدد') }}</strong>
                            <span class="mx-1.5 text-muted">•</span>
                            <span class="text-secondary fw-semibold">{{ __("الحساب:") }}</span>
                            <span class="badge {{ $modalIsInsurance ? 'bg-success' : 'bg-primary' }} rounded-pill px-2 py-0.5">{{ $modalInsLabel }}</span>
                        </div>
                        <div class="text-muted small" style="font-size: 0.75rem;">
                            @if($modalIsInsurance)
                                <i class="fas fa-shield-alt text-success me-1"></i> {{ __("نسبة التحمل: ") }}{{ $modalLabCopay }}%{{ __(" بالكاشير") }}
                            @else
                                <i class="fas fa-money-bill-wave text-primary me-1"></i> {{ __("تسديد كامل بالكاشير") }}
                            @endif
                        </div>
                    </div>

                    {{-- Package selector if available --}}
                    @if(isset($labPackages) && $labPackages->count() > 0)
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary mb-1">
                                <i class="fas fa-layer-group text-primary me-1"></i> {{ __("اختيار باقة تحاليل جاهزة (اختياري):") }}
                            </label>
                            <select name="package_id" id="modalLabPackageSelect" class="form-select form-select-sm" onchange="applyLabPackageSelection(this)">
                                <option value="">{{ __("-- اختيار تحاليل فردية يدوياً --") }}</option>
                                @foreach($labPackages as $pkg)
                                    @php
                                        $pkgTestIdsJson = json_encode($pkg->labTests->pluck('id')->toArray());
                                    @endphp
                                    <option value="{{ $pkg->id }}" data-tests="{{ $pkgTestIdsJson }}">
                                        {{ $pkg->name }} ({{ $pkg->labTests->count() }} {{ __('تحاليل') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Search filter --}}
                    <div class="mb-2">
                        <label class="form-label fw-bold small text-secondary mb-1">
                            <i class="fas fa-search text-danger me-1"></i> {{ __('تحديد الفحوصات المخبرية المطلوبة:') }}
                        </label>
                        <input type="text" id="modalLabSearchInput" class="form-control form-control-sm mb-2" placeholder="{{ __('بحث باسم التحليل...') }}" oninput="filterModalLabTests(this.value)">
                    </div>

                    {{-- Tests scrollable list --}}
                    <div class="border rounded-3 p-2.5 mb-3" style="max-height: 240px; overflow-y: auto; background: #ffffff;">
                        @if(isset($availableLabTests) && $availableLabTests->count() > 0)
                            <div class="row g-2" id="modalLabTestsListContainer">
                                @foreach($availableLabTests as $test)
                                    @php
                                        $bPrice = (float)($test->price ?? 0);
                                        $hiPrice = (float)(($test->hi_price > 0) ? $test->hi_price : $bPrice);
                                        $moiPrice = (float)(($test->moi_price > 0) ? $test->moi_price : $bPrice);
                                        $effPrice = match($modalInsType) {
                                            'hi' => $hiPrice,
                                            'moi' => $moiPrice,
                                            default => $bPrice
                                        };
                                        $displayCopay = $modalIsInsurance ? round($effPrice * ($modalLabCopay / 100)) : $effPrice;
                                    @endphp
                                    <div class="col-md-6 modal-lab-item" data-name="{{ mb_strtolower($test->name) }}">
                                        <div class="form-check p-2 border rounded-2 h-100 bg-light d-flex align-items-center justify-content-between gap-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <input class="form-check-input modal-lab-checkbox ms-0" type="checkbox" name="lab_test_ids[]" value="{{ $test->id }}" id="labTestCheck{{ $test->id }}">
                                                <label class="form-check-label small fw-semibold text-dark cursor-pointer text-truncate" for="labTestCheck{{ $test->id }}" style="max-width: 170px;" title="{{ $test->name }}">
                                                    {{ $test->name }}
                                                </label>
                                            </div>
                                            @if($modalIsInsurance)
                                                <div class="text-end lh-1">
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5 rounded-pill" style="font-size: 0.68rem;" title="{{ __('استقطاع المريض') }} ({{ $modalLabCopay }}%) {{ __('من سعر الضمان') }} {{ number_format($effPrice, 0) }} {{ __('د.ع') }}">
                                                        {{ __('تحمل') }} {{ $modalLabCopay }}%: {{ number_format($displayCopay, 0) }} {{ __('د.ع') }}
                                                    </span>
                                                    <small class="text-muted d-block mt-0.5" style="font-size: 0.60rem;">{{ __('معتمد:') }} {{ number_format($effPrice, 0) }} {{ __('د.ع') }}</small>
                                                </div>
                                            @else
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-1.5 py-0.5 rounded-pill" style="font-size: 0.68rem;">
                                                    {{ __('كاش:') }} {{ number_format($effPrice, 0) }} {{ __('د.ع') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted py-3 small">{{ __('لا توجد تحاليل مفعلة حالياً في النظام.') }}</div>
                        @endif
                    </div>

                    {{-- Priority & Notes --}}
                    <div class="row g-2 mb-2">
                        <div class="col-sm-5">
                            <label class="form-label fw-bold small text-secondary mb-1">{{ __('درجة الأهمية:') }}</label>
                            <div class="d-flex gap-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="priority" id="labPrioRoutine" value="routine" checked>
                                    <label class="form-check-label small" for="labPrioRoutine">{{ __('عادي') }} Routine</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="priority" id="labPrioUrgent" value="urgent">
                                    <label class="form-check-label small text-danger fw-bold" for="labPrioUrgent">{{ __('عاجل') }} STAT</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-7">
                            <label class="form-label fw-bold small text-secondary mb-1">{{ __("ملاحظات سريرية للمختبر (اختياري):") }}</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="{{ __('مثال: فحص سريع قبل دخول الصالة...') }}">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                        <i class="fas fa-paper-plane me-1"></i> {{ __('إرسال الطلب للمختبر والكاشير') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 2. Order Radiology Test Modal --}}
<div class="modal fade" id="orderRadiologyModal" tabindex="-1" aria-labelledby="orderRadiologyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-white border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2" id="orderRadiologyModalLabel" style="color: #0f172a;">
                    <span class="rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="fas fa-x-ray"></i>
                    </span>
                    <span>{{ __('طلب فحص أشعة / تصوير للعملية #') }}{{ $surgery->id }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('clinical-station.radiology-tests.store', $surgery) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    {{-- Financial Banner --}}
                    <div class="p-2.5 rounded-3 mb-3 border d-flex align-items-center justify-content-between gap-2" style="background: #f8fafc;">
                        <div class="small">
                            <span class="text-secondary fw-semibold">{{ __("المريض:") }}</span> <strong>{{ $surgery->patient?->user?->name ?? __('غير محدد') }}</strong>
                            <span class="mx-1.5 text-muted">•</span>
                            <span class="text-secondary fw-semibold">{{ __("الحساب:") }}</span>
                            <span class="badge {{ $modalIsInsurance ? 'bg-success' : 'bg-primary' }} rounded-pill px-2 py-0.5">{{ $modalInsLabel }}</span>
                        </div>
                        <div class="text-muted small" style="font-size: 0.75rem;">
                            @if($modalIsInsurance)
                                <i class="fas fa-shield-alt text-success me-1"></i> {{ __('نسبة التحمل:') }} {{ $modalRadCopay }}% {{ __('بالكاشير') }}
                            @else
                                <i class="fas fa-money-bill-wave text-primary me-1"></i> {{ __("تسديد كامل بالكاشير") }}
                            @endif
                        </div>
                    </div>

                    {{-- Radiology Type Selection --}}
                    <div class="mb-3">
                        <label for="modalRadiologyTypeSelect" class="form-label fw-bold small text-secondary mb-1">
                            <i class="fas fa-camera text-primary me-1"></i> {{ __('نوع الفحص الإشعاعي المطلوب:') }} <span class="text-danger">*</span>
                        </label>
                        <select name="radiology_type_id" id="modalRadiologyTypeSelect" class="form-select" required>
                            <option value="">{{ __('-- اختر نوع الأشعة / التصوير --') }}</option>
                            @if(isset($availableRadiologyTypes))
                                @foreach($availableRadiologyTypes as $rad)
                                    @php
                                        $rPrice = (float)($rad->base_price ?? 0);
                                        $rHi = (float)(($rad->hi_price > 0) ? $rad->hi_price : $rPrice);
                                        $rMoi = (float)(($rad->moi_price > 0) ? $rad->moi_price : $rPrice);
                                        $rEff = match($modalInsType) {
                                            'hi' => $rHi,
                                            'moi' => $rMoi,
                                            default => $rPrice
                                        };
                                        $displayRadCopay = $modalIsInsurance ? round($rEff * ($modalRadCopay / 100)) : $rEff;
                                    @endphp
                                    <option value="{{ $rad->id }}">
                                        {{ $rad->name }} — 
                                        @if($modalIsInsurance)
                                            [{{ __('ضمان معتمد:') }} {{ number_format($rEff, 0) }} {{ __('د.ع') }} | {{ __('استقطاع') }} {{ $modalRadCopay }}%: {{ number_format($displayRadCopay, 0) }} {{ __('د.ع') }}]
                                        @else
                                            [{{ __('كاش:') }} {{ number_format($rEff, 0) }} {{ __('د.ع') }}]
                                        @endif
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    {{-- Priority --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary mb-1">{{ __('درجة الأهمية:') }}</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="priority" id="radPrioRoutine" value="routine" checked>
                                <label class="form-check-label small" for="radPrioRoutine">{{ __('عادي') }} Routine</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="priority" id="radPrioUrgent" value="urgent">
                                <label class="form-check-label small text-danger fw-bold" for="radPrioUrgent">{{ __('عاجل STAT (قبل العملية)') }}</label>
                            </div>
                        </div>
                    </div>

                    {{-- Clinical indication / notes --}}
                    <div class="mb-2">
                        <label for="modalRadiologyNotes" class="form-label fw-bold small text-secondary mb-1">
                            {{ __('العضو المستهدف والتشخيص السريري (Clinical Indication):') }}
                        </label>
                        <textarea name="notes" id="modalRadiologyNotes" rows="2" class="form-control form-control-sm" placeholder="{{ __('مثال: صورة صدر لمريض قبل التخدير العام / سونار بطن وحوض...') }}"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="fas fa-paper-plane me-1"></i> {{ __('إرسال الطلب للأشعة والكاشير') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filterModalLabTests(query) {
    const q = (query || '').trim().toLowerCase();
    const items = document.querySelectorAll('#modalLabTestsListContainer .modal-lab-item');
    items.forEach(el => {
        const name = el.getAttribute('data-name') || '';
        el.style.display = (!q || name.includes(q)) ? '' : 'none';
    });
}

function applyLabPackageSelection(select) {
    const option = select.options[select.selectedIndex];
    const testsJson = option ? option.getAttribute('data-tests') : null;
    if (!testsJson) return;

    try {
        const testIds = JSON.parse(testsJson) || [];
        const checkboxes = document.querySelectorAll('.modal-lab-checkbox');
        checkboxes.forEach(cb => {
            const val = parseInt(cb.value);
            if (testIds.includes(val)) {
                cb.checked = true;
            }
        });
    } catch (e) {
        console.error('Error applying lab package:', e);
    }
}
</script>
{{-- Multi-Drain Entry Modal --}}
<div class="modal fade" id="drainDetailsModal" tabindex="-1" aria-labelledby="drainDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-white text-danger d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.95rem;">
                        <i class="fas fa-procedures"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0 text-white" id="drainDetailsModalLabel">
                            {{ __("قراءات الدرنقات والمفجرات الجراحية") }}
                        </h6>
                        <div class="text-white-50 small" style="font-size: 0.72rem;">{{ __('تسجيل قراءة مستقلة لكل درنقة للمريض مع تجميع تلقائي') }}</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-bold text-dark small">
                        <i class="fas fa-list-ol text-danger me-1"></i> {{ __("قائمة الدرنقات (غير محددة العدد):") }}
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" onclick="addDrainRow()">
                        <i class="fas fa-plus me-1"></i> {{ __('إضافة درنقة') }}
                    </button>
                </div>

                <div id="drainModalRowsContainer" class="d-flex flex-column gap-2 mb-3" style="max-height: 280px; overflow-y: auto;">
                    {{-- Dynamically populated rows --}}
                </div>

                <div class="p-3 bg-white rounded-3 border border-danger-subtle d-flex align-items-center justify-content-between shadow-xs">
                    <div>
                        <div class="text-muted small" style="font-size: 0.75rem;">{{ __("المجموع الكلي الخارج من الدرنقات:") }}</div>
                        <div class="fw-bold text-danger fs-5"><span id="drainModalTotalDisplay">0</span> ml</div>
                    </div>
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1 rounded-pill small">
                        {{ __('تلقائي لميزان السوائل') }}
                    </span>
                </div>
            </div>
            <div class="modal-footer bg-white border-top px-4 py-3 d-flex align-items-center justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" onclick="applyDrainModalData()">
                    <i class="fas fa-check me-1"></i> {{ __('اعتماد وحفظ الإجمالي') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- View Drains Details Modal --}}
<div class="modal fade" id="viewDrainDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light border-bottom py-3 px-4">
                <h6 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="fas fa-info-circle text-danger"></i> {{ __('تفاصيل قراءات درنقات المريض') }}
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-sm table-bordered align-middle text-center mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th>{{ __('الدرنقة / الموقع') }}</th>
                            <th>{{ __('الكمية الخارجة') }}</th>
                        </tr>
                    </thead>
                    <tbody id="viewDrainDetailsTableBody">
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="2" class="text-end pe-3">{{ __('الإجمالي الكلي:') }}</td>
                            <td class="text-danger" id="viewDrainDetailsTotal">0 ml</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="modal-footer bg-light border-0 py-2 justify-content-center">
                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('إغلاق') }}</button>
            </div>
        </div>
    </div>
</div>

@if($activeTab === 'treatments')
{{-- مودال معاينة وتطبيق باقة الأدوية المشتركة --}}
<div class="modal fade" id="applyClinicalMedPkgModal" tabindex="-1" aria-labelledby="applyClinicalMedPkgModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 760px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-white border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2" id="applyClinicalMedPkgModalLabel" style="color: #0f172a;">
                    <span class="rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="fas fa-prescription-bottle-alt"></i>
                    </span>
                    <span id="applyMedPkgModalTitle">{{ __("معاينة وتطبيق باقة الأدوية") }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('clinical-station.treatments.apply-package', $surgery) }}" method="POST" id="applyClinicalMedPkgForm">
                @csrf
                <input type="hidden" name="phase" value="{{ $currentPhase }}">
                <input type="hidden" name="group_id" id="applyMedPkgGroupId" value="">

                <div class="modal-body p-4">
                    <div class="alert alert-light border rounded-3 p-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-info-circle text-primary fs-5"></i>
                            <span class="small text-dark">
                                {{ __("يمكنك مراجعة أدوية الباقة أو استثناء أي دواء غير مناسب للمريض برفع علامة الصح قبل التطبيق.") }}
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label small fw-bold text-dark mb-0 text-nowrap">{{ __("وقت البدء:") }}</label>
                            <input type="datetime-local" name="start_at" class="form-control form-control-sm rounded-2" value="{{ now()->format('Y-m-d\TH:i') }}" style="max-width: 190px;">
                        </div>
                    </div>

                    <div class="table-responsive border rounded-3">
                        <table class="table table-sm table-hover align-middle mb-0 text-center">
                            <thead class="table-light small">
                                <tr>
                                    <th style="width: 45px;">
                                        <input class="form-check-input" type="checkbox" id="selectAllPkgItems" checked onchange="toggleSelectAllPkgItems(this)">
                                    </th>
                                    <th class="text-start">{{ __("اسم الدواء / المادة") }}</th>
                                    <th>{{ __("الجرعة") }}</th>
                                    <th>{{ __("التكرار") }}</th>
                                    <th>{{ __("المدة") }}</th>
                                </tr>
                            </thead>
                            <tbody id="applyMedPkgTableBody" class="small">
                                {{-- Rows dynamically inserted by openApplyClinicalMedPkgModal() --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-2.5 px-4 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fas fa-bolt me-1"></i> {{ __('⚡ تطبيق الباقة وإدراجها في جدول الإعطاء (MAR)') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- مودال حفظ الأدوية الحالية كباقة مشتركة لجميع المقيمين --}}
<div class="modal fade" id="saveCurrentMedsAsPackageModal" tabindex="-1" aria-labelledby="saveCurrentMedsAsPackageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 580px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-white border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2 text-dark" id="saveCurrentMedsAsPackageModalLabel">
                    <span class="rounded-3 bg-warning bg-opacity-10 text-dark d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="fas fa-save text-warning"></i>
                    </span>
                    <span>{{ __("حفظ الأدوية الحالية كباقة مشتركة لجميع المقيمين") }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('clinical-station.treatments.save-package', $surgery) }}" method="POST">
                @csrf
                <input type="hidden" name="phase" value="{{ $currentPhase }}">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">
                            {{ __("اسم الباقة الدوائية:") }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" class="form-control form-control-sm rounded-2" required placeholder="{{ __('مثال: بروتوكول ما بعد الزائدة - جراحة عامة') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">
                            {{ __("وصف أو ملاحظات سريرية (اختياري):") }}
                        </label>
                        <textarea name="description" class="form-control form-control-sm rounded-2" rows="2" placeholder="{{ __('ملاحظات حول دواعي استخدام هذه الباقة أو إرشادات للمقيمين...') }}"></textarea>
                    </div>

                    <div class="border rounded-3 p-2.5 bg-light mb-3" style="max-height: 180px; overflow-y: auto;">
                        <label class="form-label small fw-bold text-secondary mb-1.5 d-flex align-items-center gap-1">
                            <i class="fas fa-pills text-primary"></i>
                            <span>{{ __("الأدوية الحالية التي سيتم تضمينها في الباقة:") }}</span>
                            <span class="badge bg-primary rounded-pill ms-auto">{{ isset($medicationTreatments) ? $medicationTreatments->count() : 0 }}</span>
                        </label>
                        @if(isset($medicationTreatments) && $medicationTreatments->isNotEmpty())
                            <ul class="list-group list-group-flush small bg-white rounded-2 border">
                                @foreach($medicationTreatments as $tr)
                                    <li class="list-group-item py-1.5 px-2.5 d-flex align-items-center justify-content-between">
                                        <span class="fw-semibold text-dark">{{ $tr->description }}</span>
                                        <span class="badge bg-secondary-subtle text-secondary border">{{ $tr->dosage ?: '-' }} | {{ $tr->timing ?: '-' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center text-muted small py-2">{{ __('لا توجد أدوية مسجلة حالياً.') }}</div>
                        @endif
                    </div>

                    <div class="alert alert-info py-2 px-3 small border-0 rounded-3 mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-users text-primary fs-5"></i>
                        <span>{{ __("هذه الباقة ستكون مشتركة ومتاحة لجميع الأطباء المقيمين وأطباء الجراحة للاستفادة منها بنقرة واحدة في كافة العمليات.") }}</span>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-2.5 px-4 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                    <button type="submit" class="btn btn-sm btn-warning rounded-pill px-4 fw-bold text-dark shadow-sm">
                        <i class="fas fa-save me-1"></i> {{ __('💾 حفظ واعتماد الباقة المشتركة') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
window.currentReadingDrains = [];

window.openDrainModal = function() {
    const container = document.getElementById('drainModalRowsContainer');
    if (!container) return;
    container.innerHTML = '';

    if (window.currentReadingDrains && window.currentReadingDrains.length > 0) {
        window.currentReadingDrains.forEach(d => {
            window.addDrainRow(d.drain_label, d.amount);
        });
    } else {
        const currentTotal = parseFloat(document.getElementById('grid_output_drain')?.value) || 0;
        if (currentTotal > 0) {
            window.addDrainRow('{{ __('درنقة') }} 1', currentTotal);
        } else {
            window.addDrainRow('{{ __('درنقة') }} 1', '');
            window.addDrainRow('{{ __('درنقة') }} 2', '');
        }
    }

    window.calculateDrainModalTotal();
    const modalEl = document.getElementById('drainDetailsModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
};

window.addDrainRow = function(label = '', amount = '') {
    const container = document.getElementById('drainModalRowsContainer');
    if (!container) return;

    const rowIdx = container.children.length + 1;
    const defaultLabel = label || ('{{ __('درنقة') }} ' + rowIdx);

    const row = document.createElement('div');
    row.className = 'd-flex align-items-center gap-2 bg-white p-2 rounded-3 border drain-modal-row';
    row.innerHTML = `
        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-circle" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">
            ${rowIdx}
        </span>
        <div class="flex-grow-1">
            <input type="text" class="form-control form-control-sm drain-row-label" placeholder="{{ __('اسم / موقع الدرنقة') }}" value="${defaultLabel}">
        </div>
        <div style="width: 110px;">
            <div class="input-group input-group-sm">
                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-center fw-bold drain-row-amount" placeholder="0" value="${amount}" oninput="window.calculateDrainModalTotal()">
                <span class="input-group-text px-1 text-muted" style="font-size: 0.65rem;">ml</span>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger p-0 rounded-circle" onclick="window.removeDrainRow(this)" style="width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;" title="{{ __('حذف هذه الدرنقة') }}">
            <i class="fas fa-trash-alt" style="font-size: 0.7rem;"></i>
        </button>
    `;
    container.appendChild(row);
    window.calculateDrainModalTotal();
};

window.removeDrainRow = function(btn) {
    const row = btn.closest('.drain-modal-row');
    if (row) {
        row.remove();
        const container = document.getElementById('drainModalRowsContainer');
        if (container) {
            Array.from(container.children).forEach((r, idx) => {
                const badge = r.querySelector('.badge');
                if (badge) badge.textContent = (idx + 1);
            });
        }
        window.calculateDrainModalTotal();
    }
};

window.calculateDrainModalTotal = function() {
    const rows = document.querySelectorAll('.drain-modal-row');
    let total = 0;
    rows.forEach(r => {
        const inp = r.querySelector('.drain-row-amount');
        const val = parseFloat(inp?.value) || 0;
        total += val;
    });
    total = Math.round(total * 100) / 100;
    const totalDisplay = document.getElementById('drainModalTotalDisplay');
    if (totalDisplay) totalDisplay.textContent = total;
    return total;
};

window.applyDrainModalData = function() {
    const rows = document.querySelectorAll('.drain-modal-row');
    const drains = [];
    let total = 0;

    rows.forEach((r, idx) => {
        const labelInp = r.querySelector('.drain-row-label');
        const amountInp = r.querySelector('.drain-row-amount');
        const val = parseFloat(amountInp?.value);
        const label = labelInp?.value?.trim() || ('{{ __('درنقة') }} ' + (idx + 1));

        if (!isNaN(val) && val > 0) {
            drains.push({
                drain_number: idx + 1,
                drain_label: label,
                amount: Math.round(val * 100) / 100
            });
            total += val;
        }
    });

    total = Math.round(total * 100) / 100;
    window.currentReadingDrains = drains;

    const drainInput = document.getElementById('grid_output_drain');
    if (drainInput) {
        drainInput.value = total > 0 ? total : '';
    }

    const drainsJsonInput = document.getElementById('grid_drains_json');
    if (drainsJsonInput) {
        drainsJsonInput.value = drains.length > 0 ? JSON.stringify(drains) : '';
    }

    window.updateDrainBadge();

    if (typeof window.updateLiveFluidBalance === 'function') {
        window.updateLiveFluidBalance();
    }

    const modalEl = document.getElementById('drainDetailsModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
};

window.updateDrainBadge = function() {
    const btn = document.getElementById('btnOpenDrainModal');
    const labelSpan = document.getElementById('drainButtonLabel');
    const drainsJsonInput = document.getElementById('grid_drains_json');
    const drainInput = document.getElementById('grid_output_drain');

    let total = 0;
    if (window.currentReadingDrains && window.currentReadingDrains.length > 0) {
        window.currentReadingDrains.forEach(d => { total += (parseFloat(d.amount) || 0); });
        total = Math.round(total * 100) / 100;
        if (drainsJsonInput) drainsJsonInput.value = JSON.stringify(window.currentReadingDrains);
        if (drainInput) drainInput.value = total > 0 ? total : '';

        if (labelSpan) {
            labelSpan.innerHTML = `<span class="fw-bold">${total} ml</span> <span class="badge bg-white text-danger rounded-pill px-1 ms-0.5" style="font-size:0.6rem;">${window.currentReadingDrains.length}</span> <i class="fas fa-pen ms-0.5" style="font-size:0.58rem;"></i>`;
        }
        if (btn) {
            btn.className = 'btn btn-sm btn-danger py-1 px-1.5 rounded-pill text-nowrap fw-bold shadow-xs w-100';
        }
    } else {
        const val = parseFloat(drainInput?.value) || 0;
        if (val > 0) {
            if (labelSpan) labelSpan.innerHTML = `<span class="fw-bold">${val} ml</span> <i class="fas fa-pen ms-0.5" style="font-size:0.58rem;"></i>`;
            if (btn) btn.className = 'btn btn-sm btn-danger py-1 px-1.5 rounded-pill text-nowrap fw-bold shadow-xs w-100';
        } else {
            if (labelSpan) labelSpan.innerHTML = '<i class="fas fa-plus-circle me-1"></i> {{ __("الدرنقات") }}';
            if (btn) btn.className = 'btn btn-sm btn-outline-danger py-1 px-1.5 rounded-pill text-nowrap fw-bold shadow-xs w-100';
            if (drainsJsonInput) drainsJsonInput.value = '';
            if (drainInput) drainInput.value = '';
        }
    }
};

window.viewReadingDrainsModal = function(drains, total) {
    const tbody = document.getElementById('viewDrainDetailsTableBody');
    const totalEl = document.getElementById('viewDrainDetailsTotal');
    if (!tbody) return;

    tbody.innerHTML = '';
    if (Array.isArray(drains) && drains.length > 0) {
        drains.forEach((d, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-muted small">${d.drain_number || (idx + 1)}</td>
                <td class="fw-semibold text-dark">${d.drain_label || ('{{ __('درنقة') }} ' + (idx + 1))}</td>
                <td class="text-danger fw-bold">${parseFloat(d.amount)} ml</td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        tbody.innerHTML = '<tr><td colspan="3" class="text-muted">{{ __('لا توجد تفاصيل درنقات مسجلة') }}</td></tr>';
    }

    if (totalEl) totalEl.textContent = (total || 0) + ' ml';

    const modalEl = document.getElementById('viewDrainDetailsModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
};

window.consultantDoctorsData = {!! json_encode(collect($availableConsultantDoctors ?? [])->map(function($doc) {
    return [
        'id' => $doc->id,
        'name' => __('د.') . ' ' . ($doc->user?->name ?? __('طبيب استشاري')),
        'specialization' => $doc->specialization ?? $doc->department?->name ?? __('طبيب استشاري'),
        'fee' => 50000,
        'fee_formatted' => number_format(50000),
    ];
})->values(), JSON_UNESCAPED_UNICODE) !!};

document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('consultation_doctor_name');
    const hiddenInput = document.getElementById('consultation_doctor_id');
    const menu = document.getElementById('consultantDoctorsMenu');
    const clearBtn = document.getElementById('clearConsultDocBtn');
    const toggleBtn = document.getElementById('toggleConsultDocMenuBtn');
    const specInput = document.getElementById('consultationSpecialtyInput');
    const feeInput = document.getElementById('consultationFeeInput');
    const wrapper = document.getElementById('consultantAutocompleteWrapper');

    if (!input || !menu || !hiddenInput) return;

    function renderDoctorOptions(filterText = '') {
        const query = (filterText || '').trim().toLowerCase();
        const list = window.consultantDoctorsData || [];

        let matched = list.filter(item => {
            const name = (item.name || '').toLowerCase();
            const spec = (item.specialization || '').toLowerCase();
            return !query || name.includes(query) || spec.includes(query);
        });

        if (matched.length === 0) {
            menu.innerHTML = '<div class="p-3 text-center text-muted small"><i class="fas fa-info-circle me-1"></i>{{ __("لا يوجد أطباء استشاريين متاحين اليوم مطابقين للبحث") }}</div>';
            menu.style.display = 'block';
            return;
        }

        let html = '';
        matched.forEach(item => {
            const isSelected = String(hiddenInput.value) === String(item.id);
            const selectedClass = isSelected ? 'bg-primary-subtle border-primary' : '';

            html += `
                <div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 px-3 consult-doctor-option-item ${selectedClass}" style="cursor: pointer;" data-doctor-id="${item.id}" data-doctor-name="${item.name.replace(/"/g, '&quot;')}" data-specialty="${(item.specialization || '').replace(/"/g, '&quot;')}" data-fee="${item.fee}">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="fas fa-user-md text-primary"></i>
                        <div>
                            <span class="fw-bold text-dark d-block text-truncate">${item.name}</span>
                            <span class="text-muted small">${item.specialization}</span>
                        </div>
                    </div>
                    <span class="badge bg-light text-success border border-success-subtle small">${item.fee_formatted} {{ __('د.ع') }}</span>
                </div>
            `;
        });

        menu.innerHTML = html;
        menu.style.display = 'block';

        menu.querySelectorAll('.consult-doctor-option-item').forEach(el => {
            el.addEventListener('click', function() {
                const id = this.getAttribute('data-doctor-id');
                const name = this.getAttribute('data-doctor-name');
                const specialty = this.getAttribute('data-specialty');
                const fee = this.getAttribute('data-fee');
                if (id) selectDoctor(id, name, specialty, fee);
            });
        });
    }

    function selectDoctor(id, name, specialty, fee) {
        hiddenInput.value = id;
        input.value = name;
        if (specInput) specInput.value = specialty || '';
        if (feeInput) feeInput.value = fee || '0';
        if (clearBtn) clearBtn.classList.remove('d-none');
        menu.style.display = 'none';
    }

    input.addEventListener('focus', function() {
        renderDoctorOptions(this.value);
    });

    input.addEventListener('input', function() {
        if (!this.value.trim()) {
            hiddenInput.value = '';
            if (clearBtn) clearBtn.classList.add('d-none');
        }
        renderDoctorOptions(this.value);
    });

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (menu.style.display === 'block') {
                menu.style.display = 'none';
            } else {
                renderDoctorOptions('');
                input.focus();
            }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            input.value = '';
            hiddenInput.value = '';
            if (specInput) specInput.value = '';
            if (feeInput) feeInput.value = '0';
            clearBtn.classList.add('d-none');
            menu.style.display = 'none';
        });
    }

    document.addEventListener('click', function(e) {
        if (wrapper && !wrapper.contains(e.target)) {
            menu.style.display = 'none';
        }
    });

    if (hiddenInput.value) {
        const found = (window.consultantDoctorsData || []).find(d => String(d.id) === String(hiddenInput.value));
        if (found) {
            input.value = found.name;
            if (clearBtn) clearBtn.classList.remove('d-none');
        }
    }
});

// باقات الأدوية المشتركة للمقيمين والأطباء
window.openApplyClinicalMedPkgModal = function(groupId, groupName, items) {
    const modalEl = document.getElementById('applyClinicalMedPkgModal');
    if (!modalEl) return;

    const idInput = document.getElementById('applyMedPkgGroupId');
    if (idInput) idInput.value = groupId;

    const titleEl = document.getElementById('applyMedPkgModalTitle');
    if (titleEl) titleEl.textContent = '{{ __("معاينة وتطبيق باقة:") }} ' + groupName;

    const tbody = document.getElementById('applyMedPkgTableBody');
    if (tbody) {
        tbody.innerHTML = '';
        if (!items || items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-muted py-3">{{ __("لا توجد أدوية مسجلة في هذه الباقة.") }}</td></tr>';
        } else {
            items.forEach((item) => {
                const tr = document.createElement('tr');
                const isCustom = String(item.id || '').startsWith('custom_');
                const itemIdVal = isCustom ? '' : item.id;
                const inputHtml = isCustom
                    ? '<i class="fas fa-check text-success small" title="{{ __("دواء مخصص") }}"></i>'
                    : `<input class="form-check-input modal-pkg-item-check" type="checkbox" name="selected_items[]" value="${itemIdVal}" checked>`;

                tr.innerHTML = `
                    <td>${inputHtml}</td>
                    <td class="text-start fw-semibold text-dark">
                        ${escapeClinicalHtml(item.name || '')}
                        ${item.instructions ? `<small class="text-muted d-block" style="font-size: 0.72rem;">${escapeClinicalHtml(item.instructions)}</small>` : ''}
                    </td>
                    <td><span class="badge bg-light text-dark border">${escapeClinicalHtml(item.dosage || '-')}</span></td>
                    <td><span class="badge bg-primary-subtle text-primary border">${escapeClinicalHtml(item.frequency || 'q8h')}</span></td>
                    <td><span class="text-muted">${escapeClinicalHtml(String(item.duration || 3))} {{ __("أيام") }}</span></td>
                `;
                tbody.appendChild(tr);
            });
        }
    }

    if (typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
};

window.toggleSelectAllPkgItems = function(masterCheck) {
    const checkboxes = document.querySelectorAll('.modal-pkg-item-check');
    checkboxes.forEach(cb => {
        cb.checked = masterCheck.checked;
    });
};

window.toggleClinicalPkgStar = function(btn, groupId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    fetch(`{{ url('medicine-groups') }}/${groupId}/toggle-star`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const icon = btn.querySelector('i');
            if (icon) {
                if (data.is_starred) {
                    icon.className = 'fas fa-star text-warning';
                    btn.classList.remove('text-muted', 'opacity-50');
                    btn.classList.add('text-warning');
                } else {
                    icon.className = 'far fa-star';
                    btn.classList.remove('text-warning');
                    btn.classList.add('text-muted', 'opacity-50');
                }
            }
        }
    })
    .catch(err => console.error('Error toggling package star:', err));
};

function escapeClinicalHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
@endpush
@endsection
