<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class setOrderStateAsDeliveryRequest extends FormRequest
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
            'order_id'=>'required|integer',
            'deliveryman_email'=>'required|string|lowercase|email|max:255',
            'deliveryman_name'=>'required|string',
            'deliveryman_surname'=>'required|string',
            'deliveryman_number'=>'required|integer'
        ];
    }
}
