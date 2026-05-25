<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users,email',
            'password'  => 'required|string|min:6',
            'role_id'   => 'required|integer|exists:roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'   => 'An account with this email already exists.',
            'role_id.exists' => 'The selected role is invalid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];
        if (!is_null($this->input('full_name'))) {
            $data['full_name'] = strip_tags(trim($this->input('full_name')));
        }
        if (!is_null($this->input('email'))) {
            $data['email'] = strtolower(trim($this->input('email')));
        }
        if (!empty($data)) {
            $this->merge($data);
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
