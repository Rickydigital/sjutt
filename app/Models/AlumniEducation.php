<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlumniEducation extends Model
{
    protected $table = 'alumni_educations';
    protected $fillable = ['alumni_id', 'faculty_id', 'program_id', 'graduation_year_id', 'degree_program_major'];

    public function alumni(){ return $this->belongsTo(Alumni::class); }
    public function faculty(){ return $this->belongsTo(Faculty::class); }
    public function program(){ return $this->belongsTo(Program::class); }
    public function graduationYear(){ return $this->belongsTo(GraduationYear::class); }
}
