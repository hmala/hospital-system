<?php

namespace App\Http\Controllers;

use App\Exports\HealthInsuranceClaimsExport;
use App\Models\Doctor;
use App\Models\HealthInsuranceCategory;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HealthInsuranceClaimController extends Controller
{
    /**
     * عرض جدول وسجلات مطالبات الضمان الصحي مع الفلاتر والمؤشرات التنفيذية
     */
    public function index(Request $request)
    {
        $query = $this->buildFilteredQuery($request);

        // حساب المؤشرات التنفيذية التراكمية بناءً على الفلاتر المحددة
        $kpiQuery = clone $query;
        $kpis = $kpiQuery->selectRaw('
            COUNT(payments.id) as total_claims,
            COUNT(DISTINCT payments.patient_id) as total_patients,
            COALESCE(SUM(payments.amount), 0) as total_patient_paid,
            COALESCE(SUM(payments.insurance_share), 0) as total_insurance_share,
            COALESCE(SUM(payments.amount + payments.insurance_share), 0) as total_gross
        ')->first();

        // التوزيع حسب فئات الضمان للفترة المحددة
        $categoryStatsQuery = clone $query;
        $categoryBreakdown = $categoryStatsQuery
            ->join('patients', 'payments.patient_id', '=', 'patients.id')
            ->leftJoin('health_insurance_categories', 'patients.health_insurance_category_id', '=', 'health_insurance_categories.id')
            ->selectRaw('
                COALESCE(health_insurance_categories.name, "بدون فئة") as cat_name,
                COALESCE(health_insurance_categories.code, "N/A") as cat_code,
                COUNT(payments.id) as claims_count,
                SUM(payments.insurance_share) as total_share
            ')
            ->groupBy('health_insurance_categories.id', 'health_insurance_categories.name', 'health_insurance_categories.code')
            ->orderByDesc('total_share')
            ->get();

        // السجلات مع التحميل المسبق للعلاقات
        $claims = $query->with([
            'patient.healthInsuranceCategory',
            'appointment.department',
            'appointment.doctor.user',
            'request.visit.doctor.user',
            'requests.visit.doctor.user',
            'emergency.doctor.user',
            'surgery.doctor.user',
            'cashier',
        ])
        ->orderByDesc('payments.paid_at')
        ->orderByDesc('payments.id')
        ->paginate(25)
        ->appends($request->query());

        $categories = HealthInsuranceCategory::orderBy('name')->get();
        $doctors = Doctor::with('user')->where('is_active', true)->get()->sortBy(function($d) {
            return $d->user?->name;
        });

        $paymentTypes = Payment::PAYMENT_TYPES;

        return view('insurance.claims.index', compact(
            'claims',
            'kpis',
            'categoryBreakdown',
            'categories',
            'doctors',
            'paymentTypes'
        ));
    }

    /**
     * تصدير مطالبات الضمان الصحي المحددة إلى ملف Excel
     */
    public function export(Request $request)
    {
        $query = $this->buildFilteredQuery($request);

        $claims = $query->with([
            'patient.healthInsuranceCategory',
            'appointment.department',
            'appointment.doctor.user',
            'request.visit.doctor.user',
            'requests.visit.doctor.user',
            'emergency.doctor.user',
            'surgery.doctor.user',
            'cashier',
        ])
        ->orderByDesc('payments.paid_at')
        ->orderByDesc('payments.id')
        ->get();

        $fileName = 'insurance_claims_' . now()->format('Y_m_d_His') . '.xlsx';

        return Excel::download(new HealthInsuranceClaimsExport($claims), $fileName);
    }

    /**
     * بناء استعلام المطالبات المفلتَر
     */
    protected function buildFilteredQuery(Request $request)
    {
        $query = Payment::whereNotNull('payments.paid_at')
            ->where(function ($q) {
                $q->where('payments.insurance_share', '>', 0)
                  ->orWhereIn('payments.insurance_type', ['hi', 'moi', 'syndicate', 'private']);
            });

        // 1. فلترة الفترة الزمنية والتاريخ
        $quickPeriod = $request->input('quick_period');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if ($quickPeriod) {
            switch ($quickPeriod) {
                case 'today':
                    $query->whereDate('payments.paid_at', Carbon::today());
                    break;
                case 'yesterday':
                    $query->whereDate('payments.paid_at', Carbon::yesterday());
                    break;
                case 'this_week':
                    $query->whereBetween('payments.paid_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereBetween('payments.paid_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                    break;
                case 'last_month':
                    $query->whereBetween('payments.paid_at', [
                        Carbon::now()->subMonth()->startOfMonth(),
                        Carbon::now()->subMonth()->endOfMonth()
                    ]);
                    break;
                case 'this_year':
                    $query->whereBetween('payments.paid_at', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]);
                    break;
                case 'all':
                    // لا يتم تطبيق قيد زمني
                    break;
            }
        } else {
            if ($fromDate) {
                $query->whereDate('payments.paid_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('payments.paid_at', '<=', $toDate);
            }
        }

        // 2. فلترة فئة الضمان الصحي
        if ($request->filled('category_id')) {
            $catId = $request->input('category_id');
            $query->whereHas('patient', function ($q) use ($catId) {
                $q->where('health_insurance_category_id', $catId);
            });
        }

        // 3. فلترة نوع التأمين
        if ($request->filled('insurance_type')) {
            $insType = $request->input('insurance_type');
            $query->where(function ($q) use ($insType) {
                $q->where('payments.insurance_type', $insType)
                  ->orWhereHas('patient', function ($pq) use ($insType) {
                      $pq->where('insurance_type', $insType);
                  });
            });
        }

        // 4. فلترة القسم / نوع الخدمة
        if ($request->filled('payment_type')) {
            $query->where('payments.payment_type', $request->input('payment_type'));
        }

        // 5. فلترة الطبيب (سواء في الاستشارية أو عبر الزيارة والطلب أو الطوارئ أو العمليات)
        if ($request->filled('doctor_id')) {
            $doctorId = $request->input('doctor_id');
            $query->where(function ($q) use ($doctorId) {
                $q->whereHas('appointment', function ($aq) use ($doctorId) {
                    $aq->where('doctor_id', $doctorId);
                })
                ->orWhereHas('request.visit', function ($vq) use ($doctorId) {
                    $vq->where('doctor_id', $doctorId);
                })
                ->orWhereHas('requests.visit', function ($vq) use ($doctorId) {
                    $vq->where('doctor_id', $doctorId);
                })
                ->orWhereHas('emergency', function ($eq) use ($doctorId) {
                    $eq->where('doctor_id', $doctorId);
                })
                ->orWhereHas('surgery', function ($sq) use ($doctorId) {
                    $sq->where('doctor_id', $doctorId);
                });
            });
        }

        // 6. البحث النصي (اسم المريض، رقم باج/بطاقة الضمان، رقم الإيصال، رقم الهاتف، الرقم الوطني)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('payments.receipt_number', 'like', "%{$search}%")
                  ->orWhere('payments.insurance_card_no', 'like', "%{$search}%")
                  ->orWhere('payments.description', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('national_id', 'like', "%{$search}%")
                         ->orWhere('insurance_card_no', 'like', "%{$search}%");
                  });
            });
        }

        return $query;
    }
}
