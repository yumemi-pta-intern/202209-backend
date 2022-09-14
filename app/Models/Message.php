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
    
    protected $dateFormat = 'Y-m-d H:i:s';
    const CREATED_AT = 'create_datetime';
    const UPDATED_AT = NULL;
    public $timestamps = false;

    
    
}
