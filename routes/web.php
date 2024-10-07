<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;
Route::get('/', function () {
    return view('welcome');

});

Route::get('/send-bulk-message', [TelegramController::class, 'sendBulkMessage']);
Route::get('/message-progress', [TelegramController::class, 'messageProgress']);
Route::post('/send-messages', [TelegramController::class, 'sendMessages']);
