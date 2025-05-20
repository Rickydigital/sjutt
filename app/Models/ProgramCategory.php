<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProgramCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'program_category_program');
    }
}