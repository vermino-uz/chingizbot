<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;
Route::post('/telegra/webhook', [TelegramController::class, 'handle']);
