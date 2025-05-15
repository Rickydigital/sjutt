<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'faculty_id',
        'year_of_study',
        'sender_id',
        'content',
        'tagged_user_id',
        'is_deleted',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    public function sender()
    {
        return $this->belongsTo(Student::class, 'sender_id');
    }

    public function taggedUser()
    {
        return $this->belongsTo(Student::class, 'tagged_user_id');
    }
}
