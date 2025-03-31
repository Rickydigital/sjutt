<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Query extends Model
{
    use HasFactory;

    protected $fillable = ['description', 'date_sent', 'status', 'track_number', 'admin_id'];

    public function progress()
    {
        return $this->hasMany(QueryProgress::class);
    }
}
