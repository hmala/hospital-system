<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NicuAttachment extends Model
{
    use HasFactory;

    protected $table = 'nicu_attachments';

    public const TYPE_GENERAL = 'general';

    public const TYPE_ADMISSION_CONSENT = 'admission_consent';

    public const TYPE_BIRTH_CERTIFICATE = 'birth_certificate';

    public const TYPE_PARENT_ID = 'parent_id';

    public const TYPE_REFERRAL_DOC = 'referral_doc';

    protected $fillable = [
        'incubator_reservation_id',
        'patient_id',
        'user_id',
        'title',
        'document_type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'notes',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(IncubatorReservation::class, 'incubator_reservation_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isImage(): bool
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            || in_array(strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf'
            || strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function formattedSize(): string
    {
        if (! $this->file_size) {
            return '-';
        }
        if ($this->file_size >= 1048576) {
            return number_format($this->file_size / 1048576, 1).' MB';
        }

        return number_format($this->file_size / 1024, 0).' KB';
    }

    /**
     * حفظ مرفق للحاضنة من ملف مرفوع أو مسح ضوئي مباشر (Base64)
     */
    public static function storeForReservation(
        IncubatorReservation $reservation,
        ?User $user,
        string $title,
        string $documentType = self::TYPE_GENERAL,
        ?UploadedFile $uploadedFile = null,
        ?string $scannedBase64 = null,
        ?string $notes = null
    ): ?self {
        if ($uploadedFile && $uploadedFile->isValid()) {
            $ext = strtolower($uploadedFile->getClientOriginalExtension() ?: 'bin');
            $storageName = time().'_'.Str::random(8).'.'.$ext;
            $path = $uploadedFile->storeAs('nicu_attachments/'.$reservation->id, $storageName, 'public');

            return static::create([
                'incubator_reservation_id' => $reservation->id,
                'patient_id' => $reservation->patient_id,
                'user_id' => $user?->id,
                'title' => $title,
                'document_type' => $documentType,
                'file_path' => $path,
                'file_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getClientMimeType(),
                'file_size' => $uploadedFile->getSize(),
                'notes' => $notes,
            ]);
        }

        if (! empty($scannedBase64)) {
            $base64Data = $scannedBase64;
            $ext = 'jpg';
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                $ext = strtolower($type[1]);
                if ($ext === 'jpeg') {
                    $ext = 'jpg';
                }
            }

            $decoded = base64_decode($base64Data, true);
            if ($decoded === false) {
                return null;
            }

            $storageName = 'scan_'.time().'_'.Str::random(8).'.'.$ext;
            $relativePath = 'nicu_attachments/'.$reservation->id.'/'.$storageName;
            Storage::disk('public')->put($relativePath, $decoded);

            return static::create([
                'incubator_reservation_id' => $reservation->id,
                'patient_id' => $reservation->patient_id,
                'user_id' => $user?->id,
                'title' => $title,
                'document_type' => $documentType,
                'file_path' => $relativePath,
                'file_name' => $storageName,
                'mime_type' => 'image/'.($ext === 'jpg' ? 'jpeg' : $ext),
                'file_size' => strlen($decoded),
                'notes' => $notes ?? 'مسح ضوئي مباشر عبر السكانر',
            ]);
        }

        return null;
    }
}
