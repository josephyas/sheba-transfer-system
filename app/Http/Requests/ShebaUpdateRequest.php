<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShebaUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // No Auth
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [ 'required', 'string', Rule::in( [ 'confirmed', 'canceled' ] ) ],
            'note'   => 'nullable|string|max:255',
        ];
    }


    public function messages(): array
    {
        return [
            'status.required' => 'The status is required',
            'status.in'       => 'The status must be either confirmed or canceled',
        ];
    }
}
