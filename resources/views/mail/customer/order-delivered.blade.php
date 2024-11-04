<!DOCTYPE html>
<html>
<head>
    <title>Order Delivered: Payment Due</title>
</head>
<body>
<h1>Hello {{ $customerName }},</h1>
<p>We're excited to inform you that your order has been successfully delivered!</p>

<p>Please review the order details below:</p>
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


<p>Now, it's time to settle the payment. Kindly click the link below to make your payment:</p>
<p><a href="#">Pay Now</a></p>

<p>If you have any questions or need assistance, feel free to reach out.</p>

<p>Thank you for choosing our service!</p>
</body>
</html>
