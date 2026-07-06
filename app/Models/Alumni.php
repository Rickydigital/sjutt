<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Alumni extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'alumni';

    protected $fillable = [
        'f_name', 'm_name', 'l_name', 'email', 'password', 'date_of_birth', 'gender', 'phone', 'profile_photo',
        'nida_number', 'settlement_country_id', 'settlement_region', 'settlement_city', 'interested_meetings',
        'interested_social_platform', 'status', 'is_active', 'password_changed_at', 'first_login_at', 'last_login_at', 'imported_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
            'interested_meetings' => 'boolean',
            'interested_social_platform' => 'boolean',
            'password_changed_at' => 'datetime',
            'first_login_at' => 'datetime',
            'last_login_at' => 'datetime',
            'imported_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->f_name . ' ' . $this->m_name . ' ' . $this->l_name);
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'settlement_country_id');
    }

    public function educations()
    {
        return $this->hasMany(AlumniEducation::class);
    }

    public function employments()
    {
        return $this->hasMany(AlumniEmployment::class);
    }

    public function socialPlatforms()
    {
        return $this->belongsToMany(SocialPlatform::class, 'alumni_social_platform')
            ->withPivot(['accepted_invitation', 'joined_at'])
            ->withTimestamps();
    }
}
