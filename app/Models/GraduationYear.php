<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GraduationYear extends Model { protected $fillable = ['year']; public function educations(){ return $this->hasMany(AlumniEducation::class); } }
