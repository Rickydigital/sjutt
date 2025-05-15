<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Building extends Model
{
    protected $fillable = [
        'name',
        'description'];

   
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }
}