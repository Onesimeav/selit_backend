<!DOCTYPE html>
<html>
<head>
    <title>Order Completed: Invoice Available</title>
</head>
<body>
<h1>Hello {{$customerName}}</h1>
<p>We're delighted to inform you that your order on {{$shopName}} has been successfully completed!</p>

<p>Below, you'll find the details of your order:</p>
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
        <p>Something fishy is going on, please contact the seller.</p>
    @endif
</ul>

<p>To download your invoice, please click the link below:</p>
<p><a href="{{$invoiceLink}}">Download Invoice</a></p>

<p>If you have any questions or need further assistance, feel free to reach out.</p>

<p>Thank you for choosing our service!</p>
</body>
</html>
