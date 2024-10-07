<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chatId;
    protected $message;
    protected $botToken;

    /**
     * Create a new job instance.
     *
     * @param $chatId
     * @param $message
     * @param $botToken
     */
    public function __construct($chatId, $message, $botToken)
    {
        $this->chatId = $chatId;
        $this->message = $message;
        $this->botToken = $botToken;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = "https://api.telegram.org/bot".env('TELEGRAM_BOT_TOKEN')."/sendMessage";
        
        $data = [
            'chat_id' => $this->chatId,
            'text'    => $this->message
        ];

        // Send the POST request to the Telegram API
        $response = Http::post($url, $data);

        // Handle rate limit (429) by retrying
        if ($response->status() === 429) {
            $retryAfter = $response->json()['parameters']['retry_after'] ?? 60;
            $this->release($retryAfter); // Retry after the specified time
        }
    }
}
