<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Kreait\Firebase\Messaging;

class Student extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'first_name',
        'last_name',
        'reg_no',
        'email',
        'password',
        'gender',
        'faculty_id',
        'program_id',
        'is_online',
        'fcm_token',
        'can_upload',
        'last_chat_access_at',
        'status',
        'phone',
    ];

    protected $casts = [
    'can_upload' => 'boolean',
    'is_online'  => 'boolean',
    'status'     => 'string',
];

    public function faculty() {
        return $this->belongsTo(Faculty::class);
    }

    public function program() {
        return $this->belongsTo(Program::class);
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


    // In your Student model
    public static function updateFcmToken($studentId, $newToken)
    {
        if ($newToken) {
            static::where('id', $studentId)->update(['fcm_token' => $newToken]);
        }
    }
}
