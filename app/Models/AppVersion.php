<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_name',
        'version_code',
        'whats_new',
        'download_url',
        'is_force_update',
        'platform',
    ];

    protected $casts = [
        'is_force_update' => 'boolean',
        'version_code' => 'integer',
    ];
}