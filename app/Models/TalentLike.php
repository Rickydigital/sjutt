<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentLike extends Model
{
    protected $fillable = ['talent_content_id', 'student_id'];

    public function talentContent(): BelongsTo
    {
        return $this->belongsTo(TalentContent::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}