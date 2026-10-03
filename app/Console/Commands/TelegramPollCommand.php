<?php

namespace App\Console\Commands;

use App\Http\Controllers\TelegramBotController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramPollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll Telegram API for incoming messages and updates (useful for local development)';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotController $controller)
    {
        $token = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');
        if (!$token) {
            $this->error('TELEGRAM_BOT_TOKEN is not set.');
            return 1;
        }

        $apiUrl = "https://api.telegram.org/bot{$token}";
        $this->info("🤖 Telegram Bot Poller Started for @{$apiUrl}...");
        $this->info("Press Ctrl+C to stop.\n");

        $offset = 0;

        while (true) {
            try {
                $response = Http::withoutVerifying()->timeout(25)->get("{$apiUrl}/getUpdates", [
                    'offset' => $offset,
                    'timeout' => 20,
                ]);

                if ($response->successful()) {
                    $updates = $response->json('result') ?? [];

                    foreach ($updates as $update) {
                        $updateId = $update['update_id'];
                        $offset = $updateId + 1;

                        $this->line("Received update #{$updateId}: " . json_encode($update, JSON_UNESCAPED_UNICODE));

                        $fakeRequest = Request::create('/api/telegram/webhook', 'POST', $update);
                        $controller->handleWebhook($fakeRequest);
                    }
                }
            } catch (\Throwable $e) {
                $this->warn('Polling error: ' . $e->getMessage());
                sleep(2);
            }
        }
    }
}
