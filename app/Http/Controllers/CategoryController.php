<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\AddToCategoryRequest;
use App\Http\Requests\Category\CategoryRequest;
use App\Http\Requests\Category\CategorySearchRequest;
use App\Http\Requests\Category\CategoryUpdateRequest;
use App\Models\Category;
use App\Models\Shop;
use App\Services\ProductOwnershipService;
use App\Services\ShopOwnershipService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function createCategory(CategoryRequest $request, ShopOwnershipService $shopOwnershipService):JsonResponse
    {
        $shop_id = $request->input('shop_id');

        if ($shopOwnershipService->isShopOwner($shop_id))
        {
            $shop= Shop::findOrFail($shop_id);

            $category=Category::create([
                'name'=>$request->input('name'),
                'shop_id'=>$shop->id,
            ]);
            return response()->json([
                'message'=>'Category created successfully',
                'category_id'=>$category->id,
            ],201);
        }
        return response()->json([],403);
    }

    public function searchCategory(CategorySearchRequest $request,ShopOwnershipService $shopOwnershipService):JsonResponse
    {
        $shop_id=$request->input('shop_id');
        $search= $request->input('search');

        if ($shopOwnershipService->isShopOwner($shop_id))
        {
            if ($search!=null)
            {
                $category=Category::where('name','like',"%$search%")
                    ->where('shop_id',$shop_id)
                    ->paginate(15);

            }else{
                $category=Category::where('shop_id',$shop_id)
                    ->paginate(15);
            }

            return response()->json([
                'result'=>$category
            ]);
        }

        return response()->json([],403);
    }

    public function updateCategory(CategoryUpdateRequest $request,ShopOwnershipService $shopOwnershipService,$id): JsonResponse
    {
        $category = Category::find($id);
        if ($category!=null)
        {
            $shop_id=$category->shop_id;
            if ($shopOwnershipService->isShopOwner($shop_id))
            {
                $category->name=$request->input('name');
                $category->save();
                return response()->json([
                    'message'=>'Category updated successfully',
                    'category_id'=>$category->id,
                ]);
            }
            return response()->json([],403);
        }

        return response()->json([
            'message'=>'The category does not exist'
        ],404);
    }

    public function deleteCategory(ShopOwnershipService $shopOwnershipService,$id): JsonResponse
    {
        $category= Category::find($id);
        if ($category!=null)
        {
            $shop_id=$category->shop_id;
            if ($shopOwnershipService->isShopOwner($shop_id))
            {
                $category->products()->detach();
                $category->delete();
                return response()->json([],204);
            }
            return response()->json([],404);
        }
        return response()->json([
            'message'=>'The category does not exist'
        ],404);
    }

    public function getCategoryProducts(ShopOwnershipService $shopOwnershipService,$id): JsonResponse
    {
        $category=Category::find($id);

        if ($category!=null)
        {
            if ($shopOwnershipService->isShopOwner($category->shop_id))
            {
                $products=$category->products()->paginate(15);

                return response()->json([
                    'products'=>$products,
                ]);
            }
            return response()->json([],403);
        }

        return response()->json([
            'message'=>'The category does not exist',
        ],404);
    }

    public function addProductsToCategory(AddToCategoryRequest $request, ProductOwnershipService $productOwnershipService,ShopOwnershipService $shopOwnershipService): JsonResponse
    {
        $category = Category::find($request->input('category_id'));
        if ($category !== null) {
                if ($shopOwnershipService->isShopOwner($category->shop_id))
                {
                    $productsId = $request->input('products');
                    foreach ($productsId as $productId) {
                        if (!$productOwnershipService->isProductOwner($productId))
                        {
                            return response()->json([],403);
                        }
                    }
                    $category->products()->attach($productsId);

                    return response()->json([
                        'message' => 'Products added successfully'
                    ]);
                }
                return response()->json([],403);
        }
        return response()->json([
            'message' => 'The category does not exist',
        ],404);
    }


    public function removeProductsFromCategory(AddToCategoryRequest $request,ProductOwnershipService $productOwnershipService, ShopOwnershipService $shopOwnershipService): JsonResponse
    {
        $category = Category::find($request->input('category_id'));
        if ($category!=null)
        {
            if ($shopOwnershipService->isShopOwner($category->shop_id))
            {
                $productsId = $request->input('products');
                foreach ($productsId as $productId) {
                    if (!$productOwnershipService->isProductOwner($productId))
                    {
                        return response()->json([],403);
                    }
                }
                $category->products()->detach($productsId);

                return response()->json([
                    'message'=>'Products successfully removed form the category',
                ]);
            }
            return  response()->json([],403);

        }
        return response()->json([
            'message'=>'The category does not exist',
        ],404);
    }
}
