<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreScoreRequest extends FormRequest
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
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.score' => ['required', 'integer', 'min:1', 'max:3'],
            'scores.*.is_burst' => ['required', 'boolean'],
        ];
    }

    /**
     * Add the burst-finish cross-field validation.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<int, array{score?: mixed, is_burst?: mixed}> $scores */
            $scores = $this->input('scores', []);

            foreach ($scores as $index => $entry) {
                if (($entry['is_burst'] ?? false) && (int) ($entry['score'] ?? 0) !== 2) {
                    $validator->errors()->add(
                        "scores.{$index}.score",
                        'A burst finish must have a score of exactly 2.',
                    );
                }
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
            'scores.*.score.min' => 'The score must be at least 1.',
            'scores.*.score.max' => 'The score may not be greater than 3.',
            'scores.*.score.required' => 'A score is required.',
        ];
    }
}
