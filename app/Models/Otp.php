<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = ['email', 'otp', 'expires_at', 'used', 'data',];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];
}