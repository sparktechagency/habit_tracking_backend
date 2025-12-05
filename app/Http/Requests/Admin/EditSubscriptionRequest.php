<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EditSubscriptionRequest extends FormRequest
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
            'plan_name' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value == 'Free') {
                        $fail('You cannot update this plan with the name Free! Please enter a different name.');
                    }

                    // $existingPlan = DB::table('subscriptions')
                    //     ->where('plan_name', $value)
                    //     ->first();
        
                    // if ($existingPlan) {
                    //     $fail('A plan with this name already exists. Please enter a different name.');
                    // }
                },
            ],
            'duration' => 'nullable|string|in:monthly,yearly',
            'price' => 'nullable|numeric|min:1',
            'discount' => 'nullable|numeric|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255', // array এর প্রতিটি item string হতে হবে
        ];

    }
}
