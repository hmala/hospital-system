<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Request as MedicalRequest;

class RequestCreatedNotification extends Notification
{
    use Queueable;

    protected $request;

    /**
     * Create a new notification instance.
     */
    public function __construct(MedicalRequest $request)
    {
        $this->request = $request;
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
                    ->subject('طلب طبي جديد')
                    ->greeting('مرحباً ' . $notifiable->name)
                    ->line('تم إنشاء طلب طبي جديد يتطلب انتباهك.')
                    ->line('نوع الطلب: ' . $this->request->type_text)
                    ->line('الوصف: ' . $this->request->description)
                    ->action('عرض الطلب', url('/staff/requests/' . $this->request->id . '/show'))
                    ->line('يرجى مراجعة الطلب في أقرب وقت ممكن');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'طلب طبي جديد',
            'message' => 'تم إنشاء طلب ' . $this->request->type_text . ' جديد: ' . $this->request->description,
            'request_id' => $this->request->id,
            'request_type' => $this->request->type,
            'type' => 'request_created',
            'url' => route('staff.requests.show', [$this->request->id])
        ];
    }
}
