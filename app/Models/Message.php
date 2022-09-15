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
            // $message->user_uuid = 'test';
        });
	}

}
