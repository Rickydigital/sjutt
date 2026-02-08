<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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


    public function generalOfficerElections()
{
    return $this->belongsToMany(
        \App\Models\Election::class,
        'student_general_election_officers',
        'student_id',
        'election_id'
    )->withPivot(['is_active'])->withTimestamps();
}

    public function votes(): HasMany
{
    return $this->hasMany(ElectionVote::class, 'student_id');
}

public function hasActiveOfficerElection(): bool
{
    return $this->generalOfficerElections()
        ->wherePivot('is_active', 1)
        ->where('elections.is_active', 1)
        ->where('elections.status', 'open')
        ->get()
        ->filter(fn ($election) => $election->isStillOpen())
        ->isNotEmpty();
}

/**
 * Check if this student is assigned as an election officer in at least one election
 * (no filters on status, active flag, dates — just existence in the pivot table)
 */
public function isOfficer(): bool
{
    return $this->generalOfficerElections()->exists();
    // or: return $this->managedElections()->exists();  // if you prefer this relation
}

public function candidacies(): HasMany
{
    return $this->hasMany(ElectionCandidate::class, 'student_id');
}

// Optional convenience: elections the student participated in
public function participatedElections()
{
    return $this->belongsToMany(Election::class, 'election_votes', 'student_id', 'election_id')
        ->distinct();
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

    public function managedElections()
{
    return $this->belongsToMany(
        Election::class,
        'student_general_election_officers',
        'student_id',
        'election_id'
    )->withPivot(['is_active'])->withTimestamps();
}

}
