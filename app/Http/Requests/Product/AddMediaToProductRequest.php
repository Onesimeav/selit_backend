<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class AddMediaToProductRequest extends FormRequest
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
            'product_id'=>'required|integer',
            'videos'=>'array',
            'images'=>'array',
            'images.*' => 'mimes:jpg,png,jpeg|extensions:jpg,png,jpeg|required',
            'videos.*' => 'mimes:mp4,mov|extensions:mp4,mov|required',
        ];
    }
}
