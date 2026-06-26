<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Surgery;

class NursingUpdateNotification extends Notification
{
    use Queueable;

    protected Surgery $surgery;
    protected string $nurseName;
    protected array $changes;

    /**
     * إشعار يُرسل للمقيم والجراح عند تحديث بيانات التمريض.
     */
    public function __construct(Surgery $surgery, string $nurseName, array $changes = [])
    {
        $this->surgery = $surgery;
        $this->nurseName = $nurseName;
        $this->changes = $changes;
    }

    /**
     * قنوات الإرسال: قاعدة البيانات + البث الفوري (Pusher).
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * بيانات الإشعار المحفوظة في قاعدة البيانات.
     */
    public function toArray(object $notifiable): array
    {
        $patientName = optional($this->surgery->patient?->user)->full_name
                    ?? optional($this->surgery->patient?->user)->name
                    ?? 'غير محدد';

        $surgeryType = $this->surgery->surgery_type ?? 'غير محدد';

        // بناء قائمة التعديلات
        $changesList = '';
        if (!empty($this->changes)) {
            $lines = [];
            foreach ($this->changes as $label => $newValue) {
                $lines[] = "{$label}: {$newValue}";
            }
            $changesList = "\n" . implode("\n", $lines);
        }

        return [
            'title'        => 'تحديث بيانات تمريض',
            'message'      => "قامت الممرضة {$this->nurseName} بتحديث بيانات المريض {$patientName} ({$surgeryType}):{$changesList}",
            'surgery_id'   => $this->surgery->id,
            'patient_name' => $patientName,
            'surgery_type' => $surgeryType,
            'nurse_name'   => $this->nurseName,
            'changes'      => $this->changes,
            'type'         => 'nursing_update',
            'url'          => route('nursing-station.show', $this->surgery->id),
        ];
    }
}
