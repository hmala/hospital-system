<?php

namespace App\Services\Accounting;

class TafqeetService
{
    protected static array $ones = [
        0 => '',
        1 => 'واحد',
        2 => 'اثنان',
        3 => 'ثلاثة',
        4 => 'أربعة',
        5 => 'خمسة',
        6 => 'ستة',
        7 => 'سبعة',
        8 => 'ثمانية',
        9 => 'تسعة',
        10 => 'عشرة',
        11 => 'أحد عشر',
        12 => 'اثنا عشر',
        13 => 'ثلاثة عشر',
        14 => 'أربعة عشر',
        15 => 'خمسة عشر',
        16 => 'ستة عشر',
        17 => 'سبعة عشر',
        18 => 'ثمانية عشر',
        19 => 'تسعة عشر',
    ];

    protected static array $tens = [
        2 => 'عشرون',
        3 => 'ثلاثون',
        4 => 'أربعون',
        5 => 'خمسون',
        6 => 'ستون',
        7 => 'سبعون',
        8 => 'ثمانون',
        9 => 'تسعون',
    ];

    protected static array $hundreds = [
        0 => '',
        1 => 'مائة',
        2 => 'مئتان',
        3 => 'ثلاثمائة',
        4 => 'أربعمائة',
        5 => 'خمسمائة',
        6 => 'ستمائة',
        7 => 'سبعمائة',
        8 => 'ثمانمائة',
        9 => 'تسعمائة',
    ];

    /**
     * Convert an amount into Iraqi Dinar words.
     */
    public static function inIraqiDinars(float|int|string $number): string
    {
        $number = (float) $number;

        if ($number <= 0) {
            return 'صفر دينار عراقي';
        }

        $integerPart = (int) floor($number);
        $fractionPart = (int) round(($number - $integerPart) * 100);

        $words = self::convertNumber($integerPart);
        $result = "فقط {$words} دينار عراقي";

        if ($fractionPart > 0) {
            $fractionWords = self::convertNumber($fractionPart);
            $result .= " و{$fractionWords} فلس";
        }

        $result .= ' لا غير';

        return $result;
    }

    public static function convertNumber(int $number): string
    {
        if ($number === 0) {
            return 'صفر';
        }

        if ($number < 0) {
            return 'سالب '.self::convertNumber(abs($number));
        }

        $parts = [];

        // مليارات
        if ($number >= 1000000000) {
            $billions = (int) floor($number / 1000000000);
            $number %= 1000000000;
            if ($billions === 1) {
                $parts[] = 'مليار';
            } elseif ($billions === 2) {
                $parts[] = 'ملياران';
            } elseif ($billions >= 3 && $billions <= 10) {
                $parts[] = self::convertThreeDigits($billions).' مليارات';
            } else {
                $parts[] = self::convertThreeDigits($billions).' مليار';
            }
        }

        // ملايين
        if ($number >= 1000000) {
            $millions = (int) floor($number / 1000000);
            $number %= 1000000;
            if ($millions === 1) {
                $parts[] = 'مليون';
            } elseif ($millions === 2) {
                $parts[] = 'مليونان';
            } elseif ($millions >= 3 && $millions <= 10) {
                $parts[] = self::convertThreeDigits($millions).' ملايين';
            } else {
                $parts[] = self::convertThreeDigits($millions).' مليون';
            }
        }

        // آلاف
        if ($number >= 1000) {
            $thousands = (int) floor($number / 1000);
            $number %= 1000;
            if ($thousands === 1) {
                $parts[] = 'ألف';
            } elseif ($thousands === 2) {
                $parts[] = 'ألفان';
            } elseif ($thousands >= 3 && $thousands <= 10) {
                $parts[] = self::convertThreeDigits($thousands).' آلاف';
            } else {
                $parts[] = self::convertThreeDigits($thousands).' ألف';
            }
        }

        // المئات والعشرات والآحاد
        if ($number > 0) {
            $parts[] = self::convertThreeDigits($number);
        }

        return implode(' و', array_filter($parts));
    }

    protected static function convertThreeDigits(int $number): string
    {
        $h = (int) floor($number / 100);
        $remainder = $number % 100;

        $words = [];

        if ($h > 0) {
            $words[] = self::$hundreds[$h];
        }

        if ($remainder > 0) {
            if ($remainder < 20) {
                $words[] = self::$ones[$remainder];
            } else {
                $one = $remainder % 10;
                $ten = (int) floor($remainder / 10);

                if ($one > 0) {
                    $words[] = self::$ones[$one].' و'.self::$tens[$ten];
                } else {
                    $words[] = self::$tens[$ten];
                }
            }
        }

        return implode(' و', array_filter($words));
    }
}
