<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateScoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'min:1', 'max:3'],
            'is_burst' => ['required', 'boolean'],
        ];
    }

    /**
     * Add the burst-finish cross-field validation.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('is_burst') && (int) $this->input('score') !== 2) {
                $validator->errors()->add(
                    'score',
                    'A burst finish must have a score of exactly 2.',
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'score.min' => 'The score must be at least 1.',
            'score.max' => 'The score may not be greater than 3.',
        ];
    }
}
