<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShebaStoreRequest extends FormRequest
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
            'price'           => 'required|numeric|gt:0',
            'fromShebaNumber' => 'required|string|regex:/^IR[0-9]{24}$/',
            'ToShebaNumber'   => 'required|string|regex:/^IR[0-9]{24}$/|different:fromShebaNumber',
            'note'            => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'price.required' => 'The amount is required',
            'price.numeric'  => 'The amount must be a number',
            'price.gt'       => 'The amount must be greater than zero',

            'fromShebaNumber.required' => 'The source Sheba number is required',
            'fromShebaNumber.regex'    => 'The source Sheba number must start with IR followed by 24 digits',

            'ToShebaNumber.required'  => 'The destination Sheba number is required',
            'ToShebaNumber.regex'     => 'The destination Sheba number must start with IR followed by 24 digits',
            'ToShebaNumber.different' => 'The source and destination Sheba numbers cannot be the same',
        ];
    }
}
