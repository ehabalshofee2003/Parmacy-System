<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
   use HasApiTokens, HasFactory, Notifiable;
    protected $table = "users";
    protected $guarded = ["id"];
    protected $hidden = [
        'password',
    ];


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
protected $fillable = [
    'first_name',
    'last_name',
    'username',
    'phone',
    'role',
    'is_active',
    'password',
];



    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function shifts() {
    return $this->hasMany(Shift::class);
}

public function Bills() {
    return $this->hasMany(Bill::class);
}

public function notifications() {
    return $this->hasMany(Notification::class);
}

public function auditLogs() {
    return $this->hasMany(AuditLog::class);
}

public function reports() {
    return $this->hasMany(Report::class);
}
 public function chat(){
        return $this->hasMany(Chat::class , 'created_by');
    }
public function hasRole($role)
{
    return $this->role === $role;
}
}
