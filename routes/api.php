<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;
Route::post('/telegra/webhook', [TelegramController::class, 'handle']);
Route::post('/send-bulk-message', [TelegramController::class, 'sendBulkMessage']);
Route::get('/message-progress', [TelegramController::class, 'messageProgress']);