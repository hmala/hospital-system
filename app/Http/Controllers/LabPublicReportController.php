<?php

namespace App\Http\Controllers;

use App\Models\Request as MedicalRequest;
use Illuminate\Http\Request as HttpRequest;

class LabPublicReportController extends Controller
{
    /**
     * عرض التقرير الطبي المعتمد للمريض برابط عام آمن ومشفّر دون الحاجة لتسجيل دخول
     */
    public function view(HttpRequest $httpRequest, MedicalRequest $request)
    {
        // 1. التحقق من صلاحية التوكن الأمني المشفر
        $token = $httpRequest->query('token');
        $validToken = $request->getPublicReportToken();

        $isValidToken = $token && hash_equals($validToken, (string) $token);
        $isValidSignature = $httpRequest->hasValidSignature();
        $isStaffUser = auth()->check() && (auth()->user()->hasAnyRole(['lab_staff', 'admin', 'admin-hsop', 'hospital_admin', 'doctor', 'receptionist']) || auth()->user()->can('view lab tests'));

        if (!$isValidToken && !$isValidSignature && !$isStaffUser) {
            abort(403, 'عذراً، رابط تقرير التحليل غير صالح أو منتهي الصلاحية.');
        }

        // 2. تحميل البيانات والعلاقات
        $request->load(['visit.patient.user', 'visit.doctor.user']);

        $requestDetails = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
        if (!is_array($requestDetails)) $requestDetails = [];

        $isBloodBankRequest = $request->type === 'blood_bank' || data_get($requestDetails, 'blood_bank', false) === true;
        $bloodBankRequest = $isBloodBankRequest
            ? \App\Models\BloodBankRequest::where('request_id', $request->id)->first()
            : null;

        $isPublicView = true;

        return view('staff.requests.print', compact('request', 'isBloodBankRequest', 'bloodBankRequest', 'isPublicView'));
    }
}
