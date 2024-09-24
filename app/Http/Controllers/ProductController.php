<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\AddToShopRequest;
use App\Http\Requests\Product\ProductRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Models\Product;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Exception;
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

    public function updateProduct(ProductUpdateRequest $request, $id):JsonResponse
    {
        try {
            $this->isProductOwner($id);

            $product = Product::findOrFail($id);
            $product->name=$request->input('name');
            $product->description=$request->input('description');
            $product->price=$request->input('price');
            $product->save();

            return response()->json([
                'message'=>'Product updated successfully'
            ]);
        }catch (Exception $e)
        {
            return response()->json([
                'message' => $e->getMessage()
            ], 403);
        }

    }

    public function deleteProduct($id):JsonResponse
    {
        try {
            $this->isProductOwner($id);
            $product= Product::findOrFail($id);
            $medias = $product->medias;
            foreach ($medias as $media) {
                Cloudinary::destroy($media->public_id);
            }

            $product->categories()->detach();
            $product->delete();
            return response()->json([],204);
        }catch (Exception $e)
        {
            return response()->json([
                'message' => $e->getMessage()
            ], 403);
        }

    }

    /**
     * @throws Exception
     */
    public function isProductOwner($productId): void
    {
        $product = Product::find($productId);

        if ($product !== null) {
            if ($product->owner_id !== Auth::id()) {
                throw new Exception("The user does not own this product");
            }
        } else {
            throw new Exception("The product does not exist");
        }
    }


    public function addToShop(AddToShopRequest $request): JsonResponse
    {
        $products_id = $request->input('product_id');
        $shop_id= $request->input('shop_id');

        $shopController = new ShopController();
        try {
            $shopController->isShopOwner($shop_id);

            foreach ($products_id as $product_id) {
                $this->isProductOwner($product_id);
                $product =Product::find($product_id);
                $product->shop_id=$shop_id;
                $product->save();
            }

            return response()->json([
                'message'=>'Product added successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 403);
        }

    }
}
