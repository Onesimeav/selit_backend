<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\AddMediaToProductRequest;
use App\Http\Requests\Product\addSpecificationsToProductRequest;
use App\Http\Requests\Product\AddToShopRequest;
use App\Http\Requests\Product\DeleteMediaFromProductRequest;
use App\Http\Requests\Product\deleteProductSpecifiactionsRequest;
use App\Http\Requests\Product\ProductRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Http\Requests\Product\UpdateProductSpecificationRequest;
use App\Models\Media;
use App\Models\Product;
use App\Models\Specification;
use App\Services\ProductOwnershipService;
use App\Services\ShopOwnershipService;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use function PHPUnit\Framework\isEmpty;

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

    public function addMediaToProduct(ProductOwnershipService $productOwnershipService, AddMediaToProductRequest $request): JsonResponse
    {
        if ($productOwnershipService->isProductOwner($request->input('product_id')))
        {
            $mediaData=[];
            if (!isEmpty($request->input('videos')))
            {
                $videos=$request->file('videos');
                foreach ( $videos as $video) {
                    $savedVideo = $video->storeOnCloudinary('products/videos');

                    $mediaData[] = [
                        'url'=>$savedVideo->getSecurePath(),
                        'type'=>'video',
                        'public_id'=>$savedVideo->getPublicId(),
                    ];
                }
            }

            if (!isEmpty($request->input('images')))
            {
                $images=$request->file('images');

                foreach ($images as $image) {
                    $savedImage = $image->storeOnCloudinary('products/images');

                    $mediaData[]=[
                        'url'=>$savedImage->getSecurePath(),
                        'type'=>'image',
                        'public_id'=>$savedImage->getPublicId(),
                    ];
                }
            }

            if ($mediaData!=[])
            {
                $product = Product::findOrFail($request->input('product_id'));

                $product->medias()->createMany($mediaData);
            }

            return response()->json([
                'message'=>'Media added successfully to product'
            ]);

        }

        return response()->json([
            'message'=>'The user does not own this product'
        ],403);

    }

    public function deleteMediaFromProduct(ProductOwnershipService $productOwnershipService, DeleteMediaFromProductRequest $request): Response|JsonResponse
    {
        if ($productOwnershipService->isProductOwner($request->input('product_id')))
        {
            $product=Product::findOrFail($request->input('product_id'));
            $medias=$product->medias();
            $mediasIds=[];

            foreach ($medias as $media) {
                $mediasIds[]=$media->id;
            }
            if (!isEmpty($request->input('medias')))
            {
                $mediasToDelete=$request->input('medias');

                foreach ($mediasToDelete as $mediaToDelete) {
                    if (in_array($mediaToDelete,$mediasIds))
                    {
                        $media=Media::findOrFail($mediaToDelete);
                        Cloudinary::destroy($media->public_id);
                        $media->delete();
                    }
                }
            }

            return response()->noContent(204);
        }

        return response()->json([
            'message'=>'The user does not own this product'
        ],403);
    }

    public function updateProductSpecifications(ProductOwnershipService $productOwnershipService,UpdateProductSpecificationRequest $request): JsonResponse
    {
        if ($productOwnershipService->isProductOwner($request->input('product_id')))
        {
            $product = Product::findOrFail($request->input('product_id'));
            $specifications = $product->specifications();
            $specificationsIds=[];
            foreach ($specifications as $specification) {
                $specificationsIds[]=$specification->id;
            }
            if (!isEmpty($request->input('specifications')))
            {
                $specificationsToUpdate=$request->input('specifications');
                foreach ($specificationsToUpdate as $specificationToUpdate) {
                    if (in_array($specificationToUpdate['id'],$specificationsIds))
                    {
                        $specification= Specification::findOrFail($specificationToUpdate['id']);
                        $specification->name=$specificationToUpdate['name'];
                        $specificationToUpdate->value=$specificationsToUpdate['value'];
                    }
                }
            }

            return response()->json([
                'message'=>'Specification updated successfully',
            ]);
        }

        return response()->json([
            'message'=>'The user does not own this shop',
        ],403);
    }

    public function addSpecificationsToProduct(ProductOwnershipService $productOwnershipService,addSpecificationsToProductRequest $request): JsonResponse
    {
        if ($productOwnershipService->isProductOwner($request->input('product_id')))
        {
            $specifications=$request->input('specifications');
            $product=Product::findOrFail($request->input('product_id'));

            $specificationsArray = [];
            foreach ($specifications as $name=>$value) {
                $specificationsArray[] = [
                    'name'=>$name,
                    'value'=>$value,
                ];
            }
            $product->specifications()->createMany($specificationsArray);

            return response()->json([
                'message'=>'Specifications added successfully',
            ]);
        }

        return response()->json([
            'message'=>'The user does not own this shop',
        ],403);
    }

    public function deleteProductSpecifications(ProductOwnershipService $productOwnershipService, deleteProductSpecifiactionsRequest $request ): Response|JsonResponse
    {
        if ($productOwnershipService->isProductOwner($request->input('product_id')))
        {
            $product = Product::findOrFail($request->input('product_id'));
            $specifications = $product->specifications();
            $specificationsIds=[];
            foreach ($specifications as $specification) {
                $specificationsIds[]=$specification->id;
            }
            if (!isEmpty($request->input('specifications')))
            {
                $specificationsToDelete=$request->input('specifications');
                foreach ($specificationsToDelete as $specificationToDelete) {
                    if (in_array($specificationToDelete,$specificationsIds))
                    {
                        $specification= Specification::findOrFail($specificationToDelete);
                        $specification->delete();
                    }
                }
            }

            return response()->noContent(204);
        }

        return response()->json([
            'message'=>'The user does not own this shop',
        ],403);
    }

    public function deleteProduct(ProductOwnershipService $productOwnershipService,$id): Response|JsonResponse
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
