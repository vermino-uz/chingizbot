<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotUser extends Model
{
    use HasFactory;
    protected $table = 'bot_users';
    protected $fillable = ['chat_id', 'status'];
}
