<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class EditRewardRequest extends FormRequest
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
            'title' => 'nullable|string|max:255',
            'challenge_type' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'expiration_date' => 'nullable',
            'purchase_point' => 'nullable|integer',
            'status' => 'in:Enable,Disable',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:20480', // 20 mb
            'location' => 'nullable',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
        ];
    }
}
