<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlmanacWeekBlock extends Model
{
    protected $fillable = [
        'almanac_setup_id',
        'almanac_program_group_id',
        'start_date',
        'end_date',
        'label_name',
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

    protected $appends = [
        'full_label',
    ];

    public function setup(): BelongsTo
    {
        return $this->belongsTo(AlmanacSetup::class, 'almanac_setup_id');
    }

    public function programGroup(): BelongsTo
    {
        return $this->belongsTo(AlmanacProgramGroup::class, 'almanac_program_group_id');
    }

    protected function fullLabel(): Attribute
    {
        return Attribute::get(fn (): string => trim(
            trim((string) $this->label_name) . ' ' . trim((string) $this->display_value)
        ));
    }
}
