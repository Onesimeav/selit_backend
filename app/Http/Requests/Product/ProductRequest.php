<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
            'description'=>'required|string',
            'price'=>'required|integer',
            'images' => 'array',
            'images.*' => 'required|mimes:jpg,png,jpeg|extensions:jpg,png,jpeg',
            'videos' => 'array',
            'videos.*' => 'required|mimes:mp4,mov|extensions:mp4,mov',
            'specification'=>'required|array',
            'product_id'=>'integer'
        ];
    }
}
