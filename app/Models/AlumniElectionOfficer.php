<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumniElectionOfficer extends Model
{
    protected $fillable = ['alumni_election_id','user_id','role','is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function election(): BelongsTo { return $this->belongsTo(AlumniElection::class, 'alumni_election_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
