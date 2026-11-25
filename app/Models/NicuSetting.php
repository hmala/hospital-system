<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NicuSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'label',
        'description',
    ];

    public const DEFAULTS = [
        'observation_duration_minutes' => '120',
        'observation_is_billable' => '0',
        'observation_fee' => '0',
        'hospital_print_name_ar' => 'مستشفى الكفاءات الأهلي',
        'hospital_print_name_en' => 'Al-Kafaat Hospital',
        'hospital_print_email' => 'alkafaathospital@gmail.com',
        'hospital_print_phone' => '07760507060',
        'hospital_print_address' => 'بغداد - الحارثية شارع الكندي',
    ];

    public static function getValue(string $key, $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if ($setting !== null && $setting->value !== null) {
            return $setting->value;
        }

        if ($default !== null) {
            return $default;
        }

        return static::DEFAULTS[$key] ?? null;
    }

    public static function getFloat(string $key, float $default = 0.0): float
    {
        return (float) static::getValue($key, $default);
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) static::getValue($key, $default);
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $val = static::getValue($key, $default ? '1' : '0');

        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    public static function setValue(string $key, $value, ?string $label = null, ?string $description = null): self
    {
        $attributes = ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value];

        if ($label !== null) {
            $attributes['label'] = $label;
        }

        if ($description !== null) {
            $attributes['description'] = $description;
        }

        return static::updateOrCreate(['key' => $key], $attributes);
    }

    public static function observationConfig(): array
    {
        $hospital = Hospital::first();

        return [
            'duration_minutes' => max(15, static::getInt('observation_duration_minutes', 120)),
            'is_billable' => static::getBool('observation_is_billable', false),
            'fee' => static::getFloat('observation_fee', 0.0),
            'hospital_name_ar' => static::getValue('hospital_print_name_ar', $hospital->name ?? 'مستشفى الكفاءات الأهلي'),
            'hospital_name_en' => static::getValue('hospital_print_name_en', 'Al-Kafaat Hospital'),
            'hospital_email' => static::getValue('hospital_print_email', $hospital->email ?? 'alkafaathospital@gmail.com'),
            'hospital_phone' => static::getValue('hospital_print_phone', $hospital->phone ?? '07760507060'),
            'hospital_address' => static::getValue('hospital_print_address', $hospital->address ?? 'بغداد - الحارثية شارع الكندي'),
        ];
    }
}
