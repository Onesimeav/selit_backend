<?php

namespace App\Http\Requests\Shop;

use App\Enums\ProductTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShopCreationRequest extends FormRequest
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
            'name'=>'required|string|max:255',
            'description' =>'required|string',
            'logo'=>'required|mimes:jpg,png|extensions:jpg,png',
            'banner'=>'required|mimes:jpg,png|extensions:jpg,png',
            'product_type'=>['required', Rule::in(array_column(ProductTypeEnum::cases(), 'value'))],
            'subdomain'=>'required|string|max:50|lowercase',
        ];
    }
}
