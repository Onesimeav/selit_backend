<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'shop_id',
        'owner_id',
    ];

    protected $with = [
        'medias',
        'specifications',
        'autoApplyPromotions',
    ];

    public function medias(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public  function  specifications(): HasMany
    {
        return $this->hasMany(Specification::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class);
    }

   public function autoApplyPromotions():BelongsToMany
   {
       return  $this->belongsToMany(Promotion::class)
                ->where('autoApply','true');
   }
}
