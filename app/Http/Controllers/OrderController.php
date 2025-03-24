<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatusEnum;
use App\Events\SendOrderStatus;
use App\Http\Requests\Order\CancelOrderRequest;
use App\Http\Requests\Order\GetDeliveryDetailsRequest;
use App\Http\Requests\Order\GetOrderRequest;
use App\Http\Requests\Order\OrderRequest;
use App\Http\Requests\Order\OrderSearchRequest;
use App\Http\Requests\Order\setOrderStateAsDeliveryRequest;
use App\Http\Requests\Order\VerifyOrderTransactionRequest;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderProductPromotion;
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
            'address'=>$request->input('address'),
            'location_latitude'=>$request->input('location_latitude'),
            'location_longitude'=>$request->input('location_longitude'),
            'status'=>OrderStatusEnum::PENDING,
            'secret'=>mt_rand(100000, 999999),
            'shop_id'=>$shop->id,
        ]);

        $orderProducts=$request->input('products');
        foreach ($orderProducts as $orderProduct) {
            $productId=$orderProduct['product_id'];
            $product=Product::findOrFail($productId);
            $promotionValue=0;
            $pricePromotionApplied=$product->price;
            $autoAppliedPromotions = $product->autoApplyPromotions;
            if ($autoAppliedPromotions){
                foreach ($autoAppliedPromotions as $autoAppliedPromotion){
                    $promotionValue+=$autoAppliedPromotion->value;
                }
            }
            if (isset($orderProduct['promotion_id'])){
                $promotionsIds = $orderProduct['promotion_id'];
                foreach ($promotionsIds as $promotionId ){
                    $promotion = Promotion::findOrFail($promotionId);
                    $promotionValue+=$promotion->value;
                }
            }
            if ($promotionValue!=0){
                $pricePromotionApplied= ($product->price*(100-$promotionValue))/100;
            }
            $orderProductCollection=OrderProduct::create([
                'order_id'=>$order->id,
                'product_id'=>$product->id,
                'product_name'=>$product->name,
                'product_price'=>$product->price,
                'product_quantity'=>$orderProduct['quantity'],
                'price_promotion_applied'=>$pricePromotionApplied,
            ]);
            if (isset($orderProduct['promotion_id']))
            {
                $promotionsIds = $orderProduct['promotion_id'];
                foreach ($promotionsIds as $promotionId ){
                    $promotion = Promotion::findOrFail($promotionId);
                    OrderProductPromotion::create([
                        'promotion_id'=>$promotion->id,
                        'order_id'=>$orderProductCollection->id,
                        'code'=>$promotion->code,
                    ]);
                }

            }
        }

        $orderProducts=$order->products()->get();
        $orderProductsData=[];
        foreach ($orderProducts as $orderProduct) {
            $orderProductPromotions=OrderProduct::findOrFail($orderProduct->pivot->id);
            $orderProductPromotionCodes=$orderProductPromotions->promotions()->get()->toArray();
            $promotionCodes=[];
            foreach ($orderProductPromotionCodes as $orderProductPromotionCode){
                $promotionCodes[]=$orderProductPromotionCode['pivot']['code'];
            }
            $orderProduct = $orderProduct->pivot->toArray();
            $orderProduct['promotion_code']=$promotionCodes;
            $orderProductsData[]=$orderProduct;
        }
        $shopOwner = User::findOrFail($shop->owner_id);
        Mail::to($shopOwner->email)->send(new \App\Mail\Seller\SendNewOrderMail($shop->name,$shopOwner->name,$order->order_reference,$orderProductsData));
        Mail::to($order->email)->send(new \App\Mail\Customer\SendNewOrderMail($shop->name,"$order->name $order->surname",$order->order_reference,$order->secret,$orderProductsData));
        return response()->json([
            'message'=>'Order created Successfully',
            'order'=>$order->id,
        ],201);
    }

    public function getOrders(ShopOwnershipService $shopOwnershipService,OrderSearchRequest $request): JsonResponse
    {

        $userShops = Shop::where('owner_id', Auth::id())->get();
        $shopIds =[];
        foreach ($userShops as $shop){
            $shopIds[]=$shop->id;
        }

        $result = Order::whereIn('shop_id',$shopIds)->orderBy('updated_at','desc');

        if ($request->filled('shop_id')){
            if ($shopOwnershipService->isShopOwner($request->input('shop_id'))){
                $result = $result->where('shop_id',$request->input('shop_id'));
            }else{
                return response()->json([],403);
            }
        }

        if ($request->filled('search')){
            $search = $request->input('search');
            $result= $result->where('order_reference','like',"%$search%");
        }

        if ($request->filled('status')){
            $result= $result->where('status',$request->input('status'));
        }

        return response()->json([
            'result'=>$result->paginate('15'),
        ]);
    }

    public function getOrder(GetOrderRequest $request): JsonResponse
    {
        $orders = Order::whereIn('id', $request->input('ordersIds'))
                        ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'message' => 'No order retrieved'
            ], 400);
        }

        $ordersData = $orders->map(function($order) {
            $orderProducts = $order->products()->get()->map(function($orderProduct) {
                $orderProductPromotions = OrderProduct::findOrFail($orderProduct->pivot->id);
                $orderProductPromotionCodes = $orderProductPromotions->promotions()->get()->toArray();
                $promotionCodes = array_map(function($promotion) {
                    return $promotion['pivot']['code'];
                }, $orderProductPromotionCodes);

                $orderProductData = $orderProduct->pivot->toArray();
                $orderProductData['promotion_code'] = $promotionCodes;

                return $orderProductData;
            });

            $orderDetails = $order->toArray();
            $orderDetails['orderProducts'] = $orderProducts->toArray();

            return $orderDetails;
        });

        return response()->json([
            'orders' => $ordersData
        ]);
    }



    public function setOrderStateAsApproved(ShopOwnershipService $shopOwnershipService, $orderId): JsonResponse
    {
        $order=Order::findOrFail($orderId);
        if ($shopOwnershipService->isShopOwner($order->shop_id))
        {
            $order->status=OrderStatusEnum::APPROVED;
            $order->save();

            $shop = Shop::findOrFail($order->shop_id);
            $orderProducts=$order->products()->get();
            $orderProductsData=[];
            foreach ($orderProducts as $orderProduct) {
                $orderProductPromotions=OrderProduct::findOrFail($orderProduct->pivot->id);
                $orderProductPromotionCodes=$orderProductPromotions->promotions()->get()->toArray();
                $promotionCodes=[];
                foreach ($orderProductPromotionCodes as $orderProductPromotionCode){
                    $promotionCodes[]=$orderProductPromotionCode['pivot']['code'];
                }
                $orderProduct = $orderProduct->pivot->toArray();
                $orderProduct['promotion_code']=$promotionCodes;
                $orderProductsData[]=$orderProduct;
            }
            event(new SendOrderStatus($orderId,OrderStatusEnum::APPROVED->value));
            Mail::to($order->email)->send(new \App\Mail\Customer\SendApprovedOrderMail($shop->name,"$order->name $order->surname",$order->order_reference,$orderProductsData));

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
            $order->deliveryman_mail=$request->input('deliveryman_email');
            $order->deliveryman_name=$request->input('deliveryman_name');
            $order->deliveryman_surname=$request->input('deliveryman_surname');
            $order->deliveryman_number=$request->input('deliveryman_number');
            $order->save();

            $shop = Shop::findOrFail($order->shop_id);
            $deliveryLink = "https://$shop->subdomain.selit.store/order/delivery/$order->order_reference";

            $orderProducts=$order->products()->get();
            $orderProductsData=[];
            foreach ($orderProducts as $orderProduct) {
                $orderProductPromotions=OrderProduct::findOrFail($orderProduct->pivot->id);
                $orderProductPromotionCodes=$orderProductPromotions->promotions()->get()->toArray();
                $promotionCodes=[];
                foreach ($orderProductPromotionCodes as $orderProductPromotionCode){
                    $promotionCodes[]=$orderProductPromotionCode['pivot']['code'];
                }
                $orderProduct = $orderProduct->pivot->toArray();
                $orderProduct['promotion_code']=$promotionCodes;
                $orderProductsData[]=$orderProduct;
            }
            event(new SendOrderStatus($order->id,OrderStatusEnum::DELIVERY->value));
            Mail::to($order->email)->send(new \App\Mail\Customer\SendOrderDeliveryMail($shop->name,"$order->name $order->surname",$order->order_reference,$orderProductsData));
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

    public function getDeliveryDetails(GetDeliveryDetailsRequest $request): JsonResponse
    {
        $order=Order::where('order_reference',$request->input('order_reference'))
                        ->where('shop_id',$request->input('shop_id'))
                        ->where('status','like',OrderStatusEnum::DELIVERY)
                        ->first();

        if ($order!=null)
        {
            return response()->json([
                'order'=>$order->toArray(),
            ]);
        }

        return response()->json([
            'message'=>'The order does not exist on this shop',
        ],400);
    }

    public function getDeliverymanInfo(GetDeliveryDetailsRequest $request): JsonResponse
    {
        $order=Order::where('order_reference',$request->input('order_reference'))
            ->where('shop_id',$request->input('shop_id'))
            ->whereNotIn('status', [OrderStatusEnum::PENDING->value, OrderStatusEnum::APPROVED->value])
            ->first();

        if ($order!=null)
        {
            return response()->json([
                'name'=>$order->deliveryman_name,
                'surname'=>$order->deliveryman_surname,
                'email'=>$order->deliveryman_mail,
                'number'=>$order->deliveryman_number,
            ]);
        }

        return response()->json([
            'message'=>'The order does not exist on this shop',
        ],400);
    }

    public function setOrderStateAsDelivered($orderReference): JsonResponse
    {
        $order = Order::firstWhere('order_reference',$orderReference);

        if ($order!=null)
        {
            $order->status = OrderStatusEnum::DELIVERED;
            $order->save();

            $shop = Shop::findOrFail($order->shop_id);
            $shopOwner = User::findOrFail($shop->owner_id);
            $orderProducts=$order->products()->get();
            $orderProductsData=[];
            foreach ($orderProducts as $orderProduct) {
                $orderProductPromotions=OrderProduct::findOrFail($orderProduct->pivot->id);
                $orderProductPromotionCodes=$orderProductPromotions->promotions()->get()->toArray();
                $promotionCodes=[];
                foreach ($orderProductPromotionCodes as $orderProductPromotionCode){
                    $promotionCodes[]=$orderProductPromotionCode['pivot']['code'];
                }
                $orderProduct = $orderProduct->pivot->toArray();
                $orderProduct['promotion_code']=$promotionCodes;
                $orderProductsData[]=$orderProduct;
            }

            event(new SendOrderStatus($order->id,OrderStatusEnum::DELIVERED->value));

            Mail::to($order->email)->send(new \App\Mail\Customer\SendOrderDeliveredMail($shop->name,"$order->name $order->surname",$orderReference,$orderProductsData));
            Mail::to($shopOwner->email)->send(new \App\Mail\Seller\SendOrderDeliveredMail($shop->name,$shopOwner->name,$orderReference,$orderProductsData));

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
        $order = Order::firstWhere('order_reference',$request->input('order_reference'));

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
                $orderPrice+= ($orderProduct->pivot->price_promotion_applied*$orderProduct->pivot->product_quantity);
            }

            if ($result->status=="SUCCESS" && $result->amount==$orderPrice)
            {
                //update user balance
                $shop=Shop::findOrFail($order->shop_id);
                $user = User::findOrFail($shop->owner_id);
                $user->balance = ($orderPrice+$user->balance);
                $user->save();

                //update order state
                $order->status=OrderStatusEnum::FINISHED;
                $order->save();
                $orderProducts=$order->products()->get();
                $orderProductsData=[];
                foreach ($orderProducts as $orderProduct) {
                    $orderProductPromotions=OrderProduct::findOrFail($orderProduct->pivot->id);
                    $orderProductPromotionCodes=$orderProductPromotions->promotions()->get()->toArray();
                    $promotionCodes=[];
                    foreach ($orderProductPromotionCodes as $orderProductPromotionCode){
                        $promotionCodes[]=$orderProductPromotionCode['pivot']['code'];
                    }
                    $orderProduct = $orderProduct->pivot->toArray();
                    $orderProduct['promotion_code']=$promotionCodes;
                    $orderProductsData[]=$orderProduct;
                }

                //generate order invoice
                $pdf = Pdf::loadView('order.invoice', ['customerName'=>"$order->name $order->surname", 'shopName'=>$shop->name, 'orderProducts'=>$orderProductsData, 'orderPrice'=>$orderPrice, 'orderReference'=>$request->input('order_reference')]);
                $pdfContent = base64_encode($pdf->output());
                event(new SendOrderStatus($order->id,OrderStatusEnum::FINISHED->value));

                Mail::to($order->email)->send(new \App\Mail\Customer\SendFinishedOrderMail($shop->name,"$order->name $order->surname",$order->order_reference,$orderProductsData,$pdfContent));
                Mail::to($user->email)->send(new \App\Mail\Seller\SendFinishedOrderMail($shop->name,$user->name,$order->order_reference,$orderProductsData,$pdfContent));

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

    public function getOrderInvoice(ShopOwnershipService $shopOwnershipService, $orderReference): JsonResponse
    {
        $order = Order::where('order_reference',$orderReference)->first();

        if ($order!=null)
        {
            if ($shopOwnershipService->isShopOwner($order->shop_id))
            {
                $shop=Shop::findOrFail($order->shop_id);
                $user = User::findOrFail($shop->owner_id);
                $orderPrice =0;
                $orderProducts=$order->products()->get();
                foreach ($orderProducts as $orderProduct) {
                    $orderPrice+= ($orderProduct->pivot->price_promotion_applied*$orderProduct->pivot->product_quantity);
                }
                $orderProducts=$order->products()->get();
                $orderProductsData=[];
                foreach ($orderProducts as $orderProduct) {
                    $orderProductPromotions=OrderProduct::findOrFail($orderProduct->pivot->id);
                    $orderProductPromotionCodes=$orderProductPromotions->promotions()->get()->toArray();
                    $promotionCodes=[];
                    foreach ($orderProductPromotionCodes as $orderProductPromotionCode){
                        $promotionCodes[]=$orderProductPromotionCode['pivot']['code'];
                    }
                    $orderProduct = $orderProduct->pivot->toArray();
                    $orderProduct['promotion_code']=$promotionCodes;
                    $orderProductsData[]=$orderProduct;
                }
                $pdf = Pdf::loadView('order.invoice', ['customerName'=>"$order->name $order->surname", 'shopName'=>$shop->name, 'orderProducts'=>$orderProductsData, 'orderPrice'=>$orderPrice, 'orderReference'=>$orderReference]);
                $pdfContent = base64_encode($pdf->output());
                Mail::to($user->email)->send(new \App\Mail\Seller\SendFinishedOrderMail($shop->name,$user->name,$order->order_reference,$orderProductsData,$pdfContent));

                return response()->json([
                    'message'=>'The invoice have been successfully sent'
                ]);
            }

            return response()->json([
                'message'=>'The user does not own the shop',
            ],403);
        }

        return response()->json([
            'message'=>'The order does not exist'
        ],404);
    }

    public function cancelOrder(ShopOwnershipService $shopOwnershipService,CancelOrderRequest $request): JsonResponse
    {
        $order = Order::firstWhere('order_reference',$request->input('order_reference'));

        if ($order!=null)
        {
            if (Shop::where('id',$order->shop_id)->where('owner_id',Auth::guard('sanctum')->id())->exists())
            {
                $order->status=OrderStatusEnum::CANCELED;
                $order->save();
                $shop=Shop::findOrFail($order->shop_id);

                event(new SendOrderStatus($order->id,OrderStatusEnum::CANCELED->value));
                Mail::to($order->email)->send(new \App\Mail\Customer\SendCancelledOrderMail($shop->name,"$order->name $order->surname",$request->input('order_reference')));
                return response()->json([
                    'message'=>'Order cancelled successfully',
                ]);
            }elseif($order->status === OrderStatusEnum::PENDING->value || $order->status===OrderStatusEnum::APPROVED->value)
            {
                if ($request->filled('secret') && $order->secret===$request->integer('secret'))
                {
                    $order->status=OrderStatusEnum::CANCELED;
                    $order->save();
                    $shop=Shop::findOrFail($order->shop_id);
                    $user = User::findOrFail($shop->owner_id);

                    event(new SendOrderStatus($order->id,OrderStatusEnum::CANCELED->value));
                    Mail::to($user->email)->send(new \App\Mail\Seller\SendCancelledOrderMail($user->name,$request->input('order_reference')));
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
