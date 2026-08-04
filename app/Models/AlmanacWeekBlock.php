<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlmanacWeekBlock extends Model
{
    protected $fillable = [
        'almanac_setup_id',
        'almanac_program_group_id',
        'start_date',
        'end_date',
        'display_value',
        'block_type',
        'background_color',
        'text_color',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function setup(): BelongsTo
    {
        return $this->belongsTo(AlmanacSetup::class, 'almanac_setup_id');
    }

    public function programGroup(): BelongsTo
    {
        return $this->belongsTo(AlmanacProgramGroup::class, 'almanac_program_group_id');
    }
}
