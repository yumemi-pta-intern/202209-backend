<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $primaryKey = 'id';

    const UPDATED_AT = null;

    /**
     * いいねがついているメッセージを取得
     */
    public function message()
    {
        return $this->belongsTo(Message::class, 'message_uuid', 'id');
    }

    /**
     * いいねを付けているユーザーを取得
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'id');
    }
}
