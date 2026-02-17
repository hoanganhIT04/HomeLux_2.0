<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .container { width: 100%; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .title { font-size: 24px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th, table td { border: 1px solid #ddd; padding: 8px; }
        table th { background: #f5f5f5; }
        .total { text-align: right; margin-top: 20px; font-size: 18px; font-weight: bold; }
        .print-btn { margin-top: 20px; }
        @media print {
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
<div class="container">

    <div class="header">
        <div class="title">PLAYMART STORE</div>
        <p>HÓA ĐƠN BÁN HÀNG</p>
    </div>

    <p><strong>Mã đơn:</strong> #{{ $order->public_id }}</p>
    <p><strong>Ngày đặt:</strong> {{ $order->created_at }}</p>
    <p><strong>Người nhận:</strong> {{ $order->receiver_name }}</p>
    <p><strong>Điện thoại:</strong> {{ $order->receiver_phone }}</p>
    <p><strong>Địa chỉ:</strong> {{ $order->full_address }}</p>

    <table>
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Đơn giá</th>
                <th>SL</th>
                <th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product->name ?? 'Sản phẩm đã xoá' }}</td>
                <td>{{ number_format($item->price) }}₫</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->price * $item->quantity) }}₫</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="total">
        Tổng thanh toán: {{ number_format($order->total_price) }}₫
    </div>

    @if(!isset($isPdf) || !$isPdf)
        <div class="print-btn">
            <button onclick="window.print()">In hóa đơn</button>
        </div>
    @endif


</div>
</body>
</html>
