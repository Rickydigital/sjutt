<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlmanacWeekBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'almanac_program_group_id' => ['required', 'exists:almanac_program_groups,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'label_name' => ['nullable', 'string', 'max:50'],
            'display_value' => ['nullable', 'string', 'max:30'],
            'block_type' => [
                'required',
                Rule::in(['teaching', 'examination', 'registration', 'orientation', 'fieldwork', 'clinical', 'holiday', 'break', 'other']),
            ],
            'background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
