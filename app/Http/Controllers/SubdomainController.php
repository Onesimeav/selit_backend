<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class SubdomainController extends Controller
{
    public function getShop($domain): JsonResponse
    {
        $shop = Shop::where('subdomain', $domain)->first();

        if (!$shop){
            return response()->json([
                'message'=>'The shop does not exist'
            ],404);
        }

        if (!$shop->publish){
            $user = Auth::guard('sanctum')->user();
            if ($user && $shop->owner_id==$user->id){
                return response()->json([
                    'message'=>'Shops retrived successfully',
                    'shop'=>$shop->toArray(),
                    'published'=>false
                ]);
            }
            return response()->json([
                'message'=>'The shop is in preview mode',
            ],404);
        }
        return response()->json([
            'message'=>'Shops retrived successfully',
            'shop'=>$shop->toArray(),
            'published'=>true,
        ]);

    }

    public function getShopCategories($domain): JsonResponse
    {
        $shop = Shop::where('subdomain',$domain)->first();

        if (!$shop){
            return response()->json([
                'message'=>'The shop does not exist'
            ],404);
        }

        $categories=$shop->categories()->paginate(15)->toArray();

        if (!$shop->publish){
            $user = Auth::guard('sanctum')->user();
            if ($user && $shop->owner_id==$user->id){
                return response()->json([
                    'message'=>'Categories retrived successfully',
                    'categories'=>$categories,
                ]);
            }
            return response()->json([
                'message'=>'The shop is in preview mode',
            ],404);
        }
        return response()->json([
            'message'=>'Categories retrived successfully',
            'categories'=>$categories,
        ]);
    }

    public function getShopProducts($domain): JsonResponse
    {
        $shop = Shop::where('subdomain',$domain)->first();

        if (!$shop){
            return response()->json([
                'message'=>'Shop does not exist'
            ],404);
        }
        $products = $shop->products()->paginate(15)->toArray();

        if (!$shop->publish){
            $user = Auth::guard('sanctum')->user();
            if ($user && $shop->owner_id==$user->id){
                return response()->json([
                    'message'=>'Products retrived successfully',
                    'products'=>$products,
                ]);
            }
            return response()->json([
                'message'=>'The shop is in preview mode',
            ],404);
        }
        return response()->json([
            'message'=>'Products retrived successfully',
            'products'=>$products,
        ]);
    }

    public function getShopCategoryProducts($domain,$id): JsonResponse
    {

        $categoryId = $id;
        $shop = Shop::where('subdomain', $domain)->first();

        if (!$shop) {
            Log::warning("Shop not found for domain: $domain");
            return response()->json([
                'message' => 'This shop does not exist'
            ], 404);
        }

        $category = $shop->categories()->where('id', $categoryId)->first();

        if ($category) {
            $products = $category->products()->paginate(15)->toArray();

            if (!$shop->publish) {
                $user = Auth::guard('sanctum')->user();
                if ($user && $shop->owner_id==$user->id){
                    return response()->json([
                        'message' => 'Products retrieved successfully',
                        'products' => $products,
                    ]);
                }
                return response()->json([
                    'message' => 'The shop is in preview mode',
                ], 404);
            }

            return response()->json([
                'message' => 'Products retrieved successfully',
                'products' => $products,
            ]);
        }

        return response()->json([
            'message' => 'The category does not exist'
        ], 404);
    }


}
