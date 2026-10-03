<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    protected TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Handle incoming Webhook from Telegram
     */
    public function handleWebhook(Request $request)
    {
        $update = $request->all();
        Log::info('Telegram Webhook received: ' . json_encode($update));

        // 1. Handle regular text messages (e.g. /start appt_123)
        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId = $message['chat']['id'] ?? null;
            $text = trim($message['text'] ?? '');
            $username = $message['from']['username'] ?? null;

            if ($chatId && str_starts_with($text, '/start')) {
                $this->handleStartCommand($chatId, $text, $username);
            }
        }

        // 2. Handle Inline Keyboard Callback Queries (e.g. Refresh Queue)
        if (isset($update['callback_query'])) {
            $callback = $update['callback_query'];
            $chatId = $callback['message']['chat']['id'] ?? null;
            $data = $callback['data'] ?? '';

            if ($chatId && str_starts_with($data, 'refresh_queue_')) {
                $apptId = str_replace('refresh_queue_', '', $data);
                $this->handleRefreshQueue($chatId, $apptId);
            }
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Handle /start with deep link parameter
     */
    protected function handleStartCommand(int|string $chatId, string $text, ?string $username = null)
    {
        $parts = explode(' ', $text);
        $param = $parts[1] ?? null;

        if ($param && str_starts_with($param, 'appt_')) {
            $appointmentId = (int) str_replace('appt_', '', $param);
            $appointment = Appointment::with(['patient.user', 'doctor.user', 'department'])->find($appointmentId);

            if ($appointment) {
                // Link appointment and patient with telegram chat ID
                $appointment->update(['telegram_chat_id' => $chatId]);
                if ($appointment->patient) {
                    $appointment->patient->update([
                        'telegram_chat_id' => $chatId,
                        'telegram_username' => $username,
                    ]);
                }

                $this->telegram->sendWelcomeTicket($appointment, $chatId);
                return;
            }
        }

        // Generic welcome if no specific appointment was passed
        $welcome = "🏥 <b>مستشفى الكفاءات الأهلي</b>\n";
        $welcome .= "━━━━━━━━━━━━━━━━━━\n";
        $welcome .= "أهلاً بك في البوت الرسمي لمستشفى الكفاءات الأهلي 👋\n\n";
        $welcome .= "لمتابعة طابور الانتظار واستلام تذكرتك وإشعارات الفحوصات الطبية، يرجى مسح رمز الـ <b>QR Code</b> المطبوع على وصل الدفع الخاص بك.\n\n";
        $welcome .= "📞 <b>طوارئ المستشفى:</b> 07800000000\n";
        $welcome .= "📍 <b>العنوان:</b> بغداد - مستشفى الكفاءات الأهلي";

        $this->telegram->sendMessage($chatId, $welcome);
    }

    /**
     * Handle refresh queue callback
     */
    protected function handleRefreshQueue(int|string $chatId, int $appointmentId)
    {
        $appointment = Appointment::with(['patient.user', 'doctor.user', 'department'])->find($appointmentId);
        if (!$appointment) {
            $this->telegram->sendMessage($chatId, '⚠️ لم يتم العثور على بيانات الموعد.');
            return;
        }

        $queueNum = $appointment->queue_number ?? $appointment->id;
        $aheadCount = Appointment::where('doctor_id', $appointment->doctor_id)
            ->whereDate('appointment_date', today())
            ->where('status', 'scheduled')
            ->where('queue_number', '<', $queueNum)
            ->count();

        $doctorName = optional(optional($appointment->doctor)->user)->name ?? 'الاستشاري';
        $aheadText = $aheadCount > 0 ? "أمامك <b>{$aheadCount} مراجعين</b> في الطابور" : "🎉 <b>أنت التالي في الطابور!</b> يرجى التواجد أمام العيادة.";

        $text = "🔄 <b>تحديث مباشر لطابور الانتظار:</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "🎫 تذكرتك: <code>#{$queueNum}</code>\n";
        $text .= "👨‍⚕️ عيادة: د. {$doctorName}\n";
        $text .= "📊 الحالة الآن: {$aheadText}\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "⏰ <i>آخر تحديث: " . now()->format('H:i:s') . "</i>";

        $buttons = [
            [
                ['text' => '🔄 تحديث مجدداً', 'callback_data' => "refresh_queue_{$appointment->id}"],
            ]
        ];

        $this->telegram->sendMessage($chatId, $text, $buttons);
    }
}
