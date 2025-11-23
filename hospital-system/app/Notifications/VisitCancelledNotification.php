<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Visit;

class VisitCancelledNotification extends Notification
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
                    ->subject('إلغاء زيارة طبية')
                    ->greeting('مرحباً ' . $notifiable->name)
                    ->line('تم إلغاء زيارتك الطبية المجدولة.')
                    ->line('تفاصيل الزيارة:')
                    ->line('الطبيب: ' . $this->visit->doctor->user->name)
                    ->line('التاريخ: ' . $this->visit->visit_date->format('Y-m-d'))
                    ->line('الوقت: ' . $this->visit->visit_time)
                    ->action('عرض التفاصيل', url('/patient/visits/' . $this->visit->id))
                    ->line('شكراً لاستخدامك نظام المستشفى');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'إلغاء زيارة طبية',
            'message' => 'تم إلغاء زيارتك الطبية مع الطبيب ' . $this->visit->doctor->user->name . ' في ' . $this->visit->visit_date->format('Y-m-d'),
            'visit_id' => $this->visit->id,
            'doctor_name' => $this->visit->doctor->user->name,
            'visit_date' => $this->visit->visit_date->format('Y-m-d'),
            'visit_time' => $this->visit->visit_time,
            'type' => 'visit_cancelled',
            'url' => route('patient.visits.show', $this->visit->id)
        ];
    }
}
