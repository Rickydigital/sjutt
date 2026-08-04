<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlmanacProgramGroup extends Model
{
    protected $fillable = [
        'almanac_setup_id',
        'name',
        'level',
        'display_order',
        'background_color',
        'text_color',
        'is_active',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function setup(): BelongsTo
    {
        return $this->belongsTo(AlmanacSetup::class, 'almanac_setup_id');
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(
            Program::class,
            'almanac_program_group_members',
            'almanac_program_group_id',
            'program_id'
        )->withTimestamps();
    }

    public function weekBlocks(): HasMany
    {
        return $this->hasMany(AlmanacWeekBlock::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(
            AlmanacEvent::class,
            'almanac_event_program_group',
            'almanac_program_group_id',
            'almanac_event_id'
        )->withTimestamps();
    }
}
