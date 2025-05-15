<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'media'
    ];

    protected $casts = [
        'media' => 'array', 
    ];

    public function likes()
    {
        return $this->hasMany(GalleryLike::class);
    }
}
