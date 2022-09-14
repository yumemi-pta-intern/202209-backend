<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    protected $dateFormat = 'Y-m-d H:i:s';
}
