<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class RewardRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'challenge_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'expiration_date' => 'required',
            'purchase_point' => 'required|integer',
            'status' => 'in:Enable,Disable',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:20480', // 20 mb
            'location' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ];

    }
}
