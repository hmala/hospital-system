<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PatientDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'title',
        'category',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'notes',
        'uploaded_by',
    ];

    public static function categories(): array
    {
        return [
            'national_id'      => 'هوية / جواز سفر',
            'insurance'        => 'بطاقة التأمين / موافقة التأمين',
            'medical_report'   => 'تقرير طبي خارجي',
            'surgery_consent'  => 'استمارة موافقة عملية',
            'lab_result'       => 'نتائج تحاليل خارجية',
            'radiology_result' => 'تقارير أشعة وسونار خارجية',
            'prescription'     => 'وصفة طبية سابقة',
            'scan'             => 'سحب ماسح ضوئي (سكنر)',
            'other'            => 'مستندات أخرى',
        ];
    }

    public function getCategoryNameAttribute(): string
    {
        $cats = self::categories();
        return $cats[$this->category] ?? ($this->category ?: 'أخرى');
    }

    public function getFileUrlAttribute(): string
    {
        if (!$this->file_path) {
            return '';
        }
        $cleanPath = ltrim($this->file_path, '/');
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }
        if (str_starts_with($cleanPath, 'public/')) {
            $cleanPath = substr($cleanPath, 7);
        }
        return asset('storage/' . $cleanPath);
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string)$this->file_type, 'image/') ||
            in_array(strtolower(pathinfo($this->file_name ?? $this->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->file_type === 'application/pdf' ||
            strtolower(pathinfo($this->file_name ?? $this->file_path, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function getFormattedSizeAttribute(): string
    {
        if (!$this->file_size) return '-';
        if ($this->file_size < 1024) return $this->file_size . ' B';
        if ($this->file_size < 1048576) return round($this->file_size / 1024, 1) . ' KB';
        return round($this->file_size / 1048576, 2) . ' MB';
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
