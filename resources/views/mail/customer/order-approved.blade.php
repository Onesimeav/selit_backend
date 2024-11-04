<!DOCTYPE html>
<html>
<head>
    <title>Order Approved Notification</title>
</head>
<body>
<h1>Hello {{ $customerName }}!</h1>
<p>Congratulations! Your order on {{$shopName}} have been approved by the seller, it will be delivered soon</p>

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
                    <strong>Promotion code:</strong>@foreach($orderProduct['promotion_code'] as $code) {{ $code, }} @endforeach
                    <br>
                    <strong>Price promotion applied:</strong> {{ $orderProduct['price_promotion_applied'] }}
                @endif

            </li>
        @endforeach
    @else
        <p>Something fishy is going on, please contact the seller.</p>
    @endif
</ul>

<p>Thank you for using our platform!</p>
</body>
</html>
