<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCodeSubmissionRequest extends FormRequest
{
    /**
     * The languages accepted by the submission API.
     *
     * @var array<int, string>
     */
    public const AVAILABLE_LANGUAGES = [
        'typescript',
        'python',
        'java',
        'c',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'language' => ['required', 'string', Rule::in(self::AVAILABLE_LANGUAGES)],
            'code' => ['required', 'string', 'max:100000'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'language.in' => 'The selected language must be one of: '.implode(', ', self::AVAILABLE_LANGUAGES).'.',
        ];
    }
}
