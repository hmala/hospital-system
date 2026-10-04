<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramSetWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook {url? : Optional custom HTTPS webhook URL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register the Telegram Bot Webhook URL with Telegram API for production';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');
        if (!$token) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env');
            return 1;
        }

        $rawUrl = $this->argument('url') ?? config('app.url');
        $rawUrl = rtrim($rawUrl, '/');
        
        if (str_contains($rawUrl, '/telegram/webhook')) {
            $webhookUrl = $rawUrl;
        } else {
            $webhookUrl = "{$rawUrl}/api/telegram/webhook";
        }
        
        if (!str_starts_with($webhookUrl, 'https://')) {
            $this->warn("⚠️ Skipping Telegram Webhook registration: HTTPS is required for webhooks (Current URL: {$webhookUrl})");
            return 0;
        }

        $this->info("Setting Telegram Webhook to: {$webhookUrl}");

        try {
            $apiUrl = "https://api.telegram.org/bot{$token}/setWebhook";
            $response = Http::withoutVerifying()->post($apiUrl, [
                'url' => $webhookUrl,
                'drop_pending_updates' => false,
            ]);

            if ($response->successful() && $response->json('ok')) {
                $this->info("✅ Webhook registered successfully!");
                $this->line("Telegram Response: " . $response->json('description'));
                return 0;
            } else {
                $this->error("❌ Failed to set webhook: " . $response->body());
                return 1;
            }
        } catch (\Throwable $e) {
            $this->error("Exception while setting webhook: " . $e->getMessage());
            return 1;
        }
    }
}
