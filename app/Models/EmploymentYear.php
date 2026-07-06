<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmploymentYear extends Model { protected $fillable = ['year']; public function employments(){ return $this->hasMany(AlumniEmployment::class); } }
