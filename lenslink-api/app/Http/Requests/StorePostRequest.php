<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image'    => 'required|image|mimes:jpeg,png,webp,gif|max:10240',
            'caption'  => 'nullable|string|max:2200',
            'location' => 'nullable|string|max:255',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];
        if (!is_null($this->input('caption'))) {
            $data['caption'] = strip_tags(trim($this->input('caption')));
        }
        if (!is_null($this->input('location'))) {
            $data['location'] = strip_tags(trim($this->input('location')));
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
