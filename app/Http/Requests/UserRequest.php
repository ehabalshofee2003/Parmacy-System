<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'اسم المستخدم مطلوب.',
            'username.string' => 'اسم المستخدم يجب أن يكون نصاً.',
            'username.max' => 'اسم المستخدم يجب ألا يتجاوز 255 محرفاً.',
            'username.unique' => 'اسم المستخدم مستخدم مسبقاً.',

            'password.required' => 'كلمة المرور مطلوبة.',
            'password.string' => 'كلمة المرور يجب أن تكون نصاً.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 6 محارف.',
        ];
    }
}
