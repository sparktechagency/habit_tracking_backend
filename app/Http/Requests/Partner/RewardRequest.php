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
            'partner_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'challenge_type' => 'required|string|max:255',
            'challenge_group_id' => 'required|integer',
            'description' => 'nullable|string',
            'give_point' => 'nullable|integer',
            'expiration_date' => 'required|date',
            'purchase_point' => 'nullable|integer',
            'status' => 'in:Enable,disable',
        ];
    }
}
