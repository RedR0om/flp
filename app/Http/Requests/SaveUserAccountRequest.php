<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SaveUserAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->hasRole(Roles::DIVISION_ADMIN);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cluster_id' => $this->input('cluster_id') ?: null,
            'password' => $this->input('password') ?: null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $account = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($account)],
            'role' => ['required', 'string', Rule::in(Roles::ASSIGNABLE)],
            'cluster_id' => [
                Rule::requiredIf(fn () => in_array($this->input('role'), Roles::CLUSTER_EDITORS, true)),
                'nullable',
                'integer',
                Rule::exists('clusters', 'id'),
            ],
            'password' => [
                $account === null ? 'required' : 'nullable',
                'string',
                Password::default(),
                'confirmed',
            ],
        ];
    }
}
