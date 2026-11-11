<?php

namespace App\Services;

class TafqeetService
{
    private static array $ones = [
        0 => '', 1 => 'واحد', 2 => 'اثنان', 3 => 'ثلاثة', 4 => 'أربعة', 5 => 'خمسة',
        6 => 'ستة', 7 => 'سبعة', 8 => 'ثمانية', 9 => 'تسعة', 10 => 'عشرة',
        11 => 'أحد عشر', 12 => 'اثنا عشر', 13 => 'ثلاثة عشر', 14 => 'أربعة عشر', 15 => 'خمسة عشر',
        16 => 'ستة عشر', 17 => 'سبعة عشر', 18 => 'ثمانية عشر', 19 => 'تسعة عشر',
    ];

    private static array $tens = [
        2 => 'عشرون', 3 => 'ثلاثون', 4 => 'أربعون', 5 => 'خمسون',
        6 => 'ستون', 7 => 'سبعون', 8 => 'ثمانون', 9 => 'تسعون',
    ];

    private static array $hundreds = [
        0 => '', 1 => 'مائة', 2 => 'مائتان', 3 => 'ثلاثمائة', 4 => 'أربعمائة',
        5 => 'خمسمائة', 6 => 'ستمائة', 7 => 'سبعمائة', 8 => 'ثمانمائة', 9 => 'تسعمائة',
    ];

    public static function inArabic(float|int $amount, string $currency = 'دينار عراقي'): string
    {
        $amount = round($amount, 2);
        if ($amount == 0) {
            return "صفر {$currency} فقط لا غير";
        }

        $integerPart = (int) floor(abs($amount));
        $fractionPart = (int) round((abs($amount) - $integerPart) * 100);

        $words = self::convertNumber($integerPart);
        $result = $words ? ($words.' '.$currency) : '';

        if ($fractionPart > 0) {
            $fractionWords = self::convertNumber($fractionPart);
            $result .= ($result ? ' و ' : '').$fractionWords.' فلس';
        }

        return 'فقط '.trim($result).' لا غير';
    }

    private static function convertNumber(int $num): string
    {
        if ($num === 0) {
            return '';
        }

        if ($num < 20) {
            return self::$ones[$num];
        }

        if ($num < 100) {
            $ten = (int) floor($num / 10);
            $rem = $num % 10;
            if ($rem === 0) {
                return self::$tens[$ten];
            }

            return self::$ones[$rem].' و'.self::$tens[$ten];
        }

        if ($num < 1000) {
            $hundred = (int) floor($num / 100);
            $rem = $num % 100;
            $str = self::$hundreds[$hundred];
            if ($rem > 0) {
                $str .= ' و'.self::convertNumber($rem);
            }

            return $str;
        }

        if ($num < 1000000) {
            $thousands = (int) floor($num / 1000);
            $rem = $num % 1000;
            $thousandsStr = '';
            $thousandsRemainder = $thousands % 100;
            if ($thousands === 1) {
                $thousandsStr = 'ألف';
            } elseif ($thousands === 2) {
                $thousandsStr = 'ألفان';
            } elseif ($thousandsRemainder >= 3 && $thousandsRemainder <= 10 && $thousands < 100) {
                $thousandsStr = self::convertNumber($thousands).' آلاف';
            } else {
                $thousandsStr = self::convertNumber($thousands).' ألف';
            }

            if ($rem > 0) {
                $thousandsStr .= ' و'.self::convertNumber($rem);
            }

            return $thousandsStr;
        }

        if ($num < 1000000000) {
            $millions = (int) floor($num / 1000000);
            $rem = $num % 1000000;
            $millionsStr = '';
            if ($millions === 1) {
                $millionsStr = 'مليون';
            } elseif ($millions === 2) {
                $millionsStr = 'مليونان';
            } elseif ($millions >= 3 && $millions <= 10) {
                $millionsStr = self::$ones[$millions].' ملايين';
            } else {
                $millionsStr = self::convertNumber($millions).' مليون';
            }

            if ($rem > 0) {
                $millionsStr .= ' و'.self::convertNumber($rem);
            }

            return $millionsStr;
        }

        return (string) $num;
    }
}
