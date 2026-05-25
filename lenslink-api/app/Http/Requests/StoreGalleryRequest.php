<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreGalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public'   => 'nullable',
            'cover_image' => 'nullable|image|mimes:jpeg,png,webp|max:5120',
            'client_id'   => 'nullable|integer|exists:users,id',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];
        if (!is_null($this->input('title'))) {
            $data['title'] = strip_tags(trim($this->input('title')));
        }
        if (!is_null($this->input('description'))) {
            $data['description'] = strip_tags(trim($this->input('description')));
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
