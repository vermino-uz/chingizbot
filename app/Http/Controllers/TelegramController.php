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
                'text' => "*Salom👋
Man o'zbekcha va arabcha reklamalarni, ssilkalarni va join, left kabi (kirdi, chiqdilarni) guruhlarda o'chirib beraman xurmat bilan Chingiz

Man ishlashim uchun guruhizga qo'shib ADMIN berishiz kerak😄*

⚡️Reklama bo'yicha: @Reklama\_Chingizbot",
                'parse_mode' => 'Markdown',
                'reply_markup' => inlineKeyboard([
                
                    [
                        ['text' => "➕ Guruhga qo'shish", 'url' => 'https://t.me/' . env('BOT_USERNAME') . '?startgroup=start&admin=change_info+delete_messages+restrict_members+pin_messages+manage_video_chats+promote_members+invite_users']
                    ],
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
            if ($chatType === 'private') {
                greeting($chatId);
            }
        }


        if ($text === '/start' && $chatType === 'private') {
            $user = BotUser::firstOrCreate(
                ['chat_id' => $fromId],
                ['status' => 1]
            );
            if(joinChannel($chatId)){
                greeting($chatId);
            }
        }

        if ($text === '🔊Barcha goloslar' && $chatType === 'private') {
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


        if($text === "🔝 10 Goloslar" && $chatType === 'private'){
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
            } else if ($chatType === 'private') {
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
        function del(){
            global $chatId, $messageId;
            bot('deleteMessage', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);
        }
        // Handle message deletion for messages containing links, URLs, or t.me
        // Check if the message contains links or t.me
        if (preg_match('/(https?:\/\/|t\.me|@\w+)/i', $text)) {
            // Delete the message
            bot('deletemessage', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);

            // Optionally, send a warning message
            bot('sendMessage', [
                'chat_id' => $chatId,
                'text' => "Reklamalarni yuborish mumkin emas",
                'parse_mode' => 'Markdown',
            ]);
        }


        
        // Handle mute command
        if (strpos($text, '/mute') === 0) {
            // Check if the user is an admin
            $chatMember = bot('getChatMember', [
                'chat_id' => $chatId,
                'user_id' => $fromId,
            ]);
            $isAdmin = in_array($chatMember->result->status, ['creator', 'administrator']);

            if ($isAdmin) {
                $parts = explode(' ', $text);
                $userToMute = null;
                $duration = null;

                if (isset($message['reply_to_message'])) {
                    $userToMute = $message['reply_to_message']['from']['id'];
                    $duration = isset($parts[1]) ? $parts[1] : null;
                } elseif (count($parts) === 3) {
                    $userToMute = str_replace('@', '', $parts[1]);
                    $duration = $parts[2];
                }

                if (!$userToMute || !$duration) {
                    bot('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => "Noto'g'ri format. Iltimos, /mute @username 5h formatida yuboring yoki xabarga javob bering va /mute 5h deb yozing.",
                        'parse_mode' => 'Markdown',
                    ]);
                    return;
                }

                // Convert duration to seconds
                $seconds = 0;
                if (preg_match('/^(\d+)([hmd])$/', $duration, $matches)) {
                    $value = intval($matches[1]);
                    $unit = $matches[2];
                    switch ($unit) {
                        case 'h':
                            $seconds = $value * 3600;
                            break;
                        case 'm':
                            $seconds = $value * 60;
                            break;
                        case 'd':
                            $seconds = $value * 86400;
                            break;
                    }
                } else {
                    bot('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => "Noto'g'ri vaqt formati. Iltimos, h (soat), m (daqiqa) yoki d (kun) dan foydalaning.",
                        'parse_mode' => 'Markdown',
                    ]);
                    return;
                }

                // Get the user ID if username was provided
                if (!is_numeric($userToMute)) {
                    $chatMember = bot('getChatMember', [
                        'chat_id' => $chatId,
                        'user_id' => "@$userToMute",
                    ]);

                    if (!$chatMember->ok) {
                        bot('sendMessage', [
                            'chat_id' => $chatId,
                            'text' => "Foydalanuvchi topilmadi.",
                            'parse_mode' => 'Markdown',
                        ]);
                        return;
                    }

                    $userToMute = $chatMember->result->user->id;
                }

                // Mute the user
                $muteResult = bot('restrictChatMember', [
                    'chat_id' => $chatId,
                    'user_id' => $userToMute,
                    'until_date' => time() + $seconds,
                    'permissions' => json_encode([
                        'can_send_messages' => false,
                        'can_send_media_messages' => false,
                        'can_send_polls' => false,
                        'can_send_other_messages' => false,
                        'can_add_web_page_previews' => false,
                        'can_change_info' => false,
                        'can_invite_users' => false,
                        'can_pin_messages' => false,
                    ]),
                ]);

                if ($muteResult->ok) {
                    bot('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => "Foydalanuvchi $duration muddatga cheklab qo'yildi.",
                        'parse_mode' => 'Markdown',
                    ]);
                } else {
                    bot('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => "Foydalanuvchini cheklab bo'lmadi. Iltimos, kerakli huquqlarim borligiga ishonch hosil qiling.",
                        'parse_mode' => 'Markdown',
                    ]);
                }
            } else {
                bot('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "Faqat adminlar /mute buyrug'ini ishlatishi mumkin.",
                    'parse_mode' => 'Markdown',
                ]);
            }
        }
        elseif (strpos($text, '/unmute') === 0) {
            $chatMember = bot('getChatMember', [
                'chat_id' => $chatId,
                'user_id' => $fromId,
            ]);

            if (in_array($chatMember->result->status, ['creator', 'administrator'])) {
                $userToUnmute = null;

                if (isset($message['reply_to_message'])) {
                    $userToUnmute = $message['reply_to_message']['from']['id'];
                } else {
                    $parts = explode(' ', $text);
                    if (count($parts) >= 2) {
                        $userToUnmute = trim($parts[1]);
                    }
                }

                if (!$userToUnmute) {
                    bot('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => "Foydalanuvchi nomini yoki ID sini kiriting yoki xabarga javob bering. Masalan: /unmute @username yoki /unmute 123456789",
                        'parse_mode' => 'Markdown',
                    ]);
                    return;
                }

                // Check if the input is a username or user ID
                if (strpos($userToUnmute, '@') === 0) {
                    $userToUnmute = substr($userToUnmute, 1);
                    $chatMember = bot('getChatMember', [
                        'chat_id' => $chatId,
                        'user_id' => "@$userToUnmute",
                    ]);
                } elseif (!is_numeric($userToUnmute)) {
                    $chatMember = bot('getChatMember', [
                        'chat_id' => $chatId,
                        'user_id' => "@$userToUnmute",
                    ]);
                } else {
                    $chatMember = bot('getChatMember', [
                        'chat_id' => $chatId,
                        'user_id' => $userToUnmute,
                    ]);
                }

                if (!$chatMember->ok) {
                    bot('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => "Foydalanuvchi topilmadi.",
                        'parse_mode' => 'Markdown',
                    ]);
                    return;
                }

                $userToUnmuteId = $chatMember->result->user->id;

                // Unmute the user
                $unmuteResult = bot('restrictChatMember', [
                    'chat_id' => $chatId,
                    'user_id' => $userToUnmuteId,
                    'permissions' => json_encode([
                        'can_send_messages' => true,
                        'can_send_media_messages' => true,
                        'can_send_polls' => true,
                        'can_send_other_messages' => true,
                        'can_add_web_page_previews' => true,
                        'can_change_info' => true,
                        'can_invite_users' => true,
                        'can_pin_messages' => true,
                    ]),
                ]);

                if ($unmuteResult->ok) {
                    bot('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => "Foydalanuvchidan cheklov olib tashlandi.",
                        'parse_mode' => 'Markdown',
                    ]);
                } else {
                    bot('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => "Foydalanuvchidan cheklovni olib bo'lmadi. Iltimos, kerakli huquqlarim borligiga ishonch hosil qiling.",
                        'parse_mode' => 'Markdown',
                    ]);
                }
            } else {
                bot('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "Faqat adminlar /unmute buyrug'ini ishlatishi mumkin.",
                    'parse_mode' => 'Markdown',
                ]);
            }
        }

        














    }
}
