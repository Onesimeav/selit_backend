<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShopCreationRequest;
use App\Models\Shop;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class ShopController extends Controller
{
    public function createShop(ShopCreationRequest $request): JsonResponse
    {
        //images upload on cloudinary
        $logo = $request->file('logo')->storeOnCloudinary('shops/logo');
        $banner = $request->file('banner')->storeOnCloudinary('shops/banner');

        //shop creation
       $shop = Shop::create([
           'name'=>$request->input('name'),
           'description'=>$request->input('description'),
           'logo'=>$logo->getSecurePath(),
           'banner'=>$banner->getSecurePath(),
           'product_type'=>$request->input('product_type'),
           'owner_id'=>Auth::id(),
           'subdomain'=>$request->input('subdomain'),
       ]);
       return response()->json([
           'message'=>'Shop created succesfully',
           'shop_id'=>$shop->id
       ]);
    }

    public function isShopOwner(int $shopId): JsonResponse
    {
        $shop=Shop::find($shopId);

        if ($shop!=null)
        {
            if ($shop->owner_id!=Auth::id())
            {
                return response()->json([
                    'message'=>"The user doesn't own this shop"
                ],403);
            }

        }
        return response()->json([
            'message'=>'The shop does not exist',
        ],404);
    }

    public function chooseTemplate(Request $request): JsonResponse
    {
        $template_id = $request->input('template_id');
        $shop_id = $request->input('shop_id');
        ShopController::class->isShopOwner($shop_id);
        $template = Template::find($template_id);

        if ($template!=null)
        {
            $shop= Shop::findOrFail($shop_id);
            $shop->template_id=$template_id;
            $shop->save();

            return response()->json([
                'message'=>'Template added successfully',
                'shop_id'=>$shop->id,
            ]);
        }

        return response()->json([
            'message'=>'The template does not exist'
        ],403);

    }

    public function publishShop(Request $request): JsonResponse
    {
        $shop_id = $request->input('shop_id');
        $shop = Shop::findOrFail($shop_id);
        if ($shop->owner_id == Auth::id())
        {
            $shop->publish = 'true';
            $shop->save();
            return response()->json([
                'message'=>"Shop published"
            ]);
        }

        return response()->json([
            'message'=>"The user doesn't own this shop"
        ],403);
    }

}
