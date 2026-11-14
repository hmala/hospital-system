{{-- شريط تبويبات الإعدادات المشترك لقسم الخدج والحاضنات (NICU Settings Sub-tabs) --}}
@php
    $currentTab = $activeTab ?? (request()->routeIs('nicu.services.*') ? 'services' : (request()->routeIs('incubators.*') ? 'incubators' : 'milk-types'));
@endphp

<div class="card border-0 shadow-sm mb-4 bg-white" style="border-radius: 12px;">
    <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <ul class="nav nav-pills gap-2 mb-0" role="tablist">
            <li class="nav-item">
                <a class="nav-link px-3 py-2 fw-bold {{ $currentTab === 'services' ? 'active shadow-sm' : 'text-secondary' }}" 
                   href="{{ route('nicu.services.index') }}"
                   style="border-radius: 8px;">
                    <i class="fas fa-tags me-1 {{ $currentTab === 'services' ? 'text-white' : 'text-primary' }}"></i>
                    تسعير باقات وخدمات الخدج (NICU)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link px-3 py-2 fw-bold {{ $currentTab === 'incubators' ? 'active shadow-sm' : 'text-secondary' }}" 
                   href="{{ route('incubators.index') }}"
                   style="border-radius: 8px;">
                    <i class="fas fa-cogs me-1 {{ $currentTab === 'incubators' ? 'text-white' : 'text-warning' }}"></i>
                    إدارة حاضنات الخدج - ط6
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link px-3 py-2 fw-bold {{ $currentTab === 'milk-types' ? 'active shadow-sm' : 'text-secondary' }}" 
                   href="{{ route('nicu.milk-types.index') }}"
                   style="border-radius: 8px;">
                    <i class="fas fa-baby me-1 {{ $currentTab === 'milk-types' ? 'text-white' : 'text-warning' }}"></i>
                    قائمة وتصنيفات حليب وتغذية الخدج
                </a>
            </li>
        </ul>
        <div class="d-none d-md-flex align-items-center pe-2 text-muted small">
            <i class="fas fa-sliders-h text-warning me-1"></i>
            <span class="fw-semibold">إعدادات الخدج والحاضنات</span>
        </div>
    </div>
</div>
