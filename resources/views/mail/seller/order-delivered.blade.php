<!DOCTYPE html>
<html>
<head>
    <title>Order Delivered: Check Your Dashboard</title>
</head>
<body>
<h1>Hello {{ $shopOwnerName }},</h1>
<p>We're pleased to inform you that an order from your shop {{$shopName}} has been successfully delivered to one of your clients!</p>

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
                @if(isset($orderProduct['promotion_code']) && $orderProduct['promotion_code']!=[])
                    <strong>Promotion code:</strong>@foreach($orderProduct['promotion_code'] as $code) <em> {{ $code }} </em> @endforeach
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

<p>Please log in to your dashboard to view the complete order details. You'll find information about the products, delivery address, and any special instructions provided by the client.</p>

<p>Dashboard Link: <a href="#">Go to Dashboard</a></p>

<p>If you have any questions or need further assistance, feel free to reach out.</p>

<p>Thank you for being a valued seller on our platform!</p>
</body>
</html>
