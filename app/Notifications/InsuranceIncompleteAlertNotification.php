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
        $patientName = optional($this->patient->user)->name ?? 'مريض غير معروف';
        $senderName = $this->sender ? $this->sender->name : 'الاستعلامات';
        $missingText = !empty($this->missingFields) ? implode('، ', $this->missingFields) : 'بيانات أساسية';

        return [
            'title' => '🚨 طلب إكمال نواقص عاجل لمريض ضمان',
            'message' => "المريض [{$patientName}] في الاستعلامات بحاجة لإكمال النواقص: ({$missingText}). مرسل من: {$senderName}.",
            'patient_id' => $this->patient->id,
            'patient_name' => $patientName,
            'missing_fields' => $this->missingFields,
            'type' => 'insurance_incomplete_alert',
            'url' => route('patients.index', [
                'search' => $patientName,
                'open_quick' => $this->patient->id
            ]),
        ];
    }
}
