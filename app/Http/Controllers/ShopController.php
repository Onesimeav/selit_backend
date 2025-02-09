<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\ChangeShopMainColorRequest;
use App\Http\Requests\Shop\ChooseShopTemplateRequest;
use App\Http\Requests\Shop\SearchShopRequest;
use App\Http\Requests\Shop\ShopCreationRequest;
use App\Models\Order;
use App\Models\Shop;
use App\Models\Template;
use App\Models\User;
use App\Services\ShopOwnershipService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;


class ShopController extends Controller
{
    public function createShop(ShopCreationRequest $request): JsonResponse
    {
        //shop creation
       $shop = Shop::create([
           'name'=>$request->input('name'),
           'description'=>$request->input('description'),
           'logo'=>$request->file('logo')->storeOnCloudinary('shops/logo')->getSecurePath(),
           'banner'=>$request->file('banner')->storeOnCloudinary('shops/banner')->getSecurePath(),
           'product_type'=>$request->input('product_type'),
           'owner_id'=>Auth::id(),
           'subdomain'=>$request->input('subdomain'),
       ]);
       return response()->json([
           'message'=>'Shop created succesfully',
           'shop_id'=>$shop->id
       ]);
    }

    public function chooseTemplate(ChooseShopTemplateRequest $request, ShopOwnershipService $shopOwnershipService): JsonResponse
    {
        $template_id = $request->input('template_id');
        $shop_id = $request->input('shop_id');
        $shopController = new ShopController();

        if ($shopOwnershipService->isShopOwner($shop_id))
        {
            // If we reach here, the user owns the shop
            $template = Template::find($template_id);

            if ($template != null) {
                $shop = Shop::findOrFail($shop_id);
                $shop->template_id = $template_id;
                if ($request->filled('main_color'))
                {
                    $shop->main_color=$request->input('main_color');
                }
                $shop->save();

                return response()->json([
                    'message' => 'Template added successfully',
                    'shop_id' => $shop->id,
                ]);
            } else {
                return response()->json([
                    'message' => 'The template does not exist'
                ], 403);
            }
        }
        return response()->json([],403);
    }

    public function changeShopMainColor(ShopOwnershipService $shopOwnershipService,ChangeShopMainColorRequest $request): JsonResponse
    {
        if ($shopOwnershipService->isShopOwner($request->input('shop_id')))
        {
            $shop=Shop::findOrFail($request->input('shop_id'));
            $shop->main_color=$request->input('main_color');
            $shop->save();

            return response()->json([
                'message'=>'Shop main color successfully changed',
            ]);
        }

        return response()->json([
            'message'=>'The user does not own the shop',
        ],403);
    }


    public function publishShop(ShopOwnershipService $shopOwnershipService,$id): JsonResponse
    {
        if ($shopOwnershipService->isShopOwner($id))
        {
            $shop = Shop::findOrFail($id);
            $published = $shop->publish;
            if ($published){
                $shop->publish = 'false';
            }else{
                $shop->publish = 'true';
            }
            $shop->save();
            return response()->json([
                'message'=>"Shop published"
            ]);
        }
        return response()->json([],403);
    }

    public function getUserShops(SearchShopRequest $request): JsonResponse
    {
        $shop = Shop::where('owner_id',Auth::id());

        if ($request->filled('search')){
            $search = $request->input('search');
            $shop->where('name','like',"%$search%");
        }

        if ($request->filled('published')){
          $published = $request->input('published');
          if ($published){
              $shop->where('publish','true');
          }else{
              $shop->where('publish','false');
          }
        }

        if ($shop){
            return response()->json([
                'message'=>'Shop retrieved successfully',
                'shop'=>$shop->get()->toArray(),
            ]);
        }

        return response()->json([
            'message'=>'The user does not have any shop'
        ]);
    }


}
