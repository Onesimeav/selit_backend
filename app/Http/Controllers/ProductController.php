<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Media;
use App\Models\Product;
use App\Models\Specification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function createProduct(ProductRequest $request):JsonResponse
    {
        //store the product
        $product= Product::create([
            'name'=>$request->input('name'),
            'description'=>$request->input('description'),
            'price'=>$request->input('price'),
        ]);

        $images=$request->file('images');
        //upload images on cloudinary
        foreach ( $images as $item) {
            $image = $item->storeOnCloudinary('products/images');

            //store the media
            Media::create([
                'url'=>$image->getSecurePath(),
                'type'=>'image',
                'product_id'=>$product->id,
            ]);

        }

        //upload videos on cloudinary
        $videos= $request->file('videos');

        foreach ($videos as $item) {
            $video = $item->storeOnCloudinary('product/videos');

            //store the media
            Media::create([
                'url'=>$video->getSecurePath(),
                'type'=>'video',
                'product_id'=>$product->id,
            ]);
        }

        //store specifications
        $specifications=$request->input('specifications');
        foreach ($specifications as $name=>$value) {
            Specification::create([
                'name'=>$name,
                'value'=>$value,
                'product_id'=>$product->id,
            ]);
        }

        return response()->json([
            'message'=>'Product created succesfully',
            'product_id'=>$product->id,
        ],'201');
    }

    public function searchProduct(Request $request): JsonResponse
    {
        $search = $request->input('search');

        $product = Product::where('name','lke',"%$search%");

        return response()->json([
            'result'=>$product
        ]);
    }

    public function updateProduct(Request $request):JsonResponse
    {
        $product_id = $request->input('product_id');

        $product = Product::findOrFail($product_id);
        $product->name=$request->input('name');
        $product->description=$request->input('description');
        $product->price=$request->input('price');
        $product->save();

        return response()->json([
            'message'=>'Product updated successfully'
        ]);
    }

    public function deleteProduct(Request $request):JsonResponse
    {
        $product_id=$request->input('product_id');
        Product::where('id',$product_id)->delete();

        return response()->json([
            'message'=>'Deleted successfully'
        ],'204');
    }

    public function addToShop(Request $request)
    {
        $product_id = $request->input('product_id');
        $shop_id= $request->input('shop_id');

        $product = Product::findOrFail($product_id);
        $product->shop_id=$shop_id;
        $product->save();

        return response()->json([
            'message'=>'Product added successfully'
        ]);
    }
}
