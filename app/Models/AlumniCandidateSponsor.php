<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumniCandidateSponsor extends Model
{
    protected $fillable = ['candidate_id','name','faculty_school_directorate','registration_no','signature_path'];

    public function candidate(): BelongsTo { return $this->belongsTo(AlumniElectionCandidate::class, 'candidate_id'); }
}
