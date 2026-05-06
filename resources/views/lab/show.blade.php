@extends('layouts.app')

@php
use App\Models\LabTestResult;
use App\Models\LabTest;

// الحصول على نتائج التحاليل المرتبطة بهذا الطلب
$labTest = LabTestResult::where('visit_id', $request->visit_id)
    ->where('test_name', $request->description)
    ->first();

// الحصول على جميع التحاليل النشطة للاستخدام في JavaScript
$labTests = LabTest::active()->get()->keyBy('name');

function getTestIcon($testName) {
    $name = strtolower($testName);

    if (strpos($name, 'سكر') !== false || strpos($name, 'glucose') !== false) {
        return 'fas fa-tint text-danger';
    } elseif (strpos($name, 'ضغط') !== false || strpos($name, 'pressure') !== false) {
        return 'fas fa-heartbeat text-danger';
    } elseif (strpos($name, 'كوليسترول') !== false || strpos($name, 'cholesterol') !== false) {
        return 'fas fa-oil-can text-warning';
    } elseif (strpos($name, 'دم') !== false || strpos($name, 'blood') !== false) {
        return 'fas fa-tint text-danger';
    } elseif (strpos($name, 'بول') !== false || strpos($name, 'urine') !== false) {
        return 'fas fa-flask text-warning';
    } elseif (strpos($name, 'كبد') !== false || strpos($name, 'liver') !== false) {
        return 'fas fa-lungs text-success';
    } elseif (strpos($name, 'كلى') !== false || strpos($name, 'kidney') !== false) {
        return 'fas fa-kidney text-info';
    } elseif (strpos($name, 'هرمون') !== false || strpos($name, 'hormone') !== false) {
        return 'fas fa-atom text-purple';
    } elseif (strpos($name, 'فيروس') !== false || strpos($name, 'virus') !== false) {
        return 'fas fa-virus text-danger';
    } elseif (strpos($name, 'بكتيريا') !== false || strpos($name, 'bacteria') !== false) {
        return 'fas fa-bacterium text-success';
    } elseif (strpos($name, 'أشعة') !== false || strpos($name, 'x-ray') !== false) {
        return 'fas fa-x-ray text-primary';
    } elseif (strpos($name, 'تصوير') !== false || strpos($name, 'imaging') !== false) {
        return 'fas fa-camera text-secondary';
    } else {
        return 'fas fa-vial text-primary';
    }
}

function getTestUnit($testName, $labTests) {
    // البحث في قاعدة البيانات أولاً
    if (isset($labTests[$testName]) && !empty($labTests[$testName]->unit)) {
        return $labTests[$testName]->unit;
    }

    // الاحتياطي للفحوصات غير الموجودة في قاعدة البيانات
    $name = strtolower($testName);

    if (strpos($name, 'سكر') !== false || strpos($name, 'glucose') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'ضغط') !== false || strpos($name, 'pressure') !== false) {
        return 'mmHg';
    } elseif (strpos($name, 'كوليسترول') !== false || strpos($name, 'cholesterol') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'بيليروبين') !== false || strpos($name, 'bilirubin') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'كرياتينين') !== false || strpos($name, 'creatinine') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'يوريا') !== false || strpos($name, 'urea') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'sgot') !== false || strpos($name, 'ast') !== false) {
        return 'U/L';
    } elseif (strpos($name, 'sgpt') !== false || strpos($name, 'alt') !== false) {
        return 'U/L';
    } elseif (strpos($name, 'الصفائح') !== false || strpos($name, 'platelets') !== false) {
        return '/µL';
    } elseif (strpos($name, 'الهيموغلوبين') !== false || strpos($name, 'hemoglobin') !== false) {
        return 'g/dL';
    } elseif (strpos($name, 'الكرات البيضاء') !== false || strpos($name, 'wbc') !== false) {
        return '/µL';
    } elseif (strpos($name, 'الكرات الحمراء') !== false || strpos($name, 'rbc') !== false) {
        return 'million/µL';
    } elseif (strpos($name, 'الهيماتوكريت') !== false || strpos($name, 'hematocrit') !== false) {
        return '%';
    } elseif (strpos($name, 'هرمون') !== false || strpos($name, 'hormone') !== false) {
        return 'mIU/mL';
    } elseif (strpos($name, 'فيتامين') !== false || strpos($name, 'vitamin') !== false) {
        return 'ng/mL';
    } elseif (strpos($name, 'حديد') !== false || strpos($name, 'iron') !== false) {
        return 'µg/dL';
    } elseif (strpos($name, 'كالسيوم') !== false || strpos($name, 'calcium') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'صوديوم') !== false || strpos($name, 'sodium') !== false) {
        return 'mEq/L';
    } elseif (strpos($name, 'بوتاسيوم') !== false || strpos($name, 'potassium') !== false) {
        return 'mEq/L';
    } else {
        return '';
    }
}

function normalizeTestIds($testIds) {
    if (empty($testIds)) {
        return [];
    }

    if (is_string($testIds)) {
        $decoded = json_decode($testIds, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        return array_filter(array_map('trim', explode(',', $testIds)), fn($item) => $item !== '');
    }

    if (is_array($testIds)) {
        return $testIds;
    }

    return [];
}

function normalizeTestsField($tests) {
    if (empty($tests)) {
        return [];
    }

    if (is_string($tests)) {
        $decoded = json_decode($tests, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        return array_filter(array_map('trim', explode(',', $tests)), fn($item) => $item !== '');
    }

    if (is_array($tests)) {
        return $tests;
    }

    return [];
}

function buildSelectedLabTests($requestDetails) {
    $testsList = [];
    if (!is_array($requestDetails)) {
        return $testsList;
    }

    if (!empty($requestDetails['package_id'])) {
        $pkg = \App\Models\Package::find($requestDetails['package_id']);
        if ($pkg) {
            $testsList = array_merge($testsList, $pkg->labTests->pluck('name')->toArray());
        }
    }

    if (!empty($requestDetails['lab_test_ids'])) {
        $ids = normalizeTestIds($requestDetails['lab_test_ids']);
        foreach ($ids as $testId) {
            if ($testId === '') {
                continue;
            }
            $labTest = \App\Models\LabTest::find($testId);
            if ($labTest) {
                $testsList[] = $labTest->name;
            }
        }
    }

    if (isset($requestDetails['tests'])) {
        $tests = normalizeTestsField($requestDetails['tests']);
        $testsList = array_merge($testsList, $tests);
    }

    $testsList = array_filter($testsList, fn($item) => !is_null($item) && trim((string) $item) !== '');
    return array_values(array_unique($testsList));
}
@endphp

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-vials me-2"></i>
                    تفاصيل طلب المختبر
                </h2>
                <div class="d-flex gap-2">
                    @php
                        $requestDetails = $request->details;
                        if (is_string($requestDetails)) {
                            $decoded = json_decode($requestDetails, true);
                            $requestDetails = is_array($decoded) ? $decoded : [];
                        }
                        if (!is_array($requestDetails)) {
                            $requestDetails = [];
                        }
                        $isBloodBankRequest = $request->type === 'blood_bank' || ($requestDetails['blood_bank'] ?? false);
                    @endphp

                    @if($request->payment_status == 'paid' && ($request->type == 'lab' || $isBloodBankRequest))
                        <a href="{{ route('lab.print', $request) }}" 
                           class="btn btn-success" 
                           target="_blank">
                            <i class="fas fa-print me-1"></i>
                            طباعة النتائج
                        </a>
                    @endif
                    @if(!$isBloodBankRequest && in_array($request->status, ['pending', 'in_progress', 'completed']))
                        <a href="{{ route('lab.show', ['request' => $request, 'append' => 1]) }}#appendTestsSection" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-1"></i>
                            إضافة تحاليل إضافية
                        </a>
                    @endif
                    <a href="{{ route('lab.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="row text-center gy-2">
                        <div class="col-sm-6 col-md-3">
                            <div class="text-start">
                                <div class="small text-muted">اسم المريض</div>
                                <div class="fw-bold">{{ $request->visit->patient?->user?->name ?? 'غير محدد' }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="text-start">
                                <div class="small text-muted">الجنس</div>
                                <div class="fw-bold">{{ $request->visit->patient?->gender == 'male' ? 'ذكر' : ($request->visit->patient?->gender == 'female' ? 'أنثى' : 'غير محدد') }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-2">
                            <div class="text-start">
                                <div class="small text-muted">العمر</div>
                                <div class="fw-bold">{{ $request->visit->patient?->age ?? 'غير محدد' }} سنة</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="text-start">
                                <div class="small text-muted">الطبيب المرسل</div>
                                <div class="fw-bold">{{ $request->visit->doctor?->user?->name ? 'د. ' . $request->visit->doctor->user->name : 'غير محدد' }}</div>
                                <div class="small text-muted">{{ $request->visit->doctor?->specialization ?? '' }}</div>
                            </div>
                        </div>
                    </div>
                    @if($request->status !== 'pending_service_selection' && $request->payment_status != 'paid' && $request->type == 'lab')
                        @php
                            $selectedTests = collect([]);
                            if (!empty($requestDetails['lab_test_ids'])) {
                                $selectedTests = \App\Models\LabTest::whereIn('id', $requestDetails['lab_test_ids'])->get();
                            }
                        @endphp
                        @if($selectedTests->count() > 0)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <span class="badge bg-secondary py-2 px-3">التحاليل المحددة (قبل الدفع)</span>
                                        @foreach($selectedTests as $test)
                                            <form action="{{ route('staff.lab-requests.remove-test', [$request, $test]) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" title="حذف هذا التحليل" onclick="return confirm('هل أنت متأكد من حذف هذا التحليل؟');">
                                                    <span>{{ $test->name }}</span>
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        @endforeach
                                    </div>
                                    <div class="small text-muted mt-2">يمكن إزالة أي تحليل قبل الدفع. إذا حذفت آخر تحليل سيعود الطلب إلى مرحلة اختيار التحاليل.</div>
                                </div>
                            </div>
                        @endif
                    @endif
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

    @php
        $showAppendSection = request()->query('append') == '1';
        $currentTestIds = array_map('intval', $requestDetails['lab_test_ids'] ?? []);
        $favorites = \App\Models\UserLabTestStat::getFavoritesForUser(auth()->id());
    @endphp

    @if(!$isBloodBankRequest && in_array($request->status, ['pending', 'in_progress', 'completed']) && $showAppendSection)
    <div class="row mb-4" id="appendTestsSection">
        <div class="col-12">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>إضافة تحاليل إضافية</h5>
                        <small>يمكن اختيار تحاليل إضافية مباشرة هنا بدون نافذة منبثقة.</small>
                    </div>
                    <a href="{{ route('lab.show', $request) }}" class="btn btn-light btn-sm">
                        <i class="fas fa-times me-1"></i> إغلاق
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.lab-requests.append-tests', $request) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-1"></i>
                                سيتم إضافة التحاليل المختارة إلى التحاليل الموجودة في الطلب دون حذف أي منها.
                            </div>

                            @php
                                $favoriteIds = $favorites->pluck('lab_test_id')->toArray();
                            @endphp

                            @if($favorites->isNotEmpty())
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2 gap-2">
                                    <i class="fas fa-star text-warning"></i>
                                    <strong class="mb-0">مفضلاتي</strong>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($favorites as $stat)
                                        @php
                                            $test = $stat->labTest;
                                            if (!$test || !$test->is_active) continue;
                                        @endphp
                                        <div class="btn-group btn-group-sm" role="group" style="min-width: 180px;">
                                            <label class="btn {{ in_array($test->id, $currentTestIds) ? 'btn-secondary disabled' : 'btn-outline-primary' }} mb-0 text-start" style="flex: 1;">
                                                <input type="checkbox" name="extra_lab_test_ids[]" value="{{ $test->id }}" class="d-none" {{ in_array($test->id, $currentTestIds) ? 'disabled' : '' }}>
                                                {{ $test->name }}
                                                <small class="d-block text-muted">{{ $test->code }}</small>
                                            </label>
                                            <button type="button" class="btn btn-outline-warning toggle-favorite" data-test-id="{{ $test->id }}" data-is-favorite="1" title="إزالة من المفضلة">
                                                <span class="favorite-icon">⭐</span>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            @php
                                $groups = \App\Models\UserLabTestGroup::where('user_id', auth()->id())->with('labTests')->get();
                            @endphp
                            @if($groups->isNotEmpty())
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-layer-group text-success"></i>
                                        <strong class="mb-0">مجموعاتي</strong>
                                    </div>
                                    <a href="{{ route('lab-tests.groups.index') }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-cog me-1"></i> إدارة المجموعات
                                    </a>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($groups as $group)
                                        <button type="button" class="btn btn-sm btn-outline-success group-picker" data-test-ids='@json($group->labTests->pluck('id')->toArray())'>
                                            {{ $group->name }}
                                            <span class="badge bg-white text-dark ms-1">{{ $group->labTests->count() }}</span>
                                        </button>
                                    @endforeach
                                </div>
                                <div class="small text-muted mt-2">اضغط على المجموعة لتحديد جميع تحاليلها في النموذج.</div>
                            </div>
                            @endif

                            <div class="input-group mb-3">
                                <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="appendTestSearch" placeholder="ابحث عن تحليل...">
                                <button class="btn btn-outline-secondary" type="button" id="clearAppendSearch"><i class="fas fa-times"></i></button>
                            </div>

                            <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;" id="appendTestsContainer">
                                @php
                                    $allLabTests = \App\Models\LabTest::where('is_active', true)
                                        ->orderBy('main_category')->orderBy('name')
                                        ->get()->groupBy('main_category');
                                @endphp
                                @foreach($allLabTests as $cat => $tests)
                                    <div class="mb-3 append-test-group">
                                        <h6 class="text-primary border-bottom pb-1">
                                            <i class="fas fa-folder-open me-1"></i>{{ $cat }}
                                        </h6>
                                        @foreach($tests as $t)
                                            @php $isFavorite = in_array($t->id, $favoriteIds); @endphp
                                            <div class="form-check append-test-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <input class="form-check-input append-test-cb" type="checkbox"
                                                           name="extra_lab_test_ids[]" value="{{ $t->id }}"
                                                           id="append_{{ $t->id }}"
                                                           {{ in_array($t->id, $currentTestIds) ? 'disabled' : '' }}>
                                                    <label class="form-check-label {{ in_array($t->id, $currentTestIds) ? 'text-muted' : '' }}"
                                                           for="append_{{ $t->id }}">
                                                        {{ $t->name }}
                                                        <small class="text-muted">({{ $t->code }})</small>
                                                        @if(in_array($t->id, $currentTestIds))
                                                            <span class="badge bg-secondary ms-1">موجود</span>
                                                        @endif
                                                    </label>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-secondary toggle-favorite" data-test-id="{{ $t->id }}" data-is-favorite="{{ $isFavorite ? '1' : '0' }}" title="{{ $isFavorite ? 'إزالة من المفضلة' : 'إضافة إلى المفضلة' }}">
                                                    <span class="favorite-icon">{{ $isFavorite ? '⭐' : '☆' }}</span>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                            <div id="appendSelectedCount" class="mt-2 text-muted small"></div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            إضافة التحاليل المختارة
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- معلومات الطلب -->
    @php
        $requestDetails = $request->details;
        if (is_string($requestDetails)) {
            $decoded = json_decode($requestDetails, true);
            $requestDetails = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($requestDetails)) {
            $requestDetails = [];
        }
        $isBloodBankRequest = $request->type === 'blood_bank' || ($requestDetails['blood_bank'] ?? false);
    @endphp
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
               
                    @if($isBloodBankRequest)
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-danger shadow-sm">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0"><i class="fas fa-tint me-2"></i>تفاصيل طلب مصرف الدم</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="{{ route('lab.update', $request) }}">
                                        @csrf
                                        @method('PUT')

                                        @php
                                            $bb = $bloodBankRequest ?? (object) [
                                                'room_no' => $requestDetails['room_no'] ?? null,
                                                'donor_group' => $requestDetails['donor_group'] ?? null,
                                                'patient_group' => $requestDetails['patient_group'] ?? null,
                                                'at_room_temp' => $requestDetails['at_room_temp'] ?? null,
                                                'bovine_albumin' => $requestDetails['bovine_albumin'] ?? null,
                                                'anti_human_globulin' => $requestDetails['anti_human_globulin'] ?? null,
                                                'compatibility' => $requestDetails['compatibility'] ?? null,
                                                'bottle_no' => $requestDetails['bottle_no'] ?? null,
                                                'operative_date' => $requestDetails['operative_date'] ?? null,
                                                'exp_date' => $requestDetails['exp_date'] ?? null,
                                                'doctor_in_charge' => $requestDetails['doctor_in_charge'] ?? null,
                                                'total_amount' => $requestDetails['total_amount'] ?? 0,
                                                'notes' => $requestDetails['summary'] ?? null,
                                            ];
                                        @endphp

                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">رقم الغرفة / السرير</label>
                                                <input type="text" name="room_no" class="form-control" value="{{ old('room_no', $bb->room_no) }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">فصيلة المتبرع</label>
                                                <input type="text" name="donor_group" class="form-control" value="{{ old('donor_group', $bb->donor_group) }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">فصيلة المريض</label>
                                                <input type="text" name="patient_group" class="form-control" value="{{ old('patient_group', $bb->patient_group) }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">وزن المتبرع (كجم)</label>
                                                <input type="number" step="0.01" name="donor_weight" class="form-control" value="{{ old('donor_weight', $bb->donor_weight) }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">وزن المريض (كجم)</label>
                                                <input type="number" step="0.01" name="recipient_weight" class="form-control" value="{{ old('recipient_weight', $bb->recipient_weight) }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">التوافق</label>
                                                <input type="text" name="compatibility" class="form-control" value="{{ old('compatibility', $bb->compatibility) }}">
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">رقم العبوة</label>
                                                <input type="text" name="bottle_no" class="form-control" value="{{ old('bottle_no', $bb->bottle_no) }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">الدرجة في الغرفة</label>
                                                <input type="text" name="at_room_temp" class="form-control" value="{{ old('at_room_temp', $bb->at_room_temp) }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">ألبومين بقرى</label>
                                                <input type="text" name="bovine_albumin" class="form-control" value="{{ old('bovine_albumin', $bb->bovine_albumin) }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Anti-Human Globulin</label>
                                                <input type="text" name="anti_human_globulin" class="form-control" value="{{ old('anti_human_globulin', $bb->anti_human_globulin) }}">
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">تاريخ العملية</label>
                                                <input type="date" name="operative_date" class="form-control" value="{{ old('operative_date', optional($bb->operative_date)->format('Y-m-d')) }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">تاريخ الانتهاء</label>
                                                <input type="date" name="exp_date" class="form-control" value="{{ old('exp_date', optional($bb->exp_date)->format('Y-m-d')) }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">طبيب المسؤول</label>
                                                <input type="text" name="doctor_in_charge" class="form-control" value="{{ old('doctor_in_charge', $bb->doctor_in_charge) }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">السعر الكلي</label>
                                                <input type="number" step="0.01" name="total_amount" class="form-control" value="{{ old('total_amount', $bb->total_amount ?? 0) }}">
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label">ملاحظات إضافية</label>
                                                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $bb->notes) }}</textarea>
                                            </div>
                                        </div>

                                        <div class="mt-3 text-end">
                                            <button type="submit" class="btn btn-danger">حفظ بيانات مصرف الدم</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @elseif($request->type === 'lab')
                    <hr>
                    <!-- قسم اختيار الخدمات للطلبات pending_service_selection -->
                    @if($request->status === 'pending_service_selection')
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>تنبيه:</strong> هذا الطلب بانتظار تحديد التحاليل المطلوبة. الرجاء اختيار التحاليل أدناه.
                            </div>
                            <form action="{{ route('lab.update', $request) }}" method="POST">
                                @csrf
                                @method('PUT')

                                @php
                                    // افتراضيًا: إذا تم حفظ package_id نعرض قسم الباقة، وإلا نعرض قسم التحاليل العامة
                                    $serviceSelectionType = $requestDetails['service_selection_type'] ?? (!empty($requestDetails['package_id']) ? 'package' : 'general');
                                @endphp

                                <input type="hidden" name="service_selection_type" id="serviceSelectionType" value="{{ $serviceSelectionType }}">

                               
                                {{-- قسم المفضلات --}}
                                @php
                                    $favorites = \App\Models\UserLabTestStat::getFavoritesForUser(auth()->id());
                                @endphp

                                @if($favorites->isNotEmpty())
                                <div class="alert alert-dismissible fade show p-0 mb-3" style="background: linear-gradient(135deg, #FFF9E6 0%, #FFF4D6 100%); border: 2px solid #FFD700; border-radius: 12px; box-shadow: 0 4px 12px rgba(255, 215, 0, 0.2);">
                                    <div class="d-flex align-items-center px-3 py-2" style="background: linear-gradient(90deg, #FFD700 0%, #FFC107 100%); border-radius: 10px 10px 0 0;">
                                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                                            <div class="d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: white; border-radius: 50%; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                                                <i class="fas fa-star text-warning"></i>
                                            </div>
                                            <div>
                                                <strong class="text-dark" style="font-size: 1.1rem;">⚡ الاختصارات السريعة</strong>
                                                <small class="d-block text-dark" style="opacity: 0.8; font-size: 0.85rem;">{{ $favorites->count() }} تحليل مفضل</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-3">
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($favorites as $stat)
                                                @php
                                                    $test = $stat->labTest;
                                                    if (!$test || !$test->is_active) continue;
                                                    $isChecked = in_array($test->id, $requestDetails['lab_test_ids'] ?? []);
                                                @endphp
                                                <label for="fav_lab_{{ $test->id }}" class="favorite-pill {{ $isChecked ? 'active' : '' }} d-inline-block" style="cursor: pointer;">
                                                    <input class="form-check-input lab-test-checkbox d-none" type="checkbox" name="lab_test_ids[]" value="{{ $test->id }}" id="fav_lab_{{ $test->id }}" {{ $isChecked ? 'checked' : '' }}>
                                                    <div class="pill-content d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill border {{ $isChecked ? 'bg-success text-white border-success' : 'bg-white text-dark border-warning' }}" style="box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                                                        <i class="fas fa-flask text-{{ $isChecked ? 'white' : 'warning' }}" style="font-size: 0.9rem;"></i>
                                                        <span class="fw-semibold {{ $isChecked ? 'text-white' : 'text-dark' }}" style="font-size: 0.95rem;">{{ $test->name }}</span>
                                                        <span class="badge rounded-pill {{ $isChecked ? 'bg-white text-success' : 'bg-warning text-dark' }}" style="font-size: 0.75rem;">{{ $test->code }}</span>
                                                        @if($isChecked)
                                                            <i class="fas fa-check-circle ms-1"></i>
                                                        @endif
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                        <div class="mt-2 text-muted small">
                                            <i class="fas fa-info-circle me-1"></i>
                                            انقر على التحليل لإضافته أو إزالته من الطلب
                                        </div>
                                    </div>
                                </div>

                                <style>
                                .favorite-pill:hover .pill-content {
                                    transform: translateY(-2px);
                                    box-shadow: 0 4px 16px rgba(255, 215, 0, 0.4) !important;
                                }
                                .favorite-pill.active .pill-content {
                                    box-shadow: 0 4px 16px rgba(40, 167, 69, 0.3) !important;
                                }
                                .favorite-pill input[type="checkbox"]:checked + .pill-content {
                                    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
                                    border-color: #28a745 !important;
                                }
                                .favorite-pill input[type="checkbox"]:not(:checked) + .pill-content .fa-check-circle {
                                    display: none;
                                }
                                </style>
                                @endif

                                @php
                                    $packages = \App\Models\Package::where('is_active', true)->orderBy('name')->get();
                                @endphp

                                <div class="accordion" id="mainSelectionAccordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingPackage"> 
                                            <button class="accordion-button {{ $serviceSelectionType == 'package' ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#packageSection" aria-expanded="{{ $serviceSelectionType == 'package' ? 'true' : 'false' }}" aria-controls="packageSection">
                                                <strong>الباقات</strong>
                                            </button>
                                        </h2>
                                        <div id="packageSection" class="accordion-collapse collapse {{ $serviceSelectionType == 'package' ? 'show' : '' }}" aria-labelledby="headingPackage" data-bs-parent="#mainSelectionAccordion">
                                            <div class="accordion-body">
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>اختر باقة تحاليل:</strong></label>
                                                    <select class="form-select" id="packageSelect" name="package_id">
                                                        <option value="">-- لا توجد باقة مختارة --</option>
                                                        @foreach($packages as $pkg)
                                                            @php
                                                                $pkgTests = $pkg->labTests->pluck('id')->toArray();
                                                                $pkgTestNames = $pkg->labTests->pluck('name')->toArray();
                                                            @endphp
                                                            <option value="{{ $pkg->id }}" data-tests='@json($pkgTests)' data-test-names='@json($pkgTestNames)' {{ (isset($requestDetails['package_id']) && $requestDetails['package_id'] == $pkg->id) ? 'selected' : '' }}>{{ $pkg->name }} @if($pkg->price) - {{ number_format($pkg->price, 2) }} @endif</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="form-text">اختيار باقة سيحدد التحاليل تلقائياً في الطلب.</div>
                                                    <div class="mt-2" id="packageTestsPreview"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingGeneral"> 
                                            <button class="accordion-button {{ $serviceSelectionType == 'general' ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#generalTestsSection" aria-expanded="{{ $serviceSelectionType == 'general' ? 'true' : 'false' }}" aria-controls="generalTestsSection">
                                                <strong>تحاليل عامة</strong>
                                            </button>
                                        </h2>
                                        <div id="generalTestsSection" class="accordion-collapse collapse {{ $serviceSelectionType == 'general' ? 'show' : '' }}" aria-labelledby="headingGeneral" data-bs-parent="#mainSelectionAccordion">
                                            <div class="accordion-body">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-flask me-1"></i>
                                            <strong>اختر التحاليل المطلوبة:</strong>
                                        </label>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                                            <input type="text" class="form-control" id="labSearchInput" placeholder="ابحث عن تحليل بالاسم أو الرمز...">
                                            <button class="btn btn-outline-secondary" type="button" id="clearLabSearch"><i class="fas fa-times"></i></button>
                                        </div>

                                        <div class="border rounded p-3" id="labTestsContainer" style="max-height: 400px; overflow-y: auto;">
                                            @php
                                                $labTests = \App\Models\LabTest::where('is_active', true)->orderBy('main_category')->orderBy('name')->get()->groupBy('main_category');
                                                $userFavorites = \App\Models\UserLabTestStat::where('user_id', auth()->id())->where('is_favorite', true)->pluck('lab_test_id')->toArray();
                                            @endphp
                                            @foreach($labTests as $category => $tests)
                                                <div class="mb-3">
                                                    <h6 class="text-primary border-bottom pb-2"><i class="fas fa-folder-open me-1"></i>{{ $category }}</h6>
                                                    @foreach($tests as $test)
                                                        <div class="form-check d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <input class="form-check-input lab-test-checkbox" type="checkbox" name="lab_test_ids[]" value="{{ $test->id }}" id="lab_{{ $test->id }}" {{ in_array($test->id, $requestDetails['lab_test_ids'] ?? []) ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="lab_{{ $test->id }}">{{ $test->name }} <small class="text-muted">({{ $test->code }})</small></label>
                                                            </div>
                                                            <button type="button" class="btn btn-sm btn-link p-0 toggle-favorite" data-test-id="{{ $test->id }}" data-is-favorite="{{ in_array($test->id, $userFavorites) ? '1' : '0' }}" title="إضافة/إزالة من المفضلة">
                                                                <span class="favorite-icon">{{ in_array($test->id, $userFavorites) ? '⭐' : '☆' }}</span>
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>

                                        <div id="labSelectedCount" class="mt-2 text-muted small"></div>
                                    </div>
                                        </div>
                                    </div>
                                </div>
                                </div>

                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-check-circle me-2"></i>
                                    تأكيد التحاليل وإرسال للكاشير
                                </button>
                            </form>
                        </div>
                    </div>
                    @else
                    @php
                        $testsList = buildSelectedLabTests($requestDetails);
                        if (!isset($savedTestResults) || !is_array($savedTestResults)) {
                            $savedTestResults = [];
                        }
                    @endphp
                    @endif
                    @endif

                </div>
            </div>
        </div>
    </div>

    <!-- عرض النتائج إذا كانت موجودة -->
    @if($request->result && $request->status == 'completed')
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            نتائج الفحص
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
                            $testResults = [];
                            if (is_array($resultData)) {
                                $testResults = $resultData['test_results'] ?? [];
                            }
                            $notes = is_array($resultData) ? ($resultData['notes'] ?? $request->result) : $request->result;
                        @endphp

                        @if(count((array) $testResults) > 0)
                            <div class="table-responsive mb-3">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>الفحص</th>
                                            <th>القيمة</th>
                                            <th>الوحدة</th>
                                            <th>المرجع</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($testResults as $testName => $testData)
                                        <tr>
                                            <td><strong>{{ $testName }}</strong></td>
                                            <td>{{ (is_array($testData) ? ($testData['value'] ?? '-') : '-') }}</td>
                                            <td>{{ (is_array($testData) ? ($testData['unit'] ?? '-') : '-') }}</td>
                                            <td>{{ (is_array($testData) ? ($testData['reference'] ?? '-') : '-') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if($notes)
                            <div class="mt-3">
                                <h6>ملاحظات إضافية:</h6>
                                <p class="text-muted">{{ $notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @php
        $reqDetails = $request->details;
        if (is_string($reqDetails)) {
            $decoded = json_decode($reqDetails, true);
            $reqDetails = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($reqDetails)) {
            $reqDetails = [];
        }
        $hasSelectedServices = !empty($reqDetails['lab_test_ids']) || !empty($reqDetails['package_id']) || !empty($reqDetails['radiology_type_ids']) || !empty($reqDetails['services_selected']);
    @endphp
    @if($request->status !== 'pending_service_selection' || $hasSelectedServices)
    <!-- نموذج إدخال نتائج التحاليل -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-flask me-2"></i>
                        إدخال نتائج التحاليل
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('lab.update', $request) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @if($request->type == 'lab')
                        @php
                            $patientGender = $request->visit->patient?->gender ?? 'both';
                            $patientAge    = (int) ($request->visit->patient?->age ?? 0);
                            $requestDetails = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                            $testsList = buildSelectedLabTests($requestDetails);
                            // بناء خريطة lab_test_id -> LabTest مرة واحدة
                            $labTestMap = \App\Models\LabTest::whereIn('name', $testsList)->get()->keyBy('name');
                        @endphp
                        <div class="row">
                            <div class="col-12">
                                    <div class="lab-results-section">
                                        <div class="table-responsive">
                                            <table class="table table-hover table-bordered align-middle" id="resultsTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-center" style="width:40px;">#</th>
                                                        <th>التحليل</th>
                                                        <th style="width:180px;">القيمة</th>
                                                        <th style="width:90px;">الوحدة</th>
                                                        <th style="width:130px;">المرجع</th>
                                                        <th style="width:90px;" class="text-center">الحالة</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($testsList as $index => $test)
                                                        @php
                                                            $testIcon  = getTestIcon($test);
                                                            $labTestObj = $labTestMap[$test] ?? null;
                                                            $refObj    = $labTestObj
                                                                ? \App\Models\LabTestReference::forPatient($labTestObj->id, $patientGender, $patientAge)
                                                                : null;
                                                            $refDisplay  = $refObj ? $refObj->range_display : '—';
                                                            $refMin      = $refObj?->ref_min;
                                                            $refMax      = $refObj?->ref_max;
                                                            $unitDisplay = $refObj?->unit ?? getTestUnit($test, $labTests);
                                                            $savedVal    = (is_array($savedTestResults) && isset($savedTestResults[$test]) && is_array($savedTestResults[$test]))
                                                                            ? $savedTestResults[$test]['value'] : '';
                                                        @endphp
                                                        <tr class="test-row" data-test="{{ $test }}"
                                                            data-ref-min="{{ $refMin }}"
                                                            data-ref-max="{{ $refMax }}">
                                                            <td class="text-center text-muted small">{{ $index + 1 }}</td>
                                                            <td>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <i class="{{ $testIcon }}"></i>
                                                                    <strong>{{ $test }}</strong>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <input type="text"
                                                                       class="form-control form-control-sm test-value"
                                                                       name="test_results[{{ $test }}][value]"
                                                                       value="{{ old('test_results.' . $test . '.value', $savedVal) }}"
                                                                       placeholder="أدخل القيمة"
                                                                       tabindex="{{ $index + 1 }}"
                                                                       data-test="{{ $test }}">
                                                                <input type="hidden" name="test_results[{{ $test }}][unit]" value="{{ $unitDisplay }}">
                                                            </td>
                                                            <td class="text-muted small">{{ $unitDisplay }}</td>
                                                            <td>
                                                                @if($refObj)
                                                                    <span class="badge bg-light text-dark border">{{ $refDisplay }}</span>
                                                                @else
                                                                    <span class="text-muted small">غير محدد</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="result-flag" id="flag-{{ $index }}">
                                                                    <i class="fas fa-circle text-muted small"></i>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- ملخص سريع للنتائج -->
                                        <div class="results-summary mt-3 p-3 bg-light rounded">
                                            <h6 class="mb-2">
                                                <i class="fas fa-chart-bar text-primary me-2"></i>
                                                ملخص النتائج
                                            </h6>
                                            <div class="summary-stats">
                                                <span class="stat-item">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                    <span id="normal-count">0</span> طبيعي
                                                </span>
                                                <span class="stat-item">
                                                    <i class="fas fa-arrow-up text-danger"></i>
                                                    <span id="high-count">0</span> مرتفع
                                                </span>
                                                <span class="stat-item">
                                                    <i class="fas fa-arrow-down text-warning"></i>
                                                    <span id="low-count">0</span> منخفض
                                                </span>
                                                <span class="stat-item">
                                                    <i class="fas fa-question-circle text-muted"></i>
                                                    <span id="pending-count">{{ is_countable($testsList) ? count($testsList) : 0 }}</span> غير مكتمل
                                                </span>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <label for="result" class="form-label">
                                                <i class="fas fa-comment-medical text-secondary me-2"></i>
                                                ملاحظات إضافية
                                            </label>
                                            <textarea class="form-control"
                                                      id="result"
                                                      name="result"
                                                      rows="3"
                                                      placeholder="ملاحظات إضافية حول النتائج أو تفسيرها">{{ old('result', $savedNotes) }}</textarea>
                                            <small class="text-muted">
                                                <i class="fas fa-lightbulb text-warning me-1"></i>
                                                اكتب أي ملاحظات مهمة أو تفسيرات للنتائج
                                            </small>
                                        </div>

                                        <!-- قسم المراجع المرجعية -->
                                        <div class="mt-4">
                                            <details class="mb-3">
                                                <summary class="text-primary fw-bold cursor-pointer">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    القيم المرجعية الطبيعية (معلومات فقط)
                                                </summary>
                                                <div class="mt-3 p-3 bg-light rounded">
                                                    <small class="text-muted">
                                                        <strong>ملاحظة:</strong> هذه القيم تقريبية عامة وتختلف حسب المختبر، عمر المريض، وجنسه. استشر الطبيب للقيم الصحيحة.
                                                    </small>
                                                    <ul class="mt-2 mb-0 small">
                                                        <li><strong>سكر الدم:</strong> 70-140 mg/dL</li>
                                                        <li><strong>ضغط الدم:</strong> أقل من 140 mmHg</li>
                                                        <li><strong>الكوليسترول:</strong> أقل من 200 mg/dL</li>
                                                        <li><strong>البيليروبين:</strong> أقل من 1.2 mg/dL</li>
                                                        <li><strong>الكرياتينين:</strong> 0.6-1.2 mg/dL</li>
                                                        <li><strong>اليوريا:</strong> 7-50 mg/dL</li>
                                                        <li><strong>SGOT (AST):</strong> أقل من 40 U/L</li>
                                                        <li><strong>SGPT (ALT):</strong> أقل من 41 U/L</li>
                                                        <li><strong>الهيموغلوبين:</strong> 12-16 g/dL (نساء)، 14-18 g/dL (رجال)</li>
                                                        <li><strong>الكرات البيضاء:</strong> 4,000-11,000 /µL</li>
                                                        <li><strong>الصفائح:</strong> 150,000-450,000 /µL</li>
                                                    </ul>
                                                </div>
                                            </details>
                                        </div>
                                    </div>
                            </div>
                        </div>
                        @else
                        <div class="mb-3">
                            <label for="result" class="form-label">النتيجة / التقرير</label>
                            <textarea class="form-control" id="result" name="result"
                                      rows="4" placeholder="أدخل نتائج الفحص أو التقرير">{{ old('result', $request->result) }}</textarea>
                        </div>
                        @endif

                        <hr>
                        <div class="row align-items-center g-2">
                            <div class="col-md-3">
                                <label for="status" class="form-label mb-1 fw-bold">حالة الطلب</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="pending" {{ $request->status == 'pending' ? 'selected' : '' }}>في الانتظار</option>
                                    <option value="in_progress" {{ $request->status == 'in_progress' ? 'selected' : '' }}>قيد التنفيذ</option>
                                    <option value="completed" {{ $request->status == 'completed' ? 'selected' : '' }}>مكتمل</option>
                                </select>
                            </div>
                            <div class="col-md-9 d-flex justify-content-end align-items-end gap-2 pt-3">
                                @if($request->status == 'pending')
                                    <button type="button" class="btn btn-outline-primary" onclick="startProcessing()">
                                        <i class="fas fa-play me-1"></i>
                                        بدء المعالجة
                                    </button>
                                @endif
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i>
                                    حفظ النتائج
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($request->status !== 'pending_service_selection')
    <!-- تاريخ الزيارة -->
    <div class="row">
        <div class="col-12">
            @if($request->visit)
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        معلومات الزيارة
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>تاريخ الزيارة:</strong> {{ $request->visit->visit_date ? $request->visit->visit_date->format('Y-m-d') : 'غير محدد' }}</p>
                            <p><strong>نوع الزيارة:</strong> {{ $request->visit->visit_type_text ?? 'غير محدد' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>الشكوى الرئيسية:</strong></p>
                            <p class="text-muted">{{ $request->visit->chief_complaint ? \Illuminate\Support\Str::limit($request->visit->chief_complaint, 100) : 'غير محدد' }}</p>
                        </div>
                    </div>
                    @if($request->visit->diagnosis)
                    <hr>
                    <p><strong>التشخيص:</strong></p>
                    @php $diag = is_string($request->visit->diagnosis) ? json_decode($request->visit->diagnosis, true) : $request->visit->diagnosis; @endphp
                    @if($diag['code'] ?? false)
                        @if($diag['code'] === 'other' && isset($diag['custom_code']))
                            <p class="text-muted"><strong>رمز ICD:</strong> {{ $diag['custom_code'] }}</p>
                        @elseif($diag['code'] !== 'other')
                            <p class="text-muted"><strong>رمز ICD-10:</strong> {{ $diag['code'] }}</p>
                        @endif
                    @endif
                    <p class="text-muted"><strong>الوصف:</strong> {{ $diag['description'] ?? $request->visit->diagnosis }}</p>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>



<script>
function startProcessing() {
    document.getElementById('status').value = 'in_progress';
    document.querySelector('form').submit();
}

// ──────────────── تلوين نتائج التحاليل تلقائياً ────────────────
function evaluateRow(row) {
    const input   = row.querySelector('.test-value');
    const flag    = row.querySelector('.result-flag');
    if (!input || !flag) return null;

    const val    = input.value.trim();
    const refMin = row.dataset.refMin !== '' ? parseFloat(row.dataset.refMin) : null;
    const refMax = row.dataset.refMax !== '' ? parseFloat(row.dataset.refMax) : null;

    row.classList.remove('table-success', 'table-danger', 'table-warning');
    flag.innerHTML = '<i class="fas fa-circle text-muted small"></i>';

    if (val === '') return null;

    const numeric = parseFloat(val);
    if (isNaN(numeric) || (refMin === null && refMax === null)) return 'unknown';

    if (refMin !== null && numeric < refMin) {
        row.classList.add('table-warning');
        flag.innerHTML = '<span class="badge bg-warning text-dark">↓ منخفض</span>';
        return 'low';
    }
    if (refMax !== null && numeric > refMax) {
        row.classList.add('table-danger');
        flag.innerHTML = '<span class="badge bg-danger">↑ مرتفع</span>';
        return 'high';
    }
    row.classList.add('table-success');
    flag.innerHTML = '<span class="badge bg-success">✓ طبيعي</span>';
    return 'normal';
}

function updateSummary() {
    let normal = 0, high = 0, low = 0, pending = 0;
    document.querySelectorAll('#resultsTable .test-row').forEach(row => {
        const r = evaluateRow(row);
        if (r === 'normal') normal++;
        else if (r === 'high') high++;
        else if (r === 'low') low++;
        else pending++;
    });
    const n = document.getElementById('normal-count');
    const h = document.getElementById('high-count');
    const l = document.getElementById('low-count');
    const p = document.getElementById('pending-count');
    if (n) n.textContent = normal;
    if (h) h.textContent = high;
    if (l) l.textContent = low;
    if (p) p.textContent = pending;
}

function initializeLabResults() {
    document.querySelectorAll('#resultsTable .test-value').forEach((input, i) => {
        input.addEventListener('input', function () {
            evaluateRow(this.closest('.test-row'));
            updateSummary();
        });
        // تقييم القيم الموجودة مسبقاً عند التحميل
        if (input.value.trim() !== '') {
            evaluateRow(input.closest('.test-row'));
        }
    });
    updateSummary();
}

// وظائف تحليل النتائج المخبرية
document.addEventListener('DOMContentLoaded', function() {
    initializeLabResults();
    
    // تفعيل tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // إضافة تأثيرات تفاعلية لعرض الوحدات
    const unitDisplays = document.querySelectorAll('.unit-display');
    unitDisplays.forEach(display => {
        display.addEventListener('mouseover', function() {
            this.style.transform = 'scale(1.05)';
        });
        display.addEventListener('mouseout', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // تحديث عداد التحاليل عند تغيير checkboxes
    const labCheckboxes = document.querySelectorAll('.lab-test-checkbox');
    labCheckboxes.forEach(cb => cb.addEventListener('change', updateLabSelectedCount));
    updateLabSelectedCount();

    // اختيار صيغة العرض بين الباقة والتحاليل العامة
    const serviceTypeRadios = document.querySelectorAll('input[name="service_selection_type"]');
    const packageSection = document.getElementById('packageSection');
    const generalTestsSection = document.getElementById('generalTestsSection');
    const hiddenServiceType = document.getElementById('serviceSelectionType');

    function toggleServiceSelection(type) {
        if (!packageSection || !generalTestsSection) return;

        const packageCollapse = new bootstrap.Collapse(packageSection, { toggle: false });
        const generalCollapse = new bootstrap.Collapse(generalTestsSection, { toggle: false });

        if (type === 'package') {
            packageCollapse.show();
            generalCollapse.hide();
        } else {
            packageCollapse.hide();
            generalCollapse.show();
        }

        document.querySelectorAll('.lab-test-checkbox').forEach(cb => cb.checked = false);
        updateLabSelectedCount();
    }

    const activeRadio = document.querySelector('input[name="service_selection_type"]:checked');
    if (activeRadio) {
        toggleServiceSelection(activeRadio.value);
    }

    // Package -> auto-check handlers
    const packageSelect = document.getElementById('packageSelect');
    if (packageSelect) {
        packageSelect.addEventListener('change', function() {
            const raw = this.selectedOptions[0]?.dataset?.tests || '[]';
            const rawNames = this.selectedOptions[0]?.dataset?.testNames || '[]';
            let ids = [];
            let names = [];
            try { ids = JSON.parse(raw); } catch(e) { ids = []; }
            try { names = JSON.parse(rawNames); } catch(e) { names = []; }

            applyPackageByIds(ids);
            showSelectedPackageTests(names);

            // إذا تم اختيار باقة، اجعل خدمة الاختيار باقة واظهر قسم الباقات
            const pkgRadio = document.getElementById('selectPackage');
            if (pkgRadio) {
                pkgRadio.checked = true;
                toggleServiceSelection('package');
            }
            updateLabSelectedCount();
        });

        // عرض باقة محددة عند التحميل إذا كانت موجودة
        const selectedOption = packageSelect.selectedOptions[0];
        if (selectedOption && selectedOption.value) {
            const rawNames = selectedOption.dataset.testNames || '[]';
            let names = [];
            try { names = JSON.parse(rawNames); } catch(e) { names = []; }
            showSelectedPackageTests(names);
        }
    }

    function showSelectedPackageTests(names) {
        const preview = document.getElementById('packageTestsPreview');
        if (!preview) return;

        if (!Array.isArray(names) || names.length === 0) {
            preview.innerHTML = '<small class="text-muted">لم يتم اختيار باقة أو لا توجد تحاليل في الباقة.</small>';
            return;
        }

        const listItems = names.map(name => `<span class="badge bg-info text-dark me-1 mb-1">${name}</span>`).join('');
        preview.innerHTML = `<div><strong>تحاليل الباقة:</strong><br>${listItems}</div>`;
    }

});

    // دالة لحساب حالة النتيجة بناءً على اسم التحليل والقيمة
    function evaluateTestResult(testName, value, testRow, indicator, statusText) {
        // ملاحظة مهمة: هذه قيم مرجعية تقريبية عامة فقط
        // القيم الحقيقية تختلف حسب المختبر، عمر المريض، جنسه، والوحدات المستخدمة
        // يجب دائمًا مراجعة الطبيب أو المختبر للقيم المرجعية الصحيحة
        let resultType = 'normal';
        let statusMessage = '<i class="fas fa-info-circle text-info"></i> تم إدخال القيمة - راجع المرجع الطبي';
        let indicatorClass = 'fas fa-circle text-info';

    // تحليلات أساسية لأنواع شائعة من الفحوصات (قيم تقريبية عامة)
    if (testName.includes('سكر') || testName.includes('glucose')) {
        if (value < 70) {
            resultType = 'low';
            statusMessage = '<i class="fas fa-arrow-down text-warning"></i> قيمة منخفضة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-down text-warning';
        } else if (value > 140) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (70-140 mg/dL)';
        }
    } else if (testName.includes('ضغط') || testName.includes('pressure')) {
        if (value > 140) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<140 mmHg)';
        }
    } else if (testName.includes('كوليسترول') || testName.includes('cholesterol')) {
        if (value > 200) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<200 mg/dL)';
        }
    } else if (testName.includes('بيليروبين') || testName.includes('bilirubin')) {
        if (value > 1.2) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<1.2 mg/dL)';
        }
    } else if (testName.includes('كرياتينين') || testName.includes('creatinine')) {
        if (value > 1.2) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<1.2 mg/dL)';
        }
    } else if (testName.includes('يوريا') || testName.includes('urea')) {
        if (value > 50) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<50 mg/dL)';
        }
    } else if (testName.includes('sgot') || testName.includes('ast')) {
        if (value > 40) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<40 U/L)';
        }
    } else if (testName.includes('sgpt') || testName.includes('alt')) {
        if (value > 41) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<41 U/L)';
        }
    } else if (testName.includes('الصفائح') || testName.includes('platelets')) {
        if (value < 150000) {
            resultType = 'low';
            statusMessage = '<i class="fas fa-arrow-down text-warning"></i> قيمة منخفضة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-down text-warning';
        } else if (value > 450000) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (150k-450k /µL)';
        }
    } else if (testName.includes('الهيموغلوبين') || testName.includes('hemoglobin')) {
        if (value < 12) {
            resultType = 'low';
            statusMessage = '<i class="fas fa-arrow-down text-warning"></i> قيمة منخفضة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-down text-warning';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (>12 g/dL)';
        }
    } else if (testName.includes('الكرات البيضاء') || testName.includes('wbc')) {
        if (value < 4000) {
            resultType = 'low';
            statusMessage = '<i class="fas fa-arrow-down text-warning"></i> قيمة منخفضة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-down text-warning';
        } else if (value > 11000) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (4k-11k /µL)';
        }
    } else {
        // للفحوصات غير المحددة، نعتبرها طبيعية
        statusMessage = '<i class="fas fa-info-circle text-info"></i> تم إدخال القيمة - راجع المرجع الطبي';
    }

    // تحديث المؤشرات البصرية
    indicator.className = indicatorClass;
    statusText.innerHTML = statusMessage;

    // تحديث فئة الصف
    testRow.classList.remove('result-normal', 'result-high', 'result-low');
    testRow.classList.add('result-' + resultType);

    updateSummary();
}

function updateSummary() {
    const testRows = document.querySelectorAll('.test-row');
    let normal = 0, high = 0, low = 0, pending = 0;

    testRows.forEach(row => {
        const input = row.querySelector('.test-value');
        const value = parseFloat(input.value);

        if (isNaN(value) || value === 0) {
            pending++;
        } else if (row.classList.contains('result-high')) {
            high++;
        } else if (row.classList.contains('result-low')) {
            low++;
        } else {
            normal++;
        }
    });

    document.getElementById('normal-count').textContent = normal;
    document.getElementById('high-count').textContent = high;
    document.getElementById('low-count').textContent = low;
    document.getElementById('pending-count').textContent = pending;
}

// ──────────────── تفعيل/إلغاء المفضلة ────────────────
document.addEventListener('DOMContentLoaded', function() {
    // تفاعل pills المفضلات
    document.querySelectorAll('.favorite-pill').forEach(pill => {
        const checkbox = pill.querySelector('input[type="checkbox"]');
        const pillContent = pill.querySelector('.pill-content');
        
        pill.addEventListener('click', function() {
            checkbox.checked = !checkbox.checked;
            updatePillStyle(pill, checkbox.checked);
        });
        
        // تحديث الستايل عند تغيير الـcheckbox من مكان آخر
        checkbox.addEventListener('change', function() {
            updatePillStyle(pill, this.checked);
        });
    });
    
    function updatePillStyle(pill, isChecked) {
        const pillContent = pill.querySelector('.pill-content');
        const icon = pill.querySelector('.fa-flask');
        const text = pill.querySelector('span[style*="font-weight"]');
        const badge = pill.querySelector('.badge');
        
        if (isChecked) {
            pill.classList.add('active');
            pillContent.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
            pillContent.style.borderColor = '#28a745';
            icon.style.color = 'white';
            text.style.color = 'white';
            badge.style.background = 'rgba(255,255,255,0.3)';
            badge.style.color = 'white';
        } else {
            pill.classList.remove('active');
            pillContent.style.background = 'white';
            pillContent.style.borderColor = '#FFD700';
            icon.style.color = '#FF6B00';
            text.style.color = '#2c3e50';
            badge.style.background = '#FFF3CD';
            badge.style.color = '#856404';
        }
    }
    
    // زر النجمة في القائمة العادية
    document.querySelectorAll('.toggle-favorite').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const testId = this.dataset.testId;
            const isFavorite = this.dataset.isFavorite === '1';
            const icon = this.querySelector('.favorite-icon');

            fetch(`/lab-tests/${testId}/toggle-favorite`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.dataset.isFavorite = data.is_favorite ? '1' : '0';
                    icon.textContent = data.is_favorite ? '⭐' : '☆';
                    
                    // رسالة نجاح بسيطة
                    if (data.is_favorite) {
                        console.log('تمت الإضافة للمفضلة');
                    } else {
                        console.log('تمت الإزالة من المفضلة');
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
});
</script>

<style>
/* تصميم محسن للقراءات المخبرية */
.lab-results-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 20px;
    margin: 15px 0;
    border: 1px solid #dee2e6;
}

.lab-tests-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.lab-test-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
    overflow: hidden;
}

.lab-test-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    border-color: #007bff;
}

.lab-test-card.result-normal {
    border-color: #28a745;
    background: linear-gradient(135deg, #f8fff8 0%, #ffffff 100%);
}

.lab-test-card.result-high {
    border-color: #dc3545;
    background: linear-gradient(135deg, #fff8f8 0%, #ffffff 100%);
}

.lab-test-card.result-low {
    border-color: #ffc107;
    background: linear-gradient(135deg, #fffef8 0%, #ffffff 100%);
}

.test-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 12px 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.test-icon {
    background: rgba(255,255,255,0.2);
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.3s ease;
}

.test-icon:hover {
    transform: scale(1.1);
    background: rgba(255,255,255,0.3);
}

.test-info h6 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.test-info small {
    font-size: 11px;
    opacity: 0.9;
}

.test-inputs {
    padding: 15px;
}

.input-group-wrapper {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.input-group-text {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #6c757d;
    min-width: 40px;
    justify-content: center;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.result-indicator {
    background: transparent !important;
    border-left: none !important;
}

.test-status {
    padding: 8px 15px 12px;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    text-align: center;
}

.status-text {
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.results-summary {
    border: 1px solid #dee2e6;
}

.summary-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    justify-content: center;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    font-weight: 500;
}

.stat-item i {
    font-size: 16px;
}

/* تحسينات إضافية للتصميم */
.lab-results-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 20px;
    margin: 15px 0;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.results-summary {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-top: 20px;
}

.results-summary h6 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 15px;
}

.stat-item {
    background: white;
    padding: 8px 12px;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

/* تحسينات للأزرار */
.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
}

/* تحسينات للنصوص */
.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

.text-muted {
    color: #6c757d !important;
}

/* تحسينات للبطاقات */
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
    border: none;
    font-weight: 600;
}

/* تحسينات للتنبيهات */
.alert {
    border: none;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border-left: 4px solid #ffc107;
}

/* تحسينات لقسم المراجع */
details {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 10px 15px;
    background: #f8f9fa;
}

details summary {
    list-style: none;
    cursor: pointer;
    user-select: none;
}

details summary::-webkit-details-marker {
    display: none;
}

details summary::before {
    content: '▶';
    margin-right: 8px;
    transition: transform 0.3s ease;
}

details[open] summary::before {
    transform: rotate(90deg);
}

details summary:hover {
    color: #0056b3;
}

details div {
    margin-top: 15px;
    padding: 15px;
    background: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

details ul li {
    margin-bottom: 5px;
    color: #495057;
}

/* تحسينات للأيقونات */
.fas, .far {
    transition: all 0.3s ease;
}

.test-icon i {
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));
}

/* تأثيرات انتقال سلسة */
.lab-test-card {
    position: relative;
}

.lab-test-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: #007bff;
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.lab-test-card:hover::before,
.lab-test-card.result-normal::before {
    background: #28a745;
    transform: scaleX(1);
}

.lab-test-card.result-high::before {
    background: #dc3545;
    transform: scaleX(1);
}

.lab-test-card.result-low::before {
    background: #ffc107;
    transform: scaleX(1);
}

/* تحسينات لوحدة القياس */
.unit-display {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 4px 8px;
    border-radius: 0 4px 4px 0;
    transition: all 0.3s ease;
}

.unit-display:hover {
    background: #e9ecef;
}

.unit-value {
    font-weight: 500;
    color: #495057;
    font-size: 0.9rem;
}

.unit-tooltip {
    cursor: help;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.unit-tooltip:hover {
    opacity: 1;
}

.unit-display[data-test*="سكر"] .unit-value,
.unit-display[data-test*="glucose"] .unit-value {
    color: #dc3545;
}

.unit-display[data-test*="ضغط"] .unit-value,
.unit-display[data-test*="pressure"] .unit-value {
    color: #0d6efd;
}

.unit-display[data-test*="كوليسترول"] .unit-value,
.unit-display[data-test*="cholesterol"] .unit-value {
    color: #fd7e14;
}

.unit-display[data-test*="كرياتينين"] .unit-value,
.unit-display[data-test*="creatinine"] .unit-value,
.unit-display[data-test*="يوريا"] .unit-value,
.unit-display[data-test*="urea"] .unit-value {
    color: #198754;
}

.unit-display[data-test*="هيموغلوبين"] .unit-value,
.unit-display[data-test*="hemoglobin"] .unit-value {
    color: #6f42c1;
}

/* تحسينات جدول التحاليل */
.table {
    margin-bottom: 0;
    --bs-table-hover-bg: rgba(0, 123, 255, 0.05);
}

.table > :not(caption) > * > * {
    padding: 0.75rem;
    vertical-align: middle;
}

.table thead th {
    background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
    font-weight: 600;
    text-align: center;
    border-bottom: 2px solid #dee2e6;
}

.test-row {
    transition: all 0.3s ease;
}

.test-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.test-row .test-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0, 123, 255, 0.1);
    transition: all 0.3s ease;
}

.test-row:hover .test-icon {
    transform: scale(1.1);
    background: rgba(0, 123, 255, 0.2);
}

.test-row .form-control {
    border-radius: 0 4px 4px 0;
}

.test-row .input-group-text {
    border-radius: 4px 0 0 4px;
}

.status-indicator {
    font-size: 1.2rem;
    display: inline-block;
    transition: all 0.3s ease;
}

.status-text {
    font-size: 0.75rem;
    color: #6c757d;
}

/* تصميم متجاوب للجدول */
.table-responsive {
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
    .table > :not(caption) > * > * {
        padding: 0.5rem;
    }
    
    .test-row .test-icon {
        width: 28px;
        height: 28px;
    }
}

/* تحسين مظهر الحقول في الجدول */
.test-row .form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    border-color: #80bdff;
}

.test-row.result-normal {
    background-color: rgba(40, 167, 69, 0.05);
}

.test-row.result-high {
    background-color: rgba(220, 53, 69, 0.05);
}

.test-row.result-low {
    background-color: rgba(255, 193, 7, 0.05);
}

/* تحسينات للأجهزة المحمولة */
@media (max-width: 768px) {
    .lab-tests-grid {
        grid-template-columns: 1fr;
    }

    .summary-stats {
        flex-direction: column;
        align-items: center;
    }

    .test-header {
        padding: 10px 12px;
    }

    .test-inputs {
        padding: 12px;
    }

    .lab-results-section {
        padding: 15px;
        margin: 10px 0;
    }

    .stat-item {
        width: 100%;
        margin-bottom: 8px;
        text-align: center;
    }

    .container-fluid {
        padding-left: 10px;
        padding-right: 10px;
    }
}

/* تحسينات للشاشات الكبيرة */
@media (min-width: 1200px) {
    .lab-tests-grid {
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    }
}

.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

.test-item {
    transition: all 0.2s ease;
}

.test-item:hover {
    background-color: #f8f9fa;
}

.form-check-input:checked + .form-check-label .check-icon {
    opacity: 1 !important;
}

.main-group-header {
    transition: all 0.3s ease;
}

.main-group-header:hover {
    opacity: 0.9;
}

.toggle-icon {
    transition: transform 0.3s ease;
}

.collapsed .toggle-icon {
    transform: rotate(-90deg);
}
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // البحث في التحاليل
    $('#testSearchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        let visibleCount = 0;
        let totalCount = 0;

        if (searchTerm === '') {
            // إظهار كل شيء عند مسح البحث
            $('.test-item').closest('.col-md-6').show();
            $('.sub-category-section').show();
            $('.main-group-section').show();
            $('#searchResultsCount').text('');
            return;
        }

        // إخفاء كل شيء أولاً
        $('.main-group-section').hide();
        $('.sub-category-section').hide();
        $('.test-item').closest('.col-md-6').hide();

        // البحث في كل اختبار
        $('.test-item').each(function() {
            totalCount++;
            const testName = $(this).find('strong').text().toLowerCase();
            const testDesc = $(this).find('.text-muted').text().toLowerCase();
            
            if (testName.includes(searchTerm) || testDesc.includes(searchTerm)) {
                visibleCount++;
                $(this).closest('.col-md-6').show();
                $(this).closest('.sub-category-section').show();
                $(this).closest('.main-group-section').show();
            }
        });

        // تحديث عداد النتائج
        if (visibleCount === 0) {
            $('#searchResultsCount').html('<i class="fas fa-exclamation-circle text-warning"></i> لا توجد نتائج للبحث');
        } else {
            $('#searchResultsCount').html(`<i class="fas fa-check-circle text-success"></i> تم العثور على ${visibleCount} نتيجة من أصل ${totalCount}`);
        }
    });

    // زر مسح البحث
    $('#clearSearch').on('click', function() {
        $('#testSearchInput').val('').trigger('keyup').focus();
    });

    // تفعيل الـ collapse للمجموعات
    $('.main-group-header').on('click', function() {
        const target = $(this).data('bs-target');
        $(target).collapse('toggle');
    });

    // تحديث أيقونة التبديل
    $('.collapse').on('shown.bs.collapse hidden.bs.collapse', function() {
        const header = $(this).prev('.main-group-header');
        const icon = header.find('.toggle-icon');
        if ($(this).hasClass('show')) {
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        } else {
            icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
        }
    });

    // تحديث أيقونات التحقق عند تغيير التحاليل
    $('.form-check-input').on('change', function() {
        const checkIcon = $(this).siblings('.form-check-label').find('.check-icon');
        if ($(this).is(':checked')) {
            checkIcon.removeClass('opacity-0');
        } else {
            checkIcon.addClass('opacity-0');
        }
    });

    // تحديث حالة الأيقونات عند التحميل
    $('.form-check-input:checked').each(function() {
        $(this).siblings('.form-check-label').find('.check-icon').removeClass('opacity-0');
    });

    // ========== البحث في قسم اختيار الخدمات للطلبات pending_service_selection ==========
    $('#labSearchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        
        if (searchTerm === '') {
            $('.lab-test-checkbox').closest('.form-check').show();
            $('#labSelectedCount').text('');
            return;
        }

        let visibleCount = 0;
        $('.lab-test-checkbox').each(function() {
            const label = $(this).siblings('label').text().toLowerCase();
            const formCheck = $(this).closest('.form-check');
            
            if (label.includes(searchTerm)) {
                formCheck.show();
                visibleCount++;
            } else {
                formCheck.hide();
            }
        });

        if (visibleCount === 0) {
            $('#labSelectedCount').html('<i class="fas fa-exclamation-circle text-warning"></i> لا توجد نتائج');
        } else {
            $('#labSelectedCount').html(`<i class="fas fa-check-circle text-success"></i> ${visibleCount} نتيجة`);
        }
    });

    // زر مسح البحث
    $('#clearLabSearch').on('click', function() {
        $('#labSearchInput').val('').trigger('keyup');
    });

    // تحديث عداد التحاليل المختارة
    $('.lab-test-checkbox').on('change', function() {
        const selectedCount = $('.lab-test-checkbox:checked').length;
        if (selectedCount > 0) {
            $('#labSelectedCount').html(`<i class="fas fa-check-circle text-success"></i> تم اختيار ${selectedCount} تحليل`);
        } else {
            $('#labSelectedCount').text('');
        }
    });

    // ========== البحث في قسم اختيار أنواع الأشعة ==========
    $('#radiologySearchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase().trim();

        if (searchTerm === '') {
            $('.radiology-type-checkbox').closest('.form-check').show();
            $('#radiologySelectedCount').text('');
            return;
        }

        let visibleCount = 0;
        $('.radiology-type-checkbox').each(function() {
            const label = $(this).siblings('label').text().toLowerCase();
            const formCheck = $(this).closest('.form-check');

            if (label.includes(searchTerm)) {
                formCheck.show();
                visibleCount++;
            } else {
                formCheck.hide();
            }
        });

        if (visibleCount === 0) {
            $('#radiologySelectedCount').html('<i class="fas fa-exclamation-circle text-warning"></i> لا توجد نتائج');
        } else {
            $('#radiologySelectedCount').html(`<i class="fas fa-check-circle text-success"></i> ${visibleCount} نتيجة`);
        }
    });

    // زر مسح البحث للأشعة
    $('#clearRadiologySearch').on('click', function() {
        $('#radiologySearchInput').val('').trigger('keyup');
    });

    // تحديث عداد أنواع الأشعة المختارة
    $('.radiology-type-checkbox').on('change', function() {
        const selectedCount = $('.radiology-type-checkbox:checked').length;
        if (selectedCount > 0) {
            $('#radiologySelectedCount').html(`<i class="fas fa-check-circle text-success"></i> تم اختيار ${selectedCount} نوع إشعة`);
        } else {
            $('#radiologySelectedCount').text('');
        }
    });

    // بحث وعداد في مودال إضافة التحاليل
    $('#appendTestSearch').on('input', function() {
        const term = $(this).val().toLowerCase();
        $('.append-test-item').each(function() {
            const label = $(this).find('label').text().toLowerCase();
            $(this).toggle(label.includes(term));
        });
        $('.append-test-group').each(function() {
            $(this).toggle($(this).find('.append-test-item:visible').length > 0);
        });
    });

    $('#clearAppendSearch').on('click', function() {
        $('#appendTestSearch').val('').trigger('input');
    });

    $(document).on('click', '.group-picker', function() {
        const groupIds = $(this).data('test-ids') || [];

        groupIds.forEach(function(id) {
            const checkbox = $(`#append_${id}`);
            if (checkbox.length && !checkbox.prop('disabled')) {
                checkbox.prop('checked', true).trigger('change');
            }
        });

        const count = $('.append-test-cb:checked').length;
        $('#appendSelectedCount').html(count > 0
            ? `<i class="fas fa-check-circle text-success"></i> تم اختيار ${count} تحليل`
            : '');
    });

    $(document).on('change', '.append-test-cb', function() {
        const count = $('.append-test-cb:checked').length;
        $('#appendSelectedCount').html(count > 0
            ? `<i class="fas fa-check-circle text-success"></i> تم اختيار ${count} تحليل`
            : '');
    });
});
</script>
@endsection
