<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Invoice</title>
    <style>
        h4 {
            margin: 0;
        }
        .w-full {
            width: 100%;
        }
        .w-half {
            width: 50%;
        }
        .margin-top {
            margin-top: 1.25rem;
        }
        .footer {
            font-size: 0.875rem;
            padding: 1rem;
            background-color: rgb(241 245 249);
        }
        table {
            width: 100%;
            border-spacing: 0;
        }
        table.products {
            font-size: 0.875rem;
        }
        table.products tr {
            background-color: rgb(96 165 250);
        }
        table.products th {
            color: #ffffff;
            padding: 0.5rem;
        }
        table tr.items {
            background-color: rgb(241 245 249);
        }
        table tr.items td {
            padding: 0.5rem;
        }
        .total {
            text-align: right;
            margin-top: 1rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
<table class="w-full">
    <tr>
        <td class="w-half">
            <img src="data:image/png;base64,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" alt="selit logo" width="200" />
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
                        @foreach($orderProduct['promotion_code'] as $code)
                            <em> {{ $code}} </em>
                        @endforeach
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
    Total: {{$orderPrice}} XOF
</div>

<div class="footer margin-top">
    <div>Thank you</div>
    <div>&copy; Selit</div>
</div>
</body>
</html>
