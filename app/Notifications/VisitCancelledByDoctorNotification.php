<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Visit;

class VisitCancelledByDoctorNotification extends Notification
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
                    ->subject('إلغاء حجز بعد التحويل للطبيب')
                    ->greeting('مرحباً ' . $notifiable->name)
                    ->line('قام الطبيب ' . optional($this->visit->doctor)->user->name . ' بإلغاء الحجز المحول.')
                    ->line('المريض: ' . optional($this->visit->patient)->user->name)
                    ->line('التاريخ: ' . optional($this->visit->visit_date)->format('Y-m-d'))
                    ->line('الوقت: ' . optional($this->visit->visit_time))
                    ->action('عرض الموعد', route('appointments.show', optional($this->visit->appointment)->id))
                    ->line('يرجى التواصل مع الكاشير لمعالجة استرجاع المبلغ إذا كان هناك دفع سابق.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'إلغاء حجز بعد التحويل للطبيب',
            'message' => 'قام الطبيب ' . optional($this->visit->doctor)->user->name . ' بإلغاء الحجز المحول للمريض ' . optional($this->visit->patient)->user->name . '.',
            'visit_id' => $this->visit->id,
            'appointment_id' => optional($this->visit->appointment)->id,
            'doctor_name' => optional($this->visit->doctor)->user->name,
            'patient_name' => optional($this->visit->patient)->user->name,
            'visit_date' => optional($this->visit->visit_date)->format('Y-m-d'),
            'visit_time' => optional($this->visit->visit_time),
            'type' => 'visit_cancelled_by_doctor',
            'url' => route('appointments.show', optional($this->visit->appointment)->id),
        ];
    }
}
