<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class chat_messages extends Model
{
    use HasFactory;
     protected $table = "chat_messages";
    protected $guarded = ["id"];
    protected $touches = ['chat'];

     public function user(){
        return $this->belongsTo(User::class , 'user_id');
    }
     public function chat(){
        return $this->belongsTo(Chat::class , 'chat_id');
    }
}
