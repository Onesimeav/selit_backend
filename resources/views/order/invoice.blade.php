<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Invoice</title>
</head>
<body>
<table class="w-full">
    <tr>
        <td class="w-half">
            <img src="https://res.cloudinary.com/dklf43wgz/image/upload/c_crop,w_250,h_125,g_auto/v1728038876/7_wiz6tu.png" alt="selit logo" width="200" />
        </td>
        <td class="w-half">
            <h2>Invoice ID: {{$orderReference}}</h2>
        </td>
    </tr>
</table>

<div class="margin-top">
    <table class="w-full">
        <tr>
            <td class="w-half">
                <div><h4>To:</h4></div>
                <div>{{$customerName}}</div>
            </td>
            <td class="w-half">
                <div><h4>From:</h4></div>
                <div>{{$shopName}}</div>
            </td>
        </tr>
    </table>
</div>

<div class="margin-top">
    <table class="products">
        <tr>
            <th>Qty</th>
            <th>Product</th>
            <th>Price</th>
            <th>Promotion code</th>
            <th>Promo-price</th>
        </tr>
        <tr class="items">
            @foreach($orderProducts as $orderProduct)
                <td>
                    {{ $orderProduct['product_quantity'] }}
                </td>
                <td>
                    {{ $orderProduct['product_name'] }}
                </td>
                <td>
                    {{ $orderProduct['product_price'] }}
                </td>
                @if(isset($orderProducts['promotion_code']))
                    <td>
                        {{ $orderProduct['promotion_code'] }}
                    </td>
                    <td>
                        {{ $orderProduct['price_promotion_applied'] }}
                    </td>
                @endif
            @endforeach
        </tr>
    </table>
</div>

<div class="total">
    Total: {{$orderPrice}} USD
</div>

<div class="footer margin-top">
    <div>Thank you</div>
    <div>&copy; Selit</div>
</div>
</body>
</html>
