<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'upload_id' => ['required', 'uuid'],
            'original_filename' => ['required', 'string', 'max:255', 'regex:/\.(jpg|jpeg)$/i'],
            'album_id' => ['required', 'integer', 'exists:albums,id'],
            'total_chunks' => ['required', 'integer', 'min:1', 'max:2000'],
        ];
    }
}