<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoidPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('void payment receipt');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'void_type' => ['required', 'string', 'in:rebill,refund'],
            'void_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'void_type.required' => 'نوع الإلغاء مطلوب.',
            'void_type.in' => 'نوع الإلغاء غير صالح (يجب أن يكون إعادة فتح أو استرداد).',
            'void_reason.required' => 'سبب إلغاء الوصل إلزامي للرقابة المالية.',
            'void_reason.min' => 'يجب كتابة سبب واضح ومفصل (5 أحرف على الأقل).',
            'void_reason.max' => 'سبب الإلغاء طويل جداً (الحد الأقصى 1000 حرف).',
        ];
    }
}
