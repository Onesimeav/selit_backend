<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OrderProductPromotion extends Pivot
{
    use HasFactory;

    public $incrementing=true;

    protected $fillable = [
        'order_id',
        'promotion_id',
        'code',
    ];


}
