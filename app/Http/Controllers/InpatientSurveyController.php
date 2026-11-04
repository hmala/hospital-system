<?php

namespace App\Http\Controllers;

use App\Models\InpatientRoomSurvey;
use App\Models\Surgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InpatientSurveyController extends Controller
{
    /**
     * عرض الاستبيان العام عبر رمز الـ UUID لمسحه بالهاتف
     */
    public function showByUuid(string $uuid)
    {
        $survey = InpatientRoomSurvey::with(['patient.user', 'room', 'doctor.user', 'location'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        if ($survey->status === 'completed') {
            return view('surveys.thank-you', [
                'survey' => $survey,
                'alreadyCompleted' => true,
            ]);
        }

        return view('surveys.inpatient-public', compact('survey'));
    }

    /**
     * استقبال وحفظ إجابات الاستبيان المرسلة من هاتف المريض
     */
    public function submitByUuid(Request $request, string $uuid)
    {
        $survey = InpatientRoomSurvey::where('uuid', $uuid)->firstOrFail();

        if ($survey->status === 'completed') {
            return redirect()->route('inpatient-surveys.public.thank-you', ['uuid' => $uuid]);
        }

        $validated = $request->validate([
            'cleanliness_room' => 'nullable|integer|between:1,5',
            'cleanliness_bathroom' => 'nullable|integer|between:1,5',
            'temperature_ac' => 'nullable|integer|between:1,5',
            'lighting' => 'nullable|integer|between:1,5',
            'quietness_privacy' => 'nullable|integer|between:1,5',
            'bed_comfort' => 'nullable|integer|between:1,5',
            'control_panel' => 'nullable|integer|between:1,5',
            'storage_space' => 'nullable|integer|between:1,5',
            'furniture_safety' => 'nullable|integer|between:1,5',
            'entertainment' => 'nullable|integer|between:1,5',
            'room_notes' => 'nullable|string|max:1000',
            'top_three_improvements' => 'nullable|array',
            'top_three_improvements.*' => 'nullable|string|max:255',
            'patient_suggestions' => 'nullable|string|max:1000',
            'recommend_score_nps' => 'nullable|integer|between:1,10',
            'doctor_communication' => 'nullable|integer|between:1,5',
            'doctor_notes' => 'nullable|string|max:1000',
        ]);

        $survey->fill($validated);
        $survey->overall_score = $survey->calculateOverallScore();
        $survey->status = 'completed';
        $survey->submitted_via = 'patient_qr';
        $survey->completed_at = now();
        $survey->save();

        return redirect()->route('inpatient-surveys.public.thank-you', ['uuid' => $uuid]);
    }

    /**
     * شاشة الشكر بعد إرسال التقييم
     */
    public function thankYou(string $uuid)
    {
        $survey = InpatientRoomSurvey::with(['patient.user', 'room'])->where('uuid', $uuid)->firstOrFail();

        return view('surveys.thank-you', compact('survey'));
    }

    /**
     * حفظ التقييم يدوياً بواسطة الممرض أو الكادر من طبلة المريض
     */
    public function storeManual(Request $request, Surgery $surgery)
    {
        $validated = $request->validate([
            'cleanliness_room' => 'nullable|integer|between:1,5',
            'cleanliness_bathroom' => 'nullable|integer|between:1,5',
            'temperature_ac' => 'nullable|integer|between:1,5',
            'lighting' => 'nullable|integer|between:1,5',
            'quietness_privacy' => 'nullable|integer|between:1,5',
            'bed_comfort' => 'nullable|integer|between:1,5',
            'control_panel' => 'nullable|integer|between:1,5',
            'storage_space' => 'nullable|integer|between:1,5',
            'furniture_safety' => 'nullable|integer|between:1,5',
            'entertainment' => 'nullable|integer|between:1,5',
            'room_notes' => 'nullable|string|max:1000',
            'top_three_improvements' => 'nullable|array',
            'top_three_improvements.*' => 'nullable|string|max:255',
            'patient_suggestions' => 'nullable|string|max:1000',
            'recommend_score_nps' => 'nullable|integer|between:1,10',
            'doctor_communication' => 'nullable|integer|between:1,5',
            'doctor_notes' => 'nullable|string|max:1000',
        ]);

        $survey = InpatientRoomSurvey::firstOrNew(['surgery_id' => $surgery->id]);
        $survey->patient_id = $surgery->patient_id;
        $survey->doctor_id = $surgery->doctor_id;
        $survey->room_id = $surgery->room_id;
        $survey->location_id = $surgery->location_id ?? $surgery->room?->location_id;
        $survey->room_type = $surgery->room?->type ?? 'regular';

        $survey->fill($validated);
        $survey->overall_score = $survey->calculateOverallScore();
        $survey->status = 'completed';
        $survey->submitted_via = 'staff_manual';
        $survey->submitted_by_user_id = Auth::id();
        $survey->completed_at = now();
        $survey->save();

        return back()->with('success', 'تم حفظ استبيان تقييم الرقود بنجاح في طبلة المريض (النسبة العامة: '.$survey->overall_score.'%).');
    }

    /**
     * طباعة وصل حراري صغير يحتوي رمز الـ QR وبيانات المريض لإعطائه للمرافق
     */
    public function printQr(Surgery $surgery)
    {
        $survey = InpatientRoomSurvey::firstOrCreate(
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

        return view('surveys.print-qr', compact('surgery', 'survey', 'surveyUrl'));
    }

    /**
     * طباعة استبيان A4 رسمي مطابق لهوية المستشفى (معبأ بالبيانات أو نموذج فارغ)
     */
    public function printA4(Request $request, Surgery $surgery)
    {
        $isBlank = (bool) $request->query('blank', false);

        $survey = InpatientRoomSurvey::with(['patient.user', 'room', 'location', 'doctor.user'])
            ->firstOrCreate(
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

        return view('surveys.print-a4', compact('surgery', 'survey', 'isBlank'));
    }

    /**
     * طباعة استبيان A4 ورقي فارغ عام للتوزيع في الردهات
     */
    public function printBlankA4()
    {
        $isBlank = true;
        $survey = null;
        $surgery = null;

        return view('surveys.print-a4', compact('isBlank', 'survey', 'surgery'));
    }

    /**
     * تصفير وإعادة فتح الاستبيان (صلاحية خاصة بالمدير فقط)
     */
    public function reset(Surgery $surgery)
    {
        if (! Auth::user() || ! Auth::user()->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'غير مصرح لك بإعادة فتح وتصفير الاستبيان، هذه الصلاحية مخصصة لمدير النظام حصراً.');
        }

        $survey = InpatientRoomSurvey::where('surgery_id', $surgery->id)->firstOrFail();

        $survey->update([
            'status' => 'pending',
            'cleanliness_room' => null,
            'cleanliness_bathroom' => null,
            'temperature_ac' => null,
            'lighting' => null,
            'quietness_privacy' => null,
            'bed_comfort' => null,
            'control_panel' => null,
            'storage_space' => null,
            'furniture_safety' => null,
            'entertainment' => null,
            'room_notes' => null,
            'top_three_improvements' => null,
            'patient_suggestions' => null,
            'recommend_score_nps' => null,
            'doctor_communication' => null,
            'doctor_notes' => null,
            'overall_score' => null,
            'submitted_via' => null,
            'submitted_by_user_id' => null,
            'completed_at' => null,
        ]);

        return back()->with('success', 'تمت إعادة فتح وتصفير الاستبيان بنجاح، وأصبح جاهزاً لإعادة التعبئة والتقييم.');
    }
}
