<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SettingsRequest extends FormRequest
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
        $profileId = Auth::user()->profile->id ?? null; // current profile id

        return [
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:20480',
            'full_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'country_code' => 'nullable|string|max:10',
            'address' => 'nullable|string|max:500',
             'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|string|max:255',
            'longitude' => 'nullable|string|max:255',

            'business_name' => 'nullable|string|max:255',
            'user_name' => "nullable|string|max:255|unique:profiles,user_name,{$profileId}",
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'business_hours' => 'nullable|string|max:255',
        ];
    }

}




