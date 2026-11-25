<?php

namespace App\Http\Requests;

use App\Enums\FlpLevel;
use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSchoolReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $school = $this->route('school');

        return $school instanceof School && ($this->user()?->can('update', $school) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['learners_total', 'learners_reached'] as $field) {
            if ($this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }

        $this->merge([
            'ta_needed' => filter_var($this->input('ta_needed'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'learners_total' => ['nullable', 'integer', 'min:0'],
            'learners_reached' => ['nullable', 'integer', 'min:0'],
            'flp_level' => ['required', Rule::enum(FlpLevel::class)],
            'ta_needed' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $total = $this->input('learners_total');
            $reached = $this->input('learners_reached');

            if ($total !== null && $reached !== null && (int) $reached > (int) $total) {
                $validator->errors()->add('learners_reached', 'Learners reached cannot exceed total learners.');
            }
        });
    }
}
