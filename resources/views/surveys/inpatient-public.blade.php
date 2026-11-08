@php
    // الشعار المعتمد مع التضمين الآمن Base64 ليعمل محلياً وعلى هوستنجر فورياً
    $logoFile = public_path('images/1.jpg');
    if (!file_exists($logoFile) || filesize($logoFile) === 0) {
        $logoFile = public_path('images/logo.png');
    }
    $hospitalLogoSrc = file_exists($logoFile) && filesize($logoFile) > 0
        ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoFile))
        : asset('images/1.jpg');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>استبيان تجربة ورعاية المريض | مستشفى الكفاءات الأهلي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-navy: #0c2340;
            --secondary-navy: #16365c;
            --accent-gold: #c59b27;
            --accent-gold-light: #fef9e7;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --bg-page: #f8fafc;
            --card-border: rgba(226, 232, 240, 0.9);
            --card-shadow: 0 4px 20px -2px rgba(12, 35, 64, 0.05), 0 2px 6px -1px rgba(12, 35, 64, 0.03);
        }

        * {
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Cairo', 'Tajawal', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-dark);
            padding-bottom: 90px;
            margin: 0;
            line-height: 1.5;
        }

        /* Top Progress Bar Sticky */
        .progress-sticky-header {
            position: sticky;
            top: 0;
            z-index: 1020;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            padding: 8px 16px;
        }
        .progress-indicator {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 650px;
            margin: 0 auto;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--secondary-navy);
        }
        .custom-progress-bar {
            height: 6px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            max-width: 650px;
            margin: 5px auto 0;
        }
        .custom-progress-bar-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #c59b27 0%, #16365c 100%);
            transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 10px;
        }

        /* Hero Header */
        .luxury-hero {
            background: linear-gradient(135deg, #091a30 0%, #0c2340 50%, #16365c 100%);
            color: #ffffff;
            padding: 35px 20px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(12, 35, 64, 0.12);
        }
        .luxury-hero::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(197, 155, 39, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }
        .luxury-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(197, 155, 39, 0.15);
            border: 1px solid rgba(197, 155, 39, 0.35);
            color: #f6d884;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.3px;
            margin-bottom: 12px;
        }
        .luxury-hero h1 {
            font-size: 1.35rem;
            font-weight: 900;
            margin-bottom: 4px;
            color: #ffffff;
        }
        .luxury-hero h2 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 18px;
        }
        .hero-info-chips {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .hero-chip {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #f1f5f9;
        }

        /* Section Cards */
        .section-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid var(--card-border);
            box-shadow: var(--card-shadow);
            margin-bottom: 22px;
            overflow: hidden;
            transition: all 0.25s ease;
        }
        .section-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .section-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .section-icon.blue { background: #eff6ff; color: #1d4ed8; }
        .section-icon.gold { background: #fef9e7; color: #b45309; }
        .section-icon.teal { background: #f0fdf4; color: #15803d; }
        .section-icon.purple { background: #faf5ff; color: #7e22ce; }

        .section-title {
            font-size: 0.98rem;
            font-weight: 800;
            color: var(--primary-navy);
            margin: 0;
        }
        .section-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin: 0;
        }

        .section-body {
            padding: 20px;
        }

        /* Criterion Item */
        .criterion-item {
            padding-bottom: 18px;
            margin-bottom: 18px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .criterion-item:last-child {
            padding-bottom: 0;
            margin-bottom: 0;
            border-bottom: none;
        }
        .criterion-label {
            font-size: 0.88rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
            display: block;
        }

        /* Luxury Segmented Rating Pills */
        .segmented-rating-group {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 6px;
        }
        .segmented-rating-option {
            position: relative;
        }
        .segmented-rating-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .segmented-rating-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 8px 3px;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748b;
            text-align: center;
            min-height: 52px;
        }
        .segmented-rating-label .star-num {
            font-size: 0.95rem;
            font-weight: 900;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .segmented-rating-label .star-icon {
            font-size: 0.75rem;
            color: #cbd5e1;
            transition: color 0.2s ease;
        }

        /* Hover & Checked State */
        .segmented-rating-label:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        /* Rating Level 5 (ممتاز) */
        .opt-score-5 input[type="radio"]:checked + .segmented-rating-label {
            border-color: #16a34a;
            background: #f0fdf4;
            color: #15803d;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.15);
            transform: translateY(-2px);
        }
        .opt-score-5 input[type="radio"]:checked + .segmented-rating-label .star-icon {
            color: #16a34a;
        }

        /* Rating Level 4 (جيد جداً) */
        .opt-score-4 input[type="radio"]:checked + .segmented-rating-label {
            border-color: #0284c7;
            background: #f0f9ff;
            color: #0369a1;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.15);
            transform: translateY(-2px);
        }
        .opt-score-4 input[type="radio"]:checked + .segmented-rating-label .star-icon {
            color: #0284c7;
        }

        /* Rating Level 3 (جيد) */
        .opt-score-3 input[type="radio"]:checked + .segmented-rating-label {
            border-color: #eab308;
            background: #fefce8;
            color: #a16207;
            box-shadow: 0 4px 12px rgba(234, 179, 8, 0.15);
            transform: translateY(-2px);
        }
        .opt-score-3 input[type="radio"]:checked + .segmented-rating-label .star-icon {
            color: #eab308;
        }

        /* Rating Level 2 (مقبول) */
        .opt-score-2 input[type="radio"]:checked + .segmented-rating-label {
            border-color: #f97316;
            background: #fff7ed;
            color: #c2410c;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.15);
            transform: translateY(-2px);
        }
        .opt-score-2 input[type="radio"]:checked + .segmented-rating-label .star-icon {
            color: #f97316;
        }

        /* Rating Level 1 (ضعيف) */
        .opt-score-1 input[type="radio"]:checked + .segmented-rating-label {
            border-color: #dc2626;
            background: #fef2f2;
            color: #b91c1c;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15);
            transform: translateY(-2px);
        }
        .opt-score-1 input[type="radio"]:checked + .segmented-rating-label .star-icon {
            color: #dc2626;
        }

        /* Modern Form Inputs */
        .custom-form-input {
            width: 100%;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 0.85rem;
            color: #1e293b;
            background: #fafafa;
            transition: all 0.2s ease;
        }
        .custom-form-input:focus {
            outline: none;
            border-color: #16365c;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(22, 54, 92, 0.08);
        }

        /* NPS Slider Grid */
        .nps-slider-container {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 4px;
            margin-top: 8px;
        }
        .nps-pill {
            position: relative;
        }
        .nps-pill input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .nps-pill-label {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 42px;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background: #ffffff;
            font-weight: 800;
            font-size: 0.85rem;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .nps-pill-label:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }
        .nps-pill input[type="radio"]:checked + .nps-pill-label {
            background: linear-gradient(135deg, #0c2340 0%, #16365c 100%);
            color: #ffffff;
            border-color: #0c2340;
            box-shadow: 0 4px 12px rgba(12, 35, 64, 0.25);
            transform: translateY(-2px);
        }

        /* Floating / Sticky Submit Button */
        .submit-section {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(12px);
            border-top: 1px solid rgba(226, 232, 240, 0.9);
            padding: 12px 20px;
            z-index: 1000;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.06);
        }
        .submit-container {
            max-width: 650px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-luxury-submit {
            flex: 1;
            background: linear-gradient(135deg, #0c2340 0%, #16365c 50%, #c59b27 150%);
            color: #ffffff;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-size: 1rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 6px 18px rgba(12, 35, 64, 0.25);
            transition: all 0.25s ease;
            cursor: pointer;
        }
        .btn-luxury-submit:active {
            transform: scale(0.98);
        }

        @media (max-width: 480px) {
            .segmented-rating-label {
                padding: 6px 2px;
                font-size: 0.68rem;
                min-height: 48px;
            }
            .segmented-rating-label .star-num {
                font-size: 0.85rem;
            }
            .nps-pill-label {
                height: 38px;
                font-size: 0.78rem;
            }
            .section-body {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

    @php
        $criteriaList = [
            'cleanliness_room' => ['title' => '١. النظافة العامة للغرفة', 'sub' => 'نظافة الأرضيات، الأسطح، والترتيب'],
            'cleanliness_bathroom' => ['title' => '٢. نظافة الحمام والمرافق الصحية', 'sub' => 'التعقيم، الأدوات الصحية، والمناشف'],
            'temperature_ac' => ['title' => '٣. درجة حرارة الغرفة (التكييف والتدفئة)', 'sub' => 'ملاءمة برودة أو دفء الغرفة'],
            'lighting' => ['title' => '٤. إضاءة الغرفة وسكونها', 'sub' => 'قوة وسطوع الإضاءة ومرونتها'],
            'quietness_privacy' => ['title' => '٥. مستوى الهدوء والخصوصية', 'sub' => 'عزل الصوت وعدم وجود إزعاج خارجي'],
            'bed_comfort' => ['title' => '٦. راحة السرير والفراش', 'sub' => 'جودة المرتبة، المخدات، والوسائد'],
            'control_panel' => ['title' => '٧. سهولة استخدام لوحة التحكم وجرس الممرض', 'sub' => 'سرعة استجابة جهاز المناداة والإضاءة'],
            'storage_space' => ['title' => '٨. مساحات التخزين للأغراض الشخصية', 'sub' => 'سعة الخزائن والأدراج'],
            'furniture_safety' => ['title' => '٩. جودة وأمان الأثاث (الكرسي، طاولة الطعام)', 'sub' => 'سلامة الكراسي وثبات طاولات الطعام'],
            'entertainment' => ['title' => '١٠. وسائل الترفيه (التلفاز والإنترنت)', 'sub' => 'سرعة الواي فاي وجودة القنوات التلفزيونية'],
        ];

        // الورقة 2: طاقم التمريض
        $nursingCriteriaList = [
            'nursing_greeting' => ['title' => '١. الترحيب والمهنية في التعامل', 'sub' => 'أسلوب الاستقبال واللباقة'],
            'nursing_skill' => ['title' => '٢. المهارة في تقديم الرعاية السريرية', 'sub' => 'حقن الأدوية، تركيب الكانيولا، وتضميد الجروح'],
            'nursing_care_attention' => ['title' => '٣. الاهتمام والرعاية المستمرة أثناء وجودك', 'sub' => 'المتابعة الدورية وتفقد حالة المريض'],
            'nursing_instructions' => ['title' => '٤. وضوح التعليمات والتواصل معك', 'sub' => 'شرح الخطوات ومواعيد العلاج بلغة مبسطة'],
            'nursing_response_speed' => ['title' => '٥. سرعة الاستجابة عند طلب المساعدة', 'sub' => 'الحضور الفوري عند الضغط على جرس النداء'],
            'nursing_privacy_respect' => ['title' => '٦. المحافظة على الخصوصية والكرامة', 'sub' => 'مراعاة الستر وحفظ أسرار وخصوصية المريض'],
        ];

        // الورقة 2: قسم المختبر والتحاليل
        $labCriteriaList = [
            'lab_prep_instructions' => ['title' => '١. وضوح تعليمات التحضير للتحليل (إن وجدت)', 'sub' => 'توضيح شروط الصيام وأوقات سحب العينات'],
            'lab_sampling_skill' => ['title' => '٢. الاحتراف والمهارة في سحب العينة', 'sub' => 'سحب الدم بدون ألم وبدقة عالية'],
            'lab_hygiene_sterilization' => ['title' => '٣. النظافة والتعقيم في مكان سحب العينات', 'sub' => 'استخدام أدوات معقمة جديدة وقفازات نظيفة'],
            'lab_results_speed' => ['title' => '٤. سرعة استخراج النتائج وتسليمها', 'sub' => 'إصدار النتائج في الوقت المحدد'],
            'lab_staff_behavior' => ['title' => '٥. التعامل اللطيف والمطمئن أثناء العملية', 'sub' => 'تهدئة المريض والتعامل الإنساني الراقي'],
        ];

        // الورقة 2: الصيدلية والتعامل مع الأدوية
        $pharmacyCriteriaList = [
            'pharmacy_dispense_speed' => ['title' => '١. سرعة وصحة صرف الأدوية المطلوبة', 'sub' => 'صرف الأدوية الدقيقة دون تأخير أو أخطاء'],
            'pharmacy_instruction_clarity' => ['title' => '٢. وضوح شرح طريقة استخدام الدواء', 'sub' => 'شرح الجرعة، التوقيت، ومع الأكل أو قبله'],
            'pharmacy_comprehension_check' => ['title' => '٣. التأكد من فهمك الكامل لتعليمات الدواء', 'sub' => 'الإجابة على الاستفسارات والتأكد من إدراك المريض'],
            'pharmacy_respect_politeness' => ['title' => '٤. اللباقة والاحترام في التعامل من قبل الصيدلي', 'sub' => 'حسن الاستقبال وطول البال'],
            'pharmacy_med_availability' => ['title' => '٥. التوفر الكامل للأدوية الموصوفة', 'sub' => 'وجود كامل العلاجات دون نقص أو استبدال غير مبرر'],
        ];

        $ratingLabels = [
            5 => ['name' => 'ممتاز', 'stars' => '5'],
            4 => ['name' => 'جيد جداً', 'stars' => '4'],
            3 => ['name' => 'جيد', 'stars' => '3'],
            2 => ['name' => 'مقبول', 'stars' => '2'],
            1 => ['name' => 'ضعيف', 'stars' => '1'],
        ];
    @endphp

    <!-- Sticky Progress Header -->
    <div class="progress-sticky-header">
        <div class="progress-indicator">
            <span><i class="fas fa-clipboard-check text-warning me-1"></i> تقدّم التقييم</span>
            <span id="progressPercentText">0%</span>
        </div>
        <div class="custom-progress-bar">
            <div class="custom-progress-bar-fill" id="progressBarFill"></div>
        </div>
    </div>

    <!-- Luxury Hero Header -->
    <header class="luxury-hero">
        <div class="mb-3 text-center">
            <img src="{{ $hospitalLogoSrc }}" alt="شعار مستشفى الكفاءات الأهلي" class="rounded-3 shadow-xs bg-white p-1" style="max-height: 52px; max-width: 85px; object-fit: contain;">
        </div>
        <div class="luxury-badge">
            <i class="fas fa-shield-halved"></i> استبيان الجودة والاعتماد السريري
        </div>
        <h1>مستشفى الكفاءات الأهلي</h1>
        <h2>استبيان تقييم غرفة الرقود وتجربة المريض</h2>
        
        <div class="hero-info-chips">
            @if($survey->room)
                <div class="hero-chip">
                    <i class="fas fa-door-open text-warning me-1"></i> الغرفة: <strong>{{ $survey->room->room_number }}</strong>
                </div>
            @endif
            @if($survey->location)
                <div class="hero-chip">
                    <i class="fas fa-building text-info me-1"></i> {{ $survey->location->name }}
                </div>
            @endif
            @if($survey->patient?->user)
                <div class="hero-chip">
                    <i class="fas fa-user-check text-success me-1"></i> المريض: {{ $survey->patient->user->name }}
                </div>
            @endif
            <div class="hero-chip">
                <i class="far fa-calendar-alt me-1"></i> {{ date('Y-m-d') }}
            </div>
        </div>
    </header>

    <div class="container px-3 mt-4" style="max-width: 650px;">

        @if($errors->any())
            <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4">
                <i class="fas fa-exclamation-circle me-1"></i> يرجى التأكد من استكمال الحقول المطلوبة.
            </div>
        @endif

        <form method="POST" action="{{ route('inpatient-surveys.public.submit', ['uuid' => $survey->uuid]) }}" id="publicSurveyForm">
            @csrf

            <!-- البطاقة الأولى: تقييم مرافق غرفة الرقود -->
            <div class="section-card">
                <div class="section-header">
                    <div class="section-icon blue">
                        <i class="fas fa-bed"></i>
                    </div>
                    <div>
                        <h3 class="section-title">تقييم مرافق وخدمات غرفة الرقود</h3>
                        <p class="section-sub">يرجى تقييم بنود الغرفة وفق تجربتك الفعلية</p>
                    </div>
                </div>

                <div class="section-body">
                    @foreach($criteriaList as $field => $info)
                        <div class="criterion-item">
                            <span class="criterion-label">{{ $info['title'] }}</span>
                            <div class="segmented-rating-group">
                                @foreach($ratingLabels as $val => $meta)
                                    <div class="segmented-rating-option opt-score-{{ $val }}">
                                        <input type="radio" 
                                               name="{{ $field }}" 
                                               id="{{ $field }}_{{ $val }}" 
                                               value="{{ $val }}"
                                               {{ old($field) == $val ? 'checked' : '' }}
                                               onchange="updateProgress()">
                                        <label class="segmented-rating-label" for="{{ $field }}_{{ $val }}">
                                            <span class="star-num">
                                                <i class="fas fa-star star-icon"></i>
                                                {{ $meta['stars'] }}
                                            </span>
                                            <span>{{ $meta['name'] }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <!-- ملاحظات الغرفة -->
                    <div class="mt-3 pt-2">
                        <label class="form-label small fw-bold text-dark">
                            <i class="far fa-comment-alt text-primary me-1"></i> ملاحظاتك حول الغرفة (إن وجدت):
                        </label>
                        <textarea name="room_notes" class="custom-form-input" rows="2" placeholder="اكتب أي ملاحظة تخص نظافة أو تجهيزات الغرفة...">{{ old('room_notes') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- البطاقة الثانية: تقييم الفريق الطبي المعالج -->
            <div class="section-card">
                <div class="section-header">
                    <div class="section-icon teal">
                        <i class="fas fa-user-doctor"></i>
                    </div>
                    <div>
                        <h3 class="section-title">تقييم الفريق الطبي والطبيب المعالج</h3>
                        <p class="section-sub">
                            @if($survey->doctor?->user)
                                الطبيب المسؤول: د. {{ $survey->doctor->user->name }}
                            @else
                                تواصل واستجابة الفريق الطبي والزيارات السريرية
                            @endif
                        </p>
                    </div>
                </div>

                <div class="section-body">
                    <div class="criterion-item" style="border-bottom: none;">
                        <span class="criterion-label">كيف كانت زيارة الطبيب وتواصله واستماعه لك؟</span>
                        <div class="segmented-rating-group">
                            @foreach($ratingLabels as $val => $meta)
                                <div class="segmented-rating-option opt-score-{{ $val }}">
                                    <input type="radio" 
                                           name="doctor_communication" 
                                           id="doc_{{ $val }}" 
                                           value="{{ $val }}"
                                           {{ old('doctor_communication') == $val ? 'checked' : '' }}
                                           onchange="updateProgress()">
                                    <label class="segmented-rating-label" for="doc_{{ $val }}">
                                        <span class="star-num">
                                            <i class="fas fa-star star-icon"></i>
                                            {{ $meta['stars'] }}
                                        </span>
                                        <span>{{ $meta['name'] }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-2">
                        <label class="form-label small fw-bold text-dark">
                            <i class="far fa-comment-dots text-success me-1"></i> ملاحظات إضافية حول زيارة الطبيب:
                        </label>
                        <textarea name="doctor_notes" class="custom-form-input" rows="2" placeholder="ملاحظاتك حول الرعاية الطبية وشرح خطة العلاج...">{{ old('doctor_notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- الورقة 2: تقييم طاقم التمريض                              --}}
            {{-- ========================================================= --}}
            <div class="section-card">
                <div class="section-header">
                    <div class="section-icon purple">
                        <i class="fas fa-user-nurse"></i>
                    </div>
                    <div>
                        <h3 class="section-title">تقييم طاقم التمريض والرعاية السريرية</h3>
                        <p class="section-sub">الورقة الثانية: المهارة والاهتمام وسرعة الاستجابة</p>
                    </div>
                </div>

                <div class="section-body">
                    @foreach($nursingCriteriaList as $field => $info)
                        <div class="criterion-item">
                            <span class="criterion-label">{{ $info['title'] }}</span>
                            <div class="segmented-rating-group">
                                @foreach($ratingLabels as $val => $meta)
                                    <div class="segmented-rating-option opt-score-{{ $val }}">
                                        <input type="radio" 
                                               name="{{ $field }}" 
                                               id="{{ $field }}_{{ $val }}" 
                                               value="{{ $val }}"
                                               {{ old($field) == $val ? 'checked' : '' }}
                                               onchange="updateProgress()">
                                        <label class="segmented-rating-label" for="{{ $field }}_{{ $val }}">
                                            <span class="star-num">
                                                <i class="fas fa-star star-icon"></i>
                                                {{ $meta['stars'] }}
                                            </span>
                                            <span>{{ $meta['name'] }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="mt-3 pt-2">
                        <label class="form-label small fw-bold text-dark">
                            <i class="far fa-comment-alt text-primary me-1"></i> ملاحظات إضافية حول تعامل التمريض (إن وجدت):
                        </label>
                        <textarea name="nursing_notes" class="custom-form-input" rows="2" placeholder="اكتب ملاحظاتك حول خدمات واستجابة كادر التمريض...">{{ old('nursing_notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- الورقة 2: تقييم قسم المختبر والتحاليل                     --}}
            {{-- ========================================================= --}}
            <div class="section-card">
                <div class="section-header">
                    <div class="section-icon blue">
                        <i class="fas fa-vial"></i>
                    </div>
                    <div>
                        <h3 class="section-title">تقييم قسم المختبر وسحب التحاليل</h3>
                        <p class="section-sub">الورقة الثانية: مهارة سحب العينات والتعقيم وسرعة النتائج</p>
                    </div>
                </div>

                <div class="section-body">
                    @foreach($labCriteriaList as $field => $info)
                        <div class="criterion-item">
                            <span class="criterion-label">{{ $info['title'] }}</span>
                            <div class="segmented-rating-group">
                                @foreach($ratingLabels as $val => $meta)
                                    <div class="segmented-rating-option opt-score-{{ $val }}">
                                        <input type="radio" 
                                               name="{{ $field }}" 
                                               id="{{ $field }}_{{ $val }}" 
                                               value="{{ $val }}"
                                               {{ old($field) == $val ? 'checked' : '' }}
                                               onchange="updateProgress()">
                                        <label class="segmented-rating-label" for="{{ $field }}_{{ $val }}">
                                            <span class="star-num">
                                                <i class="fas fa-star star-icon"></i>
                                                {{ $meta['stars'] }}
                                            </span>
                                            <span>{{ $meta['name'] }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="mt-3 pt-2">
                        <label class="form-label small fw-bold text-dark">
                            <i class="far fa-comment-alt text-primary me-1"></i> ملاحظات إضافية حول تجربة المختبر (إن وجدت):
                        </label>
                        <textarea name="lab_notes" class="custom-form-input" rows="2" placeholder="اكتب ملاحظاتك حول تجربة التحاليل وسحب العينات...">{{ old('lab_notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- الورقة 2: تقييم الصيدلية والتعامل مع الأدوية              --}}
            {{-- ========================================================= --}}
            <div class="section-card">
                <div class="section-header">
                    <div class="section-icon gold">
                        <i class="fas fa-pills"></i>
                    </div>
                    <div>
                        <h3 class="section-title">تقييم الصيدلية والتعامل مع الأدوية</h3>
                        <p class="section-sub">الورقة الثانية: توفر العلاج ودقة الصرف وشرح الاستخدام</p>
                    </div>
                </div>

                <div class="section-body">
                    @foreach($pharmacyCriteriaList as $field => $info)
                        <div class="criterion-item">
                            <span class="criterion-label">{{ $info['title'] }}</span>
                            <div class="segmented-rating-group">
                                @foreach($ratingLabels as $val => $meta)
                                    <div class="segmented-rating-option opt-score-{{ $val }}">
                                        <input type="radio" 
                                               name="{{ $field }}" 
                                               id="{{ $field }}_{{ $val }}" 
                                               value="{{ $val }}"
                                               {{ old($field) == $val ? 'checked' : '' }}
                                               onchange="updateProgress()">
                                        <label class="segmented-rating-label" for="{{ $field }}_{{ $val }}">
                                            <span class="star-num">
                                                <i class="fas fa-star star-icon"></i>
                                                {{ $meta['stars'] }}
                                            </span>
                                            <span>{{ $meta['name'] }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="mt-3 pt-2">
                        <label class="form-label small fw-bold text-dark">
                            <i class="far fa-comment-alt text-primary me-1"></i> ملاحظات إضافية حول تعامل الصيدلية (إن وجدت):
                        </label>
                        <textarea name="pharmacy_notes" class="custom-form-input" rows="2" placeholder="اكتب ملاحظاتك حول خدمة صرف الأدوية وتوفرها...">{{ old('pharmacy_notes') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- البطاقة الثالثة: مقترحات التحسين ومؤشر التوصية NPS -->
            <div class="section-card">
                <div class="section-header">
                    <div class="section-icon gold">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div>
                        <h3 class="section-title">مقترحات التحسين ورفاهية المرضى</h3>
                        <p class="section-sub">رأيك يرسم خطط التطوير المباشرة لمستشفانا</p>
                    </div>
                </div>

                <div class="section-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">ما هي أهم ثلاثة أشياء تحتاج إلى تحسين في غرفة الرقود؟</label>
                        <div class="d-flex flex-column gap-2">
                            <input type="text" name="top_three_improvements[]" class="custom-form-input" placeholder="١. أول أولوية للتحسين...">
                            <input type="text" name="top_three_improvements[]" class="custom-form-input" placeholder="٢. ثاني أولوية للتحسين...">
                            <input type="text" name="top_three_improvements[]" class="custom-form-input" placeholder="٣. ثالث أولوية للتحسين...">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark">هل لديك أي اقتراحات أخرى لتحسين راحة ورفاهية المرضى؟</label>
                        <textarea name="patient_suggestions" class="custom-form-input" rows="2" placeholder="أي مقترح يسعدنا الاستماع إليه...">{{ old('patient_suggestions') }}</textarea>
                    </div>

                    <!-- NPS Question -->
                    <div class="pt-3 border-top">
                        <label class="form-label small fw-bold text-dark mb-1 d-block">
                            ما مدى احتمالية أن تنصح الأصدقاء أو العائلة بالعلاج في هذا المستشفى؟
                        </label>
                        <div class="d-flex justify-content-between text-muted" style="font-size: 0.72rem; font-weight: 600;">
                            <span>١ (مستبعد جداً)</span>
                            <span>١٠ (أوصي بهم بشدة)</span>
                        </div>
                        <div class="nps-slider-container">
                            @for($i = 1; $i <= 10; $i++)
                                <div class="nps-pill">
                                    <input type="radio" 
                                           name="recommend_score_nps" 
                                           id="nps_{{ $i }}" 
                                           value="{{ $i }}" 
                                           {{ old('recommend_score_nps') == $i ? 'checked' : '' }}
                                           onchange="updateProgress()">
                                    <label class="nps-pill-label" for="nps_{{ $i }}">{{ $i }}</label>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================================================= --}}
            {{-- الورقة 2: بيانات المريض والتوقيع (اختياري)                 --}}
            {{-- ========================================================= --}}
            <div class="section-card">
                <div class="section-header">
                    <div class="section-icon teal">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <h3 class="section-title">بيانات التواصل (اختياري)</h3>
                        <p class="section-sub">لمساعدتنا في متابعة ملاحظاتك وتقدير وقتك الثمين</p>
                    </div>
                </div>

                <div class="section-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold text-dark">الاسم الكريم:</label>
                            <input type="text" name="respondent_name" class="custom-form-input" value="{{ old('respondent_name', $survey->patient?->user?->name) }}" placeholder="اسم المريض أو المرافق...">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold text-dark">رقم الهاتف:</label>
                            <input type="text" name="respondent_phone" class="custom-form-input font-monospace" value="{{ old('respondent_phone', $survey->patient?->user?->phone) }}" placeholder="07XXXXXXXXX">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark">البريد الإلكتروني (إن وجد):</label>
                            <input type="email" name="respondent_email" class="custom-form-input" value="{{ old('respondent_email', '') }}" placeholder="name@example.com">
                        </div>
                    </div>
                </div>
            </div>

            <div style="height: 40px;"></div>

            <!-- Bottom Floating Action Bar -->
            <div class="submit-section">
                <div class="submit-container">
                    <button type="submit" class="btn-luxury-submit">
                        <i class="fas fa-check-circle"></i>
                        إرسال واعتماد التقييم
                    </button>
                </div>
            </div>

        </form>
    </div>

    <script>
        function updateProgress() {
            const allKeys = [
                // الغرفة (10)
                'cleanliness_room', 'cleanliness_bathroom', 'temperature_ac', 'lighting',
                'quietness_privacy', 'bed_comfort', 'control_panel', 'storage_space',
                'furniture_safety', 'entertainment',
                // الطبيب (1)
                'doctor_communication',
                // التمريض (6)
                'nursing_greeting', 'nursing_skill', 'nursing_care_attention',
                'nursing_instructions', 'nursing_response_speed', 'nursing_privacy_respect',
                // المختبر (5)
                'lab_prep_instructions', 'lab_sampling_skill', 'lab_hygiene_sterilization',
                'lab_results_speed', 'lab_staff_behavior',
                // الصيدلية (5)
                'pharmacy_dispense_speed', 'pharmacy_instruction_clarity', 'pharmacy_comprehension_check',
                'pharmacy_respect_politeness', 'pharmacy_med_availability',
                // مؤشر التوصية (1)
                'recommend_score_nps'
            ];

            const form = document.getElementById('publicSurveyForm');
            if (!form) return;

            let answeredCount = 0;
            allKeys.forEach(k => {
                if (form.querySelector('input[name="' + k + '"]:checked')) {
                    answeredCount++;
                }
            });

            const percent = Math.min(100, Math.round((answeredCount / allKeys.length) * 100));
            const fill = document.getElementById('progressBarFill');
            const text = document.getElementById('progressPercentText');

            if (fill) fill.style.width = percent + '%';
            if (text) text.textContent = percent + '%';
        }

        // Initialize progress on load
        document.addEventListener('DOMContentLoaded', updateProgress);
    </script>
</body>
</html>
