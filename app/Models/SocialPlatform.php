<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SocialPlatform extends Model { protected $fillable = ['name']; public function alumni(){ return $this->belongsToMany(Alumni::class, 'alumni_social_platform')->withPivot(['accepted_invitation', 'joined_at'])->withTimestamps(); } }
