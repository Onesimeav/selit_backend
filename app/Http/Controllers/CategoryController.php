<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\AddToCategoryRequest;
use App\Http\Requests\Category\CategoryRequest;
use App\Http\Requests\Category\CategorySearchRequest;
use App\Http\Requests\Category\CategoryUpdateRequest;
use App\Models\Category;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function createCategory(CategoryRequest $request):JsonResponse
    {
        $shop_id = $request->input('shop_id');
        $shopController = new ShopController();
        $shopController->isShopOwner($shop_id);
        $shop= Shop::find($shop_id);

        $category=Category::create([
            'name'=>$request->input('name'),
            'shop_id'=>$shop->id,
        ]);
        return response()->json([
            'message'=>'Category created successfully',
            'category_id'=>$category->id,
        ],201);
    }

    public function searchCategory(CategorySearchRequest $request):JsonResponse
    {
        $shop_id=$request->input('shop_id');
        $search= $request->input('search');

        $shopController = new ShopController();
        $shopController->isShopOwner($shop_id);
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

    public function updateCategory(CategoryUpdateRequest $request,$id): JsonResponse
    {
        $category = Category::find($id);
        if ($category!=null)
        {
            $shop_id=$category->shop_id;
            $shopController = new ShopController();
            $shopController->isShopOwner($shop_id);
            $category->name=$request->input('name');
            $category->save();
            return response()->json([
                'message'=>'Category updated successfully',
                'category_id'=>$category->id,
            ]);
        }

        return response()->json([
            'message'=>'The category does not exist'
        ]);
    }

    public function deleteCategory($id): JsonResponse
    {
        $category= Category::find($id);
        if ($category!=null)
        {
            $shop_id=$category->shop_id;
            $shopController = new ShopController();
            $shopController->isShopOwner($shop_id);
            $category->products()->detach();
            $category->delete();
            return response()->json([],204);
        }
        return response()->json([
            'message'=>'The category does not exist'
        ]);
    }

    public function getCategoryProducts($id): JsonResponse
    {
        $category=Category::find($id);

        if ($category!=null)
        {
            $shopController = new ShopController();
            $shopController->isShopOwner($category->shop_id);
            $products=$category->products()->paginate(15);

            return response()->json([
                'products'=>$products,
            ]);
        }

        return response()->json([
            'message'=>'The category does not exist',
        ],404);
    }

    public function addProductsToCategory(AddToCategoryRequest $request): JsonResponse
    {
        $category = Category::find($request->input('category_id'));
        if ($category!=null)
        {
            $shopController = new ShopController();
            $productController = new ProductController();
            $shopController->isShopOwner($category->shop_id);

            $productsId = $request->input('products');
            foreach ($productsId as $productId) {

                $productController->isProductOwner($productId);
            }
            $category->products()->attach($productsId);

            return response()->json([
                'message'=>'Products added successfully'
            ]);
        }
        return  response()->json([
            'message'=>'The does not exist',
        ]);
    }

    public function removeProductsFromCategory(AddToCategoryRequest $request): JsonResponse
    {
        $category = Category::find($request->input('category_id'));
        if ($category!=null)
        {
            $shopController = new ShopController();
            $productController = new ProductController();
            $shopController->isShopOwner($category->shop_id);

            $productsId = $request->input('products');
            foreach ($productsId as $productId) {

                $productController->isProductOwner($productId);
            }
            $category->products()->detach($productsId);

            return response()->json([
                'message'=>'Products successfully removed form the category',
            ]);
        }
        return response()->json([
            'message'=>'The category does not exist',
        ]);
    }
}
