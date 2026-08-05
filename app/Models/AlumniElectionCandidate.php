<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlumniElectionCandidate extends Model
{
    protected $fillable = [
        'alumni_election_id','alumni_election_position_id','alumni_id','photo','surname','first_name','middle_name',
        'date_of_birth','sex','marital_status','education_level','current_position','institution','address','email','phone',
        'applicant_type','institution_attended','programme_studied','year_graduated','application_status',
        'rejection_reason','approved_by','approved_at'
    ];

    protected $appends = ['photo_url'];

    protected $casts = ['date_of_birth' => 'date','approved_at' => 'datetime'];

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo) {
            return asset('storage/' . $this->photo);
        }

        // The candidate application form doesn't collect a dedicated photo —
        // fall back to the linked alumni account's own profile photo.
        if ($this->alumni?->profile_photo) {
            return asset('storage/' . $this->alumni->profile_photo);
        }

        return null;
    }

    public function election(): BelongsTo { return $this->belongsTo(AlumniElection::class, 'alumni_election_id'); }
    public function position(): BelongsTo { return $this->belongsTo(AlumniElectionPosition::class, 'alumni_election_position_id'); }
    public function alumni(): BelongsTo { return $this->belongsTo(Alumni::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function sponsors(): HasMany { return $this->hasMany(AlumniCandidateSponsor::class, 'candidate_id'); }
    public function votes(): HasMany { return $this->hasMany(AlumniElectionVote::class, 'candidate_id'); }
}
