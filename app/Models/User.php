<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'gender',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function suggestions()
    {
        return $this->hasMany(Suggestion::class);
    }

    public function routeNotificationForMail()
    {
        return $this->email;
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_lecturer');
    }

    public function program(): HasMany
    {
        return $this->hasMany(Program::class, 'administrator_id');
    }

    // Add relationship for examination timetables
    public function examinationTimetables(): BelongsToMany
    {
        return $this->belongsToMany(
            ExaminationTimetable::class,
            'examination_timetable_lecturer',
            'user_id',
            'examination_timetable_id'
        );
    }
}