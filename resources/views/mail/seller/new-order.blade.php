<!DOCTYPE html>
<html>
<head>
    <title>New Order Notification</title>
</head>
<body>
<h1>Hello {{ $shopOwnerName }}!</h1>
<p>Congratulations! You have a new order in your shop {{$shopName}}.</p>

<h2>Order Details:</h2>

<ul>
    <li>
        <strong>Order reference:</strong> {{ $order_reference }}
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
        <p>Something fishy is going on, please check your dashboard.</p>
        <!-- Add dashboard link here -->
        <p><a href="#">Dashboard</a></p>
    @endif
</ul>

<p>Please take the necessary steps to process this order promptly. You can accept the order by clicking the link below:</p>
<!-- Add your order validation link here -->
<p><a href="#">Accept Order</a></p>

<p>If you have any questions or need assistance, feel free to reach out.</p>

<p>Thank you for using our platform!</p>
</body>
</html>
