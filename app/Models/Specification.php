<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $value
 * @property int $product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Specification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Specification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Specification query()
 * @method static \Illuminate\Database\Eloquent\Builder|Specification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Specification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Specification whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Specification whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Specification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Specification whereValue($value)
 * @mixin \Eloquent
 */
class Specification extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'product_id',
    ];
}
