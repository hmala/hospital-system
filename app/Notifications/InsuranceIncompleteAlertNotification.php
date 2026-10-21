<?php

namespace App\Notifications;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InsuranceIncompleteAlertNotification extends Notification
{
    use Queueable;

    public $patient;
    public $missingFields;
    public $sender;

    /**
     * Create a new notification instance.
     */
    public function __construct(Patient $patient, array $missingFields, ?User $sender = null)
    {
        $this->patient = $patient;
        $this->missingFields = $missingFields;
        $this->sender = $sender;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $patientUser = $this->patient->user;
        $patientName = $patientUser?->name ?? 'مريض غير معروف';
        $senderName = $this->sender ? $this->sender->name : 'الاستعلامات';
        $missingText = !empty($this->missingFields) ? implode('، ', $this->missingFields) : 'بيانات أساسية';
        $insNum = $this->patient->insurance_card_no ?: ($this->patient->insurance_number ?: $this->patient->insurance_booklet_number);

        return [
            'id' => $this->id ?? null,
            'title' => '🚨 طلب إكمال نواقص عاجل لمريض ضمان',
            'message' => "المريض [{$patientName}] في الاستعلامات بحاجة لإكمال النواقص: ({$missingText}). مرسل من: {$senderName}.",
            'patient_id' => $this->patient->id,
            'patient_name' => $patientName,
            'missing_fields' => $this->missingFields,
            'type' => 'insurance_incomplete_alert',
            'time_ago' => 'الآن',
            'patient' => [
                'id' => $this->patient->id,
                'name' => $patientName,
                'phone' => $patientUser?->phone ?? '',
                'gender' => $patientUser?->gender ?? '',
                'age' => $this->patient->age ?? '',
                'dob' => $patientUser?->date_of_birth ? \Carbon\Carbon::parse($patientUser->date_of_birth)->format('Y-m-d') : '',
                'national_id' => $this->patient->national_id ?? '',
                'insurance_number' => $insNum ?? '',
                'category' => $this->patient->insurance_type === 'hi' ? 'ضمان صحي' : 'كاش / عام'
            ],
            'url' => route('patients.index', [
                'search' => $patientName,
                'open_quick' => $this->patient->id
            ]),
        ];
    }
}
