<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlmanacEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'event_column' => ['required', Rule::in(['academic', 'meeting'])],
            'category' => ['nullable', 'string', 'max:100'],
            'applies_to_all' => ['nullable', 'boolean'],
            'program_group_ids' => ['nullable', 'array'],
            'program_group_ids.*' => ['integer', 'exists:almanac_program_groups,id'],
            'is_no_classes' => ['nullable', 'boolean'],
            'is_tentative' => ['nullable', 'boolean'],
            'background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
