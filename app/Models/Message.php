<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $primaryKey = 'uuid';
    protected $keyType = 'string';

    protected $guarded = [];
    protected $attributes = [
        'like_count' => 0
    ];
    
}
