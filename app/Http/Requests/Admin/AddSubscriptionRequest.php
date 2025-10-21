<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

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
    'plan_name' => [
        'required',
        'string',
        'max:255',
        function ($attribute, $value, $fail) {
            // চেক করুন যে প্ল্যানের নাম 'Free' হলে
            if ($value == 'Free') {
                $fail('You cannot create a plan with the name Free! Please enter a different name.');
            }

            // এখন চেক করা হচ্ছে যে 'Free' ছাড়া অন্য কোন প্ল্যান আগে থেকেই আছে কিনা
            $existingPlan = DB::table('subscriptions')
                ->where('plan_name', $value)
                ->first();

            if ($existingPlan) {
                $fail('A plan with this name already exists. Please enter a different name.');
            }
        },
    ],
    'duration' => 'required|string|in:monthly,yearly',
    'price' => 'required|numeric|min:0',
    'features' => 'required|array|min:1',
    'features.*' => 'string|max:255',
];

    }
}
