<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubdomainController extends Controller
{
    public function index($domain)
    {
        $shop = Shop::where('subdomain', $domain)->first();

        if (!$shop){
            return view('templates.404');
        }

        $products = $shop->products();
        $template = Template::findOrFail($shop->template_id);
        $template_name = $template->name;
        if (!$shop->publish){
            if ($shop->owner_id==Auth::id()){
                return view("templates.{$template_name}.preview", ['shop' => $shop, 'products' => $products]);
            }
            return view('templates.404');
        }
        return view("templates.{$template_name}.index", ['shop' => $shop, 'products' => $products]);

    }

}
