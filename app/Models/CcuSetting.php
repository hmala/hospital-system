<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CcuSetting extends Model
{
    use HasFactory;

    protected $table = 'ccu_settings';

    protected $fillable = [
        'key',
        'value',
        'label',
        'description',
    ];

    /**
     * القيم الافتراضية المعتمدة وفق لوائح الضمان الصحي للعناية المركزة كبار
     */
    public const DEFAULTS = [
        'annual_max_insured_days' => '30',
        'post_max_days_hia_percent' => '0',
        'cutoff_hour' => '12',
        'grace_period_hours' => '6',
        'internal_consultation_hia_percent' => '90',
        'extra_lab_hia_percent' => '75',
        'extra_radiology_hia_percent' => '75',
        'category_a_exempt' => '1',
    ];

    public static function getValue(string $key, $default = null): mixed
    {
        $row = static::where('key', $key)->first();
        if ($row && $row->value !== null) {
            return $row->value;
        }

        return $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function getFloat(string $key, float $default = 0.0): float
    {
        $val = static::getValue($key, $default);

        return is_numeric($val) ? (float) $val : $default;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $val = static::getValue($key, $default);

        return is_numeric($val) ? (int) $val : $default;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $val = static::getValue($key, $default ? '1' : '0');

        return in_array(strtolower((string) $val), ['1', 'true', 'yes', 'on'], true);
    }
}
