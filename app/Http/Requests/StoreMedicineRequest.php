<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicineRequest extends FormRequest
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
           'name_en'           => 'required|string|max:255',
            'name_ar'          => 'required|string|max:255',
            'manufacturer'     => 'nullable|string',
            'country_of_origin'=> 'nullable|string',
            'expiry_date'      => 'required|date',
            'category_id'      => 'required|integer',
            'pharmacy_price'   => 'required|numeric',
            'consumer_price'   => 'required|numeric',
            'discount'         => 'nullable|numeric',
            'barcode'           => 'nullable|string|unique:medicines,barcode',
            'form'             => 'nullable|string',
            'size'             => 'nullable|string',
            'composition'      => 'nullable|string',
            'description'      => 'nullable|string',
            'stock_quantity'         => 'required|integer',
            'needs_prescription' => 'boolean',
        ];
    }
   public function messages(): array
{
    return [
        'name_en.required' => 'حقل الاسم بالإنجليزية مطلوب.',
        'name_en.string' => 'حقل الاسم بالإنجليزية يجب أن يكون نصاً.',
        'name_en.max' => 'حقل الاسم بالإنجليزية لا يجب أن يتجاوز 255 حرفاً.',

        'name_ar.required' => 'حقل الاسم بالعربية مطلوب.',
        'name_ar.string' => 'حقل الاسم بالعربية يجب أن يكون نصاً.',
        'name_ar.max' => 'حقل الاسم بالعربية لا يجب أن يتجاوز 255 حرفاً.',

        'manufacturer.string' => 'حقل الشركة المصنعة يجب أن يكون نصاً.',

        'country_of_origin.string' => 'حقل بلد المنشأ يجب أن يكون نصاً.',

        'expiry_date.required' => 'تاريخ الانتهاء مطلوب.',
        'expiry_date.date' => 'تاريخ الانتهاء يجب أن يكون تاريخاً صالحاً.',

        'category_id.required' => 'حقل التصنيف مطلوب.',
        'category_id.integer' => 'حقل التصنيف يجب أن يكون عدداً صحيحاً.',

        'pharmacy_price.required' => 'سعر الصيدلية مطلوب.',
        'pharmacy_price.numeric' => 'سعر الصيدلية يجب أن يكون رقماً.',

        'consumer_price.required' => 'سعر المستهلك مطلوب.',
        'consumer_price.numeric' => 'سعر المستهلك يجب أن يكون رقماً.',

        'discount.numeric' => 'قيمة الخصم يجب أن تكون رقماً.',

        'barcode.string' => 'الباركود يجب أن يكون نصاً.',
        'barcode.unique' => ' الباركود.يجب الا يتكرر',

        'form.string' => 'شكل الدواء يجب أن يكون نصاً.',

        'size.string' => 'الحجم يجب أن يكون نصاً.',

        'composition.string' => 'التركيبة يجب أن تكون نصاً.',

        'description.string' => 'الوصف يجب أن يكون نصاً.',

        'stock_quantity.required' => 'الكمية المتوفرة في المخزون مطلوبة.',
        'stock_quantity.integer' => 'الكمية المتوفرة في المخزون يجب أن تكون عدداً صحيحاً.',

        'needs_prescription.boolean' => 'قيمة هل يتطلب وصفة طبية يجب أن تكون صحيحة أو خاطئة.',
    ];
}

}
