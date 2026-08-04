<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AlmanacEvent extends Model
{
    protected $fillable = [
        'almanac_setup_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'event_column',
        'category',
        'applies_to_all',
        'is_no_classes',
        'is_tentative',
        'background_color',
        'text_color',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'applies_to_all' => 'boolean',
        'is_no_classes' => 'boolean',
        'is_tentative' => 'boolean',
    ];

    public function setup(): BelongsTo
    {
        return $this->belongsTo(AlmanacSetup::class, 'almanac_setup_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function programGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            AlmanacProgramGroup::class,
            'almanac_event_program_group',
            'almanac_event_id',
            'almanac_program_group_id'
        )->withTimestamps();
    }
}
