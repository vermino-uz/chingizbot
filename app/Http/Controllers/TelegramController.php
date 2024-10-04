<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\BotUser;
use App\Models\Channel;
use App\Models\Voice;
use App\Models\Ad;
use App\Models\Group;

class TelegramController extends Controller
{
    public function handle(Request $request)
    {

        // Log the incoming request update
     

        // Define bot function
        function bot($method, $datas = []) {
            $url = "https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN') . "/" . $method;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
            $res = curl_exec($ch);
            if (curl_error($ch)) {
                var_dump(curl_error($ch));
            } else {
                return json_decode($res);
            }
            curl_close($ch);
        }

        // Handle /start command
        
        $update = $request->all();
        
        $updateId = $update['update_id'] ?? null;
        $message = $update['message'] ?? [];
        
        $messageId = $message['message_id'] ?? null;
        $from = $message['from'] ?? [];
        $chat = $message['chat'] ?? [];
        $date = $message['date'] ?? null;
        $text = $message['text'] ?? null;
        $entities = $message['entities'] ?? [];
        
        $fromId = $from['id'] ?? null;
        $isBot = $from['is_bot'] ?? null;
        $firstName = $from['first_name'] ?? null;
        $lastName = $from['last_name'] ?? null;
        $username = $from['username'] ?? null;
        $languageCode = $from['language_code'] ?? null;
        $isPremium = $from['is_premium'] ?? null;
        
        $chatId = $chat['id'] ?? null;
        $chatFirstName = $chat['first_name'] ?? null;
        $chatLastName = $chat['last_name'] ?? null;
        $chatUsername = $chat['username'] ?? null;
        $chatType = $chat['type'] ?? null;
        
        $entityOffset = $entities[0]['offset'] ?? null;
        $entityLength = $entities[0]['length'] ?? null;
        $entityType = $entities[0]['type'] ?? null;
        $callbackQuery = $update['callback_query'] ?? null;

        if($callbackQuery){
            $callbackQueryId = $callbackQuery['id'] ?? null;
            $chatId = $callbackQuery['from']['id'] ?? null;
            $data = $callbackQuery['data'] ?? null;
            $messageId = $callbackQuery['message']['message_id'] ?? null;
        }

        function replyKeyboard($key)
        {
            return json_encode(["keyboard" => $key, "resize_keyboard" => true]);
        }

        function inlineKeyboard($key)
        {
            return json_encode(["inline_keyboard" => $key]);
        }

        function greeting($chatId) {
            bot('sendMessage', [
                'chat_id' => $chatId,
                'text' => Ad::first()->text,
                'parse_mode' => 'Markdown',
                'reply_markup' => replyKeyboard([
                    [
                        ['text' => "Kanal"]
                    ,
                        ['text' => "🔊Barcha goloslar"]
                    ]
                    ,
                    [
                        ['text' => "🔝 10 Goloslar"]
                    ]
                ]),
            ]); 

                
            bot('sendMessage', [
                'chat_id' => $chatId,
                'text' => "*Xush kelibsiz*😊

⚡️Reklama bo'yicha: @Reklama\_Chingizbot",
                'parse_mode' => 'Markdown',
                'reply_markup' => inlineKeyboard([
                    [
                        ['text' => "💻 Dasturchi",'url' => 'tg://user?id=1344497552']
                    ]
                ]),
            ]); 
        }

        function joinChannel($chatId) {
            $channel = Channel::all();
            $unjoinedChannels = [];
            foreach ($channel as $ch) {
                $chatMember = bot('getChatMember', [
                    'chat_id' => $ch->chat_id,
                    'user_id' => $chatId,
                ]);
                $status = $chatMember->result->status ?? null;
                if (!in_array($status, ['member', 'administrator', 'creator'])) {
                    $unjoinedChannels[] = $ch;
                }
            }
            
            if (!empty($unjoinedChannels)) {
                $keyboard = [];
                foreach ($unjoinedChannels as $ch) {
                    $keyboard[] = [['text' => "{$ch->name}", 'url' => "{$ch->link}"]];
                }
                $keyboard[] = [['text' => "✅ Tekshirish", 'callback_data' => 'check']];
                
                bot('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "*Botdan foydalanish uchun quyidagi kanallarga a'zo bo'ling:*",
                    'parse_mode' => 'Markdown',
                    'reply_markup' => inlineKeyboard($keyboard),
                ]);
                exit;
            }
            return true;
        }
        if(isset($data) && $data === 'check'){
            bot('deleteMessage', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);
            joinChannel($chatId);
            greeting($chatId);
        }


        if ($text === '/start') {
            $user = BotUser::firstOrCreate(
                ['chat_id' => $fromId],
                ['status' => 1]
            );
            if(joinChannel($chatId)){
                greeting($chatId);
            }
        }

        if ($text === '🔊Barcha goloslar') {
            $page = 1;
            $perPage = 10;
            $voices = Voice::orderBy('id', 'asc')->paginate($perPage, ['*'], 'page', $page);
            
            $messageText = "*Barcha Goloslar ($page-sahifa):*\n\n";
            foreach ($voices as $voice) {
                $messageText .= "/{$voice->id} | {$voice->name} | {$voice->uses}\n";
            }
            
            $keyboard = [];
            if ($voices->hasMorePages()) {
                $keyboard[] = [['text' => 'Keyingi sahifa', 'callback_data' => 'voices_page_' . ($page + 1)]];
            }
            
            bot('sendMessage', [
                'chat_id' => $chatId,
                'text' => $messageText,
                'parse_mode' => 'Markdown',
                'reply_markup' => !empty($keyboard) ? inlineKeyboard($keyboard) : null,
            ]);
        }

        if (isset($data) && strpos($data, 'voices_page_') === 0) {
            $parts = explode('_', $data);
            $page = intval($parts[2]);
            $perPage = 10;

            $voices = Voice::orderBy('id', 'asc')->paginate($perPage, ['*'], 'page', $page);
            
            $messageText = "*Barcha Goloslar ($page-sahifa):*\n\n";
            foreach ($voices as $voice) {
                $messageText .= "/{$voice->id} | {$voice->name} | {$voice->uses}\n";
            }
            
            $keyboard = [];
            
            // Add pagination buttons
            $paginationButtons = [];
            if ($voices->previousPageUrl()) {
                $paginationButtons[] = ['text' => 'Oldingi sahifa', 'callback_data' => "voices_page_" . ($page - 1)];
            }
            if ($voices->hasMorePages()) {
                $paginationButtons[] = ['text' => 'Keyingi sahifa', 'callback_data' => "voices_page_" . ($page + 1)];
            }
            if (!empty($paginationButtons)) {
                $keyboard[] = $paginationButtons;
            }
            
            bot('editMessageText', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $messageText,
                'parse_mode' => 'Markdown',
                'reply_markup' => inlineKeyboard($keyboard),
            ]);
        }


        if($text === "🔝 10 Goloslar"){
            $voices = Voice::orderBy('uses', 'desc')->limit(10)->get();
            $messageText = "*🔝 10 Goloslar:*\n\n";
            foreach ($voices as $voice) {
                $messageText .= "/{$voice->id} | {$voice->name} | {$voice->uses}\n";
            }
            
            bot('sendMessage', [
                'chat_id' => $chatId,
                'text' => $messageText,
                'parse_mode' => 'Markdown',
            ]);
        }
        if (preg_match('/^\/(\d+)$/', $text, $matches)) {
            $voiceId = $matches[1];
            $voice = Voice::find($voiceId);
            if ($voice) {
                // Increment the uses count
                $voice->increment('uses');
                
                // Send the voice message
                $ad3 = Ad::find(3);
                $params = [
                    'chat_id' => $chatId,
                    'voice' => $voice->file_id,
                ];
                if ($ad3 && $ad3->text) {
                    $params['caption'] = $ad3->text;
                    
                }
                bot('sendVoice', $params);
            } else {
                bot('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "*Kechirasiz, $voiceId ID raqamli golos topilmadi.*",
                    'parse_mode' => 'Markdown',
                ]);
            }
        }

        if (isset($update['inline_query'])) {
            $inlineQuery = $update['inline_query'];
            $queryId = $inlineQuery['id'];
            $query = $inlineQuery['query'];
            $offset = $inlineQuery['offset'] ?? '';

            // Search for voices that match the query or show all if query is empty
            $voicesQuery = Voice::query();
            if (!empty($query)) {
                $voicesQuery->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%");
                });
            }

            // Implement pagination
            $limit = 50; // Number of results per page
            $voices = $voicesQuery->orderBy('id')->skip((int)$offset)->take($limit + 1)->get();

            $results = [];
            $hasMore = false;
            foreach ($voices as $index => $voice) {
                if ($index < $limit) {
                    // Increment the uses count
                    $voice->increment('uses');
                    
                    $results[] = [
                        'type' => 'document',
                        'id' => strval($voice->id),
                        'document_file_id' => $voice->file_id,
                        'caption' => Ad::find(2)->text . "?start=" . $voice->id . ')',
                        'parse_mode' => 'Markdown',
                        'title' => $voice->name,
                        'description' => strval($voice->uses),
                        // 'thumbnail_url' => 'https://chingiz.botproject.uz/image.png',
                    ];
                } else {
                    $hasMore = true;
                    break;
                }
            }
            
            // Log the results for debugging

            $nextOffset = $hasMore ? strval((int)$offset + $limit) : '';

            $response = bot('answerInlineQuery', [
                'inline_query_id' => $queryId,
                'results' => json_encode($results),
                'next_offset' => $nextOffset,
                'cache_time' => 0, // Set to 0 to disable caching
                'switch_pm_text' => "Barcha Goloslar",
                'switch_pm_parameter' => "start",
            ]);
        }

        if (strpos($text, '/start ') === 0) {
            $parts = explode(' ', $text);
            if (count($parts) > 1) {
                $voiceId = intval($parts[1]);
                $voice = Voice::find($voiceId);
                if ($voice) {
                    $ad = Ad::find(3);
                    if ($ad && $ad->text) {
                        bot('sendVoice', [
                            'chat_id' => $chatId,
                            'voice' => $voice->file_id,
                            'caption' => $ad->text,
                            'parse_mode' => 'Markdown',
                        ]);
                    } else {
                        bot('sendVoice', [
                            'chat_id' => $chatId,
                            'voice' => $voice->file_id,
                            'parse_mode' => 'Markdown',
                        ]);
                    }
                } else {
                    bot('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => "*Kechirasiz, $voiceId ID raqamli golos topilmadi.*",
                        'parse_mode' => 'Markdown',
                    ]);
                }
            } else {
                greeting($chatId);
            }
        }










        if (strpos($text, '/stats') === 0) {

            $totalUsers = BotUser::count();
            $totalVoices = Voice::count();
            $totalChannels = Group::count();
            $stats = "*🤖 Bot Statistikasi*\n\n";
            $stats .= "👤 *Barcha userlar:* `$totalUsers` ta\n";
            $stats .= "👥 *Barcha guruhlar:* `$totalChannels` ta\n";
            $stats .= "🔊 *Barcha goloslar:* `$totalVoices` ta\n\n";

            bot('sendMessage', [
                'chat_id' => $chatId,
                'text' => $stats,
                'parse_mode' => 'Markdown',
            ]);
        }

        if (isset($message['voice']) && $fromId == 1344497552) {
            $fileId = $message['voice']['file_id'];
            bot('sendMessage', [
                'chat_id' => $chatId,
                'text' => "$fileId",
                'parse_mode' => 'Markdown',
            ]);
        }


        if (isset($message['new_chat_member']) || isset($message['left_chat_member'])) {
            // Check if the bot is an admin in the group
            $botInfo = bot('getChatMember', [
                'chat_id' => $chatId,
                'user_id' => bot('getMe')->result->id,
            ]);

            if ($botInfo->result->status === 'administrator') {
                // Bot is admin, delete the message
                bot('deleteMessage', [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                ]);
            } else {
                // Bot is not admin, send a message
                bot('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "Kirdi chiqdini tozalashim uchun admin bo'lishim kerak",
                    'parse_mode' => 'Markdown',
                ]);
            }
        }
        // Handle /ban command for admins
        if (strpos($text, '/ban') === 0 && isset($message['reply_to_message'])) {
            // Check if the user issuing the command is an admin
            $adminInfo = bot('getChatMember', [
                'chat_id' => $chatId,
                'user_id' => $fromId,
            ]);

            if (in_array($adminInfo->result->status, ['creator', 'administrator'])) {
                $userToBan = $message['reply_to_message']['from']['id'];
                $userToBanName = $message['reply_to_message']['from']['first_name'];

                // Attempt to ban the user
                $banResult = bot('banChatMember', [
                    'chat_id' => $chatId,
                    'user_id' => $userToBan,
                ]);

                if ($banResult->ok) {
                    bot('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => "$userToBanName foydalanuvchisi guruhdan chiqarildi.",
                        'parse_mode' => 'Markdown',
                    ]);
                } else {
                    bot('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => "Foydalanuvchini chiqarib bo'lmadi. Iltimos, kerakli huquqlarim borligiga ishonch hosil qiling.",
                        'parse_mode' => 'Markdown',
                    ]);
                }
            } else {
                bot('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "Faqat adminlar /ban buyrug'ini ishlatishi mumkin.",
                    'parse_mode' => 'Markdown',
                ]);
            }
        }

        














    }
}
