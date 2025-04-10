<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model {
    use HasFactory;

    protected $fillable = ['title', 'description', 'image', 'video', 'created_by'];

    public function user() {
        return $this->belongsTo(Student::class, 'created_by'); // Use Student for your app
    }

    public function reactions() {
        return $this->hasMany(Reaction::class);
    }
    
    public function comments() {
        return $this->hasMany(Comment::class);
    }
}