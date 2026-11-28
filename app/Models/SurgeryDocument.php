<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SurgeryDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'surgery_id',
        'patient_id',
        'surgery_document_type_id',
        'document_type_name',
        'title',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function surgery()
    {
        return $this->belongsTo(Surgery::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function documentType()
    {
        return $this->belongsTo(SurgeryDocumentType::class, 'surgery_document_type_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getCategoryNameAttribute(): string
    {
        return $this->document_type_name ?: ($this->documentType?->name ?: 'مستند طبي');
    }

    public function getFileUrlAttribute(): string
    {
        if (! $this->file_path) {
            return '';
        }

        return Storage::disk('public')->url($this->file_path);
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string) $this->file_type, 'image/') ||
            in_array(strtolower(pathinfo($this->file_name ?? $this->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->file_type === 'application/pdf' ||
            strtolower(pathinfo($this->file_name ?? $this->file_path, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function getFormattedSizeAttribute(): string
    {
        if (! $this->file_size) {
            return '-';
        }
        if ($this->file_size < 1024) {
            return $this->file_size.' B';
        }
        if ($this->file_size < 1048576) {
            return round($this->file_size / 1024, 1).' KB';
        }

        return round($this->file_size / 1048576, 2).' MB';
    }
}
