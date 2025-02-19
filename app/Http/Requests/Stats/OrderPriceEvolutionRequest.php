<?php

namespace App\Http\Requests\Stats;

use App\Enums\StatsPeriodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderPriceEvolutionRequest extends FormRequest
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
            'shop_id'=>'integer|required',
            'period'=>[Rule::in(array_column(StatsPeriodEnum::cases(), 'value'))]
        ];
    }
}
