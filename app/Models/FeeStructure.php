<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_type', 
        'program_name', 
        'first_year', 
        'continuing_year', 
        'final_year'
    ];
}
