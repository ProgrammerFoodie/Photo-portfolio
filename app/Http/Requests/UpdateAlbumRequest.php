<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlbumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date_taken' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('albums', 'id'),
                Rule::notIn([$this->route('album')->id]),
            ],
        ];
    }
}