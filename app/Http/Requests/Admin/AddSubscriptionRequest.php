<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AddSubscriptionRequest extends FormRequest
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
            'plan_name' => 'required|string|max:255',
            'duration' => 'required|string|in:monthly,yearly',
            'price' => 'required|numeric|min:0',
            'features' => 'required|array|min:1',
            'features.*' => 'string|max:255', // array এর প্রতিটি item string হতে হবে
        ];
    }
}
