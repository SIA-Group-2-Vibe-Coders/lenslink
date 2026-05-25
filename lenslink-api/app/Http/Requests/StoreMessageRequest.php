<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gallery_id'  => 'nullable|integer|exists:galleries,id',
            'receiver_id' => 'nullable|integer|exists:users,id',
            'body'        => 'required|string|max:2000',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->gallery_id && !$this->receiver_id) {
                $validator->errors()->add('recipient', 'Either gallery_id or receiver_id is required.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if (!is_null($this->input('body'))) {
            $this->merge(['body' => strip_tags(trim($this->input('body')))]);
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
