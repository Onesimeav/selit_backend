<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_reference',
        'name',
        'surname',
        'email',
        'number',
        'location',
        'status',
        'invoice',
        'shop_id',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->using(OrderProduct::class)->withPivot('product_name','product_price','product_quantity','promotion_id','promotion_code','price_promotion_applied');
    }


}
