<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

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
            'fromShebaNumber' => [
                'required',
                'string',
                'regex:/^IR[0-9]{24}$/',
                function ($attribute, $value, $fail) {
                    if (!Account::where('sheba_number', $value)->exists()) {
                        $fail('The source Sheba number does not exist in our records.');
                    }
                },
            ],
            'toShebaNumber'   => 'required|string|regex:/^IR[0-9]{24}$/|different:fromShebaNumber',
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

            'toShebaNumber.required'  => 'The destination Sheba number is required',
            'toShebaNumber.regex'     => 'The destination Sheba number must start with IR followed by 24 digits',
            'toShebaNumber.different' => 'The source and destination Sheba numbers cannot be the same',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
            'code' => 'VALIDATION_ERROR'
        ], 422));
    }
}
