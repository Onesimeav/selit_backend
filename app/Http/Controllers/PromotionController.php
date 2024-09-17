<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToPromotionRequest;
use App\Http\Requests\PromotionRequest;
use App\Http\Requests\PromotionSearchRequest;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function createPromotion(PromotionRequest $request): JsonResponse
    {
        ShopController::class->isShopOwner($request->input('shop_id'));
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
            $code = fake()->regexify('[A-Z0-9]{8}');
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
        }

        return response()->json([
            'message'=>'Promotion created successfully',
            'promotion_id'=>$promotion->id,
        ],201);
    }

    public function searchPromotion(PromotionSearchRequest $request): JsonResponse
    {
        $shop_id = $request->input('shop_id');
        ShopController::class->isShopOwner($shop_id);

        $search = $request->input('search');
        if ($search!=null)
        {
            $result = Promotion::where('name','like',"%$search%")
                ->where('shop',$shop_id)
                ->get();
        }else
        {
            $result = Promotion::where('shop_id',$shop_id)
                ->get();
        }

        return response()->json([
            'result'=>$result,
        ]);

    }

    public function updatePromotion(PromotionRequest $request,$id): JsonResponse
    {
        $shop_id=$request->input('shop_id');
        ShopController::class->isShopOwner($shop_id);

        $promotion = Promotion::find($id);

        if ($promotion!=null)
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
               $code = fake()->regexify('[A-Z0-9]{8}');
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
            $promotion->save();

            return response()->json([
               'message'=>'Promotion successfully updated',
               'promotion_id'=>$promotion->id,
           ]);
        }

        return response()->json([
            'message'=>'The promotion does not exist'
        ],404);
    }

    public function deletePromotion($id): JsonResponse
    {
        $promotion = Promotion::find($id);
        if ($promotion!=null)
        {
            $shop_id = $promotion->shop_id;
            ShopController::class->isShopOwner($shop_id);
            $promotion->products()->detach();
            $promotion->delete();

            return response()->json([],204);
        }
        return response()->json([
            'message'=>'The promotion does not exist'
        ],404);
    }

    public function addProductsToPromotion(AddToPromotionRequest $request): JsonResponse
    {
        $promotion = Promotion::find($request->input('promotion_id'));

        if ($promotion!=null)
        {
            ShopController::class->isShopOwner($promotion->shop_id);

            $productsId = $request->input('products');
            foreach ($productsId as $productId) {
                ProductController::class->isProductOwner($productId);
            }
            $promotion->products()->attach($productsId);
        }

        return response()->json([
            'message'=>'The promotion does not exist'
        ],404);
    }

    public function removeProductFromPromotion(AddToPromotionRequest $request): JsonResponse
    {
        $promotion = Promotion::find($request->input('promotion_id'));

        if ($promotion!=null)
        {
            ShopController::class->isShopOwner($promotion->shop_id);

            $productsId = $request->input('products');
            foreach ($productsId as $productId) {
                ProductController::class->isProductOwner($productId);
            }
            $promotion->products()->detach($productsId);
        }

        return response()->json([
            'message'=>'The promotion does not exist'
        ],404);
    }

    public function verifyPromoCode($code): JsonResponse
    {
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

            return response()->json([
                'message'=>'Promotion found',
                'promotion'=>$promotion
            ]);
        }

        return response()->json([
            'message'=>'The promotion does not exist'
        ]);
    }

}
