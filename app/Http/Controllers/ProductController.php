<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\AddToShopRequest;
use App\Http\Requests\Product\ProductRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Models\Product;
use App\Services\ProductOwnershipService;
use App\Services\ShopOwnershipService;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function createProduct(ProductRequest $request):JsonResponse
    {
        //store the product
        $product= Product::create([
            'name'=>$request->input('name'),
            'description'=>$request->input('description'),
            'price'=>$request->input('price'),
            'owner_id'=>Auth::id(),
        ]);

        $mediaData=[];
        if ($request->file('images')!=null)
        {
            $images=$request->file('images');

            //upload images on cloudinary
            foreach ( $images as $item) {
                $image= $item->storeOnCloudinary('products/images');
                $mediaData[]= [
                    'url'=>$image->getSecurePath(),
                    'type'=>'image',
                    'public_id'=>$image->getPublicId(),
                ];
            }
        }

        if ($request->file('videos')!=null)
        {
            //upload videos on cloudinary
            $videos= $request->file('videos');
            foreach ($videos as $item) {
                $video = $item->storeOnCloudinary('products/videos');

                $mediaData[] = [
                    'url'=>$video->getSecurePath(),
                    'type'=>'video',
                    'public_id'=>$video->getPublicId(),
                ];
            }

        }

        if ($mediaData!=[])
        {
            //store the medias
            $product->medias()->createMany($mediaData);
        }

        //store specifications
        $specifications=$request->input('specification');
        $specificationsArray = [];
        foreach ($specifications as $name=>$value) {
            $specificationsArray[] = [
                'name'=>$name,
                'value'=>$value,
            ];
        }
        $product->specifications()->createMany($specificationsArray);

        return response()->json([
            'message'=>'Product created succesfully',
            'product_id'=>$product->id,
        ],201);
    }

    public function searchProduct(Request $request): JsonResponse
    {
        $search = $request->input('search');

        if ($search!=null){
            $products = Product::where('name', 'like', "%$search%")
                ->where('owner_id', Auth::id())
                ->paginate(15);
        }else{
            $products = Product::where('owner_id', Auth::id())
                ->paginate(15);
        }

        return response()->json([
            'result'=>$products,
        ]);
    }

    public function updateProduct(ProductUpdateRequest $request,ProductOwnershipService $productOwnershipService, $id):JsonResponse
    {
        if ($productOwnershipService->isProductOwner($id))
        {
            $product = Product::findOrFail($id);
            $product->name=$request->input('name');
            $product->description=$request->input('description');
            $product->type=$request->input('product_type');
            $product->price=$request->input('price');
            $product->save();

            return response()->json([
                'message'=>'Product updated successfully'
            ]);
        }
        return response()->json([
            'message'=>'The user does not own this product'
        ],403);

    }

    public function deleteProduct(ProductOwnershipService $productOwnershipService,$id)
    {
        if ($productOwnershipService->isProductOwner($id))
        {
            $product= Product::findOrFail($id);
            $medias = $product->medias;
            foreach ($medias as $media) {
                Cloudinary::destroy($media->public_id);
            }

            $product->categories()->detach();
            $product->delete();
            return response()->noContent(204);
        }
        return response()->json([
            'message'=>'The user does not own this product'
        ],403);
    }


    public function addToShop(AddToShopRequest $request, ProductOwnershipService $productOwnershipService, ShopOwnershipService $shopOwnershipService): JsonResponse
    {
        $productsId = $request->input('product_id');
        $shopId= $request->input('shop_id');

        if ($shopOwnershipService->isShopOwner($shopId))
        {
            $products=Product::whereIn('id',$productsId)->get();
            foreach ($products as $product) {
                if (!$productOwnershipService->isProductOwner($product->id))
                {
                    return response()->json([
                        'message'=>'The user does not own this product'
                    ],403);
                }
                $product->shop_id=$shopId;
            }
            $products->each->save();

            return response()->json([
                'message'=>'Product added successfully'
            ]);
        }
        return response()->json([
            'message'=>'The user does not own this product'
        ],403);
    }
}
