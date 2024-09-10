<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Template;
use Illuminate\Http\Request;

class SubdomainController extends Controller
{
    public function index($domain)
    {
        $shop = Shop::findOrFail('subdomain',$domain);
        if (!$shop->publish)
        {
            return view('404');
        }
        $products = $shop->products();
        $template = Template::findOrFail($shop->template_id);
        $template_name=$template->name;
        return view("templates.{$template_name}.index",['shop'=>$shop,'products'=>$products]);
    }

}
