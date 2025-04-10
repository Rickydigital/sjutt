<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['commentable_id', 'commentable_type', 'news_id', 'comment'];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function news()
    {
        return $this->belongsTo(News::class);
    }
}

