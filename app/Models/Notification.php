<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class Notification extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'notifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',               // The type of notification, e.g., 'App\Notifications\UserFollow'
        'notifiable_type',    // The type of the notifiable model, e.g., 'App\Models\User'
        'notifiable_id',      // The ID of the notifiable model, e.g., the user ID
        'data',               // JSON data associated with the notification
        'read_at',            // Timestamp for when the notification was read
    ];

//    protected $casts = ['data' => 'array'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Relationship to the User model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function notifiable(): BelongsTo
    {
        return $this->morphTo();
    }


    /**
     * Accessor for the 'data' field to decode JSON.
     *
     * @param string $value
     * @return array
     */
    public function getDataAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Scope a query to only include unread notifications.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

}
