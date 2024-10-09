<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $email
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ResetCodePassword newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ResetCodePassword newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ResetCodePassword query()
 * @method static \Illuminate\Database\Eloquent\Builder|ResetCodePassword whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ResetCodePassword whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ResetCodePassword whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ResetCodePassword whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ResetCodePassword whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ResetCodePassword extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'code',
        'created_at',
    ];
}
