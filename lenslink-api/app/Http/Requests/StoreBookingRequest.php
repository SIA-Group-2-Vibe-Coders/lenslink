<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photographer_id' => 'required|integer|exists:users,id',
            'session_date'    => 'required|date|after:today',
            'amount'          => 'required|numeric|min:1',
            'notes'           => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'photographer_id.exists' => 'The selected photographer does not exist.',
            'session_date.after'     => 'Session date must be a future date.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!is_null($this->input('notes'))) {
            $this->merge(['notes' => strip_tags(trim($this->input('notes')))]);
        }
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
