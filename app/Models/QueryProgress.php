<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueryProgress extends Model
{
    use HasFactory;

    protected $fillable = ['query_id', 'admin_description'];

    public function relatedQuery() 
    {
        return $this->belongsTo(Query::class, 'query_id');
    }
}

