<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\ChooseShopTemplateRequest;
use App\Http\Requests\Shop\ShopCreationRequest;
use App\Models\Shop;
use App\Models\Template;
use Exception;
use Illuminate\Http\JsonResponse;
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

    /**
     * @throws Exception
     */
    public function isShopOwner(int $shopId): void
    {
        $shop = Shop::find($shopId);

        if ($shop !== null) {
            if ($shop->owner_id !== Auth::id()) {
                throw new Exception("The user doesn't own this shop");
            }
        } else {
            throw new Exception("The shop does not exist");
        }
    }


    public function chooseTemplate(ChooseShopTemplateRequest $request): JsonResponse
    {
        $template_id = $request->input('template_id');
        $shop_id = $request->input('shop_id');
        $shopController = new ShopController();

        try {
            $shopController->isShopOwner($shop_id);

            // If we reach here, the user owns the shop
            $template = Template::find($template_id);

            if ($template !== null) {
                $shop = Shop::findOrFail($shop_id);
                $shop->template_id = $template_id;
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
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 403);
        }
    }


    public function publishShop($id): JsonResponse
    {
        $shopController = new ShopController();
        try {
            $shopController->isShopOwner($id);

            $shop = Shop::find($id);

            $shop->publish = 'true';
            $shop->save();
            return response()->json([
                'message'=>"Shop published"
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 403);
        }
    }


}
