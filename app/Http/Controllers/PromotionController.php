<?php

namespace App\Http\Controllers;

use App\Http\Requests\Promotion\AddToPromotionRequest;
use App\Http\Requests\Promotion\PromotionRequest;
use App\Http\Requests\Promotion\PromotionSearchRequest;
use App\Http\Requests\Promotion\PromotionUpdateRequest;
use App\Http\Requests\Promotion\VerifyPromotionCodeRequest;
use App\Models\Promotion;
use App\Models\Shop;
use App\Services\ProductOwnershipService;
use App\Services\ShopOwnershipService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/*
 * TODO:Promotion start day should depend on activation date not on creation date
 * */
class PromotionController extends Controller
{
    public function createPromotion(PromotionRequest $request,ShopOwnershipService $shopOwnershipService): JsonResponse
    {
        if ($shopOwnershipService->isShopOwner($request->input('shop_id')))
        {
            $autoApply = $request->input('autoApply');
            if ($autoApply)
            {
                $promotion = Promotion::create([
                    'name'=>$request->input('name'),
                    'value'=>$request->input('value'),
                    'duration'=>$request->input('duration'),
                    'autoApply'=>'true',
                    'shop_id'=>$request->input('shop_id'),
                ]);
            }else{
                $code = Str::upper(Str::random(6)).rand(10,99);
                $promotion=Promotion::create([
                    'name'=>$request->input('name'),
                    'code'=>$code,
                    'value'=>$request->input('value'),
                    'duration'=>$request->input('duration'),
                    'shop_id'=>$request->input('shop_id'),
                ]);
            }

            $active=$request->input('active');
            if ($active)
            {
                $promotion->active='true';
                $promotion->save();
            }

            return response()->json([
                'message'=>'Promotion created successfully',
                'promotion_id'=>$promotion->id,
            ],201);
        }
        return response()->json([],403);
    }

    public function getPromotion($id): JsonResponse
    {
        $promotion = Promotion::findOrFail($id);
        return response()->json([
            'message'=>'Promotion retrieved successfully',
            'promotion'=>$promotion->toArray(),
        ]);
    }

    public function searchPromotion(PromotionSearchRequest $request, ShopOwnershipService $shopOwnershipService): JsonResponse
    {
        $userShops = Shop::where('owner_id',Auth::id())->get();
        $shopIds =[];
        foreach ($userShops as $shop){
            $shopIds[]=$shop->id;
        }
        $result = Promotion::whereIn('shop_id',$shopIds);

        if ($request->filled('shop_id')){
            if ($shopOwnershipService->isShopOwner($request->input('shop_id'))){
                $result = $result->where('shop_id',$request->input('shop_id'));
            }else{
                return response()->json([],403);
            }
        }

        if ($request->filled('search')){
            $search = $request->input('search');
            $result = $result->where('name','like',"%$search%");
        }

        return response()->json([
            'result'=>$result->paginate('15')->toArray(),
        ]);
    }

    public function updatePromotion(PromotionUpdateRequest $request,ShopOwnershipService $shopOwnershipService,$id): JsonResponse
    {
        $promotion = Promotion::find($id);

        if ($promotion!=null)
        {
            if ($shopOwnershipService->isShopOwner($promotion->shop_id))
            {
                $autoApply = $request->input('autoApply');
                if ($autoApply && !$promotion->autoApply)
                {
                    $promotion->code=null;
                    $promotion->name=$request->input('name');
                    $promotion->value=$request->input('value');
                    $promotion->duration=$request->input('duration');
                    $promotion->autoApply='true';
                }elseif (!$autoApply && $promotion->autoApply)
                {
                    $code = Str::upper(Str::random(6)).rand(10,99);
                    $promotion->code=$code;
                    $promotion->name=$request->input('name');
                    $promotion->value=$request->input('value');
                    $promotion->duration=$request->input('duration');
                    $promotion->autoApply='false';
                }else{
                    $promotion->name=$request->input('name');
                    $promotion->value=$request->input('value');
                    $promotion->duration=$request->input('duration');
                }
                $active=$request->input('active');
                if ($active)
                {
                    $promotion->active=$active;
                }
                $promotion->save();

                return response()->json([
                    'message'=>'Promotion successfully updated',
                    'promotion_id'=>$promotion->id,
                ]);
            }
            return response()->json([],403);
        }

        return response()->json([
            'message'=>'The promotion does not exist'
        ],404);
    }

    public function deletePromotion(ShopOwnershipService $shopOwnershipService,$id): JsonResponse
    {
        $promotion = Promotion::find($id);
        if ($promotion!=null)
        {
            $shop_id = $promotion->shop_id;
            if ($shopOwnershipService->isShopOwner($shop_id))
            {
                $promotion->products()->detach();
                $promotion->delete();

                return response()->json([],204);
            }
            return response()->json([],403);
        }
        return response()->json([
            'message'=>'The promotion does not exist'
        ],404);
    }

    public function addProductsToPromotion(AddToPromotionRequest $request,ShopOwnershipService $shopOwnershipService, ProductOwnershipService $productOwnershipService): JsonResponse
    {
        $promotion = Promotion::find($request->input('promotion_id'));

        if ($promotion!=null)
        {
            if ($shopOwnershipService->isShopOwner($promotion->shop_id))
            {
                $productController = new ProductController();
                $productsId = $request->input('products');
                foreach ($productsId as $productId) {
                    if (!$productOwnershipService->isProductOwner($productId))
                    {
                        return response()->json([],403);
                    }
                }
                $promotion->products()->attach($productsId);

                return response()->json([
                    'message'=>'Products added successfully'
                ]);
            }
            return response()->json([],403);
        }

        return response()->json([
            'message'=>'The promotion does not exist'
        ],404);
    }

    public function removeProductFromPromotion(AddToPromotionRequest $request,ProductOwnershipService $productOwnershipService, ShopOwnershipService $shopOwnershipService): JsonResponse
    {
        $promotion = Promotion::find($request->input('promotion_id'));

        if ($promotion!=null)
        {
            if ($shopOwnershipService->isShopOwner($promotion->shop_id))
            {
                $productsId = $request->input('products');
                foreach ($productsId as $productId) {
                    if (!$productOwnershipService->isProductOwner($productId))
                    {
                        return response()->json([],403);
                    }
                }
                $promotion->products()->detach($productsId);
                 return response()->json([
                     'message'=>'Products removed successfully'
                 ]);
            }
            return response()->json([],403);
        }

        return response()->json([
            'message'=>'The promotion does not exist'
        ],404);
    }

    public function verifyPromoCode(VerifyPromotionCodeRequest $request): JsonResponse
    {
        $code = $request->input('code');
        $promotion = Promotion::where('code',$code)->first();

        if ($promotion!=null)
        {
            $promoDuration = $promotion->duration;
            if ($promotion->created_at->addDays($promoDuration)->isPast())
            {
                return response()->json([
                    'message'=>'Promotion code expired'
                ]);
            }
            $products=$promotion->products()->whereIn('products.id',$request->input('products'))->pluck('products.id');
            if ($products!=null){
                return response()->json([
                    'message'=>'Promotion found',
                    'promotion'=>$promotion->toArray(),
                    'products'=>$products->toArray(),
                ]);
            }
            return response()->json([
                'message'=>'The promotion does not apply to the products',
            ]);
        }

        return response()->json([
            'message'=>'The promotion does not exist'
        ],404);
    }

    public function getPromotionProducts(int $id, ShopOwnershipService $shopOwnershipService): JsonResponse
    {
        $promotion = Promotion::findOrFail($id);

        if ($promotion!=null){
            if ($shopOwnershipService->isShopOwner($promotion->shop_id)){
                $products = $promotion->products()->paginate(15);

                return response()->json([
                    'products'=>$products,
                ]);
            }
            return response()->json([],403);
        }

        return response()->json([
            'message'=>'The promotion does not exist',
        ],404);

    }

}
