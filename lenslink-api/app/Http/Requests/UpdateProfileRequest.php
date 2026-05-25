<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // User can only update their own profile (enforced in controller)
    }

    public function rules(): array
    {
        return [
            'name'        => 'nullable|string|max:255',
            'bio'         => 'nullable|string|max:1000',
            'specialty'   => 'nullable|string|max:255',
            'location'    => 'nullable|string|max:255',
            'price_range' => 'nullable|string|max:255',
            'avatar'      => 'nullable|image|mimes:jpeg,png,webp|max:5120',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,webp|max:10240',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];
        foreach (['name', 'bio', 'specialty', 'location'] as $field) {
            if (!is_null($this->input($field))) {
                $data[$field] = strip_tags(trim($this->input($field)));
            }
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
