<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmploymentSector extends Model { protected $fillable = ['name']; public function employments(){ return $this->hasMany(AlumniEmployment::class); } }
