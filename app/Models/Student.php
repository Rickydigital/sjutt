<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'reg_no',
        'year_of_study',
        'email',
        'password',
        'gender',
        'faculty_id',
        'is_online',
        'fcm_token',
        'can_upload',
        'last_chat_access_at',
    ];

    public function faculty() {
        return $this->belongsTo(Faculty::class);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'can_upload' => 'boolean',
        ];
    }

    // Relationships for news interactions
    public function reactions()
    {
        return $this->hasMany(Reaction::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'user_id');
    }

    public function suggestions()
    {
        return $this->hasMany(Suggestion::class);
    }

}
