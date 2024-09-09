<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'shop_id',
    ];

    protected $with = [
        'medias',
        'specifications'
    ];

    public function medias(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public  function  specifications(): HasMany
    {
        return $this->hasMany(Specification::class);
    }
}
