<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlmanacProgramGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $setupId = $this->route('setup')?->id ?? $this->input('almanac_setup_id');
        $groupId = $this->route('group')?->id;

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('almanac_program_groups', 'name')
                    ->where(fn ($q) => $q->where('almanac_setup_id', $setupId))
                    ->ignore($groupId),
            ],
            'level' => ['nullable', 'string', 'max:100'],
            'display_order' => ['required', 'integer', 'min:1'],
            'background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['nullable', 'boolean'],
            'program_ids' => ['nullable', 'array'],
            'program_ids.*' => ['integer', 'exists:programs,id'],
        ];
    }
}
