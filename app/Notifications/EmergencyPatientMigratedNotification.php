<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Emergency;

class EmergencyPatientMigratedNotification extends Notification
{
    use Queueable;

    protected $emergency;

    /**
     * Create a new notification instance.
     */
    public function __construct(Emergency $emergency)
    {
        $this->emergency = $emergency;
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
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('مريض طوارئ تم ترحيله')
                    ->greeting('مرحباً ' . $notifiable->name)
                    ->line('تم ترحيل معلومات مريض من قسم الطوارئ إلى جدول المرضى العام.')
                    ->line('الرجاء إكمال بيانات المريض وتجهيز الاستشارية.')
                    ->action('عرض الحالة', url('/emergencies/' . $this->emergency->id))
                    ->line('شكراً لتعاونك.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'ترحيل مريض طوارئ',
            'message' => 'تم ترحيل مريض طوارئ من الحالة #' . $this->emergency->id . '. الرجاء إكمال بيانات المريض.',
            'emergency_id' => $this->emergency->id,
            'type' => 'emergency_patient_migrated',
            'url' => route('emergency.show', [$this->emergency->id]),
        ];
    }
}
