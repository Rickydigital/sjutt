<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $academicYearId = $this->route('academic_year')?->id
            ?? $this->route('academic_year');

        return [
            'name' => [
                'required',
                'string',
                'max:20',
                'regex:/^\d{4}\/\d{4}$/',
                Rule::unique('academic_years', 'name')->ignore($academicYearId),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Academic year must use the format 2025/2026.',
            'end_date.after' => 'The end date must be later than the start date.',
        ];
    }
}
