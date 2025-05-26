<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicineRequest extends FormRequest
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
                $medicineId = $this->route('medicine'); // تأكد أن اسم الـ route parameter هو 'medicine'

        return [
            'name_en'             => 'required|string|max:255',
            'name_ar'             => 'required|string|max:255',
            'manufacturer'        => 'nullable|string',
            'country_of_origin'   => 'nullable|string',
            'category_id'      => 'required|integer',
            'expiry_date'         => 'required|date',
            'pharmacy_price'      => 'required|numeric|min:0',
            'consumer_price'      => 'required|numeric|min:0',
            'barcode'             => 'nullable|string|unique:medicines,barcode,' . $medicineId,
            'form'                => 'nullable|string',
            'size'                => 'nullable|string',
            'composition'         => 'nullable|string',
            'description'         => 'nullable|string',
            'quantity'            => 'required|integer|min:0',
            'needs_prescription'  => 'required|boolean',
        ];
    }
     public function messages()
    {
        return $this->commonMessages();
    }
      public function commonMessages()
    {
        return [
            'name_en.required'            => 'اسم الدواء بالإنجليزية مطلوب.',
            'name_en.string'              => 'اسم الدواء بالإنجليزية يجب أن يكون نصًا.',
            'name_en.max'                 => 'اسم الدواء بالإنجليزية يجب ألا يتجاوز 255 حرفًا.',

            'name_ar.required'            => 'اسم الدواء بالعربية مطلوب.',
            'name_ar.string'              => 'اسم الدواء بالعربية يجب أن يكون نصًا.',
            'name_ar.max'                 => 'اسم الدواء بالعربية يجب ألا يتجاوز 255 حرفًا.',

            'manufacturer.string'         => 'اسم الشركة المصنعة يجب أن يكون نصًا.',
            'country_of_origin.string'    => 'بلد المنشأ يجب أن يكون نصًا.',

            'expiry_date.required'        => 'تاريخ انتهاء الصلاحية مطلوب.',
            'expiry_date.date'            => 'تاريخ انتهاء الصلاحية غير صالح.',

            'pharmacy_price.required'     => 'سعر البيع للصيدلية مطلوب.',
            'pharmacy_price.numeric'      => 'سعر البيع للصيدلية يجب أن يكون رقمًا.',
            'pharmacy_price.min'          => 'سعر البيع لا يمكن أن يكون أقل من 0.',

            'consumer_price.required'     => 'سعر المستهلك مطلوب.',
            'consumer_price.numeric'      => 'سعر المستهلك يجب أن يكون رقمًا.',
            'consumer_price.min'          => 'سعر المستهلك لا يمكن أن يكون أقل من 0.',

            'barcode.string'              => 'الباركود يجب أن يكون نصًا.',
            'barcode.unique'              => 'الباركود مستخدم من قبل.',

            'form.string'                 => 'شكل الدواء يجب أن يكون نصًا.',
            'size.string'                 => 'حجم الدواء يجب أن يكون نصًا.',
            'composition.string'          => 'التركيبة يجب أن تكون نصًا.',
            'description.string'          => 'الوصف يجب أن يكون نصًا.',

            'quantity.required'           => 'الكمية مطلوبة.',
            'quantity.integer'            => 'الكمية يجب أن تكون عددًا صحيحًا.',
            'quantity.min'                => 'الكمية لا يمكن أن تكون أقل من 0.',

            'needs_prescription.required' => 'يرجى تحديد ما إذا كان الدواء يحتاج إلى وصفة طبية.',
            'needs_prescription.boolean'  => 'قيمة "يحتاج وصفة" يجب أن تكون إما true أو false.',
        ];
    }
}
