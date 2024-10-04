<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatusEnum;
use App\Http\Requests\Order\OrderRequest;
use App\Http\Requests\Order\setOrderStateAsApprovedRequest;
use App\Http\Requests\Order\setOrderStateAsDeliveryRequest;
use App\Http\Requests\Order\VerifyOrderTransactionRequest;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Shop;
use App\Models\User;
use App\Services\ShopOwnershipService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Kkiapay\Kkiapay;

class OrderController extends Controller
{
    public function createOrder(OrderRequest $request): JsonResponse
    {
        $shop=Shop::findOrFail($request->input('shop_id'));

        $order = Order::create([
            'order_reference'=>Str::ulid(),
            'name'=>$request->input('name'),
            'surname'=>$request->input('surname'),
            'email'=>$request->input('email'),
            'number'=>$request->input('number'),
            'location'=>'location',
            'status'=>OrderStatusEnum::PENDING,
            'shop_id'=>$shop->id,
        ]);

        $orderProducts=$request->input('products');
        $orderProductsData=[];
        foreach ($orderProducts as $orderProduct) {
            $product_id=$orderProduct['product_id'];
            $product=Product::findOrFail($product_id);
            if (isset($orderProduct['promotion_id']))
            {
                $promotion_id=$orderProduct['promotion_id'];
                $promotion=Promotion::findOrFail($promotion_id);

                $orderProductsData[]=[
                    'order_id'=>$order->id,
                    'product_id'=>$product->id,
                    'product_name'=>$product->name,
                    'product_price'=>$product->price,
                    'product_quantity'=>$orderProduct['quantity'],
                    'promotion_id'=>$promotion_id,
                    'promotion_code'=>$promotion->code,
                    'price_promotion_applied'=>($product->price*$promotion->value)/100,
                ];
            }else{
                $orderProductsData[]=[
                    'order_id'=>$order->id,
                    'product_id'=>$product->id,
                    'product_name'=>$product->name,
                    'product_price'=>$product->price,
                    'product_quantity'=>$orderProduct['quantity'],
                    'price_promotion_applied'=>$product->price,
                ];
            }
        }

        OrderProduct::createMany($orderProductsData);

        $shopOwner = User::findOrFail($shop->owner_id);
        Mail::to($shopOwner->email)->send(new \App\Mail\Seller\SendNewOrderMail($shop->name,$shopOwner->name,$order->order_reference,$orderProductsData));
        Mail::to($order->email)->send(new \App\Mail\Customer\SendNewOrderMail($shop->name,"$order->name $order->surname",$order->order_reference,$orderProductsData));
        return response()->json([
            'message'=>'Order created Successfully'
        ],201);
    }

    public function setOrderStateAsApproved(ShopOwnershipService $shopOwnershipService, setOrderStateAsApprovedRequest $request): JsonResponse
    {
        $order=Order::findOrFail($request->input('order_id'));
        if ($shopOwnershipService->isShopOwner($order->shop_id))
        {
            $order->status=OrderStatusEnum::APPROVED;
            $order->save();

            $shopName = Shop::where('id',$order->shop_id)->name->get();
            $orderProducts = $order->products()->get()->toArray();
            Mail::to($order->email)->send(new \App\Mail\Customer\SendApprovedOrderMail($shopName,"$order->name $order->surname",$order->order_reference,$orderProducts));

            return response()->json([
                'message'=>'Order successfully approved'
            ]);
        }

        return response()->json([
            'message'=>'The user does not own this shop'
        ],403);
    }

    public function setOrderStateAsDelivery(ShopOwnershipService $shopOwnershipService, setOrderStateAsDeliveryRequest $request): JsonResponse
    {
        $order = Order::findOrFail($request->input('order_id'));
        if ($shopOwnershipService->isShopOwner($order->shop_id))
        {
            $order->status=OrderStatusEnum::DELIVERY;
            $order->save();

            $deliveryLink = "https://www.selit.store/order/delivery/$order->order_reference";
            $shopName = Shop::where('id',$order->shop_id)->name->get();
            $orderProducts = $order->products()->get()->toArray();
            Mail::to($order->email)->send(new \App\Mail\Customer\SendOrderDeliveryMail($shopName,"$order->name $order->surname",$order->order_reference,$orderProducts));
            Mail::to($request->input('deliveryman_email'))->send(new \App\Mail\DeliveryMan\SendOrderDeliveryMail($deliveryLink));
            return response()->json([
                'message'=>'Order state successfully set as delivery',
                'delivery_link'=>$deliveryLink,
            ]);
        }

        return response()->json([
            'message'=>'The user does not own this shop',
        ],403);
    }

    public function getDeliveryDetails($orderReference)
    {
        $order=Order::where('order_reference',$orderReference)->get();

        if ($order!=null)
        {
            $orderData = $order->toArray();
            return view('delivery.delivery-details', compact($orderData));
        }

        return view('template.404');
    }

    public function setOrderStateAsDelivered($orderReference): JsonResponse
    {
        $order = Order::wehre('order_reference',$orderReference)->get();

        if ($order!=null)
        {
            $order->status = OrderStatusEnum::DELIVERED;
            $order->save();

            $shop = Shop::findOrFail($order->shop_id);
            $shopOwner = User::findOrFail($shop->owner_id);
            $orderProducts=$order->products()->get()->toArray();
            Mail::to($order->email)->send(new \App\Mail\Customer\SendOrderDeliveredMail($shop->name,"$order->name $order->surname",$orderReference,$orderProducts));
            Mail::to($shopOwner->email)->send(new \App\Mail\Seller\SendOrderDeliveredMail($shop->name,$shopOwner->name,$orderReference,$orderProducts));

            return response()->json([
                'message'=>'Order state set as delivered successfully'
            ]);
        }

        return response()->json([
            'message'=>'The order does not exist'
        ],404);
    }


    public function setOrderStateAsFinished(VerifyOrderTransactionRequest $request): JsonResponse
    {
        $order = Order::where('order_reference',$request->input('order_reference'))->get();

        if ($order!=null)
        {
            $public_key = config('custom.kkiapay_public_key');
            $private_key=config('custom.kkiapay_private_key');
            $secret=config('custom.kkiapay_secret_key');
            $kkiapay = new Kkiapay($public_key, $private_key, $secret, sandbox:true);

            $result = $kkiapay->verifyTransaction($request->input('transaction_id'));

            $orderPrice =0;
            $orderProducts=$order->products()->get();
            foreach ($orderProducts as $orderProduct) {
                $orderPrice+= ($orderProduct->price_promotion_applied*$orderProduct->product_quantity);
            }
            if ($result->status=="SUCCESS"&& $result->amount==$orderPrice)
            {
                //update user balance
                $shop=Shop::findOrFail($order->shop_id);
                $user = User::findOrFail($shop->owner_id);
                $user->balance = $orderPrice;
                $user->save();

                //update order state
                $order->status=OrderStatusEnum::FINISHED;

                //generate order invoice
                $pdf = Pdf::loadView('pdf', ['customerName'=>"$order->name $order->surname", 'shopName'=>$shop->name, 'orderProducts'=>$orderProducts->toArray(), 'orderPrice'=>$orderPrice]);
                //upload on cloudinary
                $invoice = $pdf->storeOnCloudinary('invoices');
                $order->invoice = $invoice->getSecurePath();
                $order->save();

                Mail::to($order->email)->send(new \App\Mail\Customer\SendFinishedOrderMail($shop->name,"$order->name $order->surname",$order->order_reference,$orderProducts->toArray(),$order->invoice));
                Mail::to($user->email)->send(new \App\Mail\Seller\SendFinishedOrderMail($shop->name,$user->name,$order->order_reference,$orderProducts->toArray()));

                return response()->json([
                    'message'=>'The order was successfully verified'
                ]);
            }

            return response()->json([
                'message'=>'Order transaction failed',
            ]);
        }

        return response()->json([
            'message'=>'The order does not exist',
        ],404);
    }

    public function cancelOrder(ShopOwnershipService $shopOwnershipService,$orderReference): JsonResponse
    {
        $order = Order::where('order_reference',$orderReference)->get();

        if ($order!=null)
        {
            if ($shopOwnershipService->isShopOwner($order->shop_id))
            {
                $order->status=OrderStatusEnum::CANCELED;
                $order->save();

                return response()->json([
                    'message'=>'Order cancelled successfully',
                ]);
            }else{
                if ($order->status ==OrderStatusEnum::PENDING || $order->status==OrderStatusEnum::APPROVED)
                {
                    $order->status=OrderStatusEnum::CANCELED;
                    $order->save();

                    return response()->json([
                        'message'=>'Order cancelled successfully',
                    ]);
                }
            }

            return response()->json([
                'message'=>'This order can not be cancelled'
            ]);
        }

        return response()->json([
            'message'=>'The order does not exist',
        ],404);
    }

}
