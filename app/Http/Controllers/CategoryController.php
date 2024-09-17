<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToCategoryRequest;
use App\Http\Requests\CategoryRequest;
use App\Http\Requests\CategorySearchRequest;
use App\Models\Category;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function createCategory(CategoryRequest $request):JsonResponse
    {
        $shop= Shop::find($request->input('shop_id'));
        if ($shop!=null)
        {
            $category=Category::create([
                'name'=>$request->input('name'),
                'shop_id'=>$shop->id,
            ]);
            return response()->json([
                'message'=>'Category created successfully',
                'category_id'=>$category->id,
            ],201);
        }

        return response()->json([
            'message'=>'Incorrect shop id'
        ]);
    }

    public function searchCategory(CategorySearchRequest $request):JsonResponse
    {
        $shop_id=$request->input('shop_id');
        $search= $request->input('search');

        if ($search!=null)
        {
            $category=Category::where('name','like',"%$search%")
                ->where('shop_id',$shop_id)
                ->get();

        }else{
            $category=Category::where('shop_id',$shop_id);
        }

        return response()->json([
            'result'=>$category
        ]);
    }

    public function updateCategory(CategoryRequest $request,$id): JsonResponse
    {
        $category = Category::find($id);
        if ($category!=null)
        {
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
        ShopController::class->isShopOwner($category->shop_id);

        if ($category!=null)
        {
            $products=$category->products();

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
            $category->products()->attach($request->input('products'));

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
            $category->products()->detach($request->input('products'));

            return response()->json([
                'message'=>'Products successfully removed form the category',
            ]);
        }
        return response()->json([
            'message'=>'The category does not exist',
        ]);
    }
}
