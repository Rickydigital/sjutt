<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'location',
        'start_time',
        'end_time',
        'user_allowed',   
        'media',
        'created_by'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'location'     => 'array',
        'end_time'   => 'datetime',
        'user_allowed' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}