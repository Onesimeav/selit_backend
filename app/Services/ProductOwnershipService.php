<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductOwnershipService
{
    public function isProductOwner($productId): bool
    {
        return Product::where('id', $productId)->where('owner_id', Auth::id())->exists();
    }
}
