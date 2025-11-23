<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Visit;

class VisitCreatedNotification extends Notification
{
    use Queueable;

    protected $visit;

    /**
     * Create a new notification instance.
     */
    public function __construct(Visit $visit)
    {
        $this->visit = $visit;
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
                    ->subject('زيارة طبية جديدة')
                    ->greeting('مرحباً د. ' . $notifiable->name)
                    ->line('تم جدولة زيارة طبية جديدة لك.')
                    ->line('تفاصيل الزيارة:')
                    ->line('المريض: ' . $this->visit->patient->user->name)
                    ->line('التاريخ: ' . $this->visit->visit_date->format('Y-m-d'))
                    ->line('الوقت: ' . $this->visit->visit_time)
                    ->line('الشكوى: ' . $this->visit->chief_complaint)
                    ->action('عرض الزيارة', url('/doctor/visits/' . $this->visit->id))
                    ->line('يرجى التحضير للزيارة في الوقت المحدد');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'زيارة طبية جديدة',
            'message' => 'تم جدولة زيارة جديدة للمريض ' . $this->visit->patient->user->name . ' في ' . $this->visit->visit_date->format('Y-m-d') . ' الساعة ' . $this->visit->visit_time,
            'visit_id' => $this->visit->id,
            'patient_name' => $this->visit->patient->user->name,
            'visit_date' => $this->visit->visit_date->format('Y-m-d'),
            'visit_time' => $this->visit->visit_time,
            'chief_complaint' => $this->visit->chief_complaint,
            'type' => 'visit_created',
            'url' => route('doctor.visits.show', $this->visit->id)
        ];
    }
}