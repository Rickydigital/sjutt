<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'gallery_id', 
        'user_id'
    ];

    public function gallery()
    {
        return $this->belongsTo(Gallery::class);
    }
}
