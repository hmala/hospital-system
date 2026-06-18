@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title">متابعات الطبيب المقيم - العملية رقم {{ $surgery->id }}</h3>
                        <small>المريض: {{ $surgery->patient->user->full_name }}</small>
                    </div>
                    <a href="{{ route('surgeon-station.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-right"></i> العودة لقائمة الجراح
                    </a>
                </div>
                 <div class="card-body">
                     @if($surgery->preOpResidentStation || $surgery->postOpResidentStation)
                         <div class="mb-4">
                             @php
                                 $combinedReadings = collect();
                                 if ($surgery->preOpResidentStation) {
                                     $combinedReadings = $combinedReadings->concat($surgery->preOpResidentStation->readings->map(function($reading) {
                                         $reading->phase_label = 'قبل دخول الصالة';
                                         return $reading;
                                     }));
                                 }
                                 if ($surgery->postOpResidentStation) {
                                     $combinedReadings = $combinedReadings->concat($surgery->postOpResidentStation->readings->map(function($reading) {
                                         $reading->phase_label = 'بعد العملية';
                                         return $reading;
                                     }));
                                 }
                                 $combinedReadings = $combinedReadings->sortByDesc('created_at');
                             @endphp

                             <h5 class="mb-3"><i class="fas fa-heartbeat text-danger me-2"></i>قراءات العلامات الحيوية</h5>

                             @if($combinedReadings->count() > 0)
                                 <!-- Tabs for switching between Chart and Table views -->
                                 <ul class="nav nav-tabs mb-3 shadow-sm rounded bg-white p-1" id="vitalSignsTab" role="tablist" style="border: 1px solid #dee2e6;">
                                     <li class="nav-item" role="presentation">
                                         <button class="nav-link active fw-bold" id="charts-tab" data-bs-toggle="tab" data-bs-target="#charts-view" type="button" role="tab" aria-controls="charts-view" aria-selected="true" style="border: none; border-radius: 6px;">
                                             <i class="fas fa-chart-line me-2 text-primary"></i>المنحنى البياني للعلامات الحيوية
                                         </button>
                                     </li>
                                     <li class="nav-item ms-2" role="presentation">
                                         <button class="nav-link fw-bold" id="table-tab" data-bs-toggle="tab" data-bs-target="#table-view" type="button" role="tab" aria-controls="table-view" aria-selected="false" style="border: none; border-radius: 6px; color: #6c757d;">
                                             <i class="fas fa-table me-2"></i>جدول القراءات التفصيلي
                                         </button>
                                     </li>
                                 </ul>

                                 <div class="tab-content" id="vitalSignsTabContent">
                                     <!-- 1. Charts Tab View -->
                                     <div class="tab-pane fade show active" id="charts-view" role="tabpanel" aria-labelledby="charts-tab">
                                         <!-- Row containing 2 charts per line -->
                                         <div class="row g-2 mb-4">
                                             <!-- Chart 1: Blood Pressure -->
                                             <div class="col-md-6 col-12">
                                                 <div class="card border shadow-sm h-100" style="background: rgba(255, 255, 255, 0.9) !important; border: 1px solid #bfdbfe !important;">
                                                     <div class="card-header bg-light d-flex align-items-center justify-content-between py-1 px-2" style="border-bottom: 1px solid #bfdbfe !important; font-size: 0.8rem;">
                                                         <span class="fw-bold text-primary"><i class="fas fa-heartbeat text-danger me-1"></i>الضغط (BP)</span>
                                                         <span class="badge bg-danger p-1" style="font-size: 0.65rem;">mmHg</span>
                                                     </div>
                                                     <div class="card-body p-1" style="position: relative; height: 160px; background: transparent !important;">
                                                         <canvas id="bpChart"></canvas>
                                                     </div>
                                                 </div>
                                             </div>
                                             
                                             <!-- Chart 2: Pulse Rate -->
                                             <div class="col-md-6 col-12">
                                                 <div class="card border shadow-sm h-100" style="background: rgba(255, 255, 255, 0.9) !important; border: 1px solid #bfdbfe !important;">
                                                     <div class="card-header bg-light d-flex align-items-center justify-content-between py-1 px-2" style="border-bottom: 1px solid #bfdbfe !important; font-size: 0.8rem;">
                                                         <span class="fw-bold text-warning"><i class="fas fa-heart me-1"></i>النبض (Pulse)</span>
                                                         <span class="badge bg-warning p-1" style="font-size: 0.65rem; color: #fff;">bpm</span>
                                                     </div>
                                                     <div class="card-body p-1" style="position: relative; height: 160px; background: transparent !important;">
                                                         <canvas id="pulseChart"></canvas>
                                                     </div>
                                                 </div>
                                             </div>

                                             <!-- Chart 3: Temperature -->
                                             <div class="col-md-6 col-12">
                                                 <div class="card border shadow-sm h-100" style="background: rgba(255, 255, 255, 0.9) !important; border: 1px solid #bfdbfe !important;">
                                                     <div class="card-header bg-light d-flex align-items-center justify-content-between py-1 px-2" style="border-bottom: 1px solid #bfdbfe !important; font-size: 0.8rem;">
                                                         <span class="fw-bold text-orange" style="color: #f97316;"><i class="fas fa-thermometer-half me-1"></i>الحرارة (Temp)</span>
                                                         <span class="badge p-1" style="background-color: #f97316; color: #fff; font-size: 0.65rem;">°C</span>
                                                     </div>
                                                     <div class="card-body p-1" style="position: relative; height: 160px; background: transparent !important;">
                                                         <canvas id="tempChart"></canvas>
                                                     </div>
                                                 </div>
                                             </div>

                                             <!-- Chart 4: SPO2 -->
                                             <div class="col-md-6 col-12">
                                                 <div class="card border shadow-sm h-100" style="background: rgba(255, 255, 255, 0.9) !important; border: 1px solid #bfdbfe !important;">
                                                     <div class="card-header bg-light d-flex align-items-center justify-content-between py-1 px-2" style="border-bottom: 1px solid #bfdbfe !important; font-size: 0.8rem;">
                                                         <span class="fw-bold text-success"><i class="fas fa-wind me-1"></i>الأكسجين (SPO2)</span>
                                                         <span class="badge bg-success p-1" style="font-size: 0.65rem;">%</span>
                                                     </div>
                                                     <div class="card-body p-1" style="position: relative; height: 160px; background: transparent !important;">
                                                         <canvas id="spo2Chart"></canvas>
                                                     </div>
                                                 </div>
                                             </div>

                                             <!-- Chart 5: Respiratory Rate -->
                                             <div class="col-md-6 col-12">
                                                 <div class="card border shadow-sm h-100" style="background: rgba(255, 255, 255, 0.9) !important; border: 1px solid #bfdbfe !important;">
                                                     <div class="card-header bg-light d-flex align-items-center justify-content-between py-1 px-2" style="border-bottom: 1px solid #bfdbfe !important; font-size: 0.8rem;">
                                                         <span class="fw-bold text-purple" style="color: #8b5cf6;"><i class="fas fa-lungs me-1"></i>التنفس (RR)</span>
                                                         <span class="badge text-white p-1" style="background-color: #8b5cf6; font-size: 0.65rem;">/min</span>
                                                     </div>
                                                     <div class="card-body p-1" style="position: relative; height: 160px; background: transparent !important;">
                                                         <canvas id="rrChart"></canvas>
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>

                                     <!-- 2. Table Tab View -->
                                     <div class="tab-pane fade" id="table-view" role="tabpanel" aria-labelledby="table-tab">
                                         <div class="table-responsive mb-3">
                                             <table class="table table-bordered table-hover align-middle">
                                                 <thead class="table-light">
                                                     <tr>
                                                         <th>المرحلة</th>
                                                         <th>التاريخ</th>
                                                         <th>BP (ضغط الدم)</th>
                                                         <th>Temp (الحرارة)</th>
                                                         <th>PR (النبض)</th>
                                                         <th>RR (التنفس)</th>
                                                         <th>SPO2 (الأكسجين)</th>
                                                         <th>التسجيل</th>
                                                     </tr>
                                                 </thead>
                                                 <tbody>
                                                     @foreach($combinedReadings as $reading)
                                                         <tr>
                                                             <td class="fw-bold text-primary">{{ $reading->phase_label }}</td>
                                                             <td>{{ $reading->created_at->format('Y-m-d') }}</td>
                                                             <td class="font-monospace fw-bold">{{ $reading->bp ?? '-' }}</td>
                                                             <td class="font-monospace fw-bold text-danger">{{ $reading->temp ? $reading->temp . ' °C' : '-' }}</td>
                                                             <td class="font-monospace fw-bold text-warning">{{ $reading->pr ?? '-' }}</td>
                                                             <td class="font-monospace fw-bold">{{ $reading->rr ?? '-' }}</td>
                                                             <td class="font-monospace fw-bold text-success">{{ $reading->spo2 ? $reading->spo2 . '%' : '-' }}</td>
                                                             <td class="small text-muted">{{ $reading->created_at->format('Y-m-d H:i') }}</td>
                                                         </tr>
                                                     @endforeach
                                                 </tbody>
                                             </table>
                                         </div>
                                     </div>
                                 </div>
                             @else
                                 <div class="alert alert-secondary mb-3">
                                     لا توجد قراءات علامات حيوية مسجلة حتى الآن.
                                 </div>
                             @endif

                             <h5 class="mb-3">متابعات الطبيب المقيم</h5>
                         </div>

                         @if($surgery->postOpResidentStation && $surgery->postOpResidentStation->followUps->count() > 0)
                             <div class="table-responsive">
                                 <table class="table table-bordered table-hover align-middle">
                                     <thead class="table-light">
                                         <tr>
                                             <th>تاريخ المتابعة</th>
                                             <th>الوردية</th>
                                             <th>المقيم</th>
                                             <th>الملاحظات</th>
                                             <th>توقيت التسجيل</th>
                                             <th>منسق التسجيل</th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         @foreach($surgery->postOpResidentStation->followUps as $followUp)
                                             <tr>
                                                 <td>{{ $followUp->follow_up_date->format('Y-m-d') }}</td>
                                                 <td>{{ $followUp->session === 'morning' ? 'صباحاً' : 'مساءً' }}</td>
                                                 <td>{{ $followUp->resident?->user?->full_name ?? $followUp->resident_name ?? 'غير محدد' }}</td>
                                                 <td>{!! nl2br(e($followUp->notes)) !!}</td>
                                                 <td>{{ $followUp->created_at->format('Y-m-d H:i') }}</td>
                                                 <td>{{ $followUp->resident?->user?->full_name ?? $followUp->resident_name ?? 'غير محدد' }}</td>
                                             </tr>
                                         @endforeach
                                     </tbody>
                                 </table>
                             </div>
                         @else
                             <div class="alert alert-info mb-0">
                                 لا توجد متابعات مسجلة حتى الآن من الطبيب المقيم.
                             </div>
                         @endif
                     @else
                         <div class="alert alert-info mb-0">
                             لا توجد قراءات أو متابعات مسجلة لهذا الجراحة بعد (في مرحلة ما قبل أو ما بعد العملية).
                         </div>
                     @endif
                 </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if we have readings data
    @if($combinedReadings->count() > 0)
        const rawData = @json($combinedReadings->sortBy('created_at')->values());
        
        // 1. Prepare labels (Arabic dates and times)
        const labels = rawData.map(item => {
            const dateObj = new Date(item.created_at);
            const timeStr = dateObj.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
            return `${item.phase_label} (${timeStr})`;
        });

        // 2. Extract vital sign values
        const systolicData = [];
        const diastolicData = [];
        const prData = [];
        const tempData = [];
        const spo2Data = [];
        const rrData = [];

        rawData.forEach(item => {
            // Blood pressure splitting
            if (item.bp) {
                const parts = item.bp.split('/');
                systolicData.push(parts[0] ? parseInt(parts[0]) : null);
                diastolicData.push(parts[1] ? parseInt(parts[1]) : null);
            } else {
                systolicData.push(null);
                diastolicData.push(null);
            }

            prData.push(item.pr ? parseInt(item.pr) : null);
            tempData.push(item.temp ? parseFloat(item.temp) : null);
            spo2Data.push(item.spo2 ? parseInt(item.spo2) : null);
            rrData.push(item.rr ? parseInt(item.rr) : null);
        });

        // Config variables for charts styling
        const gridColor = 'rgba(148, 163, 184, 0.12)';
        const fontConfig = { family: 'Segoe UI', size: 10 };
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    rtl: true,
                    titleFont: { family: 'Segoe UI' },
                    bodyFont: { family: 'Segoe UI' }
                }
            },
            scales: {
                y: {
                    grid: { color: gridColor },
                    ticks: { font: fontConfig }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: fontConfig }
                }
            }
        };

        // 1. BP Chart (keeping systolic & diastolic together with legend)
        const bpCtx = document.getElementById('bpChart').getContext('2d');
        new Chart(bpCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'الانقباضي (Systolic)',
                        data: systolicData,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.06)',
                        borderWidth: 2.5,
                        tension: 0.3,
                        spanGaps: true,
                        pointRadius: 3.5,
                        pointBackgroundColor: '#ef4444'
                    },
                    {
                        label: 'الانبساطي (Diastolic)',
                        data: diastolicData,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.06)',
                        borderWidth: 2.5,
                        tension: 0.3,
                        spanGaps: true,
                        pointRadius: 3.5,
                        pointBackgroundColor: '#3b82f6'
                    }
                ]
            },
            options: {
                ...commonOptions,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        rtl: true,
                        labels: { font: { family: 'Segoe UI', size: 10, weight: '600' } }
                    },
                    tooltip: commonOptions.plugins.tooltip
                },
                scales: {
                    y: {
                        ...commonOptions.scales.y,
                        suggestedMin: 50,
                        suggestedMax: 150
                    },
                    x: commonOptions.scales.x
                }
            }
        });

        // 2. Pulse Chart
        const pulseCtx = document.getElementById('pulseChart').getContext('2d');
        new Chart(pulseCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'نبض القلب',
                    data: prData,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.06)',
                    borderWidth: 2.5,
                    tension: 0.3,
                    spanGaps: true,
                    pointRadius: 3.5,
                    pointBackgroundColor: '#f59e0b'
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        ...commonOptions.scales.y,
                        suggestedMin: 50,
                        suggestedMax: 120
                    },
                    x: commonOptions.scales.x
                }
            }
        });

        // 3. Temp Chart
        const tempCtx = document.getElementById('tempChart').getContext('2d');
        new Chart(tempCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'درجة الحرارة',
                    data: tempData,
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.06)',
                    borderWidth: 2.5,
                    tension: 0.3,
                    spanGaps: true,
                    pointRadius: 3.5,
                    pointBackgroundColor: '#f97316'
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        ...commonOptions.scales.y,
                        suggestedMin: 35,
                        suggestedMax: 40
                    },
                    x: commonOptions.scales.x
                }
            }
        });

        // 4. SPO2 Chart
        const spo2Ctx = document.getElementById('spo2Chart').getContext('2d');
        new Chart(spo2Ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'نسبة الأكسجين',
                    data: spo2Data,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.06)',
                    borderWidth: 2.5,
                    tension: 0.3,
                    spanGaps: true,
                    pointRadius: 3.5,
                    pointBackgroundColor: '#10b981'
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        ...commonOptions.scales.y,
                        suggestedMin: 80,
                        suggestedMax: 100
                    },
                    x: commonOptions.scales.x
                }
            }
        });

        // 5. RR Chart
        const rrCtx = document.getElementById('rrChart').getContext('2d');
        new Chart(rrCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'معدل التنفس',
                    data: rrData,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.06)',
                    borderWidth: 2.5,
                    tension: 0.3,
                    spanGaps: true,
                    pointRadius: 3.5,
                    pointBackgroundColor: '#8b5cf6'
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        ...commonOptions.scales.y,
                        suggestedMin: 10,
                        suggestedMax: 30
                    },
                    x: commonOptions.scales.x
                }
            }
        });
    @endif
});
</script>
@endsection
