<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Media;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Specification;
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
        if ($request->input('images')!=null)
        {
            $images=$request->file('images');

            //upload images on cloudinary
            foreach ( $images as $item) {
                $image= $item->getRealPath()->storeOnCloudinary('products/images');
                $mediaData[]= [
                    'url'=>$image->getSecurePath(),
                    'type'=>'image',
                ];
            }
        }

        if ($request->input('videos')!=null)
        {
            //upload videos on cloudinary
            $videos= $request->file('videos');
            foreach ($videos as $item) {
                $video = $item->getRealPath()->storeOnCloudinary('products/videos');
                $mediaData[] = [
                    'url'=>$video->getSecuredPath(),
                    'type'=>'video',
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
            $product = Product::where('name', 'like', "%$search%")
                ->where('owner_id', Auth::id())
                ->get();
        }else{
            $product = Product::where('owner_id', Auth::id())
                ->get();
        }

        return response()->json([
            'result'=>$product,
        ]);
    }

    public function updateProduct(Request $request):JsonResponse
    {
        $product_id = $request->input('product_id');

        $product = Product::findOrFail($product_id);
        if ($product->owner_id==Auth::id())
        {
            $product->name=$request->input('name');
            $product->description=$request->input('description');
            $product->price=$request->input('price');
            $product->save();

            return response()->json([
                'message'=>'Product updated successfully'
            ]);
        }

        return response()->json([
            'message'=>"The user doesn't own this product"
        ],401);
    }

    public function deleteProduct(Request $request):JsonResponse
    {
        $product_id=$request->input('product_id');

        $product= Product::where('id',$product_id);
        if ($product->owner_id==Auth::id())
        {
            $product->delete();
            return response()->json([],204);
        }

        return response()->json([
            'message'=>"The user doesn't this product"
        ],401);
    }

    public function addToShop(Request $request)
    {
        $product_id = $request->input('product_id');
        $shop_id= $request->input('shop_id');

        $product = Product::findOrFail($product_id);
        $shop = Shop::findOrFail($shop_id);
        if ($shop->owner_id == Auth::id() && $product->owner_id == Auth::id())
        {
            $product->shop_id=$shop_id;
            $product->save();

            return response()->json([
                'message'=>'Product added successfully'
            ]);
        }
        return response()->json([
            'message'=>"The user doesn't own this shop"
        ],401);
    }
}
