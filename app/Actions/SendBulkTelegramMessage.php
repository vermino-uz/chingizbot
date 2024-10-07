<?php

namespace App\Actions;

use App\Jobs\SendTelegramMessageJob;
use App\Models\ActiveUser;

class SendBulkTelegramMessage
{
    public function execute(string $message, string $botToken): void
    {
        $users = ActiveUser::select('chat_id')->get();
        $users->chunk(1000, function($chunkedUsers) use ($botToken, $message) {
            foreach ($chunkedUsers as $user) {
                // Dispatch job with randomized delay to stay within rate limits (25 requests per second)
                $delay = now()->addMilliseconds(rand(40, 50)); // A slight variation helps avoid overload
                
                SendTelegramMessageJob::dispatch($user->chat_id, $message, $botToken)
                    ->onQueue('telegram')
                    ->delay($delay);
            }
        });
    }
}