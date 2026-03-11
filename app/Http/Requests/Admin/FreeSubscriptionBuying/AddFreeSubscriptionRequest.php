<?php

namespace App\Http\Requests\Admin\FreeSubscriptionBuying;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddFreeSubscriptionRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'subscription_id' => [
                'required',
                'exists:subscriptions,id',
                Rule::notIn([1]),
            ],
        ];
    }
}
