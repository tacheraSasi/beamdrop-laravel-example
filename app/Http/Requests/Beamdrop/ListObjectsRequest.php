<?php

namespace App\Http\Requests\Beamdrop;

use Illuminate\Foundation\Http\FormRequest;

class ListObjectsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bucket' => ['nullable', 'string', 'min:3', 'max:63', 'regex:/^(?!\d+\.\d+\.\d+\.\d+$)[a-z0-9](?:[a-z0-9.-]{1,61}[a-z0-9])$/'],
            'prefix' => ['nullable', 'string', 'max:1024'],
            'delimiter' => ['nullable', 'string', 'size:1'],
            'max_keys' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'object_key' => ['nullable', 'string', 'max:1024'],
        ];
    }
}
