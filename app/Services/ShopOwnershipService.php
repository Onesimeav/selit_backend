<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Support\Facades\Auth;

class ShopOwnershipService
{
    public function isShopOwner(int $shopId): bool
    {
        return Shop::where('id',$shopId)->where('owner_id',Auth::id())->exists();
    }

}
