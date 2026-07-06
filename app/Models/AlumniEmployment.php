<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlumniEmployment extends Model
{
    protected $fillable = ['alumni_id', 'employment_state_id', 'employment_sector_id', 'employment_year_id', 'organization', 'is_current'];
    protected $casts = ['is_current' => 'boolean'];

    public function alumni(){ return $this->belongsTo(Alumni::class); }
    public function employmentState(){ return $this->belongsTo(EmploymentState::class); }
    public function employmentSector(){ return $this->belongsTo(EmploymentSector::class); }
    public function employmentYear(){ return $this->belongsTo(EmploymentYear::class); }
}
