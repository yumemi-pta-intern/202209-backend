<?php

namespace App\Models;


use UUID\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Message extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $primaryKey = 'uuid';
    protected $keyType = 'string';

    protected $guarded = [];
    protected $attributes = [
        'like_count' => 0,
    ];

    const UPDATED_AT = null;
    
    public static function boot() 
    {
        parent::boot();
	    self::creating(function (Message $message) {
            $message->uuid = UUID::uuid7();
            $message->user_uuid = Auth::id();
        });
	}

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }
    public function likes()
    {
        return $this->hasMany(Like::class, 'message_uuid');
    }

    public function like(string $like_user_uuid)
    {
        $already_liked = $this->likes()->where('user_uuid', $like_user_uuid)->exists();
        if (!$already_liked) {
            $this->likes()->create(['user_uuid' => $like_user_uuid]);
            $this->increment('like_count');
        }
    }

    public function delete_like(string $like_user_uuid)
    {
        $already_liked =  $this->likes()->where('user_uuid', $like_user_uuid)->exists();
        if ($already_liked) {
            $this->likes()->where('user_uuid', $like_user_uuid)->first()->delete();
            $this->decrement('like_count');
        }
    }

}
