<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCartRequest extends FormRequest
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
        'customer_name' => 'string|max:255',
        'items' => 'required|array|min:1',
        'items.*.item_type' => 'required|in:medicine,supply',
        'items.*.item_id' => 'required|integer|min:1',
        'items.*.quantity' => 'required|integer|min:1',
    ];
}
}
