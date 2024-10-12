<!DOCTYPE html>
<html>
<head>
    <title>New Order Notification</title>
</head>
<body>
<h1>Hello {{ $customerName }}!</h1>
<p>Congratulations! You have placed order in the shop {{$shopName}}.<br>
Your order will be approved soo, by the seller</p>

<h2>Order Details:</h2>
<ul>
    <li>
        <strong>Order reference:</strong> {{ $orderReference }}
    </li>
    @if(count($orderProducts) > 0)
        @foreach($orderProducts as $orderProduct)
            <li>
                <strong>Product name:</strong> {{ $orderProduct['product_name'] }}
                <br>
                <strong>Price:</strong> {{ $orderProduct['product_price'] }}
                <br>
                <strong>Quantity:</strong> {{ $orderProduct['product_quantity'] }}
                <br>
                @if(isset($orderProduct['promotion_code']))
                    <strong>Promotion code:</strong> {{ $orderProduct['promotion_code'] }}
                    <br>
                    <strong>Price promotion applied:</strong> {{ $orderProduct['price_promotion_applied'] }}
                @endif

            </li>
        @endforeach
    @else
        <p>Something fishy is going on, please contact the seller.</p>
    @endif
</ul>

<p>Here is the code to cancel your order : {{$secret}}</p>

<p>If you have any questions or need assistance, feel free to reach out.</p>

<p>Thank you for using our platform!</p>
</body>
</html>
