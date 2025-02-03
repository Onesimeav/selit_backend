<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatusEnum;
use App\Enums\ProductTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderSearchRequest extends FormRequest
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
            'search'=>'string',
            'shop_id'=>'integer',
            'status'=>[Rule::in(array_column(OrderStatusEnum::cases(), 'value'))],
        ];
    }
}
