<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>استبيان تقييم غرفة الرقود | مستشفى الكفاءات الأهلي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            padding-bottom: 60px;
        }
        .header-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            border-radius: 0 0 28px 28px;
            color: #fff;
            padding: 30px 20px 24px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
        }
        .survey-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.2s ease;
        }
        .rating-group {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 6px;
            margin-top: 10px;
        }
        .rating-option input[type="radio"] {
            display: none;
        }
        .rating-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px 4px;
            border-radius: 14px;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.78rem;
            font-weight: 700;
            color: #64748b;
            text-align: center;
            min-height: 68px;
        }
        .rating-label .emoji {
            font-size: 1.25rem;
            margin-bottom: 3px;
        }
        .rating-option input[type="radio"]:checked + .rating-label {
            border-color: #2563eb;
            background: #eff6ff;
            color: #1d4ed8;
            transform: scale(1.04);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }
        /* Color hints per level */
        .opt-5 input[type="radio"]:checked + .rating-label { border-color: #16a34a; background: #f0fdf4; color: #15803d; }
        .opt-4 input[type="radio"]:checked + .rating-label { border-color: #0284c7; background: #f0f9ff; color: #0369a1; }
        .opt-3 input[type="radio"]:checked + .rating-label { border-color: #eab308; background: #fefce8; color: #a16207; }
        .opt-2 input[type="radio"]:checked + .rating-label { border-color: #f97316; background: #fff7ed; color: #c2410c; }
        .opt-1 input[type="radio"]:checked + .rating-label { border-color: #dc2626; background: #fef2f2; color: #b91c1c; }
        
        .nps-group {
            display: flex;
            justify-content: space-between;
            gap: 4px;
            overflow-x: auto;
            padding: 4px 0;
        }
        .nps-box {
            flex: 1;
            min-width: 32px;
        }
        .nps-box input[type="radio"] { display: none; }
        .nps-label {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 44px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #fff;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
        }
        .nps-box input[type="radio"]:checked + .nps-label {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
        }
        .btn-submit {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 16px;
            padding: 15px;
            font-weight: 800;
            font-size: 1.1rem;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
            width: 100%;
            transition: all 0.2s ease;
        }
        .btn-submit:hover, .btn-submit:active {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <!-- Header Banner -->
    <header class="header-card mb-4 text-center">
        <div class="mb-2">
            <span class="badge bg-warning bg-opacity-25 text-warning px-3 py-1.5 rounded-pill fw-bold border border-warning border-opacity-25">
                <i class="fas fa-heart text-danger me-1"></i> رأيكم أمانة ورعايتكم شرفنا
            </span>
        </div>
        <h4 class="fw-bold mb-1">مستشفى الكفاءات الأهلي</h4>
        <h5 class="fw-semibold text-light opacity-90 mb-3" style="font-size: 1.15rem;">استبيان تقييم غرفة الرقود وجودة الخدمة</h5>
        
        <div class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-10 px-3 py-2 rounded-pill small border border-white border-opacity-10 flex-wrap justify-content-center">
            @if($survey->room)
                <span><i class="fas fa-door-open text-warning me-1"></i> الغرفة: <strong>{{ $survey->room->room_number }}</strong></span>
                <span>•</span>
            @endif
            @if($survey->location)
                <span><i class="fas fa-layer-group text-info me-1"></i> {{ $survey->location->name }}</span>
                <span>•</span>
            @endif
            <span><i class="far fa-calendar-alt text-light me-1"></i> {{ date('Y-m-d') }}</span>
        </div>
    </header>

    <div class="container px-3" style="max-width: 650px;">

        @if($errors->any())
            <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4">
                <i class="fas fa-exclamation-circle me-1"></i> يرجى التأكد من استكمال الحقول المطلوبة.
            </div>
        @endif

        <form method="POST" action="{{ route('inpatient-surveys.public.submit', ['uuid' => $survey->uuid]) }}" id="publicSurveyForm">
            @csrf

            <!-- المقدمة اللطيفة -->
            <div class="alert alert-light border-0 shadow-xs rounded-4 mb-4 text-muted small p-3">
                <i class="fas fa-info-circle text-primary me-1 fs-6"></i>
                عزيزنا المريض ومرافقه الكريم: نشكرك على وقتك في الإجابة على هذه الأسئلة لتقييم إقامتك معنا. ملاحظاتك تحظى باهتمام مباشر من إدارة المستشفى وقسم الجودة.
            </div>

            <!-- القسم الثاني: تقييم غرفة الرقود (10 بنود) -->
            <div class="card survey-card">
                <div class="card-header bg-primary bg-opacity-10 border-0 p-3">
                    <h6 class="mb-0 fw-bold text-primary">
                        <i class="fas fa-bed me-2"></i> القسم الأول: تقييم مرافق غرفة الرقود
                    </h6>
                    <small class="text-muted">اختر درجة رضاك عن كل عنصر من عناصر الغرفة:</small>
                </div>
                <div class="card-body p-3 p-md-4">
                    
                    @php
                        $questions = [
                            'cleanliness_room' => ['title' => '1. النظافة العامة للغرفة', 'icon' => 'fas fa-broom'],
                            'cleanliness_bathroom' => ['title' => '2. نظافة الحمام والمرافق الصحية', 'icon' => 'fas fa-shower'],
                            'temperature_ac' => ['title' => '3. درجة حرارة الغرفة (التكييف / التدفئة)', 'icon' => 'fas fa-temperature-low'],
                            'lighting' => ['title' => '4. إضاءة الغرفة (قوة وسطوع الإنارة)', 'icon' => 'fas fa-lightbulb'],
                            'quietness_privacy' => ['title' => '5. مستوى الهدوء والخصوصية في الغرفة', 'icon' => 'fas fa-volume-mute'],
                            'bed_comfort' => ['title' => '6. راحة السرير والفراش والوسائد', 'icon' => 'fas fa-bed'],
                            'control_panel' => ['title' => '7. لوحة التحكم ونداء التمريض', 'icon' => 'fas fa-bell'],
                            'storage_space' => ['title' => '8. توفر مساحة كافية لتخزين الأغراض', 'icon' => 'fas fa-box'],
                            'furniture_safety' => ['title' => '9. جودة وأمان الأثاث (الكرسي، الطاولة...)', 'icon' => 'fas fa-couch'],
                            'entertainment' => ['title' => '10. وسائل الترفيه (التلفاز والإنترنت Wi-Fi)', 'icon' => 'fas fa-tv'],
                        ];
                    @endphp

                    @foreach($questions as $field => $q)
                        <div class="mb-4 pb-3 border-bottom">
                            <label class="fw-bold text-dark small d-flex align-items-center gap-2 mb-1">
                                <i class="{{ $q['icon'] }} text-primary opacity-75"></i> {{ $q['title'] }}
                            </label>
                            <div class="rating-group">
                                <div class="rating-option opt-5">
                                    <input type="radio" name="{{ $field }}" id="{{ $field }}_5" value="5" {{ old($field) == 5 ? 'checked' : '' }}>
                                    <label class="rating-label" for="{{ $field }}_5">
                                        <span class="emoji">😍</span> ممتاز
                                    </label>
                                </div>
                                <div class="rating-option opt-4">
                                    <input type="radio" name="{{ $field }}" id="{{ $field }}_4" value="4" {{ old($field) == 4 ? 'checked' : '' }}>
                                    <label class="rating-label" for="{{ $field }}_4">
                                        <span class="emoji">😊</span> جيد جداً
                                    </label>
                                </div>
                                <div class="rating-option opt-3">
                                    <input type="radio" name="{{ $field }}" id="{{ $field }}_3" value="3" {{ old($field) == 3 ? 'checked' : '' }}>
                                    <label class="rating-label" for="{{ $field }}_3">
                                        <span class="emoji">🙂</span> جيد
                                    </label>
                                </div>
                                <div class="rating-option opt-2">
                                    <input type="radio" name="{{ $field }}" id="{{ $field }}_2" value="2" {{ old($field) == 2 ? 'checked' : '' }}>
                                    <label class="rating-label" for="{{ $field }}_2">
                                        <span class="emoji">😐</span> مقبول
                                    </label>
                                </div>
                                <div class="rating-option opt-1">
                                    <input type="radio" name="{{ $field }}" id="{{ $field }}_1" value="1" {{ old($field) == 1 ? 'checked' : '' }}>
                                    <label class="rating-label" for="{{ $field }}_1">
                                        <span class="emoji">😞</span> ضعيف
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <!-- ملاحظات الغرفة -->
                    <div>
                        <label class="form-label fw-bold small text-dark"><i class="fas fa-comment-dots text-primary me-1"></i> ملاحظات إضافية حول الغرفة (إن وجدت):</label>
                        <textarea name="room_notes" class="form-control rounded-3" rows="2" placeholder="اكتب أي ملاحظة تخص إقامتك في الغرفة...">{{ old('room_notes') }}</textarea>
                    </div>

                </div>
            </div>

            <!-- تقييم الفريق الطبي والتواصل -->
            <div class="card survey-card">
                <div class="card-header bg-success bg-opacity-10 border-0 p-3">
                    <h6 class="mb-0 fw-bold text-success">
                        <i class="fas fa-user-md me-2"></i> القسم الثاني: تقييم الطبيب المعالج
                    </h6>
                    <small class="text-muted">رأيك في زيارة وتواصل الفريق الطبي معك:</small>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div class="mb-3">
                        <label class="fw-bold text-dark small mb-2 d-block">
                            كيف كانت زيارة الطبيب وتواصله واستماعه لك؟
                            @if($survey->doctor)
                                <span class="badge bg-light text-dark border ms-1">د. {{ $survey->doctor->user?->name ?? 'الطبيب المعالج' }}</span>
                            @endif
                        </label>
                        <div class="rating-group">
                            <div class="rating-option opt-5">
                                <input type="radio" name="doctor_communication" id="doc_5" value="5" {{ old('doctor_communication') == 5 ? 'checked' : '' }}>
                                <label class="rating-label" for="doc_5"><span class="emoji">😍</span> ممتاز</label>
                            </div>
                            <div class="rating-option opt-4">
                                <input type="radio" name="doctor_communication" id="doc_4" value="4" {{ old('doctor_communication') == 4 ? 'checked' : '' }}>
                                <label class="rating-label" for="doc_4"><span class="emoji">😊</span> جيد جداً</label>
                            </div>
                            <div class="rating-option opt-3">
                                <input type="radio" name="doctor_communication" id="doc_3" value="3" {{ old('doctor_communication') == 3 ? 'checked' : '' }}>
                                <label class="rating-label" for="doc_3"><span class="emoji">🙂</span> جيد</label>
                            </div>
                            <div class="rating-option opt-2">
                                <input type="radio" name="doctor_communication" id="doc_2" value="2" {{ old('doctor_communication') == 2 ? 'checked' : '' }}>
                                <label class="rating-label" for="doc_2"><span class="emoji">😐</span> مقبول</label>
                            </div>
                            <div class="rating-option opt-1">
                                <input type="radio" name="doctor_communication" id="doc_1" value="1" {{ old('doctor_communication') == 1 ? 'checked' : '' }}>
                                <label class="rating-label" for="doc_1"><span class="emoji">😞</span> ضعيف</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-bold small text-dark"><i class="fas fa-sticky-note text-success me-1"></i> ملاحظات حول الرعاية الطبية:</label>
                        <textarea name="doctor_notes" class="form-control rounded-3" rows="2" placeholder="ملاحظاتك حول الزيارات الطبية...">{{ old('doctor_notes') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- القسم الثالث: أسئلة مفتوحة ومؤشر التوصية -->
            <div class="card survey-card">
                <div class="card-header bg-warning bg-opacity-10 border-0 p-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-lightbulb text-warning me-2"></i> القسم الثالث: مقترحات التحسين ورفاهية المرضى
                    </h6>
                    <small class="text-muted">ملاحظاتك تصنع الفارق في تطوير المستشفى:</small>
                </div>
                <div class="card-body p-3 p-md-4">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">ما هي أهم أشياء تحتاج إلى تحسين في غرفة الرقود؟</label>
                        <input type="text" name="top_three_improvements[]" class="form-control rounded-3 mb-2 small" placeholder="1. مقترح أول...">
                        <input type="text" name="top_three_improvements[]" class="form-control rounded-3 mb-2 small" placeholder="2. مقترح ثانٍ...">
                        <input type="text" name="top_three_improvements[]" class="form-control rounded-3 small" placeholder="3. مقترح ثالث...">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-dark">هل لديك أي اقتراحات أخرى لتحسين راحة ورفاهية المرضى؟</label>
                        <textarea name="patient_suggestions" class="form-control rounded-3" rows="2" placeholder="أفكارك ومقترحاتك مرحب بها دائماً...">{{ old('patient_suggestions') }}</textarea>
                    </div>

                    <!-- NPS Question -->
                    <div class="pt-3 border-top">
                        <label class="form-label fw-bold small text-dark mb-1 d-block">
                            ما مدى احتمالية أن تنصح الأصدقاء أو العائلة بالعلاج في مستشفانا؟
                        </label>
                        <div class="d-flex justify-content-between text-muted" style="font-size: 0.72rem;">
                            <span>غير محتمل إطلاقاً (1)</span>
                            <span>بالتأكيد أوصي بهم (10)</span>
                        </div>
                        <div class="nps-group mt-1">
                            @for($i = 1; $i <= 10; $i++)
                                <div class="nps-box">
                                    <input type="radio" name="recommend_score_nps" id="nps_{{ $i }}" value="{{ $i }}" {{ old('recommend_score_nps') == $i ? 'checked' : '' }}>
                                    <label class="nps-label" for="nps_{{ $i }}">{{ $i }}</label>
                                </div>
                            @endfor
                        </div>
                    </div>

                </div>
            </div>

            <!-- أزرار الإرسال -->
            <div class="mt-4 mb-5 text-center">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane me-2"></i> إرسال التقييم
                </button>
                <small class="text-muted d-block mt-2 font-monospace">كود التقييم الآمن: #{{ substr($survey->uuid, 0, 8) }}</small>
            </div>

        </form>
    </div>

</body>
</html>

