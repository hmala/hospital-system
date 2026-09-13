<?php

namespace App\Traits;

trait HasInsurancePricing
{
    /**
     * الحصول على سعر الكاش الأساسي للخدمة باختلاف مسميات الحقول
     */
    public function getRegularPrice(): float
    {
        if (isset($this->price)) {
            return (float) $this->price;
        }

        if (isset($this->base_price)) {
            return (float) $this->base_price;
        }

        if (isset($this->consultation_fee)) {
            return (float) $this->consultation_fee;
        }

        return 0.00;
    }

    /**
     * حساب تفاصيل التسعير وحصص المريض والضمان بناءً على نوع الضمان ونسبة التحمل
     *
     * @param string|null $insuranceType 'none' | 'moi' | 'hi'
     * @param float|null $copayPercent نسبة التحمل (مثلاً 15.0 لـ 15%)
     * @return array
     */
    public function calculateInsurancePricing(?string $insuranceType = 'none', ?float $copayPercent = 0.00): array
    {
        $insuranceType = strtolower((string) ($insuranceType ?? 'none'));
        $copayPercent  = max(0.00, min(100.00, (float) ($copayPercent ?? 0.00)));
        $regularPrice  = $this->getRegularPrice();

        // 1. حالة ضمان وزارة الداخلية (MOI)
        if ($insuranceType === 'moi') {
            $isCovered = (bool) ($this->is_moi_active ?? true);

            if ($isCovered) {
                // إذا كان سعر الداخلية محدد وأكبر من 0 نعتمد عليه، وإلا نعتمد السعر الأساسي
                $approvedPrice = (!is_null($this->moi_price) && (float)$this->moi_price > 0)
                    ? (float) $this->moi_price
                    : $regularPrice;

                $patientShare   = round($approvedPrice * ($copayPercent / 100.0), 2);
                $insuranceShare = round($approvedPrice - $patientShare, 2);

                return [
                    'insurance_type'   => 'moi',
                    'copay_percentage' => $copayPercent,
                    'is_covered'       => true,
                    'regular_price'    => $regularPrice,
                    'approved_price'   => $approvedPrice,
                    'total_amount'     => $approvedPrice,
                    'patient_share'    => $patientShare,
                    'insurance_share'  => $insuranceShare,
                ];
            }
        }

        // 2. حالة هيئة الضمان الصحي (Health Insurance - HI)
        if ($insuranceType === 'hi') {
            $isCovered = (bool) ($this->is_hi_active ?? true);

            if ($isCovered) {
                $approvedPrice = (!is_null($this->hi_price) && (float)$this->hi_price > 0)
                    ? (float) $this->hi_price
                    : $regularPrice;

                $patientShare   = round($approvedPrice * ($copayPercent / 100.0), 2);
                $insuranceShare = round($approvedPrice - $patientShare, 2);

                return [
                    'insurance_type'   => 'hi',
                    'copay_percentage' => $copayPercent,
                    'is_covered'       => true,
                    'regular_price'    => $regularPrice,
                    'approved_price'   => $approvedPrice,
                    'total_amount'     => $approvedPrice,
                    'patient_share'    => $patientShare,
                    'insurance_share'  => $insuranceShare,
                ];
            }
        }

        // 3. الحالة الافتراضية (كاش عادي أو خدمة غير مشمولة بالضمان)
        return [
            'insurance_type'   => $insuranceType,
            'copay_percentage' => 0.00,
            'is_covered'       => false,
            'regular_price'    => $regularPrice,
            'approved_price'   => $regularPrice,
            'total_amount'     => $regularPrice,
            'patient_share'    => $regularPrice,
            'insurance_share'  => 0.00,
        ];
    }
}
