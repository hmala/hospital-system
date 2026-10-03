<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Request as MedicalRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $token;
    protected string $botUsername;
    protected string $apiUrl;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN', '8926886048:AAFrT7bBwq4Nqus56eOxPbYqHhCJO5XE3lg');
        $this->botUsername = config('services.telegram.bot_username') ?? env('TELEGRAM_BOT_USERNAME', 'Kafathospitalbot');
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}";
    }

    /**
     * Get Bot Username
     */
    public function getBotUsername(): string
    {
        return $this->botUsername;
    }

    /**
     * Generate Deep Link URL for a specific appointment or patient
     */
    public function getAppointmentDeepLink(Appointment $appointment): string
    {
        return "https://t.me/{$this->botUsername}?start=appt_{$appointment->id}";
    }

    /**
     * Generate QR Code Image URL for appointment tracking
     */
    public function getAppointmentQrUrl(Appointment $appointment, int $size = 200): string
    {
        $deepLink = $this->getAppointmentDeepLink($appointment);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&margin=6&data=" . urlencode($deepLink);
    }

    /**
     * Send text message to a chat
     */
    public function sendMessage(string|int $chatId, string $text, ?array $inlineKeyboard = null): bool
    {
        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ];

            if ($inlineKeyboard) {
                $payload['reply_markup'] = json_encode(['inline_keyboard' => $inlineKeyboard]);
            }

            $response = Http::withoutVerifying()->timeout(10)->post("{$this->apiUrl}/sendMessage", $payload);

            if (!$response->successful()) {
                Log::error('Telegram sendMessage failed: ' . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Telegram sendMessage exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send document (PDF, Image, Report)
     */
    public function sendDocument(string|int $chatId, string $filePathOrUrl, string $caption = '', ?string $fileName = null): bool
    {
        try {
            if (filter_var($filePathOrUrl, FILTER_VALIDATE_URL)) {
                $response = Http::withoutVerifying()->timeout(15)->post("{$this->apiUrl}/sendDocument", [
                    'chat_id' => $chatId,
                    'document' => $filePathOrUrl,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]);
            } elseif (file_exists($filePathOrUrl)) {
                $response = Http::withoutVerifying()->timeout(30)->attach(
                    'document',
                    file_get_contents($filePathOrUrl),
                    $fileName ?? basename($filePathOrUrl)
                )->post("{$this->apiUrl}/sendDocument", [
                    'chat_id' => $chatId,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]);
            } else {
                Log::error("Telegram sendDocument file not found: {$filePathOrUrl}");
                return false;
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Telegram sendDocument exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send Welcome & Initial Ticket details to patient when they click Start
     */
    public function sendWelcomeTicket(Appointment $appointment, string|int $chatId): bool
    {
        $patient = $appointment->patient;
        $doctor = $appointment->doctor;
        $patientName = optional($patient->user)->name ?? 'عزيزي المراجع';
        $doctorName = optional($doctor->user)->name ?? 'الاستشاري';
        $specialization = $doctor->specialization ?? 'العيادة الاستشارية';
        $queueNum = $appointment->queue_number ?? $appointment->id;
        $date = $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : now()->format('Y-m-d');
        
        // Count patients ahead
        $aheadCount = Appointment::where('doctor_id', $appointment->doctor_id)
            ->whereDate('appointment_date', today())
            ->where('status', 'scheduled')
            ->where('queue_number', '<', $queueNum)
            ->count();

        $aheadText = $aheadCount > 0 ? "أمامك {$aheadCount} مراجعين" : "أنت التالي في الطابور!";

        $text = "🏥 <b>مستشفى الكفاءات الأهلي</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "أهلاً بك أستاذ <b>{$patientName}</b> 👋\n\n";
        $text .= "🎫 <b>رقم تذكرتك:</b> <code>#{$queueNum}</code>\n";
        $text .= "👨‍⚕️ <b>العيادة:</b> د. {$doctorName} ({$specialization})\n";
        $text .= "📅 <b>التاريخ:</b> {$date}\n";
        $text .= "📊 <b>حالة الدور:</b> {$aheadText}\n\n";
        $text .= "🔔 <i>سنرسل لك إشعاراً فورياً مع نغمة تنبيه هنا بمجرد أن يستدعي الطبيب دورك أو تجهز نتائج تحاليلك!</i>\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "✨ نتمنى لك دوام الصحة والعافية.";

        $buttons = [
            [
                ['text' => '🔄 تحديث حالة الدور الآن', 'callback_data' => "refresh_queue_{$appointment->id}"],
            ]
        ];

        return $this->sendMessage($chatId, $text, $buttons);
    }

    /**
     * Send Call Alert when Doctor presses "Call" button
     */
    public function sendTurnAlert(Appointment $appointment): bool
    {
        $chatId = $appointment->telegram_chat_id 
            ?? optional($appointment->patient)->telegram_chat_id;

        if (!$chatId && $appointment->patient_id) {
            $chatId = Patient::where('id', $appointment->patient_id)->value('telegram_chat_id')
                ?? Appointment::where('patient_id', $appointment->patient_id)->whereNotNull('telegram_chat_id')->latest()->value('telegram_chat_id');
        }

        Log::info("Telegram sendTurnAlert triggered for Appointment #{$appointment->id}, ChatId: " . ($chatId ?? 'NULL'));

        if (!$chatId) {
            return false;
        }

        $patient = $appointment->patient;
        $doctor = $appointment->doctor;
        $patientName = optional(optional($patient)->user)->name ?? 'المراجع';
        $doctorName = optional(optional($doctor)->user)->name ?? 'الاستشاري';
        $specialization = optional($doctor)->specialization ?? 'العيادة';
        $queueNum = $appointment->queue_number ?? $appointment->id;

        $text = "🔔 <b>حان دورك الآن! يرجى الدخول للعيادة</b> 🔔\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "المراجع: <b>{$patientName}</b>\n";
        $text .= "🎫 تذكرة رقم: <code>#{$queueNum}</code>\n\n";
        $text .= "👨‍⚕️ <b>الطبيب:</b> د. {$doctorName}\n";
        $text .= "🚪 <b>القسم:</b> {$specialization}\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "👉 يرجى التوجه إلى باب العيادة والدخول مباشرة.";

        return $this->sendMessage($chatId, $text);
    }

    /**
     * Send Near-Turn alert (e.g. 1 patient remaining ahead)
     */
    public function sendNearTurnAlert(Appointment $appointment, int $aheadCount = 1): bool
    {
        $chatId = $appointment->telegram_chat_id 
            ?? optional($appointment->patient)->telegram_chat_id;

        Log::info("Telegram sendNearTurnAlert triggered for Appointment #{$appointment->id}, ChatId: " . ($chatId ?? 'NULL'));

        if (!$chatId) {
            return false;
        }

        $doctor = $appointment->doctor;
        $doctorName = optional(optional($doctor)->user)->name ?? 'الاستشاري';
        $queueNum = $appointment->queue_number ?? $appointment->id;

        $text = "⏳ <b>تنبيه: اقترب موعد دخولك!</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "تذكرة رقم: <code>#{$queueNum}</code>\n";
        $text .= "بقي أمامك <b>مريض واحد فقط</b> في عيادة د. {$doctorName}.\n\n";
        $text .= "🚶‍♂️ يرجى التواجد بالقرب من صالة انتظار العيادة.";

        return $this->sendMessage($chatId, $text);
    }

    /**
     * Send Lab or Radiology Results Ready Notification
     */
    public function sendResultsReady(MedicalRequest $request, ?string $pdfPath = null): bool
    {
        $visit = $request->visit;
        $appointment = optional($visit)->appointment;
        $patient = optional($visit)->patient;

        $chatId = optional($appointment)->telegram_chat_id ?? optional($patient)->telegram_chat_id;
        if (!$chatId) {
            return false;
        }

        $typeName = $request->type === 'lab' ? 'التحاليل الطبية المخبرية' : 'فحوصات الأشعة والسونار';
        $patientName = optional(optional($patient)->user)->name ?? 'المراجع';

        $text = "🧪 <b>نتائج {$typeName} جاهزة!</b> 📄\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "المراجع: <b>{$patientName}</b>\n";
        $text .= "رقم الطلب: <code>#{$request->id}</code>\n\n";
        $text .= "✅ تم إكمال الفحص وإرسال التقرير لملفك الطبي وللطبيب المعالج.\n";
        $text .= "━━━━━━━━━━━━━━━━━━\n";
        $text .= "يمكنك مراجعة العيادة لعرض النتائج على الطبيب.";

        if ($pdfPath && (file_exists($pdfPath) || filter_var($pdfPath, FILTER_VALIDATE_URL))) {
            return $this->sendDocument($chatId, $pdfPath, $text, "Medical_Report_{$request->id}.pdf");
        }

        return $this->sendMessage($chatId, $text);
    }
}
