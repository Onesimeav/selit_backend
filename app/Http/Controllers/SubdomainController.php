<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubdomainController extends Controller
{
    public function index($domain): \Illuminate\Http\JsonResponse
    {
        $shop = Shop::where('subdomain', $domain)->first();

        if (!$shop){
            return response()->json([
                'message'=>'The shop does not exist'
            ],404);
        }

        $products = $shop->products()->get()->toArray();
        $template = Template::findOrFail($shop->template_id);
        $template_name = $template->name;
        if (!$shop->publish){
            if ($shop->owner_id==Auth::id()){
                return response()->json([
                    'message'=>'Shops retrived successfully',
                    'shop'=>$shop->toArray(),
                    'products'=>$products,
                    'published'=>false
                ]);
            }
            return response()->json([
                'message'=>'The shop is in preview mode',
            ],404);
        }
        return response()->json([
            'message'=>'Shops retrived successfully',
            'shop'=>$shop->toArray(),
            'products'=>$products,
            'published'=>true,
        ]);

    }

}
