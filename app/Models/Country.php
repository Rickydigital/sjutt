<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Country extends Model { protected $fillable = ['name', 'code']; public function alumni(){ return $this->hasMany(Alumni::class, 'settlement_country_id'); } }
