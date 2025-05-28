<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Builder\Function_;

class chat extends Model
{
    use HasFactory;
    protected $table = "chats";
    protected $guarded = ["id"];

    protected $fillable = [

    ];

    public function participants(){
        return $this->hasMany(chat_participants::class , 'chat_id');
    }
     public function  messages(){
        return $this->hasMany(chat_messages::class , 'chat_id');
    }
     public function lastmessage(){
        return $this->hasOne( chat_messages::class , 'chat_id')->latest('updated_at');
    }
    public function scopeHasParticipant($query , int $userId){
        return $query->whereHas('participants' , function($q) use ($userId){
            $q->where('user_id',$userId);
        });
    }
}
